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

namespace PrestaShop\Module\Ps_Googleanalytics\Handler;

use Module;

class ModuleHandler
{
    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        $module = Module::getInstanceByName($moduleName);

        if (!($module instanceof Module)) {
            return false;
        }

        if (false === Module::isInstalled($moduleName)) {
            return false;
        }

        if (false === $module->active) {
            return false;
        }

        return true;
    }

    /**
     * @param string $moduleName
     * @param string $hookName
     *
     * @return bool
     */
    public function isModuleEnabledAndHookedOn($moduleName, $hookName)
    {
        $module = Module::getInstanceByName($moduleName);

        if (false === $this->isModuleEnabled($moduleName)) {
            return false;
        }

        return $module->isRegisteredInHook($hookName);
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    public function uninstallModule($moduleName)
    {
        if (false === Module::isInstalled($moduleName)) {
            return false;
        }

        $oldModule = Module::getInstanceByName($moduleName);

        if (!($oldModule instanceof Module)) {
            return false;
        }

        if (method_exists($oldModule, 'uninstallTab')) {
            $oldModule->uninstallTab();
        }

        // This closure calls the parent class to prevent data to be erased
        $parentUninstallClosure = function () {
            return parent::uninstall();
        };

        $parentUninstallClosure = $parentUninstallClosure->bindTo($oldModule, get_class($oldModule));

        return $parentUninstallClosure();
    }
}
