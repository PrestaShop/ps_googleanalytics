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

<div class="panel">
	<div class="row" id="google_analytics_top">
		<div class="col-lg-6">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/ga_logo.png" alt="Google Analytics" />
		</div>
		<div class="col-lg-6 text-right">
			<a href="https://support.google.com/analytics/answer/1008015" rel="external"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/create_account_btn.png" alt="" /></a>
		</div>
	</div>
	<hr/>
	<div id="google_analytics_content">
		<div class="row">
			<div class="col-lg-6">
				<p>
					{l s='Your customers go everywhere; shouldn\'t your analytics.' mod='ganalytics'}
				</p><p>
					{l s='Google Analytics shows you the full customer picture across ads and videos, websites and social tools, tables and smartphones. That makes it easier to serve your current customers and win new ones.' mod='ganalytics'}
				</p>
				<p><b>{l s='With ecommerce functionality in Google Analytics you can gain clear insight into important metrics about shopper behavior and conversion, including:' mod='ganalytics'}</b></p>
				<div id="advantages_list">
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/product_detail_icon.png" alt="" />{l s='Product detail views' mod='ganalytics'}</div>
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/merchandising_tools_icon.png" alt="" />{l s='Internal merchandising Success' mod='ganalytics'}</div>
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/add_to_cart_icon.png" alt="" />{l s='"Add to cart" actions' mod='ganalytics'}</div>
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/checkout_icon.png" alt="" />{l s='The checkout process' mod='ganalytics'}</div>
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/campaign_clicks_icon.png" alt="" />{l s='Internal campaign clicks' mod='ganalytics'}</div>
					<div class="col-xs-6"><img src="{$module_dir|escape:'html':'UTF-8'}views/img/purchase_icon.png" alt="" />{l s='And purchase' mod='ganalytics'}</div>
				</div>
			</div>
			<div class="col-lg-6 text-center">
				<p>
					<img src="{$module_dir|escape:'html':'UTF-8'}views/img/stats.png" alt="" /><br />
					<span class="small"><em>{l s='Merchants are able to understand how far along users get in the buying process and where they are dropping off.' mod='ganalytics'}</em></span>
				</p>
				<p class="text-right">
					<b><a href="https://support.google.com/analytics/answer/1008015" rel="external">{l s='Create your account to get started.' mod='ganalytics'}</a></b>
				</p>
			</div>
		</div>
	</div>
</div>
