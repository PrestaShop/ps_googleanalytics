<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use Context;
use OrderDetail;
use Ps_Googleanalytics;
use Validate;

class HookActionProductCancel implements HookInterface
{
    /**
     * @var Ps_Googleanalytics
     */
    private $module;
    /**
     * @var Context
     */
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

        // Check if the hook provided us with a valid existing ID of order detail.
        // An example are automatic tests, which do not provide it unfortunately.
        if (!Validate::isLoadedObject($orderDetail)) {
            return;
        }

        $idProduct = empty($orderDetail->product_attribute_id) ? $orderDetail->product_id : $orderDetail->product_id . '-' . $orderDetail->product_attribute_id;
        $jsCode = $this->getGoogleAnalytics4(
            (int) $this->params['order']->id,
            $idProduct,
            (float) $this->params['cancel_quantity'],
            $orderDetail->product_name
        );

        $this->context->cookie->ga_admin_refund = $jsCode;
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

    /**
     * @param int $idOrder
     * @param string $idProduct
     * @param float $quantity
     * @param string $nameProduct
     */
    protected function getGoogleAnalytics4($idOrder, $idProduct, $quantity, $nameProduct)
    {
        $eventData = [
            'transaction_id' => (int) $idOrder,
            'items' => [
                [
                    'item_id' => (int) $idProduct,
                    'item_name' => $nameProduct,
                    'quantity' => (int) $quantity,
                ],
            ],
        ];

        return $this->module->getTools()->renderEvent(
            'refund',
            $eventData
        );
    }
}
