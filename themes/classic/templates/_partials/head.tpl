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
    .header-action-block {
      flex-shrink: 0;
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
    .header-action-sub {
      color: #004774;
    }
    body {
      overflow-x: hidden;
    }
    .product-add-to-cart .product-minimal-quantity,
    .product-add-to-cart .js-mailalert,
    #product-availability,
    .js-mailalert,
    .js-mailalert form,
    .js-mailalert input {
      max-width: 100%;
      box-sizing: border-box;
    }
    .js-mailalert input[type="email"] {
      width: 100% !important;
      min-width: 0 !important;
    }
    .js-mailalert button,
    button.js-mailalert-submit,
    .product-add-to-cart button {
      max-width: 100%;
      white-space: normal !important;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    @media (max-width: 767px) {
      .header-top-bar {
        display: none;
      }
      .header-top {
        display: none !important;
      }
      .mobile-header-sticky {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      .header-nav.hidden-md-up {
        margin-bottom: 110px;
      }
      .mobile-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
      }
      .mobile-logo img {
        height: 45px;
        width: auto;
      }
      .mobile-header-icons {
        display: flex;
        gap: 12px;
        align-items: center;
      }
      .mobile-header-icons img {
        width: 38px;
        height: 38px;
      }
      .mobile-cart-link {
        position: relative;
        display: inline-block;
      }
      .mobile-cart-badge {
        position: absolute;
        top: -4px;
        right: -6px;
        background: #e74c3c;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 3px rgba(0,0,0,.3);
      }
      .mobile-header-search {
        padding: 0 15px 5px;
      }
      .mobile-header-search #search_widget {
        float: none !important;
        min-width: 100% !important;
        width: 100% !important;
      }
    }
    /* Panier mobile : qty touchspin inline forcé */
    @media (max-width: 767px) {
      .product-line-grid-right .qty {
        white-space: nowrap;
        padding-right: 0;
      }
      .product-line-grid-right .qty .bootstrap-touchspin {
        display: inline-flex !important;
        align-items: center;
        flex-wrap: nowrap;
        width: auto;
      }
      .product-line-grid-right .qty .bootstrap-touchspin input.js-cart-line-product-quantity {
        width: 42px !important;
        min-width: 42px;
        flex: 0 0 42px;
      }
      .product-line-grid-right .qty .bootstrap-touchspin .input-group-btn-vertical {
        display: table-cell !important;
        flex-shrink: 0;
      }
    }
    /* Panier : cadre uniforme pour les vignettes produit */
    .cart-item .product-line-grid-left .product-image,
    .product-line-grid .product-line-grid-left .product-image {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      aspect-ratio: 1 / 1;
      border: 1px solid #ee7a03;
      overflow: hidden;
      background: #fff;
      padding: 4px;
      box-sizing: border-box;
    }
    .cart-item .product-line-grid-left .product-image img,
    .cart-item .product-line-grid-left .product-image picture,
    .cart-item .product-line-grid-left .product-image picture img,
    .product-line-grid .product-line-grid-left .product-image img,
    .product-line-grid .product-line-grid-left .product-image picture,
    .product-line-grid .product-line-grid-left .product-image picture img {
      max-width: 100% !important;
      max-height: 100% !important;
      width: auto !important;
      height: auto !important;
      object-fit: contain !important;
    }
    /* Modal aperçu de la création (simple, centré) */
    .mpe-preview-modal {
      position: fixed;
      inset: 0;
      z-index: 99999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .mpe-preview-modal.mpe-open {
      display: flex !important;
    }
    .mpe-preview-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,.9);
    }
    .mpe-preview-img {
      position: relative;
      max-width: 92vw;
      max-height: 90vh;
      width: auto;
      height: auto;
      box-shadow: 0 0 40px rgba(0,0,0,.5);
    }
    .mpe-preview-close {
      position: absolute;
      top: 15px;
      right: 20px;
      background: transparent;
      border: none;
      color: #fff;
      font-size: 40px;
      line-height: 1;
      cursor: pointer;
      z-index: 2;
      padding: 0;
    }
    @media (max-width: 767px) {
      .mpe-preview-modal { padding: 0; }
      .mpe-preview-img {
        width: 100vw !important;
        max-width: 100vw;
        height: auto;
        max-height: 100vh;
      }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Modal aperçu de la création (simple, delegated pour survivre aux refreshs ajax)
      document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.mpe-preview-trigger');
        if (trigger) {
          e.preventDefault();
          var sel = trigger.getAttribute('data-target');
          var modal = document.querySelector(sel);
          if (modal) modal.classList.add('mpe-open');
          return;
        }
        if (e.target.closest('.mpe-preview-close') || e.target.classList.contains('mpe-preview-backdrop')) {
          var open = document.querySelector('.mpe-preview-modal.mpe-open');
          if (open) open.classList.remove('mpe-open');
        }
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          var open = document.querySelector('.mpe-preview-modal.mpe-open');
          if (open) open.classList.remove('mpe-open');
        }
      });

      if (typeof prestashop !== 'undefined') {
        prestashop.on('updateCart', function(e) {
          if (e && e.resp && e.resp.cart) {
            var count = e.resp.cart.products_count;
            var text = count + ' ' + (count > 1 ? 'produits' : 'produit');
            var el = document.getElementById('header-cart-count');
            if (el) el.textContent = text;
            var badge = document.querySelector('.mobile-cart-badge');
            if (badge) {
              badge.textContent = count;
              badge.style.display = count > 0 ? '' : 'none';
            }
          }
        });
      }
    });
  </script>
{/block}