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

namespace PrestaShop\Module\Ps_Googleanalytics\Form;

use AdminController;
use Configuration;
use Context;
use HelperForm;
use OrderState;
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
                'desc' => $this->module->trans('Save', [], 'Modules.GAnalytics.Admin'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->module->name . '&save=' . $this->module->name .
                '&token=' . $helper->token,
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . $helper->token,
                'desc' => $this->module->trans('Back to list', [], 'Modules.GAnalytics.Admin'),
            ],
        ];

        $fields_form = [];
        // Init Fields form array
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->module->trans('Settings', [], 'Modules.GAnalytics.Admin'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->module->trans('Enable Google Analytics 4', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_V4_ENABLED',
                    'values' => [
                        [
                            'id' => 'GA_V4_ENABLED',
                            'value' => 1,
                            'label' => $this->module->trans('Yes', [], 'Modules.GAnalytics.Admin'),
                        ],
                        [
                            'id' => 'GA_V4_ENABLED',
                            'value' => 0,
                            'label' => $this->module->trans('No', [], 'Modules.GAnalytics.Admin'),
                        ],
                    ],
                    'desc' => $this->module->trans('Universal analytics will stop processing data on July 1, 2023. We recommend switching to Google Analytics 4 as soon as possible.', [], 'Modules.GAnalytics.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->trans('Google Analytics Tracking ID', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_ACCOUNT_ID',
                    'size' => 20,
                    'required' => true,
                    'desc' => $this->module->trans('This information is available in your Google Analytics account. GA4 tracking ID starts with "G-", Universal Analytics with "UA-".', [], 'Modules.GAnalytics.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->trans('Enable User ID tracking', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_USERID_ENABLED',
                    'values' => [
                        [
                            'id' => 'ga_userid_enabled',
                            'value' => 1,
                            'label' => $this->module->trans('Yes', [], 'Modules.GAnalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_userid_disabled',
                            'value' => 0,
                            'label' => $this->module->trans('No', [], 'Modules.GAnalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->trans('Anonymize IP', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_ANONYMIZE_ENABLED',
                    'hint' => $this->module->trans('Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries', [], 'Modules.GAnalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_anonymize_enabled',
                            'value' => 1,
                            'label' => $this->module->trans('Yes', [], 'Modules.GAnalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_anonymize_disabled',
                            'value' => 0,
                            'label' => $this->module->trans('No', [], 'Modules.GAnalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->trans('Enable Back Office Tracking', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_TRACK_BACKOFFICE_ENABLED',
                    'hint' => $this->module->trans('Use this option to enable the tracking inside the Back Office', [], 'Modules.GAnalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_track_backoffice',
                            'value' => 1,
                            'label' => $this->module->trans('Yes', [], 'Modules.GAnalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_do_not_track_backoffice',
                            'value' => 0,
                            'label' => $this->module->trans('No', [], 'Modules.GAnalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->trans('Canceled order states', [], 'Modules.GAnalytics.Admin'),
                    'name' => 'GA_CANCELLED_STATES',
                    'desc' => $this->module->trans('Choose order states in which you consider the given order canceled. This will usually be the default "Canceled" state, but some stores may have extra states like "Returned", etc.', [], 'Modules.GAnalytics.Admin'),
                    'class' => 'chosen',
                    'multiple' => true,
                    'options' => [
                        'query' => OrderState::getOrderStates((int) Context::getContext()->language->id),
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->trans('Save', [], 'Modules.GAnalytics.Admin'),
            ],
        ];

        if ($is_multistore_active) {
            $fields_form[0]['form']['input'][] = [
                'type' => 'switch',
                'label' => $this->module->trans('Enable Cross-Domain tracking', [], 'Modules.GAnalytics.Admin'),
                'name' => 'GA_CROSSDOMAIN_ENABLED',
                'values' => [
                    [
                        'id' => 'ga_crossdomain_enabled',
                        'value' => 1,
                        'label' => $this->module->trans('Yes', [], 'Modules.GAnalytics.Admin'),
                    ],
                    [
                        'id' => 'ga_crossdomain_disabled',
                        'value' => 0,
                         'label' => $this->module->trans('No', [], 'Modules.GAnalytics.Admin'),
                    ],
                ],
            ];
        }

        // Load current value
        $helper->fields_value['GA_ACCOUNT_ID'] = Configuration::get('GA_ACCOUNT_ID');
        $helper->fields_value['GA_V4_ENABLED'] = Configuration::get('GA_V4_ENABLED');
        $helper->fields_value['GA_USERID_ENABLED'] = Configuration::get('GA_USERID_ENABLED');
        $helper->fields_value['GA_CROSSDOMAIN_ENABLED'] = Configuration::get('GA_CROSSDOMAIN_ENABLED');
        $helper->fields_value['GA_ANONYMIZE_ENABLED'] = Configuration::get('GA_ANONYMIZE_ENABLED');
        $helper->fields_value['GA_TRACK_BACKOFFICE_ENABLED'] = Configuration::get('GA_TRACK_BACKOFFICE_ENABLED');
        $helper->fields_value['GA_CANCELLED_STATES[]'] = json_decode(Configuration::get('GA_CANCELLED_STATES'), true);

        return $helper->generateForm($fields_form);
    }

    /**
     * treat the form datas if submited
     *
     * @return string
     */
    public function treat()
    {
        // Check if multistore is active
        $is_multistore_active = Shop::isFeatureActive();

        $gaAccountId = Tools::getValue('GA_ACCOUNT_ID');
        $gaV4Enabled = Tools::getValue('GA_V4_ENABLED');
        $gaUserIdEnabled = Tools::getValue('GA_USERID_ENABLED');
        $gaCrossdomainEnabled = Tools::getValue('GA_CROSSDOMAIN_ENABLED');
        $gaAnonymizeEnabled = Tools::getValue('GA_ANONYMIZE_ENABLED');
        $gaTrackBackOffice = Tools::getValue('GA_TRACK_BACKOFFICE_ENABLED');
        $gaCancelledStates = Tools::getValue('GA_CANCELLED_STATES');

        if (!empty($gaAccountId)) {
            Configuration::updateValue('GA_ACCOUNT_ID', $gaAccountId);
            Configuration::updateValue('GANALYTICS_CONFIGURATION_OK', true);
        }

        if (null !== $gaV4Enabled) {
            Configuration::updateValue('GA_V4_ENABLED', (bool) $gaV4Enabled);
        }

        if (null !== $gaUserIdEnabled) {
            Configuration::updateValue('GA_USERID_ENABLED', (bool) $gaUserIdEnabled);
        }

        if ($is_multistore_active) {
            Configuration::updateValue('GA_CROSSDOMAIN_ENABLED', (bool) $gaCrossdomainEnabled);
        }

        if (null !== $gaAnonymizeEnabled) {
            Configuration::updateValue('GA_ANONYMIZE_ENABLED', (bool) $gaAnonymizeEnabled);
        }

        if (null !== $gaTrackBackOffice) {
            Configuration::updateValue('GA_TRACK_BACKOFFICE_ENABLED', (bool) $gaTrackBackOffice);
        }

        if ($gaCancelledStates === false) {
            Configuration::updateValue('GA_CANCELLED_STATES', '');
        } else {
            Configuration::updateValue('GA_CANCELLED_STATES', json_encode($gaCancelledStates));
        }

        return $this->module->displayConfirmation($this->module->trans('Settings updated successfully.', [], 'Modules.GAnalytics.Admin'));
    }
}
