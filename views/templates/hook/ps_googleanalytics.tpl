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

