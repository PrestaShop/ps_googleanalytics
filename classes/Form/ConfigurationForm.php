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

namespace PrestaShop\Module\Ps_Googleanalytics\Form;

use AdminController;
use Configuration;
use HelperForm;
use Ps_Googleanalytics;
use Shop;
use Tools;

class ConfigurationForm
{
    private $module;

    public function __construct(Ps_Googleanalytics $module)
    {
        $this->module = $module;
    }

    /**
     * generate
     *
     * @return string
     */
    public function generate()
    {
        // Check if multistore is active
        $is_multistore_active = Shop::isFeatureActive();

        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->module->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->module->name . '&save=' . $this->module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->module->l('Back to list'),
            ],
        ];

        $fields_form = [];
        // Init Fields form array
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->module->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Google Analytics Tracking ID'),
                    'name' => 'GA_ACCOUNT_ID',
                    'size' => 20,
                    'required' => true,
                    'hint' => $this->module->l('This information is available in your Google Analytics account'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Enable User ID tracking'),
                    'name' => 'GA_USERID_ENABLED',
                    'values' => [
                        [
                            'id' => 'ga_userid_enabled',
                            'value' => 1,
                            'label' => $this->module->l('Enabled'),
                        ],
                        [
                            'id' => 'ga_userid_disabled',
                            'value' => 0,
                            'label' => $this->module->l('Disabled'),
                        ], ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Anonymize IP'),
                    'name' => 'GA_ANONYMIZE_ENABLED',
                    'hint' => $this->module->l('Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries'),
                    'values' => [
                        [
                            'id' => 'ga_anonymize_enabled',
                            'value' => 1,
                            'label' => $this->module->l('Enabled'),
                        ],
                        [
                            'id' => 'ga_anonymize_disabled',
                            'value' => 0,
                            'label' => $this->module->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save'),
            ],
        ];

        if ($is_multistore_active) {
            $fields_form[0]['form']['input'][] = [
                'type' => 'switch',
                'label' => $this->module->l('Enable Cross-Domain tracking'),
                'name' => 'GA_CROSSDOMAIN_ENABLED',
                'values' => [
                    [
                        'id' => 'ga_crossdomain_enabled',
                        'value' => 1,
                        'label' => $this->module->l('Enabled'),
                    ],
                    [
                        'id' => 'ga_crossdomain_disabled',
                        'value' => 0,
                         'label' => $this->module->l('Disabled'),
                    ],
                ],
            ];
        }

        // Load current value
        $helper->fields_value['GA_ACCOUNT_ID'] = Configuration::get('GA_ACCOUNT_ID');
        $helper->fields_value['GA_USERID_ENABLED'] = Configuration::get('GA_USERID_ENABLED');
        $helper->fields_value['GA_CROSSDOMAIN_ENABLED'] = Configuration::get('GA_CROSSDOMAIN_ENABLED');
        $helper->fields_value['GA_ANONYMIZE_ENABLED'] = Configuration::get('GA_ANONYMIZE_ENABLED');

        return $helper->generateForm($fields_form);
    }

    /**
     * treat the form datas if submited
     *
     * @return string
     */
    public function treat()
    {
        $treatmentResult = '';
        $gaAccountId = Tools::getValue('GA_ACCOUNT_ID');
        $gaUserIdEnabled = Tools::getValue('GA_USERID_ENABLED');
        $gaCrossdomainEnabled = Tools::getValue('GA_CROSSDOMAIN_ENABLED');
        $gaAnonymizeEnabled = Tools::getValue('GA_ANONYMIZE_ENABLED');

        if (!empty($gaAccountId)) {
            Configuration::updateValue('GA_ACCOUNT_ID', $gaAccountId);
            Configuration::updateValue('GANALYTICS_CONFIGURATION_OK', true);
            $treatmentResult .= $this->module->displayConfirmation($this->module->l('Account ID updated successfully'));
        }

        if (null !== $gaUserIdEnabled) {
            Configuration::updateValue('GA_USERID_ENABLED', (bool) $gaUserIdEnabled);
            $treatmentResult .= $this->module->displayConfirmation($this->module->l('Settings for User ID updated successfully'));
        }

        if (null !== $gaCrossdomainEnabled) {
            Configuration::updateValue('GA_CROSSDOMAIN_ENABLED', (bool) $gaCrossdomainEnabled);
            $treatmentResult .= $this->module->displayConfirmation($this->module->l('Settings for User ID updated successfully'));
        }

        if (null !== $gaAnonymizeEnabled) {
            Configuration::updateValue('GA_ANONYMIZE_ENABLED', (bool) $gaAnonymizeEnabled);
            $treatmentResult .= $this->module->displayConfirmation($this->module->l('Settings for Anonymize IP updated successfully'));
        }

        return $treatmentResult;
    }
}
