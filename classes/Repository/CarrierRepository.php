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

class CarrierRepository
{
    const TABLE_NAME = 'carrier';

    /**
     * findByCarrierId
     *
     * @param int $carrierId
     *
     * @return string
     */
    public function findByCarrierId($carrierId)
    {
        return Db::getInstance()->getValue(
            'SELECT name
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_carrier = ' . (int) $carrierId
        );
    }
}
