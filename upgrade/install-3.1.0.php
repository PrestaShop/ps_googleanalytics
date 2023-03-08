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
if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_3_1_0($object)
{
    Configuration::updateValue('GANALYTICS', '3.1.0');

    return Db::getInstance()->execute('
      CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ganalytics_data` (
            `id_cart` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `data` TEXT DEFAULT NULL,
            PRIMARY KEY (`id_cart`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
}
