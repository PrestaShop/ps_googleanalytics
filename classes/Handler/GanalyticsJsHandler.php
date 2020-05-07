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

namespace PrestaShop\Module\Ps_Googleanalytics\Handler;

class GanalyticsJsHandler
{
    private $module;
    private $context;

    public function __construct(\Ps_googleanalytics $module, \Context $context) {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * Generate Google Analytics js
     *
     * @param string $jsCode
     * @param int $isBackoffice
     *
     * @return string
     */
    public function generate($jsCode, $isBackoffice = 0)
    {
        if (\Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->smarty->assign(
                array(
                    'jsCode' => $jsCode,
                    'isoCode' => \Tools::safeOutput($this->context->currency->iso_code),
                    'jsState' => $this->module->js_state,
                    'isBackoffice' => $isBackoffice,
                )
            );

            return $this->module->display(
                $this->module->getLocalPath() . $this->module->name,
                'ga_tag.tpl'
            );
        }
    }
}
