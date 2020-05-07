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

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use PrestaShop\Module\Ps_Googleanalytics\Hooks\HookInterface;

class HookDisplayHeader implements HookInterface
{
    private $module;
    private $context;
    private $params;
    private $backOffice;

    public function __construct($module, $context) {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return string
     */
    public function run()
    {
        if (\Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->controller->addJs($this->module->getPathUri().'views/js/GoogleAnalyticActionLib.js');

            $shops = \Shop::getShops();
            $isMultistoreActive = \Shop::isFeatureActive();
            $currentShopId = (int)\Context::getContext()->shop->id;
            $userId = null;
            $gaCrossdomainEnabled = false;

            if (\Configuration::get('GA_USERID_ENABLED') &&
                $this->context->customer && $this->context->customer->isLogged()
            ) {
                $userId = (int)$this->context->customer->id;
            }

            $gaAnonymizeEnabled = \Configuration::get('GA_ANONYMIZE_ENABLED');

            if ((int)\Configuration::get('GA_CROSSDOMAIN_ENABLED') && $isMultistoreActive && count($shops) > 1) {
                $gaCrossdomainEnabled = true;
            }

            $this->context->smarty->assign(
                array(
                    'backOffice' => $this->backOffice,
                    'currentShopId' => $currentShopId,
                    'userId' => $userId,
                    'gaAccountId' => \Tools::safeOutput(\Configuration::get('GA_ACCOUNT_ID')),
                    'shops' => $shops,
                    'gaCrossdomainEnabled' => $gaCrossdomainEnabled,
                    'gaAnonymizeEnabled' => $gaAnonymizeEnabled,
                    'useSecureMode' => \Configuration::get('PS_SSL_ENABLED')
                )
            );

            return $this->module->display(
                $this->module->getLocalPath() . $this->module->name,
                'ps_googleanalytics.tpl'
            );
        }
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params) {
        $this->module->params = $params;
    }

    /**
     * setBackOffice
     *
     * @param array $backOffice
     */
    public function setBackOffice($backOffice) {
        $this->module->backOffice = $backOffice;
    }
}
