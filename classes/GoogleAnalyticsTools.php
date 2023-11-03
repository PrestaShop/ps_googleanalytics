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

namespace PrestaShop\Module\Ps_Googleanalytics;

use Configuration;

class GoogleAnalyticsTools
{
    /**
     * filter
     *
     * @param string $gaScripts
     * @param int $filterable
     *
     * @return string
     */
    public function filter($gaScripts, $filterable)
    {
        if (1 == $filterable) {
            return implode(';', array_unique(explode(';', $gaScripts)));
        }

        return $gaScripts;
    }

    /**
     * Renders purchase event for order
     *
     * @param array $orderProducts
     * @param array $orderData
     * @param string $callbackUrl
     *
     * @return string|void
     */
    public function renderPurchaseEvent($orderProducts, $orderData, $callbackUrl)
    {
        if (!is_array($orderProducts)) {
            return;
        }

        $callbackData = [
            'orderid' => $orderData['id'],
            'customer' => $orderData['customer'],
        ];

        $eventData = [
            'transaction_id' => (int) $orderData['id'],
            'affiliation' => $orderData['affiliation'],
            'value' => (float) $orderData['revenue'],
            'tax' => (float) $orderData['tax'],
            'shipping' => (float) $orderData['shipping'],
            'currency' => $orderData['currency'],
            'items' => [],
            'event_callback' => "function() {
                $.get('" . $callbackUrl . "', " . json_encode($callbackData, JSON_UNESCAPED_UNICODE) . ');
            }',
        ];

        foreach ($orderProducts as $product) {
            $eventData['items'][] = [
                'item_id' => (int) $product['id'],
                'item_name' => $product['name'],
                'quantity' => (int) $product['quantity'],
                'price' => (float) $product['price'],
            ];
        }

        return $this->renderEvent(
            'purchase',
            $eventData,
            ['event_callback']
        );
    }

    /**
     * addProductClick
     *
     * @param array $products
     * @param string $currencyIsoCode
     *
     * @return string|void
     */
    public function addProductClick($products, $currencyIsoCode)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $key => $product) {
            $eventData = [
                'items' => [
                    'item_id' => (int) $product['id'],
                    'item_name' => $product['name'],
                    'quantity' => (int) $product['quantity'],
                    'price' => (float) $product['price'],
                    'currency' => $currencyIsoCode,
                    'index' => (int) $product['position'],
                    'item_brand' => $product['brand'],
                    'item_category' => $product['category'],
                    'item_list_id' => $product['list'],
                    'item_variant' => $product['variant'],
                ],
            ];

            // Add send_to parameter to avoid sending extra events
            // to other gtag configs (Ads for example).
            $eventData = array_merge(
                ['send_to' => Configuration::get('GA_ACCOUNT_ID')],
                $eventData
            );

            $productId = explode('-', $product['id']);
            $js .= '$(\'article[data-id-product="' . $productId[0] . '"] a.quick-view\').on(
            "click",
            function() {
                gtag("event", "select_item", ' . json_encode($eventData, JSON_UNESCAPED_UNICODE) . ')
            });';
        }

        return $js;
    }

    /**
     * Encodes array of data into JSON, optionally ignoring some of the values
     *
     * @param array $data Data pairs
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string json encoded data
     */
    public function jsonEncodeWithBlacklist($data, $ignoredKeys = [])
    {
        $return = [];

        foreach ($data as $k => $v) {
            if (in_array($k, $ignoredKeys)) {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . $v;
            } else {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }

        return '{' . implode(', ', $return) . '}';
    }

    /**
     * Renders gtag event and encodes the data. You can optionally pass which data keys you want to
     * output in a raw way - callbacks for example.
     *
     * @param string $eventName
     * @param array $eventData
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string render gtag event for output
     */
    public function renderEvent($eventName, $eventData, $ignoredKeys = [])
    {
        // Automatically add send_to parameter to all events to avoid sending extra events
        // to other gtag configs (Ads for example).
        $eventData = array_merge(
            ['send_to' => Configuration::get('GA_ACCOUNT_ID')],
            $eventData
        );

        return sprintf(
            'gtag("event", "%1$s", %2$s);',
            $eventName,
            $this->jsonEncodeWithBlacklist($eventData, $ignoredKeys)
        );
    }
}
