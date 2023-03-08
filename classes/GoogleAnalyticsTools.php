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
     * @var bool
     */
    protected $isV4Enabled = false;

    public function __construct($isV4Enabled = false)
    {
        $this->isV4Enabled = $isV4Enabled;
    }

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
     * add order transaction
     *
     * @param array $products
     * @param array $transaction
     *
     * @return string|void
     */
    public function addTransaction($products, $transaction)
    {
        if (!is_array($products)) {
            return;
        }

        if ($this->isV4Enabled) {
            $callbackData = [
                'orderid' => $transaction['id'],
                'customer' => $transaction['customer'],
            ];

            $eventData = [
                'transaction_id' => (int) $transaction['id'],
                'affiliation' => $transaction['affiliation'],
                'value' => (float) $transaction['revenue'],
                'tax' => (float) $transaction['tax'],
                'shipping' => (float) $transaction['shipping'],
                'currency' => $transaction['currency'],
                'items' => [],
                'event_callback' => "function() {
                    $.get('" . $transaction['url'] . "', " . json_encode($callbackData, JSON_UNESCAPED_UNICODE) . ');
                }',
            ];

            foreach ($products as $product) {
                $eventData['items'][] = [
                    'item_id' => (int) $product['id'],
                    'item_name' => $product['name'],
                    'quantity' => (int) $product['quantity'],
                    'price' => (float) $product['price'],
                ];
            }

            $js = $this->renderEvent(
                'purchase',
                $eventData,
                ['event_callback']
            );
        } else {
            unset($transaction['currency']);
            $js = '';
            foreach ($products as $product) {
                $js .= 'MBG.add(' . json_encode($product) . ');';
            }
            $js .= 'MBG.addTransaction(' . json_encode($transaction) . ');';
        }

        return $js;
    }

    /**
     * add product impression js and product click js
     *
     * @param array $products
     *
     * @return string|void
     */
    public function addProductImpression($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        if (!$this->isV4Enabled) {
            foreach ($products as $product) {
                $js .= 'MBG.add(' . json_encode($product) . ",'',true);";
            }
        }

        return $js;
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
        if ($this->isV4Enabled) {
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
        } else {
            foreach ($products as $product) {
                $js .= 'MBG.addProductClick(' . json_encode($product) . ');';
            }
        }

        return $js;
    }

    /**
     * addProductClickByHttpReferal
     *
     * @param array $products
     *
     * @return string|void
     */
    public function addProductClickByHttpReferal($products, $currencyIsoCode)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        if ($this->isV4Enabled) {
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

                $js .= $this->renderEvent(
                    'select_item',
                    $eventData
                );
            }
        } else {
            foreach ($products as $product) {
                $js .= 'MBG.addProductClickByHttpReferal(' . json_encode($product) . ');';
            }
        }

        return $js;
    }

    /**
     * Add product checkout info
     *
     * @param array $products
     *
     * @return string|void
     */
    public function addProductFromCheckout($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        if (!$this->isV4Enabled) {
            foreach ($products as $product) {
                $js .= 'MBG.add(' . json_encode($product) . ');';
            }
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
