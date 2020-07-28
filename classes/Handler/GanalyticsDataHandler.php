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

namespace PrestaShop\Module\Ps_Googleanalytics\Handler;

use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsDataRepository;

class GanalyticsDataHandler
{
    private $ganalyticsDataRepository;
    private $cartId;
    private $shopId;

    /**
     * __construct
     *
     * @param int $cartId
     * @param int $shopId
     */
    public function __construct($cartId, $shopId)
    {
        $this->ganalyticsDataRepository = new GanalyticsDataRepository();
        $this->cartId = (int) $cartId;
        $this->shopId = (int) $shopId;
    }

    /**
     * manageData
     *
     * @param string|array $data
     * @param string $action
     *
     * @return mixed
     */
    public function manageData($data, $action)
    {
        if ('R' === $action) {
            return $this->readData();
        }

        if ('W' === $action) {
            return $this->ganalyticsDataRepository->addNewRow(
                (int) $this->cartId,
                (int) $this->shopId,
                json_encode($data)
            );
        }

        if ('A' === $action) {
            return $this->appendData($data);
        }

        if ('D' === $action) {
            return $this->ganalyticsDataRepository->deleteRow(
                $this->cartId,
                $this->shopId
            );
        }

        return false;
    }

    /**
     * readData
     *
     * @return array
     */
    private function readData()
    {
        $dataReturned = $this->ganalyticsDataRepository->findDataByCartIdAndShopId(
            $this->cartId,
            $this->shopId
        );

        if (false === $dataReturned) {
            return [];
        }

        return $this->jsonDecodeValidJson($dataReturned);
    }

    /**
     * appendData
     *
     * @param string $data
     *
     * @return bool
     */
    private function appendData($data)
    {
        $dataReturned = $this->ganalyticsDataRepository->findDataByCartIdAndShopId(
            $this->cartId,
            $this->shopId
        );

        if (false === $dataReturned) {
            $newData = [$data];
        } else {
            $newData[] = $this->jsonDecodeValidJson($dataReturned);
        }

        return $this->ganalyticsDataRepository->addNewRow(
            (int) $this->cartId,
            (int) $this->shopId,
            json_encode($newData)
        );
    }

    /**
     * Check if the json is valid and returns an empty array if not
     *
     * @param string $json
     *
     * @return array
     */
    protected function jsonDecodeValidJson($json)
    {
        $array = json_decode($json, true);

        if (JSON_ERROR_NONE === json_last_error()) {
            return $array;
        }

        return [];
    }
}
