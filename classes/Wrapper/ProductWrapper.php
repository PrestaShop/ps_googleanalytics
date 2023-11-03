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
use Currency;
use PrestaShop\Module\Ps_Googleanalytics\Hooks\WrapperInterface;
use Product;
use Tools;
use Shop;
use Validate;

class ProductWrapper implements WrapperInterface
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

        $product_type = 'typical';
        if (isset($product['pack']) && $product['pack'] == 1) {
            $product_type = 'pack';
        } elseif (isset($product['virtual']) && $product['virtual'] == 1) {
            $product_type = 'virtual';
        }

        return [
            'id' => (int) $product_id,
            'name' => (string) $product['name'],
            'category' => (string) $product['category'],
            'brand' => isset($product['manufacturer_name']) ? (string) $product['manufacturer_name'] : '',
            'variant' => (string) $variant,
            'type' => (string) $product_type,
            'position' => (int) $index ? $index : '0',
            'quantity' => (int) $product_qty,
            'list' => (string) Tools::getValue('controller'),
            'url' => isset($product['link']) ? (string) urlencode($product['link']) : '',
            'price' => (float) preg_replace('/[^0-9.]/', '', $product['price']),
        ];
    }

    public function prepareItemFromProductLazyArray($product) {

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

        // Add it to our item
        $counter = 1;
        foreach ($productCategories as $productCategory) {
            $item[$counter == 1 ? 'item_category' : 'item_category' . $counter] = $productCategory['name'];
            $counter++;
        }

        return $item;
    }
}
