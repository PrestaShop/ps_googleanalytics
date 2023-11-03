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

class HookDisplayFooterProduct implements HookInterface
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
        // Check we are really on product page
        if ($this->context->controller->php_self !== 'product') {
            return;
        }

        // Get lazy array from context
        $product = $this->context->smarty->getTemplateVars('product');
        if (empty($product)) {
            return;
        }

        // Initialize tag handler
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);

        // Prepare it and format it for our purpose
        $productWrapper = new ProductWrapper($this->context);
        $item = $productWrapper->prepareItemFromProductLazyArray($product);

        $js = '';

        // Prepare and render view_item event
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => $product['price_amount'],
            'items' => [$item],
        ];
        $js .= $this->module->getTools()->renderEvent(
            'view_item',
            $eventData
        );

        // If the user got to the product page from previous page on our shop,
        // we will also send select_item event
        if ($this->wasPreviousPageOurShop()) {
            $eventData = [
                'currency' => $this->context->currency->iso_code,
                'value' => $product['price_amount'],
                'items' => [$item],
            ];
            $js .= $this->module->getTools()->renderEvent(
                'select_item',
                $eventData
            );
        }

        return $gaTagHandler->generate($js);
    }

    private function wasPreviousPageOurShop() {
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            return true;
        }

        return false;
    }
}
