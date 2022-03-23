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

use Context;
use OrderDetail;
use Ps_Googleanalytics;

class HookActionProductCancel implements HookInterface
{
    private $module;
    private $context;
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
        if (!isset($this->params['id_order_detail']) || !isset($this->params['cancel_quantity'])) {
            return;
        }

        // Display GA refund product
        $orderDetail = new OrderDetail($this->params['id_order_detail']);
        $gaScripts = 'MBG.add(' . json_encode(
            [
                'id' => empty($orderDetail->product_attribute_id) ? $orderDetail->product_id : $orderDetail->product_id . '-' . $orderDetail->product_attribute_id,
                'quantity' => $this->params['cancel_quantity'],
            ])
            . ');';

        $this->context->cookie->__set(
            'ga_admin_refund',
            $gaScripts . 'MBG.refundByProduct(' . json_encode(['id' => $this->params['order']->id]) . ');'
        );
        $this->context->cookie->write();
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
