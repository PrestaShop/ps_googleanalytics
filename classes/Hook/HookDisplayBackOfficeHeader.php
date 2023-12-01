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

use Cart;
use Configuration;
use Context;
use Db;
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
    private $gaScripts = '';

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

        // Render base tag using displayHeader hook with backoffice parameter
        $this->gaScripts .= $this->module->hookDisplayHeader(null, true);

        // Process manual orders instantly, we have their IDs in cookie
        $this->processManualOrders();

        // Backload old orders that failed to load normally
        $this->processFailedOrders();

        return $this->gaScripts;
    }

    /**
     * Checks if there are any orders that failed to be sent normally through front office and processes them
     */
    protected function processFailedOrders()
    {
        if (empty(Configuration::get('GA_BACKLOAD_ENABLED'))) {
            return;
        }

        // Check for value on how long back we will get them
        $backloadDays = (int) Configuration::get('GA_BACKLOAD_DAYS');
        if ($backloadDays < 1) {
            return;
        }

        // Get all failed orders (either not present in our table or not sent)
        // We go GA_BACKLOAD_DAYS into the past and at least 30 minutes old
        $failedOrders = Db::getInstance()->ExecuteS(
            'SELECT DISTINCT o.id_order, g.sent FROM `' . _DB_PREFIX_ . 'orders` o
            LEFT JOIN `' . _DB_PREFIX_ . GanalyticsRepository::TABLE_NAME . '` g ON o.id_order = g.id_order
            WHERE (g.sent IS NULL OR g.sent = 0) AND o.date_add BETWEEN NOW() - INTERVAL ' . $backloadDays . ' DAY AND NOW() - INTERVAL 30 MINUTE'
        );

        // Process each failed order
        foreach ($failedOrders as $row) {
            $this->processOrder((int) $row['id_order']);
        }
    }

    /**
     * Checks if there are any manual orders in cookie and processes them
     */
    protected function processManualOrders()
    {
        $adminOrders = $this->context->cookie->ga_admin_order;
        if (empty($adminOrders)) {
            return;
        }

        // Separate them by IDs and process one by one
        $adminOrders = explode(',', $adminOrders);
        foreach ($adminOrders as $idOrder) {
            $this->processOrder((int) $idOrder);
        }

        // Clean up the cookie
        unset($this->context->cookie->ga_admin_order);
        $this->context->cookie->write();
    }

    /**
     * Renders tracking code for given order
     *
     * @param int $idOrder
     */
    public function processOrder($idOrder)
    {
        $order = new Order((int) $idOrder);

        if (!Validate::isLoadedObject($order) || $order->getCurrentState() == (int) Configuration::get('PS_OS_ERROR')) {
            return;
        }

        // Load up our handlers and repositories
        $ganalyticsRepository = new GanalyticsRepository();
        $gaTagHandler = new GanalyticsJsHandler($this->module, $this->context);
        $productWrapper = new ProductWrapper($this->context);
        $orderWrapper = new OrderWrapper($this->context);

        // If it's a completely new order, add order to repository, so we can later mark it as sent
        if (empty($ganalyticsRepository->findGaOrderByOrderId((int) $order->id))) {
            $ganalyticsRepository->addOrder((int) $order->id, (int) $order->id_shop);
        }

        // If the order was already sent for some reason, don't do anything
        if ($ganalyticsRepository->hasOrderBeenAlreadySent((int) $order->id)) {
            return;
        }

        // Prepare transaction data
        $orderData = $orderWrapper->wrapOrder($order);

        // Empty script for the current order
        $gaScripts = '';

        // Prepare order products, if the cart still exists
        $orderProducts = [];
        $cart = new Cart($order->id_cart);
        if (Validate::isLoadedObject($cart)) {
            $orderProducts = $productWrapper->prepareItemListFromProductList($cart->getProducts(), true);
        }

        // Add payment event
        $gaScripts .= $this->module->getTools()->renderEvent(
            'add_payment_info',
            [
                'currency' => $orderData['currency'],
                'value' => (float) $orderData['value'],
                'payment_type' => $orderData['payment_type'],
                'items' => $orderProducts,
            ]
        );

        // Render transaction code
        $gaScripts .= $this->module->getTools()->renderPurchaseEvent(
            $orderProducts,
            $orderData,
            $this->context->link->getAdminLink('AdminGanalyticsAjax')
        );

        $this->gaScripts .= $gaTagHandler->generate($gaScripts);
    }
}
