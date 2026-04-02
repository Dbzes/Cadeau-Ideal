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
  <div class="header-top-bar"></div>
{/block}

{block name='header_nav'}
  <nav class="header-nav">
    <div class="container">
      <div class="row">
        <div class="hidden-md-up text-sm-center mobile">
          <div class="float-xs-left" id="menu-icon">
            <i class="material-icons d-inline">&#xE5D2;</i>
          </div>
          <div class="float-xs-right" id="_mobile_cart"></div>
          <div class="float-xs-right" id="_mobile_user_info"></div>
          <div class="top-logo" id="_mobile_logo"></div>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>
  </nav>
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
              <a class="header-action-item" href="{$urls.pages.my_account}" rel="nofollow">
                <img src="/img/template/icon-account.png" alt="Mon compte" class="header-action-icon" />
                <span class="header-action-text">
                  <strong>Mon compte</strong><br/>
                  {if $customer.is_logged}
                    {$customerName}
                  {else}
                    {l s='Sign in' d='Shop.Theme.Actions'}
                  {/if}
                </span>
              </a>
              <a class="header-action-item" href="{$urls.pages.cart}" rel="nofollow">
                <img src="/img/template/icon-basket.png" alt="Mon panier" class="header-action-icon" />
                <span class="header-action-text">
                  <strong>Mon panier</strong><br/>
                  {$cart.products_count} {if $cart.products_count > 1}produits{else}produit{/if}
                </span>
              </a>
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
{/block}
