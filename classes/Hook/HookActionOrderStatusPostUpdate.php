<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use Configuration;
use Context;
use Db;
use Ps_Googleanalytics;

class HookActionOrderStatusPostUpdate implements HookInterface
{
    /**
     * @var Ps_Googleanalytics
     */
    private $module;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var array
     */
    private $params;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        // If we do not have an order or a new order status, we return
        if (empty($this->params['id_order']) || empty($this->params['newOrderStatus']->id)) {
            return;
        }

        // We get all states in which the merchant want to have refund sent and check if the new state being set belongs there
        $gaCancelledStates = json_decode(Configuration::get('GA_CANCELLED_STATES'), true);
        if (empty($gaCancelledStates) || !in_array($this->params['newOrderStatus']->id, $gaCancelledStates)) {
            return;
        }

        // We check if the refund was already sent to Google Analytics
        $gaRefundSent = Db::getInstance()->getValue(
            'SELECT id_order FROM `' . _DB_PREFIX_ . 'ganalytics` WHERE id_order = ' . (int) $this->params['id_order'] . ' AND refund_sent = 1'
        );

        // If it was not already refunded
        if ($gaRefundSent === false) {
            // We refund it and set the "sent" flag to true
            $this->context->cookie->__set('ga_admin_refund', 'MBG.refundByOrderId(' . json_encode(['id' => $this->params['id_order']]) . ');');
            $this->context->cookie->write();

            // We save this information to database
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'ganalytics` SET refund_sent = 1 WHERE id_order = ' . (int) $this->params['id_order']
            );
        }
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }
}
