{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
<table class="product" width="100%" cellpadding="4" cellspacing="0">

  {assign var='widthColProduct' value=$layout.product.width - 8}
  {if !$isTaxEnabled}
    {assign var='widthColProduct' value=$widthColProduct+$layout.tax_code.width}
  {/if}
  <thead>
  <tr>
    <th class="product header small" width="8%">Visuel</th>
    <th class="product header small" width="{$layout.reference.width}%">{l s='Reference' d='Shop.Pdf' pdf='true'}</th>
    <th class="product header small" width="{$widthColProduct}%">{l s='Product' d='Shop.Pdf' pdf='true'}</th>
    {if $isTaxEnabled}
      <th class="product header small" width="{$layout.tax_code.width}%">{l s='Tax Rate' d='Shop.Pdf' pdf='true'}</th>
    {/if}
    {if isset($layout.before_discount)}
      <th class="product header small" width="{$layout.unit_price_tax_excl.width}%">
        {l s='Base price' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
      </th>
    {/if}

    <th class="product header-right small" width="{$layout.unit_price_tax_excl.width}%">
      {l s='Unit Price' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
    </th>
    <th class="product header small" width="{$layout.quantity.width}%">{l s='Qty' d='Shop.Pdf' pdf='true'}</th>
    <th class="product header-right small" width="{$layout.total_tax_excl.width}%">
      {l s='Total' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
    </th>
  </tr>
  </thead>

  <tbody>

  <!-- PRODUCTS -->
  {foreach $order_details as $order_detail}
    {cycle values=["color_line_even", "color_line_odd"] assign=bgcolor_class}
    {assign var='pdfVariant' value=''}
    {assign var='pdfCustomImg' value=''}
    {if isset($order_detail.customizedDatas)}
      {foreach $order_detail.customizedDatas as $cpa}
        {foreach $cpa as $cid => $cdata}
          {if isset($cdata.datas[Product::CUSTOMIZE_TEXTFIELD])}
            {foreach $cdata.datas[Product::CUSTOMIZE_TEXTFIELD] as $ci}
              {if $ci.name == 'Variante'}
                {assign var='pdfVariant' value=$ci.value}
              {/if}
            {/foreach}
          {/if}
          {if isset($cdata.datas[Product::CUSTOMIZE_FILE])}
            {foreach $cdata.datas[Product::CUSTOMIZE_FILE] as $cf}
              {assign var='pdfCustomImg' value="{$smarty.const._PS_UPLOAD_DIR_}{$cf.value}_small"}
            {/foreach}
          {/if}
        {/foreach}
      {/foreach}
    {/if}
    {if $pdfCustomImg && file_exists($pdfCustomImg)}
      {assign var='cellStyle' value='style="line-height:45px;"'}
    {else}
      {assign var='cellStyle' value=''}
    {/if}
    <tr class="product {$bgcolor_class}">

      <td class="product center" valign="middle">
        {if $pdfCustomImg && file_exists($pdfCustomImg)}
          <img src="{$pdfCustomImg}" style="width:45px;height:45px;" />
        {/if}
      </td>
      <td class="product center" {$cellStyle}>
        {$order_detail.product_reference}
      </td>
      <td class="product left" {$cellStyle}>
        {$order_detail.product_name}{if $pdfVariant} ({$pdfVariant}){/if}
      </td>
      {if $isTaxEnabled}
        <td class="product center" {$cellStyle}>
          {$order_detail.order_detail_tax_label}
        </td>
      {/if}

      {if isset($layout.before_discount)}
        <td class="product center" {$cellStyle}>
          {if isset($order_detail.unit_price_tax_excl_before_specific_price)}
            {displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_excl_before_specific_price}
          {else}
            --
          {/if}
        </td>
      {/if}

      <td class="product right" {$cellStyle}>
        {displayPrice currency=$order->id_currency price=$order_detail.unit_price_tax_excl_including_ecotax}
        {if $order_detail.ecotax_tax_excl > 0}
          <br>
          <small>{{displayPrice currency=$order->id_currency price=$order_detail.ecotax_tax_excl}|string_format:{l s='ecotax: %s' d='Shop.Pdf' pdf='true'}}</small>
        {/if}
      </td>
      <td class="product center" {$cellStyle}>
        {$order_detail.product_quantity}
      </td>
      <td class="product right" {$cellStyle}>
        {displayPrice currency=$order->id_currency price=$order_detail.total_price_tax_excl_including_ecotax}
      </td>
    </tr>

    {foreach $order_detail.customizedDatas as $customizationPerAddress}
      {foreach $customizationPerAddress as $customizationId => $customization}
        {if isset($customization.datas[Product::CUSTOMIZE_TEXTFIELD]) && count($customization.datas[Product::CUSTOMIZE_TEXTFIELD]) > 0}
          {assign var='hasNonVariantText' value=false}
          {foreach $customization.datas[Product::CUSTOMIZE_TEXTFIELD] as $customization_infos}
            {if $customization_infos.name != 'Variante'}{assign var='hasNonVariantText' value=true}{/if}
          {/foreach}
          {if $hasNonVariantText}
            <tr class="customization_data {$bgcolor_class}">
              <td class="center">&nbsp;</td>
              <td colspan="{$layout._colCount}">
                <table style="width: 100%;">
                  {foreach $customization.datas[Product::CUSTOMIZE_TEXTFIELD] as $customization_infos}
                    {if $customization_infos.name != 'Variante'}
                      <tr>
                        <td>{$customization_infos.name|escape:'html':'UTF-8'|string_format:{l s='%s:' d='Shop.Pdf' pdf='true'}} {if (int)$customization_infos.id_module}{$customization_infos.value nofilter}{else}{$customization_infos.value}{/if}</td>
                      </tr>
                    {/if}
                  {/foreach}
                </table>
              </td>
            </tr>
          {/if}
        {/if}
        {* Visuel déjà affiché dans la colonne Visuel — pas de doublon ici *}
      {/foreach}
    {/foreach}
  {/foreach}
  <!-- END PRODUCTS -->

  <!-- CART RULES -->

  {assign var="shipping_discount_tax_incl" value="0"}
  {foreach from=$cart_rules item=cart_rule name="cart_rules_loop"}
    {if $smarty.foreach.cart_rules_loop.first}
      <tr class="discount">
        <th class="header" colspan="{$layout._colCount + 1}">
          {l s='Discounts' d='Shop.Pdf' pdf='true'}
        </th>
      </tr>
    {/if}
    <tr class="discount">
      <td class="white right" colspan="{$layout._colCount}">
        {$cart_rule.name}
      </td>
      <td class="right white">
        - {displayPrice currency=$order->id_currency price=$cart_rule.value_tax_excl}
      </td>
    </tr>
  {/foreach}

  </tbody>

</table>
