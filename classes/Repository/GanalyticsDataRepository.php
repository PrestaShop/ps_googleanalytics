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

namespace PrestaShop\Module\Ps_Googleanalytics\Repository;

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
        return \Db::getInstance()->getValue(
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
     * @param array $data
     *
     * @return bool
     */
    public function addNewRow($cartId, $shopId, $data)
    {
        return \Db::getInstance()->Execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '` (id_cart, id_shop, data)
            VALUES(\'' . (int) $cartId . '\',\'' . (int) $shopId . '\',\'' . pSQL($data).'\')
            ON DUPLICATE KEY UPDATE data = \'' . pSQL($data) . '\';'
        );
    }

    /**
     * deleteRow
     *
     * @param int $cartId
     * @param int $shopId
     *
     * @return void
     */
    public function deleteRow($cartId, $shopId)
    {
        return \Db::getInstance()->delete(
            self::TABLE_NAME,
            'id_cart = ' . (int) $cartId . ' AND id_shop = ' . (int) $shopId
        );
    }
}
