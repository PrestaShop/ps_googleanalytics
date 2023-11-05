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
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray;
use Product;
use Shop;
use Tools;

class ProductWrapper
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Takes provided list of product (lazy) arrays and converts it to a format that GA4 requires.
     *
     * @param array $productList
     *
     * @return array Item data standardized for GA
     */
    public function prepareItemListFromProductList($productList, $isCartItem = false)
    {
        $items = [];

        // Check we actually got some product
        if (empty($productList)) {
            return [];
        }

        // Prepare each item and override the counter
        $counter = 0;
        foreach ($productList as $product) {
            $product = $this->prepareItemFromProduct($product, $isCartItem);
            $product['index'] = $counter;
            $items[] = $product;
            ++$counter;
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
    public function prepareItemFromProduct($product, $isCartItem = false)
    {
        // Standardize product ID
        $product_id = 0;
        if (!empty($product['id_product'])) {
            $product_id = $product['id_product'];
        } elseif (!empty($product['id'])) {
            $product_id = $product['id'];
        }

        // Standardize product price
        if (empty($product['price_amount'])) {
            $product['price_amount'] = $product['price'];
        }

        $item = [
            'item_id' => (int) $product_id,
            'item_name' => (string) $product['name'],
            'affiliation' => Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
            'index' => 0,
            'price' => $product['price_amount'],
            'quantity' => 1,
        ];

        // Add manufacturer info if we have it
        if (!empty($product['manufacturer_name'])) {
            $item['item_brand'] = $product['manufacturer_name'];
        }

        if ($isCartItem === true) {
            if (!empty($product['id_product_attribute'])) {
                $item['item_id'] .= '-' . $product['id_product_attribute'];
            }

            // Info about quantity in cart, if we have it
            if (isset($product['cart_quantity'])) {
                $item['quantity'] = $product['quantity'];
            }

            // In case of products from a cart, we will add more information
            // Information about a chosen variant, if we have it
            if (!empty($product['attributes_small'])) {
                $item['item_variant'] = $product['attributes_small'];
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
            ++$counter;
        }

        return $item;
    }
}
