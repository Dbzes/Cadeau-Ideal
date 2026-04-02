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
{block name='head_charset'}
  <meta charset="utf-8">
{/block}
{block name='head_ie_compatibility'}
  <meta http-equiv="x-ua-compatible" content="ie=edge">
{/block}

{block name='head_seo'}
  <title>{block name='head_seo_title'}{$page.meta.title}{/block}</title>
  {block name='hook_after_title_tag'}
    {hook h='displayAfterTitleTag'}
  {/block}
  <meta name="description" content="{block name='head_seo_description'}{$page.meta.description}{/block}">
  <meta name="keywords" content="{block name='head_seo_keywords'}{$page.meta.keywords}{/block}">
  {if $page.meta.robots !== 'index'}
    <meta name="robots" content="{$page.meta.robots}">
  {/if}
  {if $page.canonical}
    <link rel="canonical" href="{$page.canonical}">
  {/if}
  {block name='head_hreflang'}
    {foreach from=$urls.alternative_langs item=pageUrl key=code}
      <link rel="alternate" href="{$pageUrl}" hreflang="{$code}">
    {/foreach}
  {/block}
  
  {block name='head_microdata'}
    {include file="_partials/microdata/head-jsonld.tpl"}
  {/block}
  
  {block name='head_microdata_special'}{/block}
  
  {block name='head_pagination_seo'}
    {include file="_partials/pagination-seo.tpl"}
  {/block}

  {block name='head_open_graph'}
    <meta property="og:title" content="{$page.meta.title}" />
    <meta property="og:description" content="{$page.meta.description}" />
    <meta property="og:url" content="{$urls.current_url}" />
    <meta property="og:site_name" content="{$shop.name}" />
    {if !isset($product) && $page.page_name != 'product'}<meta property="og:type" content="website" />{/if}
  {/block}  
{/block}

{block name='head_viewport'}
  <meta name="viewport" content="width=device-width, initial-scale=1">
{/block}

{block name='head_icons'}
  <link rel="icon" type="image/vnd.microsoft.icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
  <link rel="shortcut icon" type="image/x-icon" href="{$shop.favicon}?{$shop.favicon_update_time}">
{/block}

{block name='stylesheets'}
  {include file="_partials/stylesheets.tpl" stylesheets=$stylesheets}
{/block}

{block name='javascript_head'}
  {include file="_partials/javascript.tpl" javascript=$javascript.head vars=$js_custom_vars}
{/block}

{block name='hook_header'}
  {$HOOK_HEADER nofilter}
{/block}

{block name='hook_extra'}{/block}

{block name='custom_css'}
  <style>
    .header-top-bar {
      width: 100%;
      height: 18px;
      background-color: #1a3c6e;
    }
    .logo-desktop {
      width: 420px;
      height: auto;
      display: block;
    }
    .header-top .row {
      align-items: flex-start;
    }
    .header-right {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: -110px;
    }
    .header-right-search {
      width: 70%;
      margin-left: auto;
    }
    #search_widget {
      min-width: 100% !important;
      width: 100% !important;
    }
    .header-right-actions {
      display: flex;
      justify-content: flex-end;
      gap: 40px;
      margin-right: 10px;
    }
    .header-action-item {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #333;
    }
    .header-action-item:hover {
      text-decoration: none;
      color: #ee7a03;
    }
    .header-action-icon {
      width: 48px;
      height: 48px;
    }
    .header-action-text {
      font-family: 'Open Sans', sans-serif;
      font-size: 0.85rem;
      line-height: 1.4;
      color: #333;
    }
    .header-action-text strong {
      font-weight: 600;
      color: #ee7a03;
    }
    @media (max-width: 767px) {
      .header-top-bar {
        display: none;
      }
      .header-top .container {
        padding: 0 15px;
      }
      .header-top .col-md-8 {
        padding: 0 15px !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        box-sizing: border-box;
      }
      .header-right {
        margin-top: 0;
      }
      .header-right-search {
        width: 100% !important;
        margin-left: 0;
        padding: 0;
        box-sizing: border-box;
      }
      #search_widget {
        float: none !important;
        min-width: 100% !important;
        width: 100% !important;
      }
      .header-right-actions {
        justify-content: flex-start;
        margin-right: 0;
        gap: 30%;
        padding: 0;
      }
      .logo-desktop {
        width: 280px;
      }
      .header-action-icon {
        width: 36px;
        height: 36px;
      }
    }
  </style>
{/block}