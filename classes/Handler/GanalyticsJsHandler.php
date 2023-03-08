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

namespace PrestaShop\Module\Ps_Googleanalytics\Handler;

use Configuration;
use Context;
use Ps_Googleanalytics;
use Tools;

class GanalyticsJsHandler
{
    private $module;
    private $context;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Generate Google Analytics js
     *
     * @param string $jsCode
     *
     * @return void|string
     */
    public function generate($jsCode)
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->smarty->assign(
                [
                    'isV4Enabled' => (bool) Configuration::get('GA_V4_ENABLED'),
                    'jsCode' => $jsCode,
                    'isoCode' => Tools::safeOutput($this->context->currency->iso_code),
                ]
            );

            return $this->module->display(
                $this->module->getLocalPath() . $this->module->name,
                'ga_tag.tpl'
            );
        }
    }
}
