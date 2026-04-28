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
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <style>
    #checkout-delivery-step button.continue,
    #checkout-delivery-step .btn-primary.continue {
      background-color: #004774 !important;
      border-color: #004774 !important;
      color: #fff !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      border-radius: 0 !important;
      padding: 10px 20px !important;
    }
    #checkout-delivery-step button.continue:hover,
    #checkout-delivery-step .btn-primary.continue:hover {
      background-color: #003359 !important;
      border-color: #003359 !important;
    }
    /* === Mondial Relay : centrage horizontal du bloc summary + bouton "Utiliser ce locker" === */
    /* Wrapper du summary (col-md-12 clearfix > pull-left) : annuler le float et centrer */
    #mondialrelay_summary,
    #mondialrelay_summary .clearfix,
    #mondialrelay_summary .col-md-12 {
      text-align: center !important;
      width: 100% !important;
    }
    #mondialrelay_summary .pull-left {
      float: none !important;
      width: 100% !important;
      display: block !important;
      text-align: center !important;
    }
    /* Délai estimé de livraison en orange (tous transporteurs, y compris Mondial Relay) */
    /* Sélecteurs très spécifiques pour gagner contre body#checkout & label */
    body#checkout span.carrier-delay,
    body#checkout section.checkout-step span.carrier-delay,
    body#checkout .delivery-option span.carrier-delay,
    body#checkout #checkout-delivery-step span.carrier-delay,
    body#checkout label .carrier-delay {
      color: #ee7a03 !important;
      font-weight: 600 !important;
    }
    /* Date estimée de livraison sous le délai */
    .carrier-delay-estimate {
      display: block;
      font-size: 0.78rem;
      color: #ee7a03;
      font-weight: 500;
      margin-top: 3px;
      line-height: 1.25;
    }
    @media (max-width: 767px) {
      .carrier-delay-estimate {
        font-size: 0.72rem;
      }
    }
    /* Titre h4 "Locker / Point Relais sélectionné" : taille réduite + centré */
    #mondialrelay_summary h4 {
      font-size: 0.9rem !important;
      font-weight: 700 !important;
      color: #004774 !important;
      text-align: center !important;
      margin: 0 auto 6px auto !important;
      line-height: 1.25 !important;
    }
    /* Adresse */
    #mondialrelay_summary .col-md-12 > .col-md-12,
    #mondialrelay_summary > .clearfix > .pull-left > .col-md-12 {
      font-size: 0.92rem !important;
      text-align: center !important;
      padding: 0 !important;
      margin: 0 auto !important;
    }
    /* Override du justify-self: end inline sur le wrapper du bouton "Utiliser ce locker" */
    #mondialrelay_save-container {
      justify-self: center !important;
      text-align: center !important;
      width: 100% !important;
    }
    /* Boutons mondialrelay : "Changer" + "Utiliser ce locker" — vrais sélecteurs */
    #mondialrelay_summary .mondialrelay_change-relay,
    .mondialrelay_save-relay,
    #checkout-delivery-step [class*="mondialrelay"] button,
    #checkout-delivery-step [class*="mondialrelay"] .btn,
    .carrier-extra-content button[class*="mondialrelay"],
    .carrier-extra-content .btn[class*="mondialrelay"] {
      font-size: 0.9rem !important;
      padding: 8px 14px !important;
      line-height: 1.2 !important;
      white-space: normal !important;
      word-wrap: break-word !important;
      display: inline-block !important;
      margin: 4px auto !important;
      float: none !important;
    }
    @media (max-width: 767px) {
      #mondialrelay_summary h4 {
        font-size: 0.82rem !important;
      }
      #mondialrelay_summary .col-md-12 > .col-md-12,
      #mondialrelay_summary > .clearfix > .pull-left > .col-md-12 {
        font-size: 0.88rem !important;
      }
      #mondialrelay_summary .mondialrelay_change-relay,
      .mondialrelay_save-relay,
      #checkout-delivery-step [class*="mondialrelay"] button,
      #checkout-delivery-step [class*="mondialrelay"] .btn,
      .carrier-extra-content button[class*="mondialrelay"],
      .carrier-extra-content .btn[class*="mondialrelay"] {
        font-size: 0.82rem !important;
        padding: 7px 12px !important;
      }
    }
  </style>
  {literal}
  <script>
    (function () {
      function formatFr(date) {
        return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
      }
      function addBusinessDays(date, days) {
        var d = new Date(date.getTime());
        var added = 0;
        while (added < days) {
          d.setDate(d.getDate() + 1);
          var dow = d.getDay();
          if (dow !== 0 && dow !== 6) added++;
        }
        return d;
      }
      function injectEstimates() {
        var nodes = document.querySelectorAll('.carrier-delay');
        nodes.forEach(function (el) {
          if (el.dataset.estimateDone === '1') return;
          var txt = el.textContent || '';
          var rangeMatch = txt.match(/(\d+)\s*(?:à|-|à|et)\s*(\d+)\s*jour/i);
          var singleMatch = !rangeMatch ? txt.match(/(\d+)\s*jour/i) : null;
          if (!rangeMatch && !singleMatch) return;
          var minDays, maxDays;
          if (rangeMatch) { minDays = parseInt(rangeMatch[1], 10); maxDays = parseInt(rangeMatch[2], 10); }
          else { minDays = parseInt(singleMatch[1], 10); maxDays = minDays; }
          var today = new Date();
          var minDate = addBusinessDays(today, minDays);
          var maxDate = addBusinessDays(today, maxDays);
          var estimate = document.createElement('span');
          estimate.className = 'carrier-delay-estimate';
          estimate.textContent = (minDays === maxDays)
            ? 'Livraison estimée ' + formatFr(minDate)
            : 'Entre ' + formatFr(minDate) + ' et ' + formatFr(maxDate);
          el.parentNode.insertBefore(estimate, el.nextSibling);
          el.dataset.estimateDone = '1';
        });
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectEstimates);
      } else {
        injectEstimates();
      }
      if (typeof prestashop !== 'undefined' && prestashop.on) {
        prestashop.on('updatedDeliveryForm', injectEstimates);
        prestashop.on('updatedDeliveryOptions', injectEstimates);
      }
    })();
  </script>
  {/literal}
  <div id="hook-display-before-carrier">
    {$hookDisplayBeforeCarrier nofilter}
  </div>

  <div class="delivery-options-list">
    {if $delivery_options|count}
      <form
        class="clearfix"
        id="js-delivery"
        data-url-update="{url entity='order' params=['ajax' => 1, 'action' => 'selectDeliveryOption']}"
        method="post"
      >
        <div class="form-fields">
          {block name='delivery_options'}
            <div class="delivery-options">
              {foreach from=$delivery_options item=carrier key=carrier_id}
                  <div class="delivery-option js-delivery-option">
                    <div class="col-sm-1">
                      <span class="custom-radio float-xs-left">
                        <input type="radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}" value="{$carrier_id}"{if $delivery_option == $carrier_id} checked{/if}>
                        <span></span>
                      </span>
                    </div>
                    <label for="delivery_option_{$carrier.id}" class="col-xs-9 col-sm-11 delivery-option-2">
                      <div class="row">
                        <div class="col-sm-5 col-xs-12">
                          <div class="row carrier{if $carrier.logo} carrier-hasLogo{/if}">
                            {if $carrier.logo}
                            <div class="col-xs-12 col-md-4 carrier-logo">
                                <img src="{$carrier.logo}" alt="{$carrier.name}" loading="lazy" />
                            </div>
                            {/if}
                            <div class="col-xs-12 carriere-name-container{if $carrier.logo} col-md-8{/if}">
                              <span class="h6 carrier-name">{$carrier.name}</span>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-4 col-xs-12">
                          <span class="carrier-delay">{$carrier.delay}</span>
                        </div>
                        <div class="col-sm-3 col-xs-12">
                          <span class="carrier-price">{$carrier.price}</span>
                        </div>
                      </div>
                    </label>
                  </div>
                  <div class="carrier-extra-content js-carrier-extra-content"{if ($delivery_option != $carrier_id) || ($delivery_option == $carrier_id && empty($carrier.extraContent))} style="display:none;"{/if}>
                    {$carrier.extraContent nofilter}
                  </div>
                  <div class="clearfix"></div>
              {/foreach}
            </div>
          {/block}
          <div class="order-options">
            <div id="delivery">
              <label for="delivery_message">{l s='If you would like to add a comment about your order, please write it in the field below.' d='Shop.Theme.Checkout'}</label>
              <textarea rows="2" cols="120" id="delivery_message" name="delivery_message">{$delivery_message}</textarea>
            </div>

            {if $recyclablePackAllowed}
              <span class="custom-checkbox">
                <input type="checkbox" id="input_recyclable" name="recyclable" value="1" {if $recyclable} checked {/if}>
                <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                <label for="input_recyclable">{l s='I would like to receive my order in recycled packaging.' d='Shop.Theme.Checkout'}</label>
              </span>
            {/if}

            {if $gift.allowed}
              <span class="custom-checkbox">
                <input class="js-gift-checkbox" id="input_gift" name="gift" type="checkbox" value="1" {if $gift.isGift}checked="checked"{/if}>
                <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                <label for="input_gift">{$gift.label}</label >
              </span>

              <div id="gift" class="collapse{if $gift.isGift} in{/if}">
                <label for="gift_message">{l s='If you\'d like, you can add a note to the gift:' d='Shop.Theme.Checkout'}</label>
                <textarea rows="2" cols="120" id="gift_message" name="gift_message">{$gift.message}</textarea>
              </div>
            {/if}

          </div>
        </div>
        <button type="submit" class="continue btn btn-primary float-xs-right" name="confirmDeliveryOption" value="1">
          {l s='Continue' d='Shop.Theme.Actions'}
        </button>
      </form>
    {else}
      <p class="alert alert-danger">{l s='Unfortunately, there are no carriers available for your delivery address.' d='Shop.Theme.Checkout'}</p>
    {/if}
  </div>

  <div id="hook-display-after-carrier">
    {$hookDisplayAfterCarrier nofilter}
  </div>

  <div id="extra_carrier"></div>
{/block}
