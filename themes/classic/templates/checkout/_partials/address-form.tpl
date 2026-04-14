{extends file='customer/_partials/address-form.tpl'}

{block name='form_field'}
  {if $field.name eq "alias" and $customer.is_guest}
    {* we don't ask for alias here if customer is not registered *}
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="address_form_url"}
    <style>
      .js-address-form .form-control,
      .js-address-form input[type="text"],
      .js-address-form input[type="email"],
      .js-address-form input[type="tel"],
      .js-address-form input[type="password"],
      .js-address-form textarea,
      .js-address-form select {
        border: 1px solid #ee7a03 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
      }
      .js-address-form .form-control:focus,
      .js-address-form input:focus,
      .js-address-form textarea:focus,
      .js-address-form select:focus {
        border-color: #004774 !important;
        outline: none !important;
      }
      .js-address-form .js-cancel-address,
      .js-address-form .cancel-address {
        display: inline-block;
        background-color: #004774 !important;
        color: #fff !important;
        padding: 10px 20px !important;
        text-decoration: none !important;
        font-weight: 700 !important;
        border: none !important;
        border-radius: 0 !important;
        margin-right: 8px;
      }
      .js-address-form .js-cancel-address:hover,
      .js-address-form .cancel-address:hover {
        background-color: #003359 !important;
        color: #fff !important;
      }
      .js-address-form .btn-primary {
        background-color: #004774 !important;
        border-color: #004774 !important;
        color: #fff !important;
        font-weight: 700 !important;
        border-radius: 0 !important;
      }
      .js-address-form .btn-primary:hover {
        background-color: #003359 !important;
        border-color: #003359 !important;
      }
    </style>
    <form
      method="POST"
      action="{url entity='order' params=['id_address' => $id_address]}"
      data-id-address="{$id_address}"
      data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm']}"
    >
{/block}

{block name='form_fields' append}
  <input type="hidden" name="saveAddress" value="{$type}">
  {if $type === "delivery"}
    <div class="form-group row">
      <div class="col-md-9 col-md-offset-3">
        <input name = "use_same_address" id="use_same_address" type = "checkbox" value = "1" {if $use_same_address} checked {/if}>
        <label for="use_same_address">{l s='Use this address for invoice too' d='Shop.Theme.Checkout'}</label>
      </div>
    </div>
  {/if}
{/block}

{block name='form_buttons'}
  {if !$form_has_continue_button}
    <button type="submit" class="btn btn-primary float-xs-right">{l s='Save' d='Shop.Theme.Actions'}</button>
    <a class="js-cancel-address cancel-address float-xs-right" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
  {else}
    <form>
      <button type="submit" class="continue btn btn-primary float-xs-right" name="confirm-addresses" value="1">
          {l s='Continue' d='Shop.Theme.Actions'}
      </button>
      {if $customer.addresses|count > 0}
        <a class="js-cancel-address cancel-address float-xs-right" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
      {/if}
    </form>
  {/if}
{/block}
