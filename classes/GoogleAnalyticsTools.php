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
            $js = 'gtag(\'event\', \'purchase\', {
                "transaction_id": "' . $transaction['id'] . '",
                "items": [';

            $isFirst = true;
            foreach ($products as $product) {
                if (!$isFirst) {
                    $js .= ',';
                }
                $js .= '{
                    "item_id": "' . $product['id'] . '",
                    "item_name": "' . $product['name'] . '",
                    "quantity": "' . $product['quantity'] . '",
                    "price": "' . $product['price'] . '"
                  }';
                $isFirst = false;
            }
            $js .= "],
            'event_callback': function() {
				$.get('" . $transaction['url']  . "', {
					orderid: " . $transaction['id']  . ',
					customer: ' . $transaction['customer']  . '
				});
			}
            });';
        } else {
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
                $productId = explode('-', $product['id']);
                $js .= '$(\'article[data-id-product="' . $productId[0] . '"] a.quick-view\').on(
                "click",
                function() {
                    gtag("event", "select_item", {
                        items: [
                            {
                                item_id: "' . $product['id'] . '",
                                item_name: "' . $product['name'] . '",
                                quantity: "' . $product['quantity'] . '",
                                price: "' . $product['price'] . '",
                                currency: "' . $currencyIsoCode . '",
                                index: ' . $product['position'] . ',
                                item_brand: "' . $product['brand'] . '",
                                item_category: "' . $product['category'] . '",
                                item_list_id: "' . $product['list'] . '",
                                item_variant: "' . $product['variant'] . '",
                            }
                        ]
                    })
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
                $js .= 'gtag("event", "select_item", {
                    items: [
                        {
                            item_id: "' . $product['id'] . '",
                            item_name: "' . $product['name'] . '",
                            quantity: "' . $product['quantity'] . '",
                            price: "' . $product['price'] . '",
                            currency: "' . $currencyIsoCode . '",
                            index: ' . $product['position'] . ',
                            item_brand: "' . $product['brand'] . '",
                            item_category: "' . $product['category'] . '",
                            item_list_id: "' . $product['list'] . '",
                            item_variant: "' . $product['variant'] . '",
                        }
                    ]
                });';
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
}
