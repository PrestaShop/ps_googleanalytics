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

use Category;
use Configuration;
use Context;
use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Handler\ModuleHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;

class HookDisplayHome implements HookInterface
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
        $moduleHandler = new ModuleHandler();
        $gaTools = new GoogleAnalyticsTools();
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $gaScripts = '';

        // Home featured products
        if ($moduleHandler->isModuleEnabledAndHookedOn('ps_featuredproducts', 'displayHome')) {
            $category = new Category($this->context->shop->getCategory(), $this->context->language->id);
            $productWrapper = new ProductWrapper($this->context);
            $homeFeaturedProducts = $productWrapper->wrapProductList(
                $category->getProducts(
                    (int) $this->context->language->id,
                    1,
                    (Configuration::get('HOME_FEATURED_NBR') ? (int) Configuration::get('HOME_FEATURED_NBR') : 8),
                    'position'
                ),
                [],
                true
            );
            $gaScripts .= $gaTools->addProductImpression($homeFeaturedProducts) . $gaTools->addProductClick($homeFeaturedProducts);
        }

        $this->module->js_state = 1;

        return $gaTagHandler->generate(
            $gaTools->filter($gaScripts, $this->module->filterable)
        );
    }
}
