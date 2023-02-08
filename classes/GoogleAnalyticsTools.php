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

namespace PrestaShop\Module\Ps_Googleanalytics;

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
                'transaction_id' => $transaction['id'],
                'affiliation' => $transaction['affiliation'],
                'value' => $transaction['revenue'],
                'tax' => $transaction['tax'],
                'shipping' => $transaction['shipping'],
                'currency' => $transaction['currency'],
                'items' => [],
                'event_callback' => "function() {
                    $.get('" . $transaction['url'] . "', " . json_encode($callbackData, JSON_UNESCAPED_UNICODE) . ');
                }',
            ];

            foreach ($products as $product) {
                $eventData['items'][] = [
                    'item_id' => $product['id'],
                    'item_name' => $product['name'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ];
            }

            $js = 'gtag("event", "purchase", ' . $this->jsonEncodeWithBlacklist($eventData, ['event_callback']) . ');';
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
                        'item_id' => $product['id'],
                        'item_name' => $product['name'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'currency' => $currencyIsoCode,
                        'index' => $product['position'],
                        'item_brand' => $product['brand'],
                        'item_category' => $product['category'],
                        'item_list_id' => $product['list'],
                        'item_variant' => $product['variant'],
                    ],
                ];

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
                        'item_id' => $product['id'],
                        'item_name' => $product['name'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'currency' => $currencyIsoCode,
                        'index' => $product['position'],
                        'item_brand' => $product['brand'],
                        'item_category' => $product['category'],
                        'item_list_id' => $product['list'],
                        'item_variant' => $product['variant'],
                    ],
                ];

                $js .= 'gtag("event", "select_item", ' . json_encode($eventData, JSON_UNESCAPED_UNICODE) . ');';
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
}
