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
{block name='header_banner'}
  <div class="container">
    <div class="header-top-bar"></div>
  </div>
{/block}

{block name='header_nav'}
  <nav class="header-nav hidden-md-up">
    <div class="mobile-header-sticky">
      <div class="mobile-header-top">
        <a href="{$urls.base_url}" class="mobile-logo">
          <img src="/img/template/icone-lci-mobile.png" alt="{$shop.name}" />
        </a>
        <div class="mobile-header-icons">
          <a href="{if $customer.is_logged}{$urls.pages.my_account}{else}{$urls.pages.authentication}{/if}" rel="nofollow">
            {if $customer.is_logged}
              <img src="/img/template/icon-account-mobile.png" alt="Mon compte" />
            {else}
              <img src="/img/template/icon-account-disabled.png" alt="Mon compte" />
            {/if}
          </a>
          <a href="{$urls.pages.cart}?action=show" rel="nofollow" class="mobile-cart-link">
            <img src="/img/template/icon-basket-mobile.png" alt="Mon panier" />
            <span class="mobile-cart-badge" {if $cart.products_count <= 0}style="display:none;"{/if}>{$cart.products_count}</span>
          </a>
        </div>
      </div>
      <div class="mobile-header-search">
        {hook h='displayTop'}
      </div>
    </div>
  </nav>
  <nav class="header-nav hidden-sm-down"></nav>
{/block}

{block name='header_top'}
  <div class="header-top">
    <div class="container">
      <div class="row align-items-start">
        <div class="col-md-4 hidden-sm-down" id="_desktop_logo">
          {if $page.page_name == 'index'}
            <h1>
              <a href="{$urls.base_url}">
                <img src="/img/template/le-cadeau-ideal.png" alt="{$shop.name}" class="logo-desktop" />
              </a>
            </h1>
          {else}
            <a href="{$urls.base_url}">
              <img src="/img/template/le-cadeau-ideal.png" alt="{$shop.name}" class="logo-desktop" />
            </a>
          {/if}
        </div>
        <div class="col-md-8 col-sm-12 position-static">
          <div class="header-right">
            <div class="header-right-search">
              {hook h='displayTop'}
            </div>
            <div class="header-right-actions">
              <div class="header-action-block header-action-block--account">
                <div class="header-action-item">
                  <a href="{if $customer.is_logged}{$urls.pages.my_account}{else}{$urls.pages.authentication}{/if}" rel="nofollow">
                    {if $customer.is_logged}
                      <img src="/img/template/icon-account-mobile.png" alt="Mon compte" class="header-action-icon" />
                    {else}
                      <img src="/img/template/icon-account-disabled.png" alt="Mon compte" class="header-action-icon" />
                    {/if}
                  </a>
                  <span class="header-action-text">
                    <a href="{if $customer.is_logged}{$urls.pages.my_account}{else}{$urls.pages.authentication}{/if}" rel="nofollow" style="text-decoration:none;color:inherit;"><strong>Mon compte</strong></a><br/>
                    {if $customer.is_logged}
                      <a href="{$urls.actions.logout}" class="header-action-sub" rel="nofollow"><strong>Déconnexion</strong></a>
                    {else}
                      <a href="{$urls.pages.authentication}" class="header-action-sub" rel="nofollow"><strong>Connexion</strong></a>
                    {/if}
                  </span>
                </div>
              </div>
              <div class="header-action-block header-action-block--cart">
                <a class="header-action-item" href="{$urls.pages.cart}?action=show" rel="nofollow">
                  <img src="/img/template/icon-basket-mobile.png" alt="Mon panier" class="header-action-icon" />
                  <span class="header-action-text">
                    <strong>Mon panier</strong><br/>
                    <strong><span class="header-action-sub" id="header-cart-count">{$cart.products_count} {if $cart.products_count > 1}produits{else}produit{/if}</span></strong>
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div id="mobile_top_menu_wrapper" class="row hidden-md-up" style="display:none;">
        <div class="js-top-menu mobile" id="_mobile_top_menu"></div>
        <div class="js-top-menu-bottom">
          <div id="_mobile_currency_selector"></div>
          <div id="_mobile_language_selector"></div>
          <div id="_mobile_contact_link"></div>
        </div>
      </div>
    </div>
  </div>
  {hook h='displayNavFullWidth'}

  <div class="desktop-sticky-header hidden-sm-down" id="desktop-sticky-header">
    <div class="container">
      <div class="desktop-sticky-inner">
        <a href="{$urls.base_url}" class="desktop-sticky-logo">
          <img src="/img/template/icone-lci-mobile.png" alt="{$shop.name}" />
        </a>
        <div class="desktop-sticky-search" id="desktop-sticky-search"></div>
        <div class="desktop-sticky-icons">
          <a href="{if $customer.is_logged}{$urls.pages.my_account}{else}{$urls.pages.authentication}{/if}" rel="nofollow">
            {if $customer.is_logged}
              <img src="/img/template/icon-account-mobile.png" alt="Mon compte" />
            {else}
              <img src="/img/template/icon-account-disabled.png" alt="Mon compte" />
            {/if}
          </a>
          <a href="{$urls.pages.cart}?action=show" rel="nofollow" class="desktop-sticky-cart">
            <img src="/img/template/icon-basket-mobile.png" alt="Mon panier" />
            <span class="desktop-sticky-badge" {if $cart.products_count <= 0}style="display:none;"{/if}>{$cart.products_count}</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  {literal}
  <style>
    .desktop-sticky-header {
      position: fixed;
      top: -60px;
      left: 0;
      right: 0;
      height: 50px;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.12);
      z-index: 9999;
      transition: top .3s ease;
    }
    .desktop-sticky-header.sticky-visible {
      top: 0;
    }
    .desktop-sticky-inner {
      display: flex;
      align-items: center;
      height: 50px;
      gap: 20px;
    }
    .desktop-sticky-logo img {
      height: 36px;
      width: auto;
    }
    .desktop-sticky-search {
      flex: 1;
    }
    .desktop-sticky-search form {
      display: flex;
      align-items: center;
    }
    .desktop-sticky-search input[type="text"] {
      width: 100%;
      height: 34px;
      padding: 4px 12px;
      border: 1px solid #ccc;
      font-size: 14px;
      outline: none;
    }
    .desktop-sticky-search button {
      height: 34px;
      padding: 0 12px;
      background: #004774;
      color: #fff;
      border: none;
      cursor: pointer;
    }
    .desktop-sticky-icons {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .desktop-sticky-icons img {
      height: auto;
      width: auto;
    }
    .desktop-sticky-cart {
      position: relative;
    }
    .desktop-sticky-badge {
      position: absolute;
      top: -6px;
      right: -8px;
      background: #ee7a03;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      min-width: 16px;
      height: 16px;
      line-height: 16px;
      text-align: center;
      border-radius: 50%;
    }
  </style>
  <script>
  (function(){
    var sticky = document.getElementById('desktop-sticky-header');
    var headerTop = document.querySelector('.header-top');
    if (!sticky || !headerTop) return;

    // Cloner la barre de recherche dans le sticky
    var origSearch = document.querySelector('.header-top .header-right-search');
    var stickySearch = document.getElementById('desktop-sticky-search');
    if (origSearch && stickySearch) {
      var searchClone = origSearch.cloneNode(true);
      stickySearch.appendChild(searchClone);
      // Synchroniser le formulaire cloné pour soumettre correctement
      var cloneForm = stickySearch.querySelector('form');
      var origForm = origSearch.querySelector('form');
      if (cloneForm && origForm) {
        cloneForm.addEventListener('submit', function(e){
          e.preventDefault();
          var input = cloneForm.querySelector('input[type="text"]');
          var origInput = origForm.querySelector('input[type="text"]');
          if (input && origInput) {
            origInput.value = input.value;
            origForm.submit();
          }
        });
      }
    }

    // Afficher/masquer selon le scroll
    var threshold = headerTop.offsetTop + headerTop.offsetHeight;
    var visible = false;
    window.addEventListener('scroll', function(){
      var shouldShow = window.scrollY > threshold;
      if (shouldShow && !visible) {
        sticky.classList.add('sticky-visible');
        visible = true;
      } else if (!shouldShow && visible) {
        sticky.classList.remove('sticky-visible');
        visible = false;
      }
    }, { passive: true });

    // Synchroniser le badge panier du sticky avec le header original
    var stickyBadge = sticky.querySelector('.desktop-sticky-badge');
    function syncCartBadge() {
      var origCount = document.querySelector('#header-cart-count');
      var mobileBadge = document.querySelector('.mobile-cart-badge');
      var count = 0;
      if (origCount) {
        var m = origCount.textContent.match(/(\d+)/);
        if (m) count = parseInt(m[1], 10);
      } else if (mobileBadge) {
        count = parseInt(mobileBadge.textContent, 10) || 0;
      }
      if (stickyBadge) {
        stickyBadge.textContent = count;
        stickyBadge.style.display = count > 0 ? '' : 'none';
      }
    }
    // PrestaShop déclenche un événement sur le body après mise à jour du panier
    if (typeof prestashop !== 'undefined') {
      prestashop.on('updateCart', function(){ setTimeout(syncCartBadge, 500); });
    }
    // Fallback : observer les mutations sur le compteur original
    var origCartCount = document.querySelector('#header-cart-count');
    if (origCartCount && window.MutationObserver) {
      new MutationObserver(syncCartBadge).observe(origCartCount, { childList: true, characterData: true, subtree: true });
    }
  })();
  </script>
  {/literal}
{/block}
