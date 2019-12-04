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

class Ps_Googleanalytics extends Module
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'ganalytics';

    protected $js_state = 0;
    protected $eligible = 0;
    protected $filterable = 1;
    protected static $products = array();
    protected $_debug = 0;

    public function __construct()
    {
        $this->name = 'ps_googleanalytics';
        $this->tab = 'analytics_stats';
        $this->version = '3.2.0';
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        $this->author = 'PrestaShop';
        $this->module_key = 'fd2aaefea84ac1bb512e6f1878d990b8';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Google Analytics', array(), 'Modules.GAnalytics.Admin');
        $this->description = $this->trans('Gain clear insights into important metrics about your customers, using Google Analytics', array(), 'Modules.GAnalytics.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall Google Analytics? You will lose all the data related to this module.', array(), 'Modules.GAnalytics.Admin');
    }
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $this->uninstallPrestaShop16Module();

        if (parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('actionProductCancel') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionCarrierProcess')
        ) {
            return $this->createTables();
        }

        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            return $this->deleteTables();
        }

        return false;
    }

    /**
     * Migrate data from 1.6 equivalent module (if applicable), then uninstall
     */
    public function uninstallPrestaShop16Module()
    {
        if (!Module::isInstalled(self::PS_16_EQUIVALENT_MODULE)) {
            return false;
        }
        $oldModule = Module::getInstanceByName(self::PS_16_EQUIVALENT_MODULE);
        if ($oldModule) {
            if (method_exists($oldModule, 'uninstallTab')) {
                $oldModule->uninstallTab();
            }
            // This closure calls the parent class to prevent data to be erased
            // It allows the new module to be configured without migration
            $parentUninstallClosure = function() {
                return parent::uninstall();
            };
            $parentUninstallClosure = $parentUninstallClosure->bindTo($oldModule, get_class($oldModule));
            $parentUninstallClosure();
        }
        return true;
    }

    /**
     * Creates tables
     */
    protected function createTables()
    {
        if ((bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ganalytics` (
				`id_google_analytics` int(11) NOT NULL AUTO_INCREMENT,
				`id_order` int(11) NOT NULL,
				`id_customer` int(10) NOT NULL,
				`id_shop` int(11) NOT NULL,
				`sent` tinyint(1) DEFAULT NULL,
				`date_add` datetime DEFAULT NULL,
				PRIMARY KEY (`id_google_analytics`),
				KEY `id_order` (`id_order`),
				KEY `sent` (`sent`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1
		') && (bool)Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ganalytics_data` (
				`id_cart` int(11) NOT NULL,
				`id_shop` int(11) NOT NULL,
				`data` TEXT DEFAULT NULL,
				PRIMARY KEY (`id_cart`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8
		'))
		{
			return true;
		}

	    	return false;
    }

    /**
     * deletes tables
     */
    protected function deleteTables()
    {
        if ((bool)Db::getInstance()->execute('
    		DROP TABLE IF EXISTS `'._DB_PREFIX_.'ganalytics`
    	') && (bool)Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'ganalytics_data`
        ')) {
            return true;
        }

        return false;
    }

    public function displayForm()
    {
        // Check if multistore is active
        $is_multistore_active = Shop::isFeatureActive();

        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->trans('Save', array(), 'Admin.Actions'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->trans('Back to list', array(), 'Admin.Actions')
            )
        );

        $fields_form = array();
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->trans('Settings', array(),'Admin.Global'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Google Analytics Tracking ID', array(), 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_ACCOUNT_ID',
                    'size' => 20,
                    'required' => true,
                    'hint' => $this->trans('This information is available in your Google Analytics account', array(), 'Modules.GAnalytics.Admin')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Enable User ID tracking', array(), 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_USERID_ENABLED',
                    'values' => array(
                        array(
                            'id' => 'ga_userid_enabled',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'ga_userid_disabled',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global')
                        ))
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Anonymize IP', array(), 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_ANONYMIZE_ENABLED',
                    'hint' => $this->trans('Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries'),
                    'values'    => array(
                        array(
                            'id' => 'ga_anonymize_enabled',
                            'value' => 1,
                            'label' => $this->trans('Enabled', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'ga_anonymize_disabled',
                            'value' => 0,
                            'label' => $this->trans('Disabled', array(), 'Admin.Global')
                        ),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            )
        );

        if ($is_multistore_active) {
            $fields_form[0]['form']['input'][] = array(
                'type' => 'switch',
                'label' => $this->trans('Enable Cross-Domain tracking', array(), 'Modules.GAnalytics.Admin'),
                'name' => 'GA_CROSSDOMAIN_ENABLED',
                'values' => array(
                    array(
                        'id' => 'ga_crossdomain_enabled',
                        'value' => 1,
                        'label' => $this->trans('Enabled', array(), 'Admin.Global')
                    ),
                    array(
                        'id' => 'ga_crossdomain_disabled',
                        'value' => 0,
                         'label' => $this->trans('Disabled', array(), 'Admin.Global')
                    )
                )
            );
        }

        // Load current value
        $helper->fields_value['GA_ACCOUNT_ID'] = Configuration::get('GA_ACCOUNT_ID');
        $helper->fields_value['GA_USERID_ENABLED'] = Configuration::get('GA_USERID_ENABLED');
        $helper->fields_value['GA_CROSSDOMAIN_ENABLED'] = Configuration::get('GA_CROSSDOMAIN_ENABLED');
        $helper->fields_value['GA_ANONYMIZE_ENABLED'] = Configuration::get('GA_ANONYMIZE_ENABLED');

        return $helper->generateForm($fields_form);
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
                $output .= $this->displayConfirmation($this->trans('Account ID updated successfully', array(), 'Modules.GAnalytics.Admin'));
            }
            $ga_userid_enabled = Tools::getValue('GA_USERID_ENABLED');
            if (null !== $ga_userid_enabled) {
                Configuration::updateValue('GA_USERID_ENABLED', (bool)$ga_userid_enabled);
                $output .= $this->displayConfirmation($this->trans('Settings for User ID updated successfully', array(), 'Modules.GAnalytics.Admin'));
            }

            $ga_crossdomain_enabled = Tools::getValue('GA_CROSSDOMAIN_ENABLED');
            if (null !== $ga_crossdomain_enabled) {
                Configuration::updateValue('GA_CROSSDOMAIN_ENABLED', (bool)$ga_crossdomain_enabled);
                $output .= $this->displayConfirmation($this->trans('Settings for User ID updated successfully', array(), 'Modules.GAnalytics.Admin'));
            }

            $ga_anonymize_enabled = Tools::getValue('GA_ANONYMIZE_ENABLED');
            if (null !== $ga_anonymize_enabled) {
                Configuration::updateValue('GA_ANONYMIZE_ENABLED', (bool)$ga_anonymize_enabled);
                $output .= $this->displayConfirmation($this->trans('Settings for Anonymize IP updated successfully', array(), 'Modules.GAnalytics.Admin'));
            }
        }

        $output .= $this->displayForm();

        return $this->display(__FILE__, './views/templates/admin/configuration.tpl').$output;
    }

    public function hookdisplayHeader($params, $back_office = false)
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->controller->addJs($this->_path.'views/js/GoogleAnalyticActionLib.js');

            $shops = Shop::getShops();
            $is_multistore_active = Shop::isFeatureActive();

            $current_shop_id = (int)Context::getContext()->shop->id;

            $user_id = null;
            $ga_crossdomain_enabled = false;

            if (Configuration::get('GA_USERID_ENABLED') &&
                $this->context->customer && $this->context->customer->isLogged()
            ) {
                $user_id = (int)$this->context->customer->id;
            }

            $ga_anonymize_enabled = Configuration::get('GA_ANONYMIZE_ENABLED');

            if ((int)Configuration::get('GA_CROSSDOMAIN_ENABLED') && $is_multistore_active && sizeof($shops) > 1) {
                $ga_crossdomain_enabled = true;
            }

            $this->smarty->assign(
                array(
                    'backOffice' => $back_office,
                    'currentShopId' => $current_shop_id,
                    'userId' => $user_id,
                    'gaAccountId' => Tools::safeOutput(Configuration::get('GA_ACCOUNT_ID')),
                    'shops' => $shops,
                    'gaCrossdomainEnabled' => $ga_crossdomain_enabled,
                    'gaAnonymizeEnabled' => $ga_anonymize_enabled,
                    'useSecureMode' => Configuration::get('PS_SSL_ENABLED')
                )
            );
            return $this->display(__FILE__, 'ps_googleanalytics.tpl');
        }
    }

    /**
     * Return a detailed transaction for Google Analytics
     */
    public function wrapOrder($id_order)
    {
        $order = new Order((int)$id_order);

        if (Validate::isLoadedObject($order)) {
            return array(
                'id' => $id_order,
                'affiliation' => Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                'revenue' => $order->total_paid,
                'shipping' => $order->total_shipping,
                'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                'url' => $this->context->link->getAdminLink('AdminGanalyticsAjax'),
                'customer' => $order->id_customer);
        }
    }

    /**
     * To track transactions
     */
    public function hookdisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int)Configuration::get('PS_OS_ERROR')) {
            $ga_order_sent = Db::getInstance()->getValue('SELECT id_order FROM `'._DB_PREFIX_.'ganalytics` WHERE id_order = '.(int)$order->id);
            if ($ga_order_sent === false) {
                Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ganalytics` (id_order, id_shop, sent, date_add) VALUES ('.(int)$order->id.', '.(int)$this->context->shop->id.', 0, NOW())');
                if ($order->id_customer == $this->context->cookie->id_customer) {
                    $order_products = array();
                    $cart = new Cart($order->id_cart);
                    foreach ($cart->getProducts() as $order_product) {
                        $order_products[] = $this->wrapProduct($order_product, array(), 0, true);
                    }

                    $ga_scripts = 'MBG.addCheckoutOption(3,\''.$order->payment.'\');';

                    $transaction = array(
                        'id' => $order->id,
                        'affiliation' => (Shop::isFeatureActive()) ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                        'revenue' => $order->total_paid,
                        'shipping' => $order->total_shipping,
                        'tax' => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                        'url' => $this->context->link->getModuleLink('ps_googleanalytics', 'ajax', array(), true),
                        'customer' => $order->id_customer);
                    $ga_scripts .= $this->addTransaction($order_products, $transaction);

                    $this->js_state = 1;
                    return $this->_runJs($ga_scripts);
                }
            }
        }
    }

    /**
     * hook footer to load JS script for standards actions such as product clicks
     */
    public function hookdisplayFooter()
    {
        $ga_scripts = '';
        $this->js_state = 0;
        $gacarts = $this->_manageData("", "R");
        $controller_name = Tools::getValue('controller');
        if (count($gacarts)>0 && $controller_name!='product') {
            $this->filterable = 0;

            foreach ($gacarts as $gacart) {
                if (isset($gacart['quantity']))
                {
                    if ($gacart['quantity'] > 0) {
                        $ga_scripts .= 'MBG.addToCart('.json_encode($gacart).');';
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
                        $ga_scripts .= 'MBG.removeFromCart('.json_encode($gacart).');';
                    }
                } else {
                    $ga_scripts .= $gacart;
                }
            }
            $gacarts = $this->_manageData("", "D");
        }

        $listing = $this->context->smarty->getTemplateVars('listing');
        $products = $this->wrapProducts($listing['products'], array(), true);

        if ($controller_name == 'order' || $controller_name == 'orderopc') {
            $this->js_state = 1;
            $this->eligible = 1;
            $step = Tools::getValue('step');
            if (empty($step)) {
                $step = 0;
            }
            $ga_scripts .= $this->addProductFromCheckout($products, $step);
            $ga_scripts .= 'MBG.addCheckout(\''.(int)$step.'\');';
        }

        $confirmation_hook_id = (int)Hook::getIdByName('displayOrderConfirmation');
        if (isset(Hook::$executed_hooks[$confirmation_hook_id])) {
            $this->eligible = 1;
        }

        if (isset($products) && count($products) && $controller_name != 'index') {
            if ($this->eligible == 0) {
                $ga_scripts .= $this->addProductImpression($products);
            }
            $ga_scripts .= $this->addProductClick($products);
        }

        return $this->_runJs($ga_scripts);
    }

    protected function filter($ga_scripts)
    {
        if ($this->filterable = 1) {
            return implode(';', array_unique(explode(';', $ga_scripts)));
        }

        return $ga_scripts;
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     */
    public function hookdisplayHome()
    {
        $ga_scripts = '';

        // Home featured products
        if ($this->isModuleEnabled('ps_featuredproducts')) {
            $category = new Category($this->context->shop->getCategory(), $this->context->language->id);
            $home_featured_products = $this->wrapProducts(
                $category->getProducts(
                    (int)Context::getContext()->language->id,
                    1,
                    (Configuration::get('HOME_FEATURED_NBR') ? (int)Configuration::get('HOME_FEATURED_NBR') : 8),
                    'position'
                ),
                array(),
                true
            );
            $ga_scripts .= $this->addProductImpression($home_featured_products).$this->addProductClick($home_featured_products);
        }

        $this->js_state = 1;
        return $this->_runJs($this->filter($ga_scripts));
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     */
    public function isModuleEnabled($module_name)
    {
        if (($module = Module::getInstanceByName($module_name)) !== false &&
            Module::isInstalled($module_name) &&
            $module->active) {
            return $module->registerHook('displayHome');
        }
    }

    /**
     * wrap products to provide a standard products information for google analytics script
     */
    public function wrapProducts($products, $extras = array(), $full = false)
    {
        $result_products = array();
        if (!is_array($products)) {
            return;
        }

        $currency = new Currency($this->context->currency->id);
        $usetax = (Product::getTaxCalculationMethod((int)$this->context->customer->id) != PS_TAX_EXC);

        if (count($products) > 20) {
            $full = false;
        } else {
            $full = true;
        }

        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $product = (array)$product;
            }

            if (!isset($product['price'])) {
                $product['price'] = (float)Tools::displayPrice(Product::getPriceStatic((int)$product['id_product'], $usetax), $currency);
            }
            $result_products[] = $this->wrapProduct($product, $extras, $index, $full);
        }

        return $result_products;
    }

    /**
     * wrap product to provide a standard product information for google analytics script
     */
    public function wrapProduct($product, $extras, $index = 0, $full = false)
    {
        $ga_product = '';

        $variant = null;
        if (isset($product['attributes_small'])) {
            $variant = $product['attributes_small'];
        } elseif (isset($extras['attributes_small'])) {
            $variant = $extras['attributes_small'];
        }

        $product_qty = 1;
        if (isset($extras['qty'])) {
            $product_qty = $extras['qty'];
        } elseif (isset($product['cart_quantity'])) {
            $product_qty = $product['cart_quantity'];
        }

        $product_id = 0;
        if (!empty($product['id_product'])) {
            $product_id = $product['id_product'];
        } elseif (!empty($product['id'])) {
            $product_id = $product['id'];
        }

        if (!empty($product['id_product_attribute'])) {
            $product_id .= '-'. $product['id_product_attribute'];
        }

        $product_type = 'typical';
        if (isset($product['pack']) && $product['pack'] == 1) {
            $product_type = 'pack';
        } elseif (isset($product['virtual']) && $product['virtual'] == 1) {
            $product_type = 'virtual';
        }

        if ($full) {
            $ga_product = array(
                'id' => $product_id,
                'name' => Tools::str2url($product['name']),
                'category' => Tools::str2url($product['category']),
                'brand' => isset($product['manufacturer_name']) ? Tools::str2url($product['manufacturer_name']) : '',
                'variant' => Tools::str2url($variant),
                'type' => $product_type,
                'position' => $index ? $index : '0',
                'quantity' => $product_qty,
                'list' => Tools::getValue('controller'),
                'url' => isset($product['link']) ? urlencode($product['link']) : '',
                'price' => $product['price']
            );
        } else {
            $ga_product = array(
                'id' => $product_id,
                'name' => Tools::str2url($product['name'])
            );
        }
        return $ga_product;
    }

    /**
     * add order transaction
     */
    public function addTransaction($products, $order)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js.'MBG.addTransaction('.json_encode($order).');';
    }

    /**
     * add product impression js and product click js
     */
    public function addProductImpression($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).",'',true);";
        }

        return $js;
    }

    public function addProductClick($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClick('.json_encode($product).');';
        }

        return $js;
    }

    public function addProductClickByHttpReferal($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClickByHttpReferal('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * Add product checkout info
     */
    public function addProductFromCheckout($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * hook product page footer to load JS for product details view
     */
    public function hookdisplayFooterProduct($params)
    {
        $controller_name = Tools::getValue('controller');
        if ($controller_name == 'product')
        {
            if ($params['product'] instanceof Product) {
                $params['product'] = (array) $params['product'];
            }
            // Add product view
            $ga_product = $this->wrapProduct($params['product'], null, 0, true);
            $js = 'MBG.addProductDetailView('.json_encode($ga_product).');';

            if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
                $js .= $this->addProductClickByHttpReferal(array($ga_product));
            }

            $this->js_state = 1;
            return $this->_runJs($js);
        }
    }

    /**
     * Generate Google Analytics js
     */
    protected function _runJs($js_code, $backoffice = 0)
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $runjs_code = '';
            if (!empty($js_code)) {
                $runjs_code .= '
				<script type="text/javascript">
					document.addEventListener(\'DOMContentLoaded\', function() {
						var MBG = GoogleAnalyticEnhancedECommerce;
						MBG.setCurrency(\''.Tools::safeOutput($this->context->currency->iso_code).'\');
						'.$js_code.'
					});
				</script>';
            }

            if (($this->js_state) != 1 && ($backoffice == 0)) {
                $runjs_code .= '
				<script type="text/javascript">
					ga(\'send\', \'pageview\');
				</script>';
            }

            return $runjs_code;
        }
    }

    /**
     * Manage data
     * @param string $action "R" read data from DB, "W" write data, "A" append data, D" delete data
     * @return array dans le cas du R, sinon true
     */
    protected function _manageData($data, $action)
    {
        if ($action == 'R') {
            $dataretour = Db::getInstance()->getValue('SELECT data FROM `'._DB_PREFIX_.'ganalytics_data` WHERE id_cart = \''.(int)$this->context->cart->id.'\' AND id_shop = \''.(int)$this->context->shop->id.'\'');
            if ($dataretour === false)
                return array();
            else
                return json_decode($dataretour,true);
        }
        if ($action == 'W') {
            return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ganalytics_data` (id_cart, id_shop, data) VALUES(\''.(int)$this->context->cart->id.'\',\''.(int)$this->context->shop->id.'\',\''.json_encode($data).'\') ON DUPLICATE KEY UPDATE data =\''.json_encode($data).'\' ;');
        }
        if ($action == 'A') {
            $dataretour = Db::getInstance()->getValue('SELECT data FROM `'._DB_PREFIX_.'ganalytics_data` WHERE id_cart = \''.(int)$this->context->cart->id.'\' AND id_shop = \''.(int)$this->context->shop->id.'\'');
            if ($dataretour === false)
                $datanew = array($data);
            else {
                $datanew = json_decode($dataretour,true);
                $datanew[] = $data;
            }
            return Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ganalytics_data` (id_cart, id_shop, data) VALUES(\''.(int)$this->context->cart->id.'\',\''.(int)$this->context->shop->id.'\',\''.pSQL(json_encode($datanew)).'\') ON DUPLICATE KEY UPDATE data =\''.pSQL(json_encode($datanew)).'\' ;');
        }
        if ($action == 'D') {
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'ganalytics_data` WHERE id_cart = \''.(int)$this->context->cart->id.'\' AND id_shop = \''.(int)$this->context->shop->id.'\'');
        }
    }

    /**
     * Hook admin order to send transactions and refunds details
     */
    public function hookdisplayAdminOrder()
    {
        echo $this->_runJs($this->context->cookie->ga_admin_refund, 1);
        unset($this->context->cookie->ga_admin_refund);
    }

    /**
     *  admin office header to add google analytics js
     */
    public function hookdisplayBackOfficeHeader()
    {
        $js = '';
        if (strcmp(Tools::getValue('configure'), $this->name) === 0) {
            $this->context->controller->addCSS($this->_path.'views/css/ganalytics.css');
        }

        $ga_account_id = Configuration::get('GA_ACCOUNT_ID');

        if (!empty($ga_account_id) && $this->active) {
            $this->context->controller->addJs($this->_path.'views/js/GoogleAnalyticActionLib.js');

            $this->context->smarty->assign('GA_ACCOUNT_ID', $ga_account_id);

            $ga_scripts = '';
            if ($this->context->controller->controller_name == 'AdminOrders') {
                if (Tools::getValue('id_order')) {
                    $order = new Order((int)Tools::getValue('id_order'));
                    if (Validate::isLoadedObject($order) && strtotime('+1 day', strtotime($order->date_add)) > time()) {
                        $ga_order_sent = Db::getInstance()->getValue('SELECT id_order FROM `'._DB_PREFIX_.'ganalytics` WHERE id_order = '.(int)Tools::getValue('id_order'));
                        if ($ga_order_sent === false) {
                            Db::getInstance()->Execute('INSERT IGNORE INTO `'._DB_PREFIX_.'ganalytics` (id_order, id_shop, sent, date_add) VALUES ('.(int)Tools::getValue('id_order').', '.(int)$this->context->shop->id.', 0, NOW())');
                        }
                    }
                } else {
                    $ga_order_records = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'ganalytics` WHERE sent = 0 AND id_shop = \''.(int)$this->context->shop->id.'\' AND DATE_ADD(date_add, INTERVAL 30 minute) < NOW()');

                    if ($ga_order_records) {
                        foreach ($ga_order_records as $row) {
                            $transaction = $this->wrapOrder($row['id_order']);
                            if (!empty($transaction)) {
                                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ganalytics` SET date_add = NOW(), sent = 1 WHERE id_order = '.(int)$row['id_order'].' AND id_shop = \''.(int)$this->context->shop->id.'\'');
                                $transaction = json_encode($transaction);
                                $ga_scripts .= 'MBG.addTransaction('.$transaction.');';
                            }
                        }
                    }
                }
            }
            return $js.$this->hookdisplayHeader(null, true).$this->_runJs($ga_scripts, 1);
        } else {
            return $js;
        }
    }

    /**
     * Hook admin office header to add google analytics js
     */
    public function hookactionProductCancel($params)
    {
        $qty_refunded = Tools::getValue('cancelQuantity');
        $ga_scripts = '';
        foreach ($qty_refunded as $orderdetail_id => $qty) {
            // Display GA refund product
            $order_detail = new OrderDetail($orderdetail_id);
            $ga_scripts .= 'MBG.add('.json_encode(
                array(
                    'id' => empty($order_detail->product_attribute_id)?$order_detail->product_id:$order_detail->product_id.'-'.$order_detail->product_attribute_id,
                    'quantity' => $qty)
                )
                .');';
        }
        $this->context->cookie->ga_admin_refund = $ga_scripts.'MBG.refundByProduct('.json_encode(array('id' => $params['order']->id)).');';
    }

    /**
     * hook save cart event to implement addtocart and remove from cart functionality
     */
    public function hookactionCartSave()
    {
        if (!isset($this->context->cart)) {
            return;
        }

        if (!Tools::getIsset('id_product')) {
            return;
        }

        $cart = array(
            'controller' => Tools::getValue('controller'),
            'addAction' => Tools::getValue('add') ? 'add' : '',
            'removeAction' => Tools::getValue('delete') ? 'delete' : '',
            'extraAction' => Tools::getValue('op'),
            'qty' => (int)Tools::getValue('qty', 1)
        );

        $cart_products = $this->context->cart->getProducts();
        if (isset($cart_products) && count($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($cart_product['id_product'] == Tools::getValue('id_product')) {
                    $add_product = $cart_product;
                }
            }
        }

        if ($cart['removeAction'] == 'delete') {
            $add_product_object = new Product((int)Tools::getValue('id_product'), true, (int)Configuration::get('PS_LANG_DEFAULT'));
            if (Validate::isLoadedObject($add_product_object)) {
                $add_product['name'] = $add_product_object->name;
                $add_product['manufacturer_name'] = $add_product_object->manufacturer_name;
                $add_product['category'] = $add_product_object->category;
                $add_product['reference'] = $add_product_object->reference;
                $add_product['link_rewrite'] = $add_product_object->link_rewrite;
                $add_product['link'] = $add_product_object->link_rewrite;
                $add_product['price'] = $add_product_object->price;
                $add_product['ean13'] = $add_product_object->ean13;
                $add_product['id_product'] = Tools::getValue('id_product');
                $add_product['id_category_default'] = $add_product_object->id_category_default;
                $add_product['out_of_stock'] = $add_product_object->out_of_stock;
                $add_product['minimal_quantity'] = 1;
                $add_product['unit_price_ratio'] = 0;
                $add_product = Product::getProductProperties((int)Configuration::get('PS_LANG_DEFAULT'), $add_product);
            }
        }

        if (isset($add_product) && !in_array((int)Tools::getValue('id_product'), self::$products)) {
            self::$products[] = (int)Tools::getValue('id_product');
            $ga_products = $this->wrapProduct($add_product, $cart, 0, true);

            if (array_key_exists('id_product_attribute', $ga_products) && $ga_products['id_product_attribute'] != '' && $ga_products['id_product_attribute'] != 0) {
                $id_product = $ga_products['id_product_attribute'];
            } else {
                $id_product = Tools::getValue('id_product');
            }

            $gacart = $this->_manageData("", "R");

            if ($cart['removeAction'] == 'delete') {
                $ga_products['quantity'] = -1;
            } elseif ($cart['extraAction'] == 'down') {
                if (array_key_exists($id_product, $gacart)) {
                    $ga_products['quantity'] = $gacart[$id_product]['quantity'] - $cart['qty'];
                } else {
                    $ga_products['quantity'] = $cart['qty'] * -1;
                }
            } elseif (Tools::getValue('step') <= 0) { // Sometimes cartsave is called in checkout
                if (array_key_exists($id_product, $gacart)) {
                    $ga_products['quantity'] = $gacart[$id_product]['quantity'] + $cart['qty'];
                }
            }

            $gacart[$id_product] = $ga_products;
            $this->_manageData($gacart, 'W');
        }
    }

    public function hookactionCarrierProcess($params)
    {
        if (isset($params['cart']->id_carrier)) {
            $carrier_name = Db::getInstance()->getValue('SELECT name FROM `'._DB_PREFIX_.'carrier` WHERE id_carrier = '.(int)$params['cart']->id_carrier);
            $this->_manageData('MBG.addCheckoutOption(2,\''.$carrier_name.'\');', 'A');
        }
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
}
