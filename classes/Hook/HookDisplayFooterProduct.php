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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Product;
use Ps_Googleanalytics;
use Tools;

class HookDisplayFooterProduct implements HookInterface
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
        $isV4Enabled = (bool) Configuration::get('GA_V4_ENABLED');
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $controllerName = Tools::getValue('controller');

        if ('product' !== $controllerName) {
            return '';
        }

        if ($this->params['product'] instanceof Product) {
            $this->params['product'] = (array) $this->params['product'];
        }
        // Add product view
        if ($isV4Enabled) {
            $js = $this->getGoogleAnalytics4();
        } else {
            $js = $this->getUniversalAnalytics();
        }

        return $gaTagHandler->generate($js);
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

    protected function getUniversalAnalytics()
    {
        $gaProduct = $this->getProduct();

        $js = 'MBG.addProductDetailView(' . json_encode($gaProduct) . ');';
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            $js .= $this->module->getTools()->addProductClickByHttpReferal([$gaProduct], $this->context->currency->iso_code);
        }

        return $js;
    }

    protected function getGoogleAnalytics4()
    {
        $gaProduct = $this->getProduct();
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => $this->params['product']['price_amount'],
            'items' => [
                [
                    'item_id' => (int) $gaProduct['id'],
                    'item_name' => $this->params['product']['name'],
                    'currency' => $this->context->currency->iso_code,
                    'item_brand' => $this->params['product']['manufacturer_name'],
                    'item_category' => $this->params['product']['category_name'],
                    'price' => (float) $this->params['product']['price_amount'],
                    'quantity' => (int) $gaProduct['quantity'],
                ],
            ],
        ];
        $js = $this->module->getTools()->renderEvent(
            'view_item',
            $eventData
        );

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            $js .= $this->module->getTools()->addProductClickByHttpReferal([$gaProduct], $this->context->currency->iso_code);
        }

        return $js;
    }

    /**
     * @return array
     */
    protected function getProduct()
    {
        $productWrapper = new ProductWrapper($this->context);

        return $productWrapper->wrapProduct($this->params['product'], null, 0, true);
    }
}
