<?php
/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\Ps_Googleanalytics\Wrapper;

use PrestaShop\Module\Ps_Googleanalytics\Hooks\WrapperInterface;

class OrderWrapper implements WrapperInterface
{
    private $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     * Return a detailed transaction for Google Analytics
     */
    public function wrapOrder($id_order)
    {
        $order = new \Order((int)$id_order);

        if (\Validate::isLoadedObject($order)) {
            return array(
                'id' => $id_order,
                'affiliation' => \Shop::isFeatureActive() ? $this->context->shop->name : \Configuration::get('PS_SHOP_NAME'),
                'revenue' => $order->total_paid,
                'shipping' => $order->total_shipping,
                'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                'url' => $this->context->link->getAdminLink('AdminGanalyticsAjax'),
                'customer' => $order->id_customer);
        }
    }
}
