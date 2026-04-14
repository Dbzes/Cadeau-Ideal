{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Your addresses' d='Shop.Theme.Customeraccount'}
{/block}

{block name='page_content'}
  <style>
    /* Encart adresse : bordure orange */
    .page-addresses .address {
      border: 1px solid #ee7a03 !important;
    }
    /* Mettre à jour et Supprimer en bleu gras */
    .page-addresses .address-footer a {
      color: #004774 !important;
      font-weight: 700 !important;
    }
    .page-addresses .address-footer a i {
      color: #004774 !important;
    }
    /* Créer une nouvelle adresse en bouton bleu */
    .page-addresses .addresses-footer a {
      display: inline-block;
      background-color: #004774 !important;
      color: #fff !important;
      padding: 10px 20px;
      text-decoration: none !important;
      font-weight: 700;
      border: none;
    }
    .page-addresses .addresses-footer a:hover {
      background-color: #003359 !important;
    }
    .page-addresses .addresses-footer a i,
    .page-addresses .addresses-footer a span {
      color: #fff !important;
    }
  </style>
  {if $customer.addresses}
    {foreach $customer.addresses as $address}
      <div class="col-lg-4 col-md-6 col-sm-6">
      {block name='customer_address'}
        {include file='customer/_partials/block-address.tpl' address=$address}
      {/block}
      </div>
    {/foreach}
  {else}
    <div class="alert alert-info" role="alert" data-alert="info">
      {l s='No addresses are available.' d='Shop.Notifications.Success'} <a href="{$urls.pages.address}" title="{l s='Add a new address' d='Shop.Theme.Actions'}">{l s='Add a new address' d='Shop.Theme.Actions'}</a>
    </div>
  {/if}
  <div class="clearfix"></div>
  <div class="addresses-footer">
    <a href="{$urls.pages.address}" data-link-action="add-address">
      <i class="material-icons">&#xE145;</i>
      <span>{l s='Create new address' d='Shop.Theme.Actions'}</span>
    </a>
  </div>
{/block}
