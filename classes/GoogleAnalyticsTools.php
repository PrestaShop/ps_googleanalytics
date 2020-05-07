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

namespace PrestaShop\Module\Ps_Googleanalytics;

class GoogleAnalyticsTools
{
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
     * @param array $order
     *
     * @return string|void
     */
    public function addTransaction($products, $order)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js.'MBG.addTransaction('.json_encode($order).');';
    }

    /**
     * add product impression js and product click js
     *
     * @param array ixed $products
     *
     * @return string|void
     */
    public function addProductImpression($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).",'',true);";
        }

        return $js;
    }

    /**
     * addProductClick
     *
     * @param array $products
     *
     * @return string|void
     */
    public function addProductClick($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClick('.json_encode($product).');';
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
    public function addProductClickByHttpReferal($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClickByHttpReferal('.json_encode($product).');';
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
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * Generate Google Analytics js
     *
     * @param string $jsCode
     * @param int $backoffice
     *
     * @return string
     */
    public function generateJs($jsState, $isoCode, $jsCode, $backoffice = 0)
    {
        if (\Configuration::get('GA_ACCOUNT_ID')) {
            $generateJsCode = '';
            if (!empty($jsCode)) {
                $generateJsCode .= '
				<script type="text/javascript">
					document.addEventListener(\'DOMContentLoaded\', function() {
						var MBG = GoogleAnalyticEnhancedECommerce;
						MBG.setCurrency(\''.\Tools::safeOutput($isoCode).'\');
						'.$jsCode.'
					});
				</script>';
            }

            if (($jsState) != 1 && ($backoffice == 0)) {
                $generateJsCode .= '
				<script type="text/javascript">
					ga(\'send\', \'pageview\');
				</script>';
            }

            return $generateJsCode;
        }
    }
}
