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

{if $isV4Enabled}
  <script async src="https://www.googletagmanager.com/gtag/js?id={$gaAccountId|escape:'htmlall':'UTF-8'}"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    {literal}function gtag(){dataLayer.push(arguments);}{/literal}
    gtag('js', new Date());
    gtag(
      'config',
      '{$gaAccountId|escape:'htmlall':'UTF-8'}',
      {ldelim}
        'debug_mode':false
        {if $gaAnonymizeEnabled}, 'anonymize_ip': true{/if}
        {if $userId && !$backOffice}, 'user_id': '{$userId|escape:'htmlall':'UTF-8'}'{/if}
        {if $backOffice && !$trackBackOffice}, 'non_interaction': true, 'send_page_view': false{/if}
      {rdelim}
    );
  </script>
{else}
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
    {if $backOffice && !$trackBackOffice}
      ga('set', 'nonInteraction', true);
    {else}
      ga('send', 'pageview');
    {/if}
    ga('require', 'ec');
  </script>
{/if}

