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

<div class="block_newsletter col-lg-8 col-md-12 col-sm-12" id="blockEmailSubscription_{$hookName}">
  <style>
    .block_newsletter .nw-header {
      text-align: center;
      width: 100%;
    }
    .block_newsletter .nw-header img {
      display: block;
      margin: 0 auto 8px;
      width: 100%;
      max-width: max-content;
      height: auto;
    }
    .block_newsletter #block-newsletter-label {
      font-family: 'Bebas Neue', sans-serif !important;
      font-size: 1.6rem;
      letter-spacing: 1px;
      text-align: center;
      width: 100%;
    }
    .block_newsletter .nw-form-inline {
      display: flex;
      gap: 10px;
      align-items: stretch;
    }
    .block_newsletter .nw-form-inline .input-wrapper {
      flex: 1;
    }
    .block_newsletter .nw-form-inline .input-wrapper input {
      width: 100%;
      height: 100%;
    }
    .block_newsletter .nw-btn-subscribe {
      background: #ee7a03 !important;
      border-color: #ee7a03 !important;
      color: #fff !important;
      border-radius: 0 !important;
      white-space: nowrap;
      padding: 8px 20px;
      text-transform: uppercase;
      font-weight: 700;
    }
    .block_newsletter .nw-conditions {
      text-align: center;
    }
    @media (max-width: 767px) {
      .block_newsletter .nw-form-inline {
        flex-direction: column;
      }
      .block_newsletter .nw-btn-subscribe {
        width: 100%;
      }
    }
  </style>
  <div class="row">
    <div class="col-xs-12 nw-header">
      <img src="/img/template/header-newsletters.png" alt="Newsletter" />
      <p id="block-newsletter-label">Inscription à la newsletter</p>
    </div>
    <div class="col-md-12 col-xs-12">
      <form action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
        <div class="row">
          <div class="col-xs-12">
            <div class="nw-form-inline">
              <div class="input-wrapper">
                <input
                  name="email"
                  type="email"
                  value="{$value}"
                  placeholder="{l s='Your email address' d='Shop.Forms.Labels'}"
                  aria-labelledby="block-newsletter-label"
                  required
                >
              </div>
              <input
                class="btn nw-btn-subscribe"
                name="submitNewsletter"
                type="submit"
                value="S'abonner"
              >
            </div>
            <input type="hidden" name="blockHookName" value="{$hookName}" />
            <input type="hidden" name="action" value="0">
          </div>
          <div class="col-xs-12 nw-conditions">
              {if $conditions}
                <p>{$conditions}</p>
              {/if}
              {if $msg}
                <p class="alert {if $nw_error}alert-danger{else}alert-success{/if}">
                  {$msg}
                </p>
              {/if}
              {hook h='displayNewsletterRegistration'}
              {if isset($id_module)}
                {hook h='displayGDPRConsent' id_module=$id_module}
              {/if}
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
