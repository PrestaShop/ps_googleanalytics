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

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use Context;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Product;
use Ps_Googleanalytics;
use Validate;

class HookActionObjectProductInCartDeleteBefore implements HookInterface
{
    private $module;

    /**
     * @var Context
     */
    private $context;
    private $params;

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
        // Format product and standardize ID
        $product = new Product((int) $this->params['id_product'], false, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            return;
        }
        $product = (array) $product;
        $product['id_product'] = $product['id'];

        // Get some basic information
        $product = Product::getProductProperties($this->context->language->id, $product);

        // Add information about attribute
        if (!empty($this->params['id_product_attribute'])) {
            $product['id_product_attribute'] = (int) $this->params['id_product_attribute'];
        }

        // Prepare it and format it for our purpose
        $productWrapper = new ProductWrapper($this->context);
        $item = $productWrapper->prepareItemFromProduct($product, false);

        // Prepare and render event
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => $item['price'] * $item['quantity'],
            'items' => [$item],
        ];
        $jsCode = $this->module->getTools()->renderEvent(
            'remove_from_cart',
            $eventData
        );

        // Store this event
        $this->module->getDataHandler()->persistData($jsCode);
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }
}
