{*
* Customers Inspector — Admin view
*}

<style>
.ci-wrapper { padding: 16px 0; }
.ci-card { background: #fff; border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 20px; }
.ci-card h3 { margin: 0 0 16px; color: #004774; font-size: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.ci-card h3 small { display: block; margin-top: 4px; color: #888; font-size: 11px; font-weight: 400; text-transform: none; letter-spacing: 0; }
.ci-presets { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.ci-presets a { display: inline-block; padding: 6px 14px; background: #f4f4f4; color: #333; text-decoration: none; border: 1px solid #ddd; font-size: 13px; }
.ci-presets a:hover { background: #004774; color: #fff; border-color: #004774; }
.ci-form-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 16px; }
.ci-form-group { display: flex; flex-direction: column; gap: 4px; }
.ci-form-group label { font-size: 12px; font-weight: 600; color: #666; text-transform: uppercase; }
.ci-form-group input[type="date"], .ci-form-group select { padding: 6px 10px; border: 1px solid #ccc; min-width: 180px; font-size: 14px; }
.ci-form-group select[multiple] { min-height: 140px; min-width: 240px; }
.ci-btn { padding: 8px 22px; background: #ee7a03; color: #fff; border: 0; cursor: pointer; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.ci-btn:hover { background: #d66c00; }
.ci-btn-secondary { background: #f4f4f4; color: #333; border: 1px solid #ddd; text-decoration: none; display: inline-block; padding: 8px 22px; font-size: 14px; }
.ci-kpi { display: flex; gap: 20px; flex-wrap: wrap; }
.ci-kpi-box { flex: 1; min-width: 240px; background: #004774; color: #fff; padding: 24px; }
.ci-kpi-box .ci-kpi-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.85; margin-bottom: 8px; }
.ci-kpi-box .ci-kpi-value { font-size: 42px; font-weight: 700; line-height: 1; }
.ci-kpi-box .ci-kpi-meta { font-size: 12px; opacity: 0.8; margin-top: 8px; }
.ci-kpi-box.ci-kpi-secondary { background: #f4f4f4; color: #333; }
.ci-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.ci-table th, .ci-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
.ci-table th { background: #f8f8f8; color: #666; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
.ci-table td.right, .ci-table th.right { text-align: right; }
.ci-table td.muted { color: #999; font-style: italic; }
.ci-warn { padding: 12px 16px; background: #fff3cd; border: 1px solid #ffe69c; color: #664d03; margin-bottom: 16px; }
.ci-info { padding: 10px 14px; background: #e7f1f9; border: 1px solid #b6d4ed; color: #004774; margin-bottom: 12px; font-size: 12px; }
.ci-pill { display: inline-block; padding: 2px 8px; background: #004774; color: #fff; font-size: 11px; margin-left: 6px; vertical-align: middle; }
.ci-bar { display: inline-block; height: 8px; background: #ee7a03; vertical-align: middle; margin-left: 8px; }
.ci-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 1100px) { .ci-grid-2 { grid-template-columns: 1fr; } }
.ci-empty { padding: 32px; text-align: center; color: #999; font-style: italic; }
</style>

<div class="ci-wrapper">

  {if !$ci_geoip_ready}
    <div class="ci-warn">
      <strong>Base GeoIP indisponible.</strong> Le filtre par pays n'est pas opérationnel. Vérifier <code>modules/customersinspector/data/dbip-country-lite.mmdb</code>.
    </div>
  {/if}

  <div class="ci-card">
    <h3>Filtres</h3>

    <div class="ci-presets">
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=today">Aujourd'hui</a>
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=yesterday">Hier</a>
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=7d">7 derniers jours</a>
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=30d">30 derniers jours</a>
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=month">Ce mois</a>
      <a href="{$ci_form_action|escape:'html':'UTF-8'}&preset=year">Cette année</a>
    </div>

    <form method="get" action="index.php">
      {foreach from=$smarty.get key=k item=v}
        {if $k === 'controller' || $k === 'token'}
          <input type="hidden" name="{$k|escape:'html':'UTF-8'}" value="{$v|escape:'html':'UTF-8'}">
        {/if}
      {/foreach}

      <div class="ci-form-row">
        <div class="ci-form-group">
          <label for="ci-date-from">Du</label>
          <input type="date" id="ci-date-from" name="date_from" value="{$ci_date_from|escape:'html':'UTF-8'}">
        </div>
        <div class="ci-form-group">
          <label for="ci-date-to">Au</label>
          <input type="date" id="ci-date-to" name="date_to" value="{$ci_date_to|escape:'html':'UTF-8'}">
        </div>
        <div class="ci-form-group">
          <label for="ci-countries">Pays (origine IP) — Ctrl/Cmd pour multi</label>
          <select id="ci-countries" name="countries[]" multiple>
            {foreach from=$ci_country_list item=c}
              <option value="{$c.iso|escape:'html':'UTF-8'}" {if in_array($c.iso, $ci_selected_countries)}selected{/if}>
                {$c.label|escape:'html':'UTF-8'} — {$c.count}
              </option>
            {/foreach}
          </select>
        </div>
        <div class="ci-form-group">
          <button type="submit" class="ci-btn">Filtrer</button>
        </div>
        <div class="ci-form-group">
          <a href="{$ci_form_action|escape:'html':'UTF-8'}" class="ci-btn-secondary">Réinitialiser</a>
        </div>
      </div>
    </form>
  </div>

  <div class="ci-card">
    <h3>Résultat
      <span class="ci-pill">{$ci_date_from|escape:'html':'UTF-8'} → {$ci_date_to|escape:'html':'UTF-8'}</span>
      {if $ci_selected_countries|@count > 0}
        <span class="ci-pill" style="background:#ee7a03;">{$ci_selected_countries|@count} pays</span>
      {else}
        <span class="ci-pill" style="background:#888;">Tous pays</span>
      {/if}
    </h3>

    <div class="ci-kpi">
      <div class="ci-kpi-box">
        <div class="ci-kpi-label">Visiteurs uniques</div>
        <div class="ci-kpi-value">{$ci_total_unique|number_format:0:',':' '}</div>
        <div class="ci-kpi-meta">id_guest distincts sur la période et la sélection pays</div>
      </div>
      <div class="ci-kpi-box ci-kpi-secondary">
        <div class="ci-kpi-label">Connexions analysées</div>
        <div class="ci-kpi-value">{$ci_total_connections|number_format:0:',':' '}</div>
        <div class="ci-kpi-meta">Lignes ps_connections sur la période</div>
      </div>
    </div>
  </div>

  <div class="ci-grid-2">

    <div class="ci-card">
      <h3>
        Pages d'entrée
        <small>1ère page de chaque session — données rétroactives complètes</small>
      </h3>
      {if $ci_entry_pages|@count > 0}
        <table class="ci-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Nom</th>
              <th class="right">Visites</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$ci_entry_pages item=p}
              <tr>
                <td>{$p.page_type_label|escape:'html':'UTF-8'}</td>
                <td {if !$p.object_name}class="muted"{/if}>
                  {if $p.object_name}{$p.object_name|escape:'html':'UTF-8'}{else}—{/if}
                </td>
                <td class="right"><strong>{$p.count|number_format:0:',':' '}</strong></td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <div class="ci-empty">Aucune donnée sur la période.</div>
      {/if}
    </div>

    <div class="ci-card">
      <h3>
        Pages vues (toutes pages)
        <small>Tracking activé le 2026-05-08 — total période : {$ci_viewed_pages_total|number_format:0:',':' '}</small>
      </h3>
      {if $ci_viewed_pages|@count > 0}
        <table class="ci-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Nom</th>
              <th class="right">Vues</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$ci_viewed_pages item=p}
              <tr>
                <td>{$p.page_type_label|escape:'html':'UTF-8'}</td>
                <td {if !$p.object_name}class="muted"{/if}>
                  {if $p.object_name}{$p.object_name|escape:'html':'UTF-8'}{else}—{/if}
                </td>
                <td class="right"><strong>{$p.count|number_format:0:',':' '}</strong></td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <div class="ci-info">
          Aucune page vue enregistrée sur la période. Le tracking <code>ps_connections_page</code> a été activé le 2026-05-08, les données arriveront avec les visites futures.
        </div>
      {/if}
    </div>

  </div>

  <div class="ci-card">
    <h3>
      Appareils
      <small>Mobile / Tablette / Ordinateur — tracking custom (User-Agent) activé le 2026-05-08</small>
    </h3>
    {if $ci_device_total > 0}
      <table class="ci-table">
        <thead>
          <tr>
            <th>Type d'appareil</th>
            <th class="right">Visites</th>
            <th class="right">%</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$ci_device_stats item=d}
            <tr>
              <td><strong>{$d.label|escape:'html':'UTF-8'}</strong></td>
              <td class="right">{$d.count|number_format:0:',':' '}</td>
              <td class="right">{$d.pct}%</td>
              <td>
                <span class="ci-bar" style="width: {$d.pct}%; max-width: 200px;"></span>
              </td>
            </tr>
          {/foreach}
        </tbody>
        <tfoot>
          <tr>
            <td><strong>Total</strong></td>
            <td class="right"><strong>{$ci_device_total|number_format:0:',':' '}</strong></td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    {else}
      <div class="ci-info">
        Aucune visite enregistrée par le tracking custom sur la période. Le module a été activé le 2026-05-08, les données arriveront avec les prochaines visites front.
      </div>
    {/if}
  </div>

</div>
