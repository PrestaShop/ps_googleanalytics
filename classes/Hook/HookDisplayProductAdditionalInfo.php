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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use Tools;

class HookDisplayProductAdditionalInfo implements HookInterface
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
     * Re-sends view_item after the shopper picks another combination.
     *
     * The product page swaps a handful of blocks over AJAX and pushes a new URL instead of
     * reloading, so displayFooterProduct - which carries view_item - is never rendered again and
     * Google Analytics keeps reporting the combination the page was opened with. This partial IS
     * one of the blocks ProductController returns for that refresh, so hooking it here reports the
     * combination the shopper is actually looking at.
     *
     * @return string|void
     */
    public function run()
    {
        /*
         * Only the product refresh, nothing else. On a normal page render displayFooterProduct has
         * already sent view_item, and emitting here as well would double every product view.
         */
        if (!Tools::getValue('ajax') || Tools::getValue('action') !== 'refresh') {
            return;
        }

        // Quickview has never reported to Google Analytics; its refresh should not either.
        if (Tools::getValue('quickview')) {
            return;
        }

        if ($this->context->controller->php_self !== 'product') {
            return;
        }

        $product = $this->context->smarty->getTemplateVars('product');
        if (empty($product)) {
            return;
        }

        /*
         * The 'product' template variable is expected to be a ProductLazyArray (array-accessible).
         * Some themes or modules assign a raw Product object instead, which prepareItemFromProduct()
         * cannot consume. Skip tracking in that case rather than breaking the refresh.
         */
        if (!is_array($product) && !$product instanceof \ArrayAccess) {
            return;
        }

        $productWrapper = new ProductWrapper($this->context);
        $item = $productWrapper->prepareItemFromProduct($product);

        $jsCode = $this->module->getTools()->renderEvent(
            'view_item',
            [
                'currency' => $this->context->currency->iso_code,
                'value' => $item['price'],
                'items' => [$item],
            ]
        );

        // Injected into a page that has already loaded, so it must not wait for DOMContentLoaded.
        return (new GanalyticsJsHandler($this->module, $this->context))->generateImmediate($jsCode);
    }
}
