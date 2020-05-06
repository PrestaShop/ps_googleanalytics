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
       $this->ganalyticsDataRepository = new GanalyticsDataRepository;
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
        $dataRetuned = $this->ganalyticsDataRepository->findDataByCartIdAndShopId(
            $this->cartId,
            $this->shopId
        );

        if (false === $dataRetuned) {
            return array();
        }

        return json_decode($dataRetuned, true);
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
        $dataRetuned = $this->ganalyticsDataRepository->findDataByCartIdAndShopId(
            $this->cartId,
            $this->shopId
        );

        if (false === $dataRetuned) {
            $newData = array($data);
        } else {
            $newData = json_decode($dataRetuned, true);
            $newData[] = $data;
        }

        return $this->ganalyticsDataRepository->addNewRow(
            (int) $this->cartId,
            (int) $this->shopId,
            json_encode($newData)
        );
    }
}
