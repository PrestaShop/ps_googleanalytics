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
use Db;
use Manufacturer;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray;
use Shop;

class ProductWrapper
{
    private $context;
    private $categories = [];
    private $homeCategory = 0;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Takes provided list of product (lazy) arrays and converts it to a format that GA4 requires.
     *
     * @param array $productList
     * @param bool $useProvidedQuantity Should provided quantity be used, usually for cart related events
     *
     * @return array Item data standardized for GA
     */
    public function prepareItemListFromProductList($productList, $useProvidedQuantity = false)
    {
        $items = [];

        // Check we actually got some product
        if (empty($productList)) {
            return [];
        }

        // Preload categories for the whole list
        $productIds = [];
        foreach ($productList as $product) {
            if (!empty($product['id_product'])) {
                $productIds[] = $product['id_product'];
            } elseif (!empty($product['id'])) {
                $productIds[] = $product['id'];
            }
        }
        $this->loadCategories($productIds);

        // Prepare each item and override the counter
        $counter = 0;
        foreach ($productList as $product) {
            $product = $this->prepareItemFromProduct($product, $useProvidedQuantity);
            $product['index'] = $counter;
            $items[] = $product;
            ++$counter;
        }

        return $items;
    }

    /**
     * Loads all product categories for provided product IDs
     *
     * @param array $productIds
     */
    private function loadCategories($productIds)
    {
        if (empty($productIds)) {
            return;
        }

        // Initialize home category
        $this->homeCategory = (int) Configuration::get('PS_HOME_CATEGORY');

        // Initialize our cache
        $this->categories = [];
        foreach ($productIds as $id) {
            $this->categories[(int) $id] = [];
        }

        // Load categories for all products
        $result = Db::getInstance()->executeS(
            '
            SELECT cp.`id_category`, cp.`id_product`, cl.`name` FROM `' . _DB_PREFIX_ . 'category_product` cp
            LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON (c.id_category = cp.id_category)
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (cp.`id_category` = cl.`id_category`' . Shop::addSqlRestrictionOnLang('cl') . ')
            ' . Shop::addSqlAssociation('category', 'c') . '
            WHERE cp.`id_product` IN (' . implode(',', $productIds) . ') AND cl.`id_lang` = ' . (int) $this->context->language->id . '
            ORDER BY c.`level_depth` DESC'
        );

        // Store it by product ID
        foreach ($result as $row) {
            $this->categories[(int) $row['id_product']][] = $row;
        }
    }

    /**
     * Takes provided (lazy) array and converts it to a format that GA4 requires. It can handle:
     * - ProductLazyArray from product page
     * - ProductListingLazyArray from presented listings
     * - ProductListingLazyArray from presented cart
     * - Raw $cart->getProducts()
     * - Legacy product object converted to an array enriched with Product::getProductProperties
     *
     * @param ProductLazyArray|ProductListingLazyArray|array $product
     * @param bool $useProvidedQuantity Should provided quantity be used, usually for cart related events
     *
     * @return array Item data standardized for GA
     */
    public function prepareItemFromProduct($product, $useProvidedQuantity = false)
    {
        // Standardize product ID
        $product_id = 0;
        if (!empty($product['id_product'])) {
            $product_id = $product['id_product'];
        } elseif (!empty($product['id'])) {
            $product_id = $product['id'];
        }

        // Standardize product price, make sure this price went through calculation before you pass it here
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
        // If we don't, which can happen due to some bugs in getProductProperties, we will fetch it manually
        } elseif (!empty($product['id_manufacturer'])) {
            $manufacturerName = Manufacturer::getNameById((int) $product['id_manufacturer']);
            if (!empty($manufacturerName)) {
                $item['item_brand'] = $manufacturerName;
            }
        }

        // We will specify variant ID if we have it
        if (!empty($product['id_product_attribute'])) {
            $item['item_id'] .= '-' . $product['id_product_attribute'];
        }

        // Information about a chosen variant, if we have it (cart list has this out of the box)
        if (!empty($product['attributes_small'])) {
            $item['item_variant'] = $product['attributes_small'];

        // If we don't, we will construct it in the same format
        } elseif (!empty($product['id_product_attribute'])) {
            $variant = $this->getProductVariant((int) $product['id_product_attribute']);
            if (!empty($variant)) {
                $item['item_variant'] = $variant;
            }
        }

        if ($useProvidedQuantity === true) {
            // Info about quantity in cart, if we have it
            $item['quantity'] = $product['quantity'];
        }

        // Prepare category information, if not loaded before, we will fetch it
        if (!isset($this->categories[(int) $product_id])) {
            $this->loadCategories([(int) $product_id]);
        }

        $productCategories = $this->categories[(int) $product_id];

        // If the category is our default one, we will move it to the beginning of that list
        foreach ($productCategories as $key => $category) {
            if ($category['id_category'] == $product['id_category_default']) {
                $productCategories = [$key => $category] + $productCategories;
                break;
            }
        }

        // Add it to our item
        $counter = 1;
        foreach ($productCategories as $productCategory) {
            // Skip home category if we have more than 1 category
            if (count($productCategories) > 1 && $productCategory['id_category'] == $this->homeCategory) {
                continue;
            }

            // Add it with proper key
            $item[$counter == 1 ? 'item_category' : 'item_category' . $counter] = $productCategory['name'];
            if ($counter == 5) {
                break;
            }
            ++$counter;
        }

        return $item;
    }

    /**
     * Method that will provide product combination attribute in the same format and order as cart does.
     *
     * @param int $id_product_attribute ID of the combination
     *
     * @return string Attribute list
     */
    public function getProductVariant($id_product_attribute)
    {
        $result = Db::getInstance()->executeS(
            'SELECT al.`name` AS attribute_name
            FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (
                a.`id_attribute` = al.`id_attribute`
                AND al.`id_lang` = ' . (int) $this->context->language->id . '
            )
            WHERE pac.`id_product_attribute` = ' . $id_product_attribute . '
            ORDER BY ag.`position` ASC, a.`position` ASC'
        );

        $attributes = array_column($result, 'attribute_name');

        // Prepare our separator
        $separator = Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        if ($separator === '-') {
            // Add a space before the dash between attributes
            $separator = ' - ';
        }

        return implode($separator, $attributes);
    }
}
