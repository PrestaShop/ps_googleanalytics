{**
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
 *}

<div class="panel">
	<div id="google_analytics_top">
		<img src="{$module_dir}views/img/ga_logo.png" class="img-responsive" alt="Google Analytics" />
	</div>
	<hr/>
	<div id="google_analytics_content">
		<div class="row">
			<div class="col-lg-6">
				<p>{l s='Your customers go everywhere; shouldn\'t your analytics.' d='Modules.Googleanalytics.Admin'}</p>
				<p>{l s='Google Analytics shows you the full customer picture across ads and videos, websites and social tools, tables and smartphones. That makes it easier to serve your current customers and win new ones.' d='Modules.Googleanalytics.Admin'}</p>
				<p><b>{l s='With ecommerce functionality in Google Analytics you can gain clear insight into important metrics about shopper behavior and conversion, including:' d='Modules.Googleanalytics.Admin'}</b></p>
				<div class="row google-analytics-advantages-list">
					<div class="col-sm-6"><img src="{$module_dir}views/img/product_detail_icon.png" alt="" />{l s='Product detail views' d='Modules.Googleanalytics.Admin'}</div>
					<div class="col-sm-6"><img src="{$module_dir}views/img/merchandising_tools_icon.png" alt="" />{l s='Internal merchandising Success' d='Modules.Googleanalytics.Admin'}</div>
					<div class="col-sm-6"><img src="{$module_dir}views/img/add_to_cart_icon.png" alt="" />{l s='"Add to cart" actions' d='Modules.Googleanalytics.Admin'}</div>
					<div class="col-sm-6"><img src="{$module_dir}views/img/checkout_icon.png" alt="" />{l s='The checkout process' d='Modules.Googleanalytics.Admin'}</div>
					<div class="col-sm-6"><img src="{$module_dir}views/img/campaign_clicks_icon.png" alt="" />{l s='Internal campaign clicks' d='Modules.Googleanalytics.Admin'}</div>
					<div class="col-sm-6"><img src="{$module_dir}views/img/purchase_icon.png" alt="" />{l s='And purchase' d='Modules.Googleanalytics.Admin'}</div>
				</div>
				<div class="google-analytics-create">
					<a href="https://support.google.com/analytics/answer/1008015" rel="external" class="btn btn-primary">{l s='Create your account to get started' d='Modules.Googleanalytics.Admin'}</a>
				</div>
			</div>
			<div class="col-lg-6 text-center">
					<img src="{$module_dir}views/img/stats.png" class="img-responsive google-analytics-stats" alt="" /><br />
					<span class="small"><em>{l s='Merchants are able to understand how far along users get in the buying process and where they are dropping off.' d='Modules.Googleanalytics.Admin'}</em></span>
			</div>
		</div>
	</div>
</div>
