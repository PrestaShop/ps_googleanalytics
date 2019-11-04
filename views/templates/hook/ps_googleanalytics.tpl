{*
 * 2007-2017 PrestaShop
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
 *  @copyright  2007-2017 PrestaShop SA
 *  @version  Release: $Revision:7040 $
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *}

{literal}
<script type="text/javascript">
	(window.gaDevIds=window.gaDevIds||[]).push('d6YPbH');
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
{/literal}
    {if $gaCrossdomainEnabled}
        ga('create', '{$gaAccountId|escape:'htmlall':'UTF-8'}', 'auto', {literal}{'allowLinker': true}{/literal});
        ga('require', 'linker');
        ga('linker:autoLink', [
        {foreach from=$shops item=shop}
            {if $shop.id_shop != $currentShopId}
            {if $useSecureMode}'{$shop.domain_ssl|escape:'htmlall':'UTF-8'}'{else}'{$shop.domain|escape:'htmlall':'UTF-8'}'{/if},
            {/if}
        {/foreach}
        ]);
    {else}
        ga('create', '{$gaAccountId|escape:'htmlall':'UTF-8'}', 'auto');
    {/if}
    {if $userId && !$backOffice}
        ga('set', 'userId', '{$userId|escape:'htmlall':'UTF-8'}');
    {/if}
    {if $gaAnonymizeEnabled}
        ga('set', 'anonymizeIp', true);
    {/if}
    {if $backOffice}
        ga('set', 'nonInteraction', true);
    {else}
        ga('send', 'pageview');
    {/if}
{literal}
    ga('require', 'ec');
</script>
{/literal}
