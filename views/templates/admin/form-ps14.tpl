{*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @version  Release: $Revision: 7040 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<form enctype="multipart/form-data" method="post" class="defaultForm ganalytics" id="configuration_form">
	<fieldset id="fieldset_0">
		<legend>
			Paramètres
		</legend>

		<label>Google Analytics Tracking ID </label>							

		<div class="margin-form">
			<input type="text" size="20" class="" value="{$account_id|escape:'htmlall':'UTF-8'}" id="GA_ACCOUNT_ID" name="GA_ACCOUNT_ID">&nbsp;<sup>*</sup>
			<span name="help_box" class="hint" style="display: none;">This information is available in your Google Analytics account<span class="hint-pointer"></span></span>  
		</div>
		<div class="clear"></div>

		<div class="margin-form">
			<input class="button" type="submit" name="submitganalytics" value="{l s='Save' mod='ganalytics'}" id="configuration_form_submit_btn">
		</div>

		<div class="small"><sup>*</sup> Champ requis</div>
	</fieldset>
</form>
