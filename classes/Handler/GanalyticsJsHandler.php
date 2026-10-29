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
        return $this->render($jsCode, 'ga_tag.tpl');
    }

    /**
     * Generate Google Analytics js that runs as soon as it is parsed.
     *
     * ga_tag.tpl defers to DOMContentLoaded, which never fires again once the page has loaded, so
     * anything injected into an already-rendered page - a block swapped in by an AJAX refresh -
     * has to run immediately or not at all.
     *
     * @param string $jsCode
     *
     * @return void|string
     */
    public function generateImmediate($jsCode)
    {
        return $this->render($jsCode, 'ga_tag_immediate.tpl');
    }

    /**
     * @param string $jsCode
     * @param string $template
     *
     * @return void|string
     */
    private function render($jsCode, $template)
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->smarty->assign(
                [
                    'jsCode' => $jsCode,
                ]
            );

            return $this->module->display(
                $this->module->getLocalPath() . $this->module->name,
                $template
            );
        }
    }
}
