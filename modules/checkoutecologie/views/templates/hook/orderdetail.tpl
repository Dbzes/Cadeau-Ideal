{**
 * Affiché en BO sur la fiche commande quand le client a choisi
 * l'option carton de seconde main.
 *}
<div class="card mb-3" style="border-left:4px solid #27ae60;">
  <div class="card-body" style="display:flex;align-items:center;gap:14px;">
    {if $ceco_icon_url}
      <img src="{$ceco_icon_url|escape:'htmlall':'UTF-8'}" alt="" style="width:48px;height:48px;object-fit:contain;" />
    {else}
      <span style="font-size:32px;">♻️</span>
    {/if}
    <div>
      <div style="font-weight:600;color:#27ae60;font-size:14px;">{$ceco_bo_label|escape:'htmlall':'UTF-8'}</div>
      <div style="font-size:12px;color:#666;">{l s='Le client a coché l\'option lors du tunnel de commande.' mod='checkoutecologie'}</div>
    </div>
  </div>
</div>
