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

use Configuration;
use Context;
use PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools;
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
        $gaTools = new GoogleAnalyticsTools($isV4Enabled);
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
            $js = $this->getGoogleAnalytics4($gaTools);
        } else {
            $js = $this->getUniversalAnalytics($gaTools);
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

    protected function getUniversalAnalytics(GoogleAnalyticsTools $gaTools)
    {
        $gaProduct = $this->getProduct();

        $js = 'MBG.addProductDetailView(' . json_encode($gaProduct) . ');';
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            $js .= $gaTools->addProductClickByHttpReferal([$gaProduct], $this->context->currency->iso_code);
        }

        return $js;
    }

    protected function getGoogleAnalytics4(GoogleAnalyticsTools $gaTools)
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
        $js = 'gtag("event", "view_item", ' . json_encode($eventData, JSON_UNESCAPED_UNICODE) . ');';

        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
            $js .= $gaTools->addProductClickByHttpReferal([$gaProduct], $this->context->currency->iso_code);
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
