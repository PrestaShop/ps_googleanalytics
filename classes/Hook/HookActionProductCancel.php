<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookInterface;

class HookActionProductCancel implements HookInterface
{
    private $module;
    private $context;
    private $params;

    public function __construct($module, $context) {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return string
     */
    public function run()
    {
        $quantityRefunded = \Tools::getValue('cancelQuantity');
        $gaScripts = '';

        foreach ($quantityRefunded as $orderDetailId => $quantity) {
            // Display GA refund product
            $orderDetail = new \OrderDetail($orderDetailId);
            $gaScripts .= 'MBG.add('.json_encode(
                array(
                    'id' => empty($orderDetail->product_attribute_id)?$orderDetail->product_id:$orderDetail->product_id.'-'.$orderDetail->product_attribute_id,
                    'quantity' => $quantity)
                )
                .');';
        }

        $this->context->cookie->ga_admin_refund = $gaScripts.'MBG.refundByProduct('.json_encode(array('id' => $this->params['order']->id)).');';
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params) {
        $this->params = $params;
    }
}
