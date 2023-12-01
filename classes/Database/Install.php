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

namespace PrestaShop\Module\Ps_Googleanalytics\Database;

use Configuration;
use Db;
use Language;
use Ps_Googleanalytics;
use Shop;
use Tab;

class Install
{
    /**
     * @var Ps_Googleanalytics
     */
    private $module;

    public function __construct(Ps_Googleanalytics $module)
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $this->module = $module;
    }

    /**
     * installTables
     *
     * @return bool
     */
    public function installTables()
    {
        $sql = [];

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ganalytics` (
            `id_google_analytics` int(11) NOT NULL AUTO_INCREMENT,
            `id_order` int(11) NOT NULL,
            `id_customer` int(10) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `sent` tinyint(1) DEFAULT NULL,
            `refund_sent` tinyint(1) DEFAULT NULL,
            `date_add` datetime DEFAULT NULL,
            PRIMARY KEY (`id_google_analytics`),
            KEY `id_order` (`id_order`),
            KEY `sent` (`sent`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';

        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ganalytics_data` (
            `id_cart` int(11) NOT NULL,
            `id_shop` int(11) NOT NULL,
            `data` TEXT DEFAULT NULL,
            PRIMARY KEY (`id_cart`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Insert default data to database
     *
     * @return bool
     */
    public function setDefaultConfiguration()
    {
        Configuration::updateValue('GA_CANCELLED_STATES', json_encode([Configuration::get('PS_OS_CANCELED')]));
        Configuration::updateValue('GA_BACKLOAD_ENABLED', false);
        Configuration::updateValue('GA_BACKLOAD_DAYS', 30);

        return true;
    }

    /**
     * Register Module hooks
     *
     * @return bool
     */
    public function registerHooks()
    {
        return $this->module->registerHook('displayHeader') &&
            $this->module->registerHook('displayAdminOrder') &&
            $this->module->registerHook('displayBeforeBodyClosingTag') &&
            $this->module->registerHook('displayFooterProduct') &&
            $this->module->registerHook('displayOrderConfirmation') &&
            $this->module->registerHook('actionProductCancel') &&
            $this->module->registerHook('actionValidateOrder') &&
            $this->module->registerHook('actionOrderStatusPostUpdate') &&
            $this->module->registerHook('actionCartUpdateQuantityBefore') &&
            $this->module->registerHook('actionObjectProductInCartDeleteBefore') &&
            $this->module->registerHook('displayBackOfficeHeader') &&
            $this->module->registerHook('actionCarrierProcess');
    }

    /**
     * Installs hidden tab for our ajax controller
     *
     * @return bool
     */
    public function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminGanalyticsAjax';
        $tab->module = $this->module->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->module->displayName
        );

        return $tab->add();
    }
}
