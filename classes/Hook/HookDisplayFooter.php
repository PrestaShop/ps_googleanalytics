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
use Hook;
use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
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
        $gaTools = new GoogleAnalyticsTools();
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $ganalyticsDataHandler = new GanalyticsDataHandler(
            $this->context->cart->id,
            $this->context->shop->id
        );

        $gaScripts = '';
        $this->module->js_state = 0;
        $gacarts = $ganalyticsDataHandler->manageData('', 'R');
        $controller_name = Tools::getValue('controller');

        if (count($gacarts) > 0 && $controller_name != 'product') {
            $this->module->filterable = 0;

            foreach ($gacarts as $gacart) {
                if (isset($gacart['quantity'])) {
                    if ($gacart['quantity'] > 0) {
                        $gaScripts .= 'MBG.addToCart(' . json_encode($gacart) . ');';
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
                        $gaScripts .= 'MBG.removeFromCart(' . json_encode($gacart) . ');';
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
        $products = $productWrapper->wrapProductList($listing['products'], [], true);

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->module->js_state = 1;
            $this->module->eligible = 1;
            $step = Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
            $gaScripts .= $gaTools->addProductFromCheckout($products);
            $gaScripts .= 'MBG.addCheckout(\'' . (int) $step . '\');';
        }

        $confirmation_hook_id = (int) Hook::getIdByName('displayOrderConfirmation');
        if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
            $this->module->eligible = 1;
        }

        if (isset($products) && count($products) && $controller_name != 'index') {
            if ($this->module->eligible == 0) {
                $gaScripts .= $gaTools->addProductImpression($products);
            }
            $gaScripts .= $gaTools->addProductClick($products);
        }

        return $gaTagHandler->generate($gaScripts);
    }
}
