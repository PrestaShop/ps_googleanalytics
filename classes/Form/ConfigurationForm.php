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
                'desc' => $this->module->getTranslator()->trans('Save', [], 'Modules.Googleanalytics.Admin'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->module->name . '&save=' . $this->module->name .
                '&token=' . $helper->token,
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . $helper->token,
                'desc' => $this->module->getTranslator()->trans('Back to list', [], 'Modules.Googleanalytics.Admin'),
            ],
        ];

        $fields_form = [];
        // Init Fields form array
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->module->getTranslator()->trans('Settings', [], 'Modules.Googleanalytics.Admin'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->getTranslator()->trans('Google Analytics Tracking ID', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_ACCOUNT_ID',
                    'size' => 20,
                    'required' => true,
                    'desc' => $this->module->getTranslator()->trans('This information is available in your Google Analytics account. Google Analytics 4 tracking ID starts with "G-".', [], 'Modules.Googleanalytics.Admin'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->getTranslator()->trans('Enable User ID tracking', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_USERID_ENABLED',
                    'desc' => $this->module->getTranslator()->trans('This option adds unique user ID to the tag to better track the customer. Use this option only if it complies with laws in your country.', [], 'Modules.Googleanalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_userid_enabled',
                            'value' => 1,
                            'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Googleanalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_userid_disabled',
                            'value' => 0,
                            'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Googleanalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->getTranslator()->trans('Anonymize IP', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_ANONYMIZE_ENABLED',
                    'desc' => $this->module->getTranslator()->trans('Use this option to anonymize the visitor’s IP to comply with data privacy laws in some countries', [], 'Modules.Googleanalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_anonymize_enabled',
                            'value' => 1,
                            'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Googleanalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_anonymize_disabled',
                            'value' => 0,
                            'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Googleanalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->getTranslator()->trans('Enable Back Office Tracking', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_TRACK_BACKOFFICE_ENABLED',
                    'desc' => $this->module->getTranslator()->trans('Use this option to enable the tracking inside the Back Office', [], 'Modules.Googleanalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_track_backoffice',
                            'value' => 1,
                            'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Googleanalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_do_not_track_backoffice',
                            'value' => 0,
                            'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Googleanalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->getTranslator()->trans('Canceled order states', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_CANCELLED_STATES',
                    'desc' => $this->module->getTranslator()->trans('Choose order states in which you consider the given order canceled. This will usually be the default "Canceled" state, but some stores may have extra states like "Returned", etc.', [], 'Modules.Googleanalytics.Admin'),
                    'class' => 'chosen',
                    'multiple' => true,
                    'options' => [
                        'query' => OrderState::getOrderStates((int) Context::getContext()->language->id),
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->getTranslator()->trans('Re-send failed orders', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_BACKLOAD_ENABLED',
                    'desc' => $this->module->getTranslator()->trans('This option will resend all orders that failed to be sent normally in front-office, due to failures or ad-blockers.', [], 'Modules.Googleanalytics.Admin'),
                    'values' => [
                        [
                            'id' => 'ga_backload_enabled',
                            'value' => 1,
                            'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Googleanalytics.Admin'),
                        ],
                        [
                            'id' => 'ga_backload_enabled',
                            'value' => 0,
                            'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Googleanalytics.Admin'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->getTranslator()->trans('Failed orders period', [], 'Modules.Googleanalytics.Admin'),
                    'name' => 'GA_BACKLOAD_DAYS',
                    'class' => 'input fixed-width-md',
                    'suffix' => 'days',
                    'desc' => $this->module->getTranslator()->trans('If you want to resend failed orders, specify how many days back the module should look for them. Default: 30.', [], 'Modules.Googleanalytics.Admin'),
                ],
            ],
            'submit' => [
                'title' => $this->module->getTranslator()->trans('Save', [], 'Modules.Googleanalytics.Admin'),
            ],
        ];

        // Load current value
        $helper->fields_value['GA_ACCOUNT_ID'] = Configuration::get('GA_ACCOUNT_ID');
        $helper->fields_value['GA_USERID_ENABLED'] = (bool) Configuration::get('GA_USERID_ENABLED');
        $helper->fields_value['GA_ANONYMIZE_ENABLED'] = (bool) Configuration::get('GA_ANONYMIZE_ENABLED');
        $helper->fields_value['GA_TRACK_BACKOFFICE_ENABLED'] = (bool) Configuration::get('GA_TRACK_BACKOFFICE_ENABLED');
        $helper->fields_value['GA_CANCELLED_STATES[]'] = json_decode(Configuration::get('GA_CANCELLED_STATES'), true);
        $helper->fields_value['GA_BACKLOAD_ENABLED'] = (bool) Configuration::get('GA_BACKLOAD_ENABLED');
        $helper->fields_value['GA_BACKLOAD_DAYS'] = (int) Configuration::get('GA_BACKLOAD_DAYS');

        return $helper->generateForm($fields_form);
    }

    /**
     * treat the form datas if submited
     *
     * @return string
     */
    public function treat()
    {
        $gaAccountId = Tools::getValue('GA_ACCOUNT_ID');
        $gaUserIdEnabled = Tools::getValue('GA_USERID_ENABLED');
        $gaAnonymizeEnabled = Tools::getValue('GA_ANONYMIZE_ENABLED');
        $gaTrackBackOffice = Tools::getValue('GA_TRACK_BACKOFFICE_ENABLED');
        $gaCancelledStates = Tools::getValue('GA_CANCELLED_STATES');
        $gaBackloadEnabled = Tools::getValue('GA_BACKLOAD_ENABLED');
        $gaBackloadDays = Tools::getValue('GA_BACKLOAD_DAYS');

        if (!empty($gaAccountId)) {
            Configuration::updateValue('GA_ACCOUNT_ID', $gaAccountId);
            Configuration::updateValue('GANALYTICS_CONFIGURATION_OK', true);
        }

        if (null !== $gaUserIdEnabled) {
            Configuration::updateValue('GA_USERID_ENABLED', (bool) $gaUserIdEnabled);
        }

        if (null !== $gaAnonymizeEnabled) {
            Configuration::updateValue('GA_ANONYMIZE_ENABLED', (bool) $gaAnonymizeEnabled);
        }

        if (null !== $gaTrackBackOffice) {
            Configuration::updateValue('GA_TRACK_BACKOFFICE_ENABLED', (bool) $gaTrackBackOffice);
        }

        if (null !== $gaBackloadEnabled) {
            Configuration::updateValue('GA_BACKLOAD_ENABLED', (bool) $gaBackloadEnabled);
        }

        if (null !== $gaBackloadDays) {
            Configuration::updateValue('GA_BACKLOAD_DAYS', (int) $gaBackloadDays);
        }

        if ($gaCancelledStates === false) {
            Configuration::updateValue('GA_CANCELLED_STATES', '');
        } else {
            Configuration::updateValue('GA_CANCELLED_STATES', json_encode($gaCancelledStates));
        }

        return $this->module->displayConfirmation($this->module->getTranslator()->trans('Settings updated successfully.', [], 'Modules.Googleanalytics.Admin'));
    }
}
