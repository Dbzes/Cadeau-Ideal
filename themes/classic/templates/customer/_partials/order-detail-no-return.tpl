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
{block name='order_products_table'}
  <div class="box hidden-sm-down">
    <table id="order-products" class="table table-bordered">
      <thead class="thead-default">
        <tr>
          <th>{l s='Product' d='Shop.Theme.Catalog'}</th>
          <th>{l s='Quantity' d='Shop.Theme.Catalog'}</th>
          <th>{l s='Unit price' d='Shop.Theme.Catalog'}</th>
          <th>{l s='Total price' d='Shop.Theme.Catalog'}</th>
        </tr>
      </thead>
      {foreach from=$order.products item=product}
        <tr>
          <td>
            <strong>
              <a href="{$urls.pages.product}&id_product={$product.id_product}">
                {$product.name}
              </a>
              {assign var='mpeVariantSuffix' value=''}
              {if $product.customizations}
                {foreach from=$product.customizations item="vCust"}
                  {foreach from=$vCust.fields item="vF"}
                    {if $vF.type == 'text' && $vF.label == 'Variante'}
                      {assign var='mpeVariantSuffix' value=$vF.text}
                    {/if}
                  {/foreach}
                {/foreach}
              {/if}
              {if $mpeVariantSuffix} <span style="color:#ee7a03;font-weight:600;">{$mpeVariantSuffix}</span>{/if}
            </strong><br/>
            {if $product.product_reference}
              {l s='Reference' d='Shop.Theme.Catalog'}: {$product.product_reference}<br/>
            {/if}
            {if isset($product.download_link)}
              <a href="{$product.download_link}">{l s='Download' d='Shop.Theme.Actions'}</a><br/>
            {/if}
            {if $product.is_virtual}
              {l s='Virtual products can\'t be returned.' d='Shop.Theme.Customeraccount'}</br>
            {/if}
            {if $product.customizations}
              {foreach from=$product.customizations item="customization"}
                {assign var='mpeImg' value=''}
                {assign var='mpeImgLarge' value=''}
                {foreach from=$customization.fields item="f"}
                  {if $f.type == 'image' && $f.image.small.url}
                    {assign var='mpeImg' value=$f.image.small.url}
                    {* Modal aperçu = fichier _preview, pas large.url qui pointe sur la planche HD *}
                    {assign var='mpeImgLarge' value=$f.image.small.url|replace:'_small':'_preview'}
                  {/if}
                {/foreach}
                {if $mpeImg}
                  <div class="customization">
                    <a href="#" class="mpe-preview-trigger" data-target="#mpe-od-preview-{$customization.id_customization}" style="color:#004774;font-weight:700;text-decoration:none;">Aperçu de la personnalisation</a>
                  </div>
                  <div id="mpe-od-preview-{$customization.id_customization}" class="mpe-preview-modal" style="display:none;">
                    <div class="mpe-preview-backdrop"></div>
                    <button type="button" class="mpe-preview-close" aria-label="Fermer">&times;</button>
                    <img src="{$mpeImgLarge}" alt="Aperçu de la personnalisation" class="mpe-preview-img" />
                  </div>
                {/if}
              {/foreach}
            {/if}
          </td>
          <td>
            {if $product.customizations}
              {foreach $product.customizations as $customization}
                {$customization.quantity}
              {/foreach}
            {else}
              {$product.quantity}
            {/if}
          </td>
          <td class="text-xs-right">{$product.price}</td>
          <td class="text-xs-right">{$product.total}</td>
        </tr>
      {/foreach}
      <tfoot>
        {foreach $order.subtotals as $line}
          {if $line.value}
            <tr class="text-xs-right line-{$line.type}">
              <td colspan="3">{$line.label}</td>
              <td>{$line.value}</td>
            </tr>
          {/if}
        {/foreach}
        <tr class="text-xs-right line-{$order.totals.total.type}">
          <td colspan="3">{$order.totals.total.label}</td>
          <td>{$order.totals.total.value}</td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="order-items hidden-md-up box">
    {foreach from=$order.products item=product}
      <div class="order-item">
        <div class="row">
          <div class="col-sm-5 desc">
            <div class="name">{$product.name}{if $mpeVariantSuffix} <span style="color:#ee7a03;font-weight:600;">{$mpeVariantSuffix}</span>{/if}</div>
            {if $product.product_reference}
              <div class="ref">{l s='Reference' d='Shop.Theme.Catalog'}: {$product.product_reference}</div>
            {/if}
            {if isset($product.download_link)}
              <a href="{$product.download_link}">{l s='Download' d='Shop.Theme.Actions'}</a><br/>
            {/if}
            {if $product.customizations}
              {foreach $product.customizations as $customization}
                {assign var='mpeImgM' value=''}
                {assign var='mpeImgMLarge' value=''}
                {foreach from=$customization.fields item="f"}
                  {if $f.type == 'image' && $f.image.small.url}
                    {assign var='mpeImgM' value=$f.image.small.url}
                    {* Modal aperçu = fichier _preview, pas large.url qui pointe sur la planche HD *}
                    {assign var='mpeImgMLarge' value=$f.image.small.url|replace:'_small':'_preview'}
                  {/if}
                {/foreach}
                {if $mpeImgM}
                  <div class="customization">
                    <a href="#" class="mpe-preview-trigger" data-target="#mpe-od-preview-m-{$customization.id_customization}" style="color:#004774;font-weight:700;text-decoration:none;">Aperçu de la personnalisation</a>
                  </div>
                  <div id="mpe-od-preview-m-{$customization.id_customization}" class="mpe-preview-modal" style="display:none;">
                    <div class="mpe-preview-backdrop"></div>
                    <button type="button" class="mpe-preview-close" aria-label="Fermer">&times;</button>
                    <img src="{$mpeImgMLarge}" alt="Aperçu de la personnalisation" class="mpe-preview-img" />
                  </div>
                {/if}
              {/foreach}
            {/if}
          </div>
          <div class="col-sm-7 qty">
            <div class="row">
              <div class="col-xs-4 text-sm-left text-xs-left">
                {$product.price}
              </div>
              <div class="col-xs-4">
                {if $product.customizations}
                  {foreach $product.customizations as $customization}
                    {$customization.quantity}
                  {/foreach}
                {else}
                  {$product.quantity}
                {/if}
              </div>
              <div class="col-xs-4 text-xs-right">
                {$product.total}
              </div>
            </div>
          </div>
        </div>
      </div>
    {/foreach}
  </div>
  <div class="order-totals hidden-md-up box">
    {foreach $order.subtotals as $line}
      {if $line.value}
        <div class="order-total row">
          <div class="col-xs-8"><strong>{$line.label}</strong></div>
          <div class="col-xs-4 text-xs-right">{$line.value}</div>
        </div>
      {/if}
    {/foreach}
    <div class="order-total row">
      <div class="col-xs-8"><strong>{$order.totals.total.label}</strong></div>
      <div class="col-xs-4 text-xs-right">{$order.totals.total.value}</div>
    </div>
  </div>
{/block}
