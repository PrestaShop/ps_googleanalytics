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
use Ps_Googleanalytics;
use Shop;
use Tools;

class HookDisplayHeader implements HookInterface
{
    private $module;
    private $context;
    private $params;

    /**
     * @var bool
     */
    private $backOffice;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return void|string
     */
    public function run()
    {
        if (!Configuration::get('GA_ACCOUNT_ID')) {
            return '';
        }

        $this->context->controller->addJs($this->module->getPathUri() . 'views/js/GoogleAnalyticActionLib.js');

        $shops = Shop::getShops();
        $isMultistoreActive = Shop::isFeatureActive();
        $currentShopId = (int) Context::getContext()->shop->id;
        $userId = null;
        $gaCrossdomainEnabled = false;

        if (Configuration::get('GA_USERID_ENABLED') &&
            $this->context->customer && $this->context->customer->isLogged()
        ) {
            $userId = (int) $this->context->customer->id;
        }

        $gaAnonymizeEnabled = Configuration::get('GA_ANONYMIZE_ENABLED');

        if ((int) Configuration::get('GA_CROSSDOMAIN_ENABLED') && $isMultistoreActive && count($shops) > 1) {
            $gaCrossdomainEnabled = true;
        }

        $this->context->smarty->assign(
            [
                'backOffice' => $this->backOffice,
                'trackBackOffice' => Configuration::get('GA_TRACK_BACKOFFICE_ENABLED'),
                'currentShopId' => $currentShopId,
                'userId' => $userId,
                'gaAccountId' => Tools::safeOutput(Configuration::get('GA_ACCOUNT_ID')),
                'shops' => $shops,
                'gaCrossdomainEnabled' => $gaCrossdomainEnabled,
                'gaAnonymizeEnabled' => $gaAnonymizeEnabled,
                'useSecureMode' => Configuration::get('PS_SSL_ENABLED'),
            ]
        );

        return $this->module->display(
            $this->module->getLocalPath() . $this->module->name,
            'ps_googleanalytics.tpl'
        );
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->module->params = $params;
    }

    /**
     * @param bool $backOffice
     */
    public function setBackOffice($backOffice)
    {
        $this->acknowledgeBackOfficeContext($backOffice);
    }

    /**
     * @param bool $isBackOffice
     */
    public function acknowledgeBackOfficeContext($isBackOffice)
    {
        $this->backOffice = $isBackOffice;
    }
}
