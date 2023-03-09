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
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler;
use PrestaShop\Module\Ps_Googleanalytics\Repository\CarrierRepository;
use Ps_Googleanalytics;

class HookActionCarrierProcess implements HookInterface
{
    private $module;
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
        if (isset($this->params['cart']->id_carrier)) {
            $carrierRepository = new CarrierRepository();
            $ganalyticsDataHandler = new GanalyticsDataHandler(
                $this->context->cart->id,
                $this->context->shop->id
            );

            $carrierName = $carrierRepository->findByCarrierId((int) $this->params['cart']->id_carrier);

            if ((bool) Configuration::get('GA_V4_ENABLED')) {
                $js = $this->getGoogleAnalytics4($carrierName);
            } else {
                $js = $this->getUniversalAnalytics($carrierName);
            }
            $ganalyticsDataHandler->manageData($js, 'A');
        }
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @param string $carrierName
     */
    protected function getUniversalAnalytics($carrierName)
    {
        return 'MBG.addCheckoutOption(2,\'' . $carrierName . '\');';
    }

    /**
     * @param string $carrierName
     */
    protected function getGoogleAnalytics4($carrierName)
    {
        $eventData = [
            'currency' => $this->context->currency->iso_code,
            'value' => (float) $this->context->cart->getSummaryDetails()['total_price'],
            'shipping_tier' => $carrierName,
        ];

        return $this->module->getTools()->renderEvent(
            'add_shipping_info',
            $eventData
        );
    }
}
