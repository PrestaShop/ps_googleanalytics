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

use Cart;
use Configuration;
use Context;
use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use Shop;
use Validate;

class HookDisplayOrderConfirmation implements HookInterface
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
     * @return string
     */
    public function run()
    {
        if (true === $this->module->psVersionIs17) {
            $order = $this->params['order'];
        } else {
            $order = $this->params['objOrder'];
        }

        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int) Configuration::get('PS_OS_ERROR')) {
            $ganalyticsRepository = new GanalyticsRepository();
            $gaOrderSent = $ganalyticsRepository->findGaOrderByOrderId((int) $order->id);

            if (false === $gaOrderSent) {
                $ganalyticsRepository->addNewRow(
                    [
                        'id_order' => (int) $order->id,
                        'id_shop' => (int) $this->context->shop->id,
                        'sent' => 0,
                        'date_add' => ['value' => 'NOW()', 'type' => 'sql'],
                    ]
                );

                if ($order->id_customer == $this->context->cookie->id_customer) {
                    $orderProducts = [];
                    $cart = new Cart($order->id_cart);
                    $gaTools = new GoogleAnalyticsTools();
                    $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
                    $productWrapper = new ProductWrapper($this->context);

                    foreach ($cart->getProducts() as $order_product) {
                        $orderProducts[] = $productWrapper->wrapProduct($order_product, [], 0, true);
                    }

                    $gaScripts = 'MBG.addCheckoutOption(3,\'' . $order->payment . '\');';
                    $transaction = [
                        'id' => $order->id,
                        'affiliation' => (Shop::isFeatureActive()) ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                        'revenue' => $order->total_paid,
                        'shipping' => $order->total_shipping,
                        'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                        'url' => $this->context->link->getModuleLink('ps_googleanalytics', 'ajax', [], true),
                        'customer' => $order->id_customer, ];
                    $gaScripts .= $gaTools->addTransaction($orderProducts, $transaction);

                    $this->module->js_state = 1;

                    return $gaTagHandler->generate($gaScripts);
                }
            }
        }

        return '';
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
