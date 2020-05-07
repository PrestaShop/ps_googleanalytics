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
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;

class HookDisplayOrderConfirmation implements HookInterface
{
    private $module;
    private $context;
    private $params;

    public function __construct($module, $context) {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * manageHook
     *
     * @return string
     */
    public function manageHook()
    {
        if (true === $this->module->psVersionIs17) {
            $order = $this->params['order'];
        } else {
            $order = $this->params['objOrder'];
        }

        if (\Validate::isLoadedObject($order) && $order->getCurrentState() != (int)\Configuration::get('PS_OS_ERROR')) {
            $ganalyticsRepository = new GanalyticsRepository();
            $gaOrderSent = $ganalyticsRepository->findGaOrderByOrderId((int) $order->id);

            if (false === $gaOrderSent) {
                $ganalyticsRepository->addNewRow(
                    array(
                        'id_order' => (int) $order->id,
                        'id_shop' => (int) $this->context->shop->id,
                        'sent' => 0,
                        'date_add' => 'NOW()',
                    )
                );

                if ($order->id_customer == $this->context->cookie->id_customer) {
                    $orderProducts = array();
                    $cart = new Cart($order->id_cart);

                    foreach ($cart->getProducts() as $order_product) {
                        $orderProducts[] = $this->module->wrapProduct($order_product, array(), 0, true);
                    }

                    $gaScripts = 'MBG.addCheckoutOption(3,\''.$order->payment.'\');';
                    $transaction = array(
                        'id' => $order->id,
                        'affiliation' => (\Shop::isFeatureActive()) ? $this->context->shop->name : \Configuration::get('PS_SHOP_NAME'),
                        'revenue' => $order->total_paid,
                        'shipping' => $order->total_shipping,
                        'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                        'url' => $this->context->link->getModuleLink('ps_googleanalytics', 'ajax', array(), true),
                        'customer' => $order->id_customer);
                    $gaScripts .= $this->module->addTransaction($orderProducts, $transaction);

                    $this->module->js_state = 1;
                    return $this->module->_runJs($gaScripts);
                }
            }
        }
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
