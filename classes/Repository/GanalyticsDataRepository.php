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

class GanalyticsDataRepository
{
    const TABLE_NAME = 'ganalytics_data';

    /**
     * findByCartId
     *
     * @param int $cartId
     * @param int $shopId
     *
     * @return mixed
     */
    public function findDataByCartIdAndShopId($cartId, $shopId)
    {
        return Db::getInstance()->getValue(
            'SELECT data
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_cart = ' . (int) $cartId . '
                AND id_shop = ' . (int) $shopId
        );
    }

    /**
     * addNewRow
     *
     * @param int $cartId
     * @param int $shopId
     * @param string $data
     *
     * @return bool
     */
    public function addNewRow($cartId, $shopId, $data)
    {
        return Db::getInstance()->Execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '` (id_cart, id_shop, data)
            VALUES(\'' . (int) $cartId . '\',\'' . (int) $shopId . '\',\'' . pSQL($data) . '\')
            ON DUPLICATE KEY UPDATE data = \'' . pSQL($data) . '\';'
        );
    }

    /**
     * deleteRow
     *
     * @param int $cartId
     * @param int $shopId
     *
     * @return bool
     */
    public function deleteRow($cartId, $shopId)
    {
        return Db::getInstance()->delete(
            self::TABLE_NAME,
            'id_cart = ' . (int) $cartId . ' AND id_shop = ' . (int) $shopId
        );
    }
}
