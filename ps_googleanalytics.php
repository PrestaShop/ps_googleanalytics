<?php
/**
 * 2007-2018 PrestaShop
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
 *  @copyright 2007-2018 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Ps_Googleanalytics extends Module
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'ganalytics';

    public $js_state = 0;
    public $eligible = 0;
    public $filterable = 1;
    public $products = array();
    public $_debug = 0;
    public $psVersionIs17;

    public function __construct()
    {
        $this->name = 'ps_googleanalytics';
        $this->tab = 'analytics_stats';
        $this->version = '3.2.0';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->author = 'PrestaShop';
        $this->module_key = 'fd2aaefea84ac1bb512e6f1878d990b8';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Google Analytics');
        $this->description = $this->l('Gain clear insights into important metrics about your customers, using Google Analytics');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Google Analytics? You will lose all the data related to this module.');
        $this->psVersionIs17 = (bool) version_compare(_PS_VERSION_, '1.7', '>=');
    }

    public function displayForm()
    {
        $configurationForm = new PrestaShop\Module\Ps_Googleanalytics\Form\ConfigurationForm($this);
        return $configurationForm->generateForm();
    }

    /**
     * back office module configuration page content
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            $ga_account_id = Tools::getValue('GA_ACCOUNT_ID');
            if (!empty($ga_account_id)) {
                Configuration::updateValue('GA_ACCOUNT_ID', $ga_account_id);
                Configuration::updateValue('GANALYTICS_CONFIGURATION_OK', true);
                $output .= $this->displayConfirmation($this->l('Account ID updated successfully'));
            }
            $ga_userid_enabled = Tools::getValue('GA_USERID_ENABLED');
            if (null !== $ga_userid_enabled) {
                Configuration::updateValue('GA_USERID_ENABLED', (bool)$ga_userid_enabled);
                $output .= $this->displayConfirmation($this->l('Settings for User ID updated successfully'));
            }

            $ga_crossdomain_enabled = Tools::getValue('GA_CROSSDOMAIN_ENABLED');
            if (null !== $ga_crossdomain_enabled) {
                Configuration::updateValue('GA_CROSSDOMAIN_ENABLED', (bool)$ga_crossdomain_enabled);
                $output .= $this->displayConfirmation($this->l('Settings for User ID updated successfully'));
            }

            $ga_anonymize_enabled = Tools::getValue('GA_ANONYMIZE_ENABLED');
            if (null !== $ga_anonymize_enabled) {
                Configuration::updateValue('GA_ANONYMIZE_ENABLED', (bool)$ga_anonymize_enabled);
                $output .= $this->displayConfirmation($this->l('Settings for Anonymize IP updated successfully'));
            }
        }

        $output .= $this->displayForm();

        return $this->display(__FILE__, './views/templates/admin/configuration.tpl').$output;
    }

    public function hookdisplayHeader($params, $back_office = false)
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayHeader($this, $this->context);
        $hook->setBackOffice($back_office);
        return $hook->run();
    }

    /**
     * To track transactions
     */
    public function hookdisplayOrderConfirmation($params)
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayOrderConfirmation($this, $this->context);
        $hook->setParams($params);
        return $hook->run();
    }

    /**
     * hook footer to load JS script for standards actions such as product clicks
     */
    public function hookdisplayFooter()
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayFooter($this, $this->context);
        return $hook->run();
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     */
    public function hookdisplayHome()
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayHome($this, $this->context);
        return $hook->run();
    }

    /**
     * hook product page footer to load JS for product details view
     */
    public function hookdisplayFooterProduct($params)
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayFooterProduct($this, $this->context);
        $hook->setParams($params);
        return $hook->run();
    }

    /**
     * Hook admin order to send transactions and refunds details
     */
    public function hookdisplayAdminOrder()
    {
        $gaTools = new PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools();
        echo $gaTools->generateJs(
            $this->js_state,
            $this->context->currency->iso_code,
            $this->context->cookie->ga_admin_refund,
            1
        );
        unset($this->context->cookie->ga_admin_refund);
    }

    /**
     *  admin office header to add google analytics js
     */
    public function hookdisplayBackOfficeHeader()
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayBackOfficeHeader($this, $this->context);
        return $hook->run();
    }

    /**
     * Hook admin office header to add google analytics js
     */
    public function hookactionProductCancel($params)
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionProductCancel($this, $this->context);
        $hook->setParams($params);
        return $hook->run();
    }

    /**
     * hook save cart event to implement addtocart and remove from cart functionality
     */
    public function hookactionCartSave()
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCartSave($this, $this->context);
        return $hook->run();
    }

    public function hookactionCarrierProcess($params)
    {
        $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCarrierProcess($this, $this->context);
        $hook->setParams($params);

        return $hook->run();
    }

    protected function _debugLog($function, $log)
    {
        if (!$this->_debug) {
            return true;
        }

        $myFile = _PS_MODULE_DIR_.$this->name.'/logs/analytics.log';
        $fh = fopen($myFile, 'a');
        fwrite($fh, date('F j, Y, g:i a').' '.$function."\n");
        fwrite($fh, print_r($log, true)."\n\n");
        fclose($fh);
    }

    /**
     * This method is trigger at the installation of the module
     * - install all module tables
     * - register hook used by the module.
     *
     * @return bool
     */
    public function install()
    {
        $moduleHandler = new PrestaShop\Module\Ps_Googleanalytics\Handler\ModuleHandler();
        $database = new PrestaShop\Module\Ps_Googleanalytics\Database\Install($this);

        $moduleHandler->uninstallModule(self::PS_16_EQUIVALENT_MODULE);

        return parent::install() &&
            $database->registerHooks() &&
            $database->installTables();
    }

    /**
     * Triggered at the uninstall of the module
     * - erase tables
     *
     * @return bool
     */
    public function uninstall()
    {
        $database = new PrestaShop\Module\Ps_Googleanalytics\Database\Uninstall();

        return parent::uninstall() &&
            $database->uninstallTables();
    }
}
