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

{include file='_partials/helpers.tpl'}

<!doctype html>
<html lang="{$language.locale}">

  <head>
    {block name='head'}
      {include file='_partials/head.tpl'}
    {/block}
  </head>

  <body id="{$page.page_name}" class="{$page.body_classes|classnames}">
    <style>.all-product-link { color: #004774 !important; font-weight: 700 !important; }</style>

    {block name='hook_after_body_opening_tag'}
      {hook h='displayAfterBodyOpeningTag'}
    {/block}

    <main>
      {block name='product_activation'}
        {include file='catalog/_partials/product-activation.tpl'}
      {/block}

      <header id="header">
        {block name='header'}
          {include file='_partials/header.tpl'}
        {/block}
      </header>

      <section id="wrapper">
        {block name='notifications'}
          {include file='_partials/notifications.tpl'}
        {/block}

        {hook h="displayWrapperTop"}
        <div class="container">
          {block name='breadcrumb'}
            {include file='_partials/breadcrumb.tpl'}
          {/block}

          <div class="row">
            {block name="left_column"}
              <div id="left-column" class="col-xs-12 col-md-4 col-lg-3">
                {if $page.page_name == 'product'}
                  {hook h='displayLeftColumnProduct' product=$product category=$category}
                {else}
                  {hook h="displayLeftColumn"}
                {/if}
              </div>
            {/block}

            {block name="content_wrapper"}
              <div id="content-wrapper" class="js-content-wrapper left-column right-column col-md-4 col-lg-3">
                {hook h="displayContentWrapperTop"}
                {block name="content"}
                  <p>Hello world! This is HTML5 Boilerplate.</p>
                {/block}
                {hook h="displayContentWrapperBottom"}
              </div>
            {/block}

            {block name="right_column"}
              <div id="right-column" class="col-xs-12 col-md-4 col-lg-3">
                {if $page.page_name == 'product'}
                  {hook h='displayRightColumnProduct'}
                {else}
                  {hook h="displayRightColumn"}
                {/if}
              </div>
            {/block}
          </div>
        </div>
        {hook h="displayWrapperBottom"}
      </section>

      <footer id="footer" class="js-footer">
        {block name="footer"}
          {include file="_partials/footer.tpl"}
        {/block}
      </footer>

    </main>

    {block name='javascript_bottom'}
      {include file="_partials/password-policy-template.tpl"}
      {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}

    {block name='hook_before_body_closing_tag'}
      {hook h='displayBeforeBodyClosingTag'}
    {/block}

    <div id="cookie-consent-banner" style="display:none;">
      <div class="cookie-consent-inner">
        <div class="cookie-consent-text">
          <strong>Nous respectons votre vie privée</strong>
          <p>Ce site utilise des cookies pour améliorer votre expérience de navigation, analyser le trafic et personnaliser le contenu. En cliquant sur « Tout accepter », vous consentez à l'utilisation de tous les cookies. Vous pouvez également choisir de les refuser. <a href="/content/3-conditions-generales-de-vente" class="cookie-consent-link">En savoir plus</a></p>
        </div>
        <div class="cookie-consent-actions">
          <button type="button" id="cookie-accept-all" class="cookie-btn cookie-btn-accept">TOUT ACCEPTER</button>
          <button type="button" id="cookie-refuse" class="cookie-btn cookie-btn-refuse">REFUSER</button>
        </div>
      </div>
    </div>

    {literal}
    <style>
      #cookie-consent-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        box-shadow: 0 -4px 20px rgba(0,0,0,.15);
        z-index: 99999;
        padding: 20px;
        border-top: 3px solid #ee7a03;
      }
      .cookie-consent-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 30px;
      }
      .cookie-consent-text {
        flex: 1;
      }
      .cookie-consent-text strong {
        font-size: 16px;
        color: #004774;
        display: block;
        margin-bottom: 6px;
      }
      .cookie-consent-text p {
        font-size: 13px;
        color: #555;
        line-height: 1.5;
        margin: 0 0 4px;
      }
      .cookie-consent-link {
        font-size: 12px;
        color: #004774;
        font-weight: 700;
        text-decoration: underline;
      }
      .cookie-consent-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
      }
      .cookie-btn {
        padding: 12px 24px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
      }
      .cookie-btn-accept {
        background: #ee7a03;
        color: #fff;
      }
      .cookie-btn-accept:hover {
        background: #d96d00;
      }
      .cookie-btn-refuse {
        background: #fff;
        color: #004774;
        border: 2px solid #004774;
      }
      .cookie-btn-refuse:hover {
        background: #004774;
        color: #fff;
      }
      @media (max-width: 767px) {
        .cookie-consent-inner {
          flex-direction: column;
          gap: 15px;
          text-align: center;
        }
        .cookie-consent-actions {
          width: 100%;
          justify-content: center;
        }
        .cookie-btn {
          padding: 10px 18px;
          font-size: 13px;
        }
      }
    </style>
    <script>
    (function(){
      function getCookie(name) {
        var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return v ? v.pop() : null;
      }
      function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
      }

      var consent = getCookie('cookie_consent');
      if (consent) return; // Déjà répondu, ne pas afficher

      var banner = document.getElementById('cookie-consent-banner');
      if (!banner) return;
      banner.style.display = 'block';

      document.getElementById('cookie-accept-all').addEventListener('click', function(){
        setCookie('cookie_consent', 'accepted', 365);
        banner.style.display = 'none';
      });
      document.getElementById('cookie-refuse').addEventListener('click', function(){
        setCookie('cookie_consent', 'refused', 365);
        banner.style.display = 'none';
      });
    })();
    </script>
    {/literal}
  </body>

</html>
