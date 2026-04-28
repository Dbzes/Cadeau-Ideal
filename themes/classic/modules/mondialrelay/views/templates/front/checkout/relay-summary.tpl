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
  .mr-summary {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 0;
    margin: 0;
  }
  .mr-summary__title {
    font-size: 0.72rem !important;
    font-weight: 700;
    color: #004774;
    margin: 0 0 6px 0;
    line-height: 1.25;
    text-align: center;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  .mr-summary__address {
    font-size: 0.72rem !important;
    line-height: 1.35;
    color: #333;
    margin: 0 0 10px 0;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  .mr-summary__address div {
    padding: 0;
    margin: 0;
  }
  .mr-summary__button {
    display: inline-block;
    width: 100%;
    max-width: 100%;
    padding: 6px 10px !important;
    font-size: 0.68rem !important;
    line-height: 1.2;
    white-space: normal;
    word-wrap: break-word;
    box-sizing: border-box;
  }
  .mr-summary__button i {
    font-size: 0.68rem !important;
  }
  @media (min-width: 768px) {
    .mr-summary__button {
      width: auto;
      max-width: 100%;
    }
  }
  @media (max-width: 767px) {
    .mr-summary {
      text-align: center;
    }
    .mr-summary__title {
      font-size: 0.65rem !important;
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
