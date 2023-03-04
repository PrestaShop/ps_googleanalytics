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

use Configuration;
use Context;
use Hook;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Shop;
use Tools;

class HookDisplayFooter implements HookInterface
{
    private $module;
    private $context;

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
        $isV4Enabled = (bool) Configuration::get('GA_V4_ENABLED');
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $ganalyticsDataHandler = new GanalyticsDataHandler(
            $this->context->cart->id,
            $this->context->shop->id
        );

        $gaScripts = '';
        $gacarts = $ganalyticsDataHandler->manageData('', 'R');
        $controller_name = Tools::getValue('controller');

        if (count($gacarts) > 0 && $controller_name != 'product') {
            $this->module->filterable = 0;

            foreach ($gacarts as $key => $gacart) {
                if (isset($gacart['quantity'])) {
                    if ($gacart['quantity'] > 0) {
                        if ($isV4Enabled) {
                            $eventData = [
                                'currency' => $this->context->currency->iso_code,
                                'value' => $gacart['price'],
                                'items' => [
                                    [
                                        'item_id' => (int) $gacart['id'],
                                        'item_name' => $gacart['name'],
                                        'affiliation' => (Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME')),
                                        'currency' => $this->context->currency->iso_code,
                                        'index' => (int) $key,
                                        'item_brand' => $gacart['brand'],
                                        'item_category' => $gacart['category'],
                                        'item_variant' => $gacart['variant'],
                                        'price' => (float) $gacart['price'],
                                        'quantity' => (int) $gacart['quantity'],
                                    ],
                                ],
                            ];
                            $gaScripts .= $this->module->getTools()->renderEvent(
                                'add_to_cart',
                                $eventData
                            );
                        } else {
                            $gaScripts .= 'MBG.addToCart(' . json_encode($gacart) . ');';
                        }
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
                        if ($isV4Enabled) {
                            $eventData = [
                                'currency' => $this->context->currency->iso_code,
                                'value' => $gacart['price'],
                                'items' => [
                                    [
                                        'item_id' => (int) $gacart['id'],
                                        'item_name' => $gacart['name'],
                                        'affiliation' => (Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME')),
                                        'currency' => $this->context->currency->iso_code,
                                        'index' => (int) $key,
                                        'item_brand' => $gacart['brand'],
                                        'item_category' => $gacart['category'],
                                        'item_variant' => $gacart['variant'],
                                        'price' => (float) $gacart['price'],
                                        'quantity' => (int) $gacart['quantity'],
                                    ],
                                ],
                            ];
                            $gaScripts .= $this->module->getTools()->renderEvent(
                                'remove_from_cart',
                                $eventData
                            );
                        } else {
                            $gaScripts .= 'MBG.removeFromCart(' . json_encode($gacart) . ');';
                        }
                    }
                } elseif (is_array($gacart)) {
                    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($gacart));
                    foreach ($it as $v) {
                        $gaScripts .= $v;
                    }
                } else {
                    $gaScripts .= $gacart;
                }
            }

            $ganalyticsDataHandler->manageData('', 'D');
        }

        $listing = $this->context->smarty->getTemplateVars('listing');
        $productWrapper = new ProductWrapper($this->context);
        $products = $productWrapper->wrapProductList(isset($listing['products']) ? $listing['products'] : [], [], true);

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->module->eligible = 1;
            $step = Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
            if ($isV4Enabled) {
                $eventData = [
                    'currency' => $this->context->currency->iso_code,
                ];
                $gaScripts = $this->module->getTools()->renderEvent(
                    'begin_checkout',
                    $eventData
                );
            } else {
                $gaScripts .= $this->module->getTools()->addProductFromCheckout($products);
                $gaScripts .= 'MBG.addCheckout(\'' . (int) $step . '\');';
            }
        }

        $confirmation_hook_id = (int) Hook::getIdByName('displayOrderConfirmation');
        if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
            $this->module->eligible = 1;
        }

        if (isset($products) && count($products) && $controller_name != 'index') {
            if ($this->module->eligible == 0) {
                $gaScripts .= $this->module->getTools()->addProductImpression($products);
            }
            $gaScripts .= $this->module->getTools()->addProductClick($products, $this->context->currency->iso_code);
        }

        return $gaTagHandler->generate($gaScripts);
    }
}
