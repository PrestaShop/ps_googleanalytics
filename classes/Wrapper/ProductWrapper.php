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

namespace PrestaShop\Module\Ps_Googleanalytics\Wrapper;

use Configuration;
use Context;
use Product;
use Tools;
use Shop;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray;

class ProductWrapper
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * wrap product to provide a standard product information for google analytics script
     */
    public function wrapProduct($product, $extras = [], $index = 0)
    {
        $variant = null;
        if (isset($product['attributes_small'])) {
            $variant = $product['attributes_small'];
        } elseif (isset($extras['attributes_small'])) {
            $variant = $extras['attributes_small'];
        }

        $product_qty = 1;
        if (isset($extras['qty'])) {
            $product_qty = $extras['qty'];
        } elseif (isset($product['cart_quantity'])) {
            $product_qty = $product['cart_quantity'];
        }

        $product_id = 0;
        if (!empty($product['id_product'])) {
            $product_id = $product['id_product'];
        } elseif (!empty($product['id'])) {
            $product_id = $product['id'];
        }

        if (!empty($product['id_product_attribute'])) {
            $product_id .= '-' . $product['id_product_attribute'];
        }

        return [
            'id' => (int) $product_id,
            'name' => (string) $product['name'],
            'category' => (string) $product['category'],
            'brand' => isset($product['manufacturer_name']) ? (string) $product['manufacturer_name'] : '',
            'variant' => (string) $variant,
            'position' => (int) $index ? $index : '0',
            'quantity' => (int) $product_qty,
            'list' => (string) Tools::getValue('controller'),
            'url' => isset($product['link']) ? (string) urlencode($product['link']) : '',
            'price' => (float) preg_replace('/[^0-9.]/', '', $product['price']),
        ];
    }

    /**
     * Takes provided list of product (lazy) arrays and converts it to a format that GA4 requires.
     * 
     * @param array $productList
     * 
     * @return array Item data standardized for GA
     */
    public function prepareItemListFromProductList($productList, $isCartList = false)
    {
        $items = [];

        // Check we actually got some product
        if (empty($productList)) {
            return [];
        }

        // Prepare each item and override the counter
        $counter = 0;
        foreach ($productList as $product) {
            $product = $this->prepareItemFromProduct($product, $isCartList);
            $product['index'] = $counter;
            $items[] = $product;
            $counter++;
        }

        return $items;
    }

    /**
     * Takes provided (lazy) array and converts it to a format that GA4 requires. It can handle:
     * - ProductLazyArray from product page
     * - ProductListingLazyArray from presented listings
     * - ProductListingLazyArray from presented cart
     * - Raw $cart->getProducts()
     * 
     * @param ProductLazyArray|ProductListingLazyArray|array $product
     * 
     * @return array Item data standardized for GA
     */
    public function prepareItemFromProduct($product, $isCartList = false)
    {
        // Now, let's standardize some data in case of raw data.
        if (!($product instanceof ProductLazyArray) && !($product instanceof ProductListingLazyArray)) {
            // There may not be "id" property, we will use "id_product" instead.
            if (empty($product['id'])) {
                $product['id'] = $product['id_product'];
            }
            if (empty($product['price_amount'])) {
                $product['price_amount'] = $product['price'];
            }
        }

        $item = [
            'item_id' => (int) $product['id'],
            'item_name' => (string) $product['name'],
            'affiliation' => Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
            'index' => 0,
            'price' => $product['price_amount'],
            'quantity' => 1
        ];

        // Add manufacturer info if we have it
        if (!empty($product['manufacturer_name'])) {
            $item['item_brand'] = $product['manufacturer_name'];
        }

        if ($isCartList === true) {
            // Info about quantity in cart, if we have it
            if (isset($product['cart_quantity'])) {
                $item['quantity'] = $product['quantity'];
            }

            // In case of products from a cart, we will add more information
            // Information about a chosen variant, if we have it
            if (!empty($product['attributes_small'])) {
                $item['item_variant'] = $product['attributes_small'];
            }

            if (!empty($product['id_product_attribute'])) {
                $item['item_id'] .= '-' . $product['id_product_attribute'];
            }
        }

        // Prepare category information, put default category as the main one
        $productCategories1 = [];
        $productCategories2 = [];
        foreach (Product::getProductCategoriesFull((int) $product['id']) as $productCategory) {
            if ($productCategory['id_category'] == $product['id_category_default']) {
                $productCategories1[] = $productCategory;
            } else {
                $productCategories2[] = $productCategory;
            }
        }
        $productCategories = array_merge($productCategories1, $productCategories2);

        // Limit categories to 5
        $productCategories = array_slice($productCategories, 0, 5, true);

        // Add it to our item
        $counter = 1;
        foreach ($productCategories as $productCategory) {
            $item[$counter == 1 ? 'item_category' : 'item_category' . $counter] = $productCategory['name'];
            $counter++;
        }

        return $item;
    }
}
