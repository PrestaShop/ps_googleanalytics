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

class ProductWrapper implements WrapperInterface
{
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * wrap products to provide a standard products information for google analytics script
     */
    public function wrapProductList($products, $extras = [], $full = false)
    {
        $result_products = [];
        if (!is_array($products)) {
            return;
        }

        $currency = new Currency($this->context->currency->id);
        $usetax = (Product::getTaxCalculationMethod((int) $this->context->customer->id) != PS_TAX_EXC);

        if (count($products) > 20) {
            $full = false;
        } else {
            $full = true;
        }

        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $product = (array) $product;
            }

            if (!isset($product['price'])) {
                $product['price'] = (float) Tools::displayPrice(Product::getPriceStatic((int) $product['id_product'], $usetax), $currency);
            }
            $result_products[] = $this->wrapProduct($product, $extras, $index, $full);
        }

        return $result_products;
    }

    /**
     * wrap product to provide a standard product information for google analytics script
     */
    public function wrapProduct($product, $extras, $index = 0, $full = false)
    {
        $ga_product = '';

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

        if ($full) {
            $ga_product = [
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
        } else {
            $ga_product = [
                'id' => (int) $product_id,
                'name' => (string) $product['name'],
            ];
        }

        $isV4Enabled = (bool) Configuration::get('GA_V4_ENABLED');
        if (!$isV4Enabled) {
            foreach ($ga_product as $k => $v) {
                if (in_array($k, ['name', 'category', 'brand', 'variant', 'brand'])) {
                    $ga_product[$k] = Tools::str2url($v);
                }
            }
        }

        return $ga_product;
    }
}
