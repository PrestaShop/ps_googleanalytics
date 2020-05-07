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
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\OrderWrapper;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;

class HookDisplayBackOfficeHeader implements HookInterface
{
    private $module;
    private $context;

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
        $js = '';
        if (strcmp(\Tools::getValue('configure'), $this->module->name) === 0) {
            $this->context->controller->addCSS($this->module->getPathUri().'views/css/ganalytics.css');
        }

        $ga_account_id = \Configuration::get('GA_ACCOUNT_ID');

        if (!empty($ga_account_id) && $this->module->active) {
            $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
            $this->context->controller->addJs($this->module->getPathUri().'views/js/GoogleAnalyticActionLib.js');

            $this->context->smarty->assign('GA_ACCOUNT_ID', $ga_account_id);

            $gaScripts = '';
            if ($this->context->controller->controller_name == 'AdminOrders') {
                $ganalyticsRepository = new GanalyticsRepository();

                if (\Tools::getValue('id_order')) {
                    $order = new \Order((int)\Tools::getValue('id_order'));
                    if (\Validate::isLoadedObject($order) && strtotime('+1 day', strtotime($order->date_add)) > time()) {
                        $gaOrderSent = $ganalyticsRepository->findGaOrderByOrderId((int) \Tools::getValue('id_order'));
                        if ($gaOrderSent === false) {
                            $ganalyticsRepository->addNewRow(
                                array(
                                    'id_order' => (int) \Tools::getValue('id_order'),
                                    'id_shop' => (int) $this->context->shop->id,
                                    'sent' => 0,
                                    'date_add' => 'NOW()',
                                )
                            );
                        }
                    }
                } else {
                    $gaOrderRecords = $ganalyticsRepository->findAllByShopIdAndDateAdd((int) $this->context->shop->id);

                    if ($gaOrderRecords) {
                        $orderWrapper = new OrderWrapper($this->context);
                        foreach ($gaOrderRecords as $row) {
                            $transaction = $orderWrapper->wrapOrder($row['id_order']);
                            if (!empty($transaction)) {
                                $ganalyticsRepository->updateData(
                                    array(
                                        'date_add' => 'NOW()',
                                        'sent' => 1
                                    ),
                                    'id_order = ' . (int) $row['id_order'] . ' AND id_shop = ' . (int) $this->context->shop->id
                                );
                                $transaction = json_encode($transaction);
                                $gaScripts .= 'MBG.addTransaction(' . $transaction.');';
                            }
                        }
                    }
                }
            }

            return $js . $this->module->hookdisplayHeader(null, true) . $gaTagHandler->generate($gaScripts, 1);
        }

        return $js;
    }
}
