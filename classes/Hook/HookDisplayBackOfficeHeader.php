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

use Configuration;
use Context;
use Order;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\OrderWrapper;
use Ps_Googleanalytics;
use Tools;
use Validate;

class HookDisplayBackOfficeHeader implements HookInterface
{
    private $module;
    private $context;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
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
        if (strcmp(Tools::getValue('configure'), $this->module->name) === 0) {
            $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/ganalytics.css');
        }

        $ga_account_id = Configuration::get('GA_ACCOUNT_ID');

        if (!empty($ga_account_id) && $this->module->active) {
            $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
            $this->context->controller->addJs($this->module->getPathUri() . 'views/js/GoogleAnalyticActionLib.js');

            $this->context->smarty->assign('GA_ACCOUNT_ID', $ga_account_id);

            $gaScripts = '';
            if ($this->context->controller->controller_name == 'AdminOrders') {
                $ganalyticsRepository = new GanalyticsRepository();

                if (Tools::getValue('id_order')) {
                    $order = new Order((int) Tools::getValue('id_order'));
                    if (Validate::isLoadedObject($order) && strtotime('+1 day', strtotime($order->date_add)) > time()) {
                        $gaOrderSent = $ganalyticsRepository->findGaOrderByOrderId((int) Tools::getValue('id_order'));
                        if ($gaOrderSent === false) {
                            $ganalyticsRepository->addNewRow(
                                [
                                    'id_order' => (int) Tools::getValue('id_order'),
                                    'id_shop' => (int) $this->context->shop->id,
                                    'sent' => 0,
                                    'date_add' => ['value' => 'NOW()', 'type' => 'sql'],
                                ]
                            );
                        }
                    }
                } else {
                    $gaOrderRecords = $ganalyticsRepository->findAllByShopIdAndDateAdd((int) $this->context->shop->id);

                    if ($gaOrderRecords) {
                        $orderWrapper = new OrderWrapper($this->context);
                        $isV4Enabled = (bool) Configuration::get('GA_V4_ENABLED');
                        foreach ($gaOrderRecords as $row) {
                            $transaction = $orderWrapper->wrapOrder($row['id_order']);
                            if (!empty($transaction)) {
                                $ganalyticsRepository->updateData(
                                    [
                                        'date_add' => ['value' => 'NOW()', 'type' => 'sql'],
                                        'sent' => 1,
                                    ],
                                    'id_order = ' . (int) $row['id_order'] . ' AND id_shop = ' . (int) $this->context->shop->id
                                );

                                // Generate transaction event
                                if ($isV4Enabled) {
                                    $callbackData = [
                                        'orderid' => (int) $transaction['id'],
                                        'customer' => (int) $transaction['customer'],
                                    ];

                                    $eventData = [
                                        'transaction_id' => (int) $transaction['id'],
                                        'affiliation' => $transaction['affiliation'],
                                        'value' => (float) $transaction['revenue'],
                                        'tax' => (float) $transaction['tax'],
                                        'shipping' => (float) $transaction['shipping'],
                                        'currency' => $this->context->currency->iso_code,
                                        'event_callback' => "function() {
                                            $.get('" . $transaction['url'] . "', " . json_encode($callbackData, JSON_UNESCAPED_UNICODE) . ');
                                        }',
                                    ];
                                    $gaScripts .= $this->module->getTools()->renderEvent(
                                        'purchase',
                                        $eventData,
                                        ['event_callback']
                                    );
                                } else {
                                    $gaScripts .= 'MBG.addTransaction(' . json_encode($transaction) . ');';
                                }
                            }
                        }
                    }
                }
            }

            return $js . $this->module->hookDisplayHeader(null, true) . $gaTagHandler->generate($gaScripts);
        }

        return $js;
    }
}
