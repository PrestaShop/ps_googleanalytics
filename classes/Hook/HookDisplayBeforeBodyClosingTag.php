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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Shop;
use Tools;

class HookDisplayBeforeBodyClosingTag implements HookInterface
{
    private $module;
    private $context;
    private $gaScripts = '';

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
        // Prepare our tag handler
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);

        // Log information about product listing
        $this->saveInformationAboutListing();

        // Add events
        $this->renderProductListing();
        $this->renderSearch();
        $this->renderCartPage();
        $this->renderBeginCheckout();
        $this->renderLogin();
        $this->renderRegistration();

        // TODO
        // Unify wrappers - add proper quantities for cart items
        // Cart actions adding/removing
        // Shipping info

        return $gaTagHandler->generate($this->gaScripts);
        die;


        $ganalyticsDataHandler = new GanalyticsDataHandler(
            $this->context->cart->id,
            $this->context->shop->id
        );

        $gacarts = $ganalyticsDataHandler->manageData('', 'R');
        $controller_name = Tools::getValue('controller');

        if (count($gacarts) > 0 && $controller_name != 'product') {
            foreach ($gacarts as $key => $gacart) {
                if (isset($gacart['quantity'])) {
                    if ($gacart['quantity'] > 0) {
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
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
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

        return $gaTagHandler->generate($gaScripts);
    }

    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function renderProductListing()
    {
        // Try to get product list variable
        $listing = $this->context->smarty->getTemplateVars('listing');
        if (empty($listing['products'])) {
            return;
        }

        // Prepare items to our format
        $productWrapper = new ProductWrapper($this->context);
        $items = $productWrapper->prepareItemListFromProductList($listing['products']);

        // Prepare info about the list
        $item_list_id = $this->context->controller->php_self;
        $item_list_name = $listing['label'];

        // Render the event
        $eventData = [
            'item_list_id' => $item_list_id,
            'item_list_name' => $item_list_name,
            'items' => $items,
        ];
        $this->gaScripts .= $this->module->getTools()->renderEvent(
            'view_item_list',
            $eventData
        );
    }

    /**
     * This method renders tracking code when user searches on the shop.
     */
    private function renderSearch()
    {
        // Check if we are on search page and we have a search string
        if ($this->context->controller->php_self != 'search' || empty($_GET['s'])) {
            return;
        }

        // Render the event
        $eventData = [
            'search_term' => (string) $_GET['s'],
        ];
        $this->gaScripts .= $this->module->getTools()->renderEvent(
            'search',
            $eventData
        );
    }

    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function renderCartpage()
    {
        // Check if we are on cart page
        if ($this->context->controller->php_self != 'cart') {
            return;
        }

        // Try to get product list variable and check if it's not empty
        $cart = $this->context->smarty->getTemplateVars('cart');
        if (empty($cart['products'])) {
            return;
        }

        // Prepare items to our format
        $productWrapper = new ProductWrapper($this->context);
        $items = $productWrapper->prepareItemListFromProductList($cart['products'], true);
        
        // Render the event
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => $cart['totals']['total']['amount'],
            'items' => $items,
        ];
        $this->gaScripts .= $this->module->getTools()->renderEvent(
            'view_cart',
            $eventData
        );
    }

    /**
     * This method renders tracking code for product listings, like category pages.
     */
    private function renderBeginCheckout()
    {
        // Check if we are on some supported order controller
        $allowed_controllers = ['order', 'orderopc', 'checkout'];
        if (!in_array($this->context->controller->php_self, $allowed_controllers)) {
            return;
        }

        // If using default OrderController that comes with prestashop, we will check if we are
        // on step 1 of the checkout. Otherwise, we will flush the output anyway. It's probably OPC
        // handling everything with javascript, so our code will load only once.
        if (get_class($this->context->controller) == 'OrderController') {
            // If we are not in the first step of checkout, we don't do anything
            // TODO test how it behaves with logged in customer
            if (!$this->context->controller->getCheckoutProcess()->getSteps()[0]->isCurrent()) {
                return;
            }
        }

        // Try to get product list variable and check if it's not empty
        $cart = $this->context->smarty->getTemplateVars('cart');
        if (empty($cart['products'])) {
            return;
        }

        // Prepare items to our format
        $productWrapper = new ProductWrapper($this->context);
        $items = $productWrapper->prepareItemListFromProductList($cart['products'], true);

        // Render the event
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => $cart['totals']['total']['amount'],
            'items' => $items,
        ];
        $this->gaScripts .= $this->module->getTools()->renderEvent(
            'begin_checkout',
            $eventData
        );
    }

    /**
     * This method renders tracking code after user logs in.
     */
    private function renderLogin()
    {
        // Render it only on login page AND if we are not creating a new account in older PS versions
        // For newer versions, registrations are handled with standalone registration controller.
        if ($this->context->controller->php_self != 'authentication' || isset($_GET['create_account'])) {
            return;
        }

        // Render the event
        $this->gaScripts .= $this->module->getTools()->renderEvent('login', []);
    }

    /**
     * This method renders tracking code after user registers.
     */
    private function renderRegistration()
    {
        if ($this->context->controller->php_self != 'registration' &&
            ($this->context->controller->php_self != 'authentication' || !isset($_GET['create_account']))
        ) {
            return;
        }

        // Render the event
        $this->gaScripts .= $this->module->getTools()->renderEvent('sign_up', []);
    }

    /**
     * Saves information about last visited product listing, so we can later use it for select_item event.
     */
    private function saveInformationAboutListing()
    {
        // Try to get product list variable
        $listing = $this->context->smarty->getTemplateVars('listing');
        if (empty($listing['products']) || empty($listing['label'])) {
            return;
        }

        // Save this information to a cookie
        $this->context->cookie->ga_last_listing = json_encode([
            'item_list_url' => $_SERVER['REQUEST_URI'],
            'item_list_id' => $this->context->controller->php_self,
            'item_list_name' => $listing['label'],
        ]);
    }
}
