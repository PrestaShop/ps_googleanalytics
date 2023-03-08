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

use Cart;
use Configuration;
use Context;
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

                $cart = new Cart($order->id_cart);
                $isV4Enabled = (bool) Configuration::get('GA_V4_ENABLED');
                $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
                $productWrapper = new ProductWrapper($this->context);

                // Add payment data
                if ($isV4Enabled) {
                    $eventData = [
                        'currency' => $this->context->currency->iso_code,
                        'payment_type' => $order->payment,
                    ];
                    $gaScripts = $this->module->getTools()->renderEvent(
                        'add_payment_info',
                        $eventData
                    );
                } else {
                    $gaScripts = 'MBG.addCheckoutOption(3,\'' . $order->payment . '\');';
                }

                // Prepare transaction data
                $transaction = [
                    'id' => (int) $order->id,
                    'affiliation' => (Shop::isFeatureActive()) ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                    'revenue' => (float) $order->total_paid,
                    'shipping' => (float) $order->total_shipping,
                    'tax' => (float) $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                    'url' => $this->context->link->getModuleLink('ps_googleanalytics', 'ajax', [], true),
                    'customer' => (int) $order->id_customer,
                    'currency' => $this->context->currency->iso_code,
                ];

                // Prepare order products
                $orderProducts = [];
                foreach ($cart->getProducts() as $order_product) {
                    $orderProducts[] = $productWrapper->wrapProduct($order_product, [], 0, true);
                }
                $gaScripts .= $this->module->getTools()->addTransaction($orderProducts, $transaction);

                return $gaTagHandler->generate($gaScripts);
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
