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

namespace PrestaShop\Module\Ps_Googleanalytics;

use Configuration;

class GoogleAnalyticsTools
{
    /**
     * Renders purchase event for order
     *
     * @param array $orderProducts
     * @param array $orderData
     * @param string $callbackUrl
     *
     * @return string|void
     */
    public function renderPurchaseEvent($orderProducts, $orderData, $callbackUrl)
    {
        if (!is_array($orderProducts)) {
            return;
        }

        $callbackData = [
            'orderid' => $orderData['transaction_id'],
            'customer' => $orderData['customer'],
        ];

        $eventData = [
            'transaction_id' => (int) $orderData['transaction_id'],
            'affiliation' => $orderData['affiliation'],
            'value' => (float) $orderData['value'],
            'tax' => (float) $orderData['tax'],
            'shipping' => (float) $orderData['shipping'],
            'currency' => $orderData['currency'],
            'items' => $orderProducts,
            'event_callback' => "function() {
                $.get('" . $callbackUrl . "', " . json_encode($callbackData, JSON_UNESCAPED_UNICODE) . ');
            }',
        ];

        return $this->renderEvent(
            'purchase',
            $eventData,
            ['event_callback']
        );
    }

    /**
     * Encodes array of data into JSON, optionally ignoring some of the values
     *
     * @param array $data Data pairs
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string json encoded data
     */
    public function jsonEncodeWithBlacklist($data, $ignoredKeys = [])
    {
        $return = [];

        foreach ($data as $k => $v) {
            if (in_array($k, $ignoredKeys)) {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . $v;
            } else {
                $return[] = json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }

        return '{' . implode(', ', $return) . '}';
    }

    /**
     * Renders gtag event and encodes the data. You can optionally pass which data keys you want to
     * output in a raw way - callbacks for example.
     *
     * @param string $eventName
     * @param array $eventData
     * @param array $ignoredKeys Values of these keys won't be encoded, for literal output of functions
     *
     * @return string render gtag event for output
     */
    public function renderEvent($eventName, $eventData, $ignoredKeys = [])
    {
        // Automatically add send_to parameter to all events to avoid sending extra events
        // to other gtag configs (Ads for example).
        $eventData = array_merge(
            ['send_to' => Configuration::get('GA_ACCOUNT_ID')],
            $eventData
        );

        return sprintf(
            'gtag("event", "%1$s", %2$s);',
            $eventName,
            $this->jsonEncodeWithBlacklist($eventData, $ignoredKeys)
        );
    }
}
