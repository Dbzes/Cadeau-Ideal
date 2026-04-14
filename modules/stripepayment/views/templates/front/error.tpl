{extends file='page.tpl'}
{block name='page_content'}
<div class="alert alert-danger" role="alert">
  {if $errors}
    {foreach from=$errors item=err}<p>{$err}</p>{/foreach}
  {else}
    <p>Une erreur est survenue lors du paiement.</p>
  {/if}
  <p><a href="{$urls.pages.order}?step=3" style="color:#004774;font-weight:700;">Retourner au paiement</a></p>
</div>
{/block}
