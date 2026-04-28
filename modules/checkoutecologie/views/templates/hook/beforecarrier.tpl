{**
 * Affiché juste avant la liste des transporteurs dans le tunnel de commande.
 *}
<div class="ceco-block">
  <label for="ceco-checkbox" class="ceco-label">
    <input type="checkbox" id="ceco-checkbox" name="ceco-checkbox" value="1"{if $ceco_active} checked="checked"{/if} data-toggle-url="{$ceco_toggle_url|escape:'htmlall':'UTF-8'}" />
    <span class="ceco-mark" aria-hidden="true"></span>
    {if $ceco_icon_url}
      <img src="{$ceco_icon_url|escape:'htmlall':'UTF-8'}" class="ceco-icon" alt="" />
    {/if}
    <span class="ceco-text">{$ceco_label|escape:'htmlall':'UTF-8'}</span>
  </label>
  <span class="ceco-feedback" id="ceco-feedback"></span>
</div>
