<style>
  .stripe-refund-card { border: 1px solid #ddd; padding: 16px; margin: 16px 0; border-radius: 4px; background: #fafafa; }
  .stripe-refund-card h3 { margin-top: 0; color: #004774; font-weight: 700; }
  .stripe-refund-stats { display: flex; gap: 30px; margin: 10px 0 18px; flex-wrap: wrap; }
  .stripe-refund-stats div { font-size: 13px; }
  .stripe-refund-stats strong { font-size: 18px; color: #ee7a03; display: block; }
  .stripe-refund-open { background: #004774; color: #fff; border: none; padding: 10px 20px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
  .stripe-refund-open:hover { background: #003359; color: #fff; }
  .stripe-refund-open:disabled { background: #aaa; cursor: not-allowed; }
  .stripe-refund-history { margin-top: 20px; }
  .stripe-refund-history table { width: 100%; border-collapse: collapse; }
  .stripe-refund-history th, .stripe-refund-history td { padding: 6px 10px; border: 1px solid #eee; text-align: left; font-size: 13px; }
  .stripe-refund-history th { background: #004774; color: #fff; }

  .stripe-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 10000; display: none; }
  .stripe-modal-backdrop.open { display: flex; align-items: center; justify-content: center; }
  .stripe-modal { background: #fff; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; padding: 24px; position: relative; }
  .stripe-modal-close { position: absolute; top: 10px; right: 14px; background: none; border: none; font-size: 28px; cursor: pointer; }
  .stripe-modal h3 { margin-top: 0; color: #004774; }
  .stripe-tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 16px; }
  .stripe-tab { padding: 10px 20px; cursor: pointer; font-weight: 700; border-bottom: 3px solid transparent; margin-bottom: -2px; }
  .stripe-tab.active { color: #004774; border-bottom-color: #ee7a03; }
  .stripe-tab-content { display: none; }
  .stripe-tab-content.active { display: block; }
  .stripe-lines-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
  .stripe-lines-table th, .stripe-lines-table td { padding: 8px; border: 1px solid #ddd; font-size: 13px; }
  .stripe-lines-table th { background: #f5f5f5; }
  .stripe-total-preview { font-size: 18px; font-weight: 700; color: #ee7a03; margin: 15px 0; }
  .stripe-submit-refund { background: #ee7a03; color: #fff; border: none; padding: 12px 28px; font-weight: 700; text-transform: uppercase; cursor: pointer; }
  .stripe-submit-refund:hover { background: #d96d00; }
  .stripe-submit-refund:disabled { background: #aaa; cursor: not-allowed; }
  .stripe-error { color: #c00; margin-top: 10px; font-weight: 600; }
</style>

<div class="stripe-refund-card">
  <h3>💳 Stripe — Paiement #{$stripe_pi|escape:'htmlall'}</h3>
  <div class="stripe-refund-stats">
    <div>Payé<strong>{$stripe_amount|string_format:"%.2f"} {$stripe_currency}</strong></div>
    <div>Déjà remboursé<strong>{$stripe_refunded|string_format:"%.2f"} {$stripe_currency}</strong></div>
    <div>Remboursable<strong>{$stripe_refundable|string_format:"%.2f"} {$stripe_currency}</strong></div>
  </div>
  <button type="button" class="stripe-refund-open" id="stripe-refund-open-btn" {if $stripe_refundable <= 0}disabled{/if}>
    Rembourser via Stripe
  </button>

  {if $stripe_refunds}
    <div class="stripe-refund-history">
      <h4 style="color:#004774;">Historique des remboursements</h4>
      <table>
        <thead>
          <tr><th>Date</th><th>Montant</th><th>Motif</th><th>Statut</th><th>ID</th></tr>
        </thead>
        <tbody>
          {foreach from=$stripe_refunds item=r}
            <tr>
              <td>{$r.created_at}</td>
              <td>{$r.amount|string_format:"%.2f"} {$r.currency}</td>
              <td>{$r.reason}</td>
              <td>{$r.status}</td>
              <td><code style="font-size:11px;">{$r.refund_id}</code></td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  {/if}
</div>

<div class="stripe-modal-backdrop" id="stripe-refund-modal">
  <div class="stripe-modal">
    <button type="button" class="stripe-modal-close" id="stripe-modal-close">&times;</button>
    <h3>Rembourser via Stripe</h3>

    <div class="stripe-tabs">
      <div class="stripe-tab active" data-tab="amount">💰 Montant libre</div>
      <div class="stripe-tab" data-tab="lines">📦 Par produit</div>
    </div>

    <div class="stripe-tab-content active" data-tab="amount">
      <p>Remboursement partiel d'un montant spécifique.</p>
      <div class="form-group">
        <label>Montant à rembourser ({$stripe_currency})</label>
        <input type="number" id="stripe-amount-free" step="0.01" min="0.01" max="{$stripe_refundable|string_format:"%.2f"}" class="form-control" value="{$stripe_refundable|string_format:"%.2f"}" style="max-width:250px;" />
      </div>
    </div>

    <div class="stripe-tab-content" data-tab="lines">
      <p>Cochez les lignes produits à rembourser. Le montant sera calculé automatiquement.</p>
      <table class="stripe-lines-table">
        <thead>
          <tr><th style="width:40px;"></th><th>Produit</th><th style="width:70px;">Qté</th><th style="width:100px;">PU TTC</th><th style="width:110px;">Total</th></tr>
        </thead>
        <tbody>
          {foreach from=$stripe_lines item=line}
            <tr>
              <td><input type="checkbox" class="stripe-line-check" data-total="{$line.line_total}" value="{$line.id}" /></td>
              <td>{$line.name}</td>
              <td>{$line.qty}</td>
              <td>{$line.price|string_format:"%.2f"}</td>
              <td>{$line.line_total|string_format:"%.2f"}</td>
            </tr>
          {/foreach}
        </tbody>
      </table>
      <label style="display:block;margin:10px 0;">
        <input type="checkbox" id="stripe-include-shipping" data-shipping="{$stripe_shipping}" /> Inclure les frais de port ({$stripe_shipping|string_format:"%.2f"} {$stripe_currency})
      </label>
      <label style="display:block;margin:10px 0;">
        <input type="checkbox" id="stripe-restock" /> Remettre les articles en stock
      </label>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label>Motif</label>
      <select id="stripe-refund-reason" class="form-control" style="max-width:300px;">
        <option value="requested_by_customer">Demande client</option>
        <option value="duplicate">Doublon</option>
        <option value="fraudulent">Fraude</option>
      </select>
    </div>

    <div class="stripe-total-preview">Total à rembourser : <span id="stripe-preview">0.00</span> {$stripe_currency}</div>

    <button type="button" class="stripe-submit-refund" id="stripe-submit-refund">Confirmer le remboursement</button>
    <div class="stripe-error" id="stripe-refund-error"></div>
  </div>
</div>

<script>
(function(){
  var openBtn = document.getElementById('stripe-refund-open-btn');
  var modal = document.getElementById('stripe-refund-modal');
  var closeBtn = document.getElementById('stripe-modal-close');
  if (!openBtn) return;
  openBtn.addEventListener('click', function(){ modal.classList.add('open'); updatePreview(); });
  closeBtn.addEventListener('click', function(){ modal.classList.remove('open'); });
  modal.addEventListener('click', function(e){ if (e.target === modal) modal.classList.remove('open'); });

  var tabs = document.querySelectorAll('.stripe-tab');
  var contents = document.querySelectorAll('.stripe-tab-content');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      tabs.forEach(function(x){ x.classList.remove('active'); });
      contents.forEach(function(x){ x.classList.remove('active'); });
      t.classList.add('active');
      document.querySelector('.stripe-tab-content[data-tab="' + t.dataset.tab + '"]').classList.add('active');
      updatePreview();
    });
  });

  function currentAmount(){
    var activeTab = document.querySelector('.stripe-tab.active').dataset.tab;
    if (activeTab === 'amount') {
      return parseFloat(document.getElementById('stripe-amount-free').value) || 0;
    } else {
      var sum = 0;
      document.querySelectorAll('.stripe-line-check:checked').forEach(function(cb){
        sum += parseFloat(cb.dataset.total) || 0;
      });
      var ship = document.getElementById('stripe-include-shipping');
      if (ship.checked) sum += parseFloat(ship.dataset.shipping) || 0;
      return sum;
    }
  }

  function updatePreview(){
    document.getElementById('stripe-preview').textContent = currentAmount().toFixed(2);
  }

  document.getElementById('stripe-amount-free').addEventListener('input', updatePreview);
  document.querySelectorAll('.stripe-line-check, #stripe-include-shipping').forEach(function(el){
    el.addEventListener('change', updatePreview);
  });

  document.getElementById('stripe-submit-refund').addEventListener('click', function(){
    var btn = this;
    var errBox = document.getElementById('stripe-refund-error');
    errBox.textContent = '';
    var amount = currentAmount();
    if (amount <= 0) { errBox.textContent = 'Montant invalide'; return; }

    var activeTab = document.querySelector('.stripe-tab.active').dataset.tab;
    var lineIds = [];
    var restock = 0;
    if (activeTab === 'lines') {
      document.querySelectorAll('.stripe-line-check:checked').forEach(function(cb){ lineIds.push(cb.value); });
      restock = document.getElementById('stripe-restock').checked ? 1 : 0;
    }

    btn.disabled = true;
    btn.textContent = 'Traitement…';

    var fd = new FormData();
    fd.append('ajax', '1');
    fd.append('action', 'refund');
    fd.append('id_order', '{$stripe_order_id}');
    fd.append('amount', amount.toFixed(2));
    fd.append('reason', document.getElementById('stripe-refund-reason').value);
    fd.append('restock', restock);
    lineIds.forEach(function(id){ fd.append('line_ids[]', id); });

    fetch('{$stripe_refund_url nofilter}', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (data.success) {
          alert('Remboursement de ' + data.amount.toFixed(2) + ' effectué. ID: ' + data.refund_id);
          window.location.reload();
        } else {
          errBox.textContent = data.error || 'Erreur inconnue';
          btn.disabled = false;
          btn.textContent = 'Confirmer le remboursement';
        }
      })
      .catch(function(e){
        errBox.textContent = 'Erreur réseau : ' + e.message;
        btn.disabled = false;
        btn.textContent = 'Confirmer le remboursement';
      });
  });
})();
</script>
