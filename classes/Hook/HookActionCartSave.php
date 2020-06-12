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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Product;
use Ps_Googleanalytics;
use Tools;
use Validate;

class HookActionCartSave implements HookInterface
{
    private $module;

    /**
     * @var Context
     */
    private $context;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        if (!isset($this->context->cart)) {
            return;
        }

        if (!Tools::getIsset('id_product')) {
            return;
        }

        $cart = [
            'controller' => Tools::getValue('controller'),
            'addAction' => Tools::getValue('add') ? 'add' : '',
            'removeAction' => Tools::getValue('delete') ? 'delete' : '',
            'extraAction' => Tools::getValue('op'),
            'qty' => (int) Tools::getValue('qty', 1),
        ];

        $cartProducts = $this->context->cart->getProducts();
        if (!empty($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                if ($cartProduct['id_product'] == Tools::getValue('id_product')) {
                    $addProduct = $cartProduct;
                    break;
                }
            }
        }

        if ($cart['removeAction'] == 'delete') {
            $addProductObject = new Product((int) Tools::getValue('id_product'), true, (int) Configuration::get('PS_LANG_DEFAULT'));
            if (Validate::isLoadedObject($addProductObject)) {
                $addProduct['name'] = $addProductObject->name;
                $addProduct['manufacturer_name'] = $addProductObject->manufacturer_name;
                $addProduct['category'] = $addProductObject->category;
                $addProduct['reference'] = $addProductObject->reference;
                $addProduct['link_rewrite'] = $addProductObject->link_rewrite;
                $addProduct['link'] = $addProductObject->link_rewrite;
                $addProduct['price'] = $addProductObject->price;
                $addProduct['ean13'] = $addProductObject->ean13;
                $addProduct['id_product'] = Tools::getValue('id_product');
                $addProduct['id_category_default'] = $addProductObject->id_category_default;
                $addProduct['out_of_stock'] = $addProductObject->out_of_stock;
                $addProduct['minimal_quantity'] = 1;
                $addProduct['unit_price_ratio'] = 0;
                $addProduct = Product::getProductProperties((int) Configuration::get('PS_LANG_DEFAULT'), $addProduct);
            }
        }

        if (isset($addProduct) && !in_array((int) Tools::getValue('id_product'), $this->module->products)) {
            $ganalyticsDataHandler = new GanalyticsDataHandler(
                $this->context->cart->id,
                $this->context->shop->id
            );

            $this->module->products[] = (int) Tools::getValue('id_product');
            $productWrapper = new ProductWrapper($this->context);
            $gaProducts = $productWrapper->wrapProduct($addProduct, $cart, 0, true);

            if (array_key_exists('id_product_attribute', $gaProducts) && $gaProducts['id_product_attribute'] != '' && $gaProducts['id_product_attribute'] != 0) {
                $productId = $gaProducts['id_product_attribute'];
            } else {
                $productId = Tools::getValue('id_product');
            }

            $gaCart = $ganalyticsDataHandler->manageData('', 'R');

            if ($cart['removeAction'] == 'delete') {
                $gaProducts['quantity'] = -1;
            } elseif ($cart['extraAction'] == 'down') {
                if (array_key_exists($productId, $gaCart)) {
                    $gaProducts['quantity'] = $gaCart[$productId]['quantity'] - $cart['qty'];
                } else {
                    $gaProducts['quantity'] = $cart['qty'] * -1;
                }
            } elseif (Tools::getValue('step') <= 0) { // Sometimes cartsave is called in checkout
                if (array_key_exists($productId, $gaCart)) {
                    $gaProducts['quantity'] = $gaCart[$productId]['quantity'] + $cart['qty'];
                }
            }

            $gaCart[$productId] = $gaProducts;
            $ganalyticsDataHandler->manageData($gaCart, 'W');
        }
    }
}
