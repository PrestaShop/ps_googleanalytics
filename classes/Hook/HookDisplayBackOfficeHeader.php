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

namespace PrestaShop\Module\Ps_Googleanalytics\Hooks;

use Configuration;
use Context;
use Currency;
use Order;
use PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler;
use PrestaShop\Module\Ps_Googleanalytics\Repository\GanalyticsRepository;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\OrderWrapper;
use PrestaShop\Module\Ps_Googleanalytics\Wrapper\ProductWrapper;
use Ps_Googleanalytics;
use Tools;
use Validate;

class HookDisplayBackOfficeHeader implements HookInterface
{
    private $module;
    private $context;

    public function __construct(Ps_Googleanalytics $module, Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * run
     *
     * @return string
     */
    public function run()
    {
        // Add assets if we are on configuration page
        if (strcmp(Tools::getValue('configure'), $this->module->name) === 0) {
            $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/ganalytics.css');
        }

        $gaScripts = '';

        // Render base tag using displayHeader hook with backoffice parameter
        $gaScripts .= $this->module->hookDisplayHeader(null, true);

        // Load up our handlers and repositories
        $ganalyticsRepository = new GanalyticsRepository();
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $productWrapper = new ProductWrapper($this->context);
        $orderWrapper = new OrderWrapper($this->context);

        // Process manual orders instantly, we have their IDs in cookie
        $gaScripts .= $this->processManualOrders();
        
        // TEMP RETURN
        return $gaTagHandler->generate($gaScripts);

        return;
        dump($this->context);
        dump($_POST);
        dump(Tools::getValue('id_order'));
        dump($_GET);
        die;

        // TEMP RETURN

        // Backload all unsuccessfully sent orders into our database
        if ($this->context->controller->controller_name == 'AdminOrders') {
            if (Tools::getValue('id_order')) {
                $order = new Order((int) Tools::getValue('id_order'));
                if (Validate::isLoadedObject($order) && strtotime('+1 day', strtotime($order->date_add)) > time()) {
                    $gaOrderSent = $ganalyticsRepository->findGaOrderByOrderId((int) Tools::getValue('id_order'));
                    if ($gaOrderSent === false) {
                        // Add order to repository, so we can later mark it as sent
                        $ganalyticsRepository->addOrder((int) $order->id, (int) $order->id_shop);
                    }
                }
            } else {
                $gaOrderRecords = $ganalyticsRepository->findAllByShopIdAndDateAdd((int) $this->context->shop->id);

                if ($gaOrderRecords) {
                    foreach ($gaOrderRecords as $row) {
                        $orderData = $orderWrapper->wrapOrder($row['id_order']);
                        if (!empty($orderData)) {
                            // Mark it as successfully sent
                            $ganalyticsRepository->markOrderAsSent((int) $row['id_order']);

                            // Add payment data
                            $gaScripts .= $this->module->getTools()->renderEvent(
                                'add_payment_info',
                                [
                                    'currency' => $orderData['currency'],
                                    'payment_type' => $orderData['payment_type'],
                                ]
                            );

                            // Render purchase
                            $gaScripts .= $this->module->getTools()->renderPurchaseEvent(
                                [],
                                $orderData,
                                $this->context->link->getAdminLink('AdminGanalyticsAjax')
                            );
                        }
                    }
                }
            }
        }

        return $gaTagHandler->generate($gaScripts);
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Checks if there are any manual orders in cookie and processes them
     */
    protected function processManualOrders()
    {
        $adminOrders = $this->context->cookie->__get('ga_admin_order');
        if (empty($adminOrders)) {
            return;
        }

        // Separate them by IDs and process one by one
        $adminOrders = explode(",", $adminOrders);
        $gaScripts = '';
        foreach ($adminOrders as $idOrder) {
            $gaScripts .= $this->processOrder((int) $idOrder);
        }

        // Clean up the cookie
        $this->context->cookie->__unset('ga_admin_order');
        $this->context->cookie->write();

        return $gaScripts;
    }

    /**
     * Renders tracking code for given order 
     *
     * @param int $idOrder
     */
    public function processOrder($idOrder)
    {
        $gaScripts = '';
        $order = new Order((int) $idOrder);

        if (!Validate::isLoadedObject($order) || $order->getCurrentState() == (int) Configuration::get('PS_OS_ERROR')) {
            return $gaScripts;
        }

        // Load up our handlers and repositories
        $ganalyticsRepository = new GanalyticsRepository();
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $productWrapper = new ProductWrapper($this->context);
        $orderWrapper = new OrderWrapper($this->context);

        // If the order was already sent for some reason, don't do anything
        if ($ganalyticsRepository->orderAlreadySent((int) $order->id)) {
            return $gaScripts;
        }
        
        // Add order to repository, so we can later mark it as sent
        // If revisiting this page, repository inserts ignore, so no worries
        $ganalyticsRepository->addOrder((int) $order->id, (int) $order->id_shop);

        // Prepare transaction data
        $orderData = $orderWrapper->wrapOrder((int) $order->id);
        
        // Add payment event
        $gaScripts .= $this->module->getTools()->renderEvent(
            'add_payment_info',
            [
                'currency' => $orderData['currency'],
                'payment_type' => $orderData['payment_type'],
            ]
        );

        // Prepare order products, if the cart still exists
        $orderProducts = [];
        $cart = new Cart($order->id_cart);
        if (Validate::isLoadedObject($cart)) {
            foreach ($cart->getProducts() as $order_product) {
                $orderProducts[] = $productWrapper->wrapProduct($order_product);
            }
        }

        // Render transaction code
        $gaScripts .= $this->module->getTools()->renderPurchaseEvent(
            $orderProducts,
            $orderData,
            $this->context->link->getAdminLink('AdminGanalyticsAjax')
        );

        return $gaTagHandler->generate($gaScripts);
    }
}
