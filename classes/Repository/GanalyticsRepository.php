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

namespace PrestaShop\Module\Ps_Googleanalytics\Repository;

use Db;

class GanalyticsRepository
{
    const TABLE_NAME = 'ganalytics';

    /**
     * findGaOrderByOrderId
     *
     * @param int $orderId
     *
     * @return mixed
     */
    public function findGaOrderByOrderId($orderId)
    {
        return Db::getInstance()->getValue(
            'SELECT id_order
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_order = ' . (int) $orderId);
    }

    /**
     * findAllByShopIdAndDateAdd
     *
     * @param int $shopId
     *
     * @return array
     */
    public function findAllByShopIdAndDateAdd($shopId)
    {
        return Db::getInstance()->ExecuteS(
            'SELECT *
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE sent = 0
                AND id_shop = ' . (int) $shopId . '
                AND DATE_ADD(date_add, INTERVAL 30 minute) < NOW()'
        );
    }

    /**
     * addNewRow
     *
     * @param array $data
     * @param int $type
     *
     * @return bool
     */
    public function addNewRow(array $data, $type = Db::INSERT_IGNORE)
    {
        return Db::getInstance()->insert(
            self::TABLE_NAME,
            $data,
            false,
            true,
            $type
        );
    }

    /**
     * updateData
     *
     * @param array $data
     * @param string $where
     * @param int $limit
     *
     * @return bool
     */
    public function updateData($data, $where, $limit = 0)
    {
        return Db::getInstance()->update(
            self::TABLE_NAME,
            $data,
            $where,
            $limit
        );
    }
}
