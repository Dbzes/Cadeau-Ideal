{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

<div class="mr-summary">

  {if $deliveryMode == "MED"}
    <h4 class="mr-summary__title">{l s='Your selected Point Relais® :' mod='mondialrelay'}</h4>
  {elseif $deliveryMode == "24R"}
    <h4 class="mr-summary__title">{l s='Your selected Locker / Point Relais® :' mod='mondialrelay'}</h4>
  {elseif $deliveryMode == "APM"}
    <h4 class="mr-summary__title">{l s='Your selected Locker :' mod='mondialrelay'}</h4>
  {/if}

  <div class="mr-summary__address">
    <div>{$selectedRelay->selected_relay_adr1|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_adr2|escape:'htmlall':'UTF-8'}</div>
    <div>{$selectedRelay->selected_relay_adr3|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_adr4|escape:'htmlall':'UTF-8'}</div>
    <div>{$selectedRelay->selected_relay_postcode|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_city|escape:'htmlall':'UTF-8'}</div>
  </div>

  {if $deliveryMode == "MED"}
    <button id="mondialrelay_change-relay" type="button" class="btn btn-primary mondialrelay_change-relay mr-summary__button">
      <i class="icon-pencil"></i> {l s='Change Point Relais®' mod='mondialrelay'}
    </button>
  {elseif $deliveryMode == "24R"}
    <button id="mondialrelay_change-relay" type="button" class="btn btn-primary mondialrelay_change-relay mr-summary__button">
      <i class="icon-pencil"></i> {l s='Change Locker / Point Relais®' mod='mondialrelay'}
    </button>
  {elseif $deliveryMode == "APM"}
    <button id="mondialrelay_change-relay" type="button" class="btn btn-primary mondialrelay_change-relay mr-summary__button">
      <i class="icon-pencil"></i> {l s='Change Locker' mod='mondialrelay'}
    </button>
  {/if}

</div>

{literal}
<style>
  .mr-summary,
  .mr-summary * {
    text-align: center !important;
  }
  .mr-summary {
    display: block !important;
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 0 !important;
    margin: 0 auto !important;
  }
  .mr-summary__title {
    font-size: 0.6rem !important;
    font-weight: 700 !important;
    color: #004774 !important;
    margin: 0 auto 6px auto !important;
    line-height: 1.25 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
  }
  .mr-summary__address {
    font-size: 0.72rem !important;
    line-height: 1.35 !important;
    color: #333 !important;
    margin: 0 auto 10px auto !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
  }
  .mr-summary__address div {
    padding: 0 !important;
    margin: 0 auto !important;
    text-align: center !important;
  }
  .mr-summary__button {
    display: block !important;
    width: auto !important;
    max-width: 100% !important;
    margin: 0 auto 6px auto !important;
    padding: 6px 10px !important;
    font-size: 0.68rem !important;
    line-height: 1.2 !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    box-sizing: border-box !important;
  }
  .mr-summary__button i {
    font-size: 0.68rem !important;
  }
  @media (max-width: 767px) {
    .mr-summary__title {
      font-size: 0.55rem !important;
    }
    .mr-summary__address {
      font-size: 0.68rem !important;
    }
    .mr-summary__button {
      font-size: 0.62rem !important;
      padding: 5px 8px !important;
    }
  }
</style>
{/literal}
