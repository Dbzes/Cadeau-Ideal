{extends file='page.tpl'}
{block name='page_content'}

<script src="https://js.stripe.com/v3/"></script>

<div class="stripe-payment-wrapper">
  <div class="stripe-payment-card">

    <div class="stripe-payment-header">
      <div class="stripe-payment-icon">
        {if $stripe_payment_method == 'paypal'}
          <img src="/img/template/payment/PayPal.png" alt="PayPal" />
        {else}
          <img src="/img/template/payment/Visa.png" alt="Visa" />
          <img src="/img/template/payment/Mastercard.png" alt="Mastercard" />
          <img src="/img/template/payment/Amex.png" alt="Amex" />
        {/if}
      </div>
      <h2 class="stripe-payment-title">
        Paiement {if $stripe_payment_method == 'paypal'}PayPal{else}par carte bancaire{/if}
      </h2>
    </div>

    <div class="stripe-payment-amount">
      <span class="stripe-amount-label">Montant à régler</span>
      <span class="stripe-amount-value">{$stripe_amount_display}</span>
    </div>

    <form id="stripe-payment-form" class="stripe-payment-form">
      <div id="stripe-payment-element"></div>

      <button id="stripe-submit-btn" type="submit" class="stripe-pay-button">
        <span id="stripe-btn-text">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Payer {$stripe_amount_display}
        </span>
        <span id="stripe-spinner" style="display:none;">
          <svg class="stripe-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
          Traitement en cours…
        </span>
      </button>

      <div id="stripe-error" class="stripe-payment-error"></div>
    </form>

    <div class="stripe-payment-trust">
      <div class="stripe-trust-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>Paiement 100% sécurisé</span>
      </div>
      <div class="stripe-trust-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span>Données chiffrées SSL</span>
      </div>
      <div class="stripe-trust-item">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Propulsé par Stripe</span>
      </div>
    </div>

  </div>
</div>

{literal}
<style>
.stripe-payment-wrapper {
  max-width: 560px;
  margin: 30px auto;
  padding: 0 16px;
}
.stripe-payment-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 16px rgba(0,71,116,0.08);
  padding: 32px;
}
.stripe-payment-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 18px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.stripe-payment-title {
  font-family: 'Bebas Neue', sans-serif;
  color: #004774;
  font-size: 26px;
  margin: 0;
  letter-spacing: 0.5px;
}
.stripe-payment-icon {
  display: flex;
  gap: 6px;
  align-items: center;
}
.stripe-payment-icon img { height: 26px; width: auto; }
.stripe-payment-amount {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.stripe-amount-label { color: #555; font-size: 14px; }
.stripe-amount-value {
  color: #ee7a03;
  font-weight: 800;
  font-size: 22px;
  font-family: 'Bebas Neue', sans-serif;
  letter-spacing: 0.5px;
}
.stripe-payment-form { margin: 0; }
#stripe-payment-element { margin-bottom: 22px; }
.stripe-pay-button {
  width: 100%;
  background: #004774;
  color: #fff;
  border: none;
  padding: 16px 24px;
  font-weight: 700;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.1s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stripe-pay-button:hover:not(:disabled) { background: #003355; }
.stripe-pay-button:active:not(:disabled) { transform: translateY(1px); }
.stripe-pay-button:disabled { opacity: 0.7; cursor: wait; }
.stripe-payment-error {
  color: #c00;
  margin-top: 14px;
  font-size: 14px;
  text-align: center;
  min-height: 20px;
}
.stripe-payment-trust {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-around;
  gap: 12px;
  flex-wrap: wrap;
}
.stripe-trust-item {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #555;
  font-size: 13px;
}
.stripe-spin { animation: stripe-spin 0.9s linear infinite; }
@keyframes stripe-spin { to { transform: rotate(360deg); } }

/* --- Success in-page --- */
.stripe-success-block { text-align: center; padding: 8px 0; }
.stripe-success-icon { margin-bottom: 18px; display: flex; justify-content: center; }
.stripe-success-title {
  font-family: 'Bebas Neue', sans-serif;
  color: #004774;
  font-size: 30px;
  margin: 0 0 16px;
  letter-spacing: 0.5px;
  line-height: 1.2;
}
.stripe-success-message { color: #444; font-size: 16px; line-height: 1.55; margin: 0 0 26px; }
.stripe-success-info {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  padding: 16px 20px;
  margin: 0 0 24px;
  text-align: left;
}
.stripe-success-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}
.stripe-success-info-row:last-child { border-bottom: none; }
.stripe-info-label { color: #555; font-size: 14px; }
.stripe-info-value {
  color: #004774;
  font-weight: 700;
  font-size: 15px;
  font-family: 'Bebas Neue', sans-serif;
  letter-spacing: 0.5px;
}
.stripe-success-email { color: #555; font-size: 14px; line-height: 1.5; margin: 0 0 28px; }
.stripe-success-home {
  display: inline-block;
  background: #004774;
  color: #fff !important;
  padding: 16px 36px;
  font-weight: 700;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-decoration: none;
  transition: background 0.2s ease, transform 0.1s ease;
}
.stripe-success-home:hover { background: #003355; text-decoration: none; color: #fff; }
.stripe-success-home:active { transform: translateY(1px); }

@media (max-width: 600px) {
  .stripe-payment-wrapper { margin: 16px auto; padding: 0 12px; }
  .stripe-payment-card { padding: 20px; }
  .stripe-payment-header {
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
  }
  .stripe-payment-title { font-size: 22px; }
  .stripe-payment-amount { padding: 14px 16px; }
  .stripe-amount-value { font-size: 20px; }
  .stripe-pay-button { padding: 14px 18px; font-size: 14px; }
  .stripe-payment-trust { gap: 10px; justify-content: center; }
  .stripe-trust-item { font-size: 12px; flex: 1 1 100%; justify-content: center; text-align: center; }
  .stripe-success-title { font-size: 24px; }
  .stripe-success-message { font-size: 15px; }
  .stripe-success-info { padding: 12px 16px; }
  .stripe-success-info-row { flex-direction: column; align-items: flex-start; gap: 4px; }
  .stripe-success-home { padding: 14px 28px; font-size: 14px; width: 100%; box-sizing: border-box; }
}
</style>
{/literal}

{literal}
<script>
(function(){
  var pk = {/literal}"{$stripe_pk|escape:'javascript'}"{literal};
  var clientSecret = {/literal}"{$stripe_client_secret|escape:'javascript'}"{literal};
  var returnUrl = {/literal}"{$stripe_return_url|escape:'javascript'}"{literal};
  var ajaxUrl = {/literal}"{$stripe_ajax_url|escape:'javascript'}"{literal};
  var homeUrl = {/literal}"{$stripe_home_url|escape:'javascript'}"{literal};
  var method = {/literal}"{$stripe_payment_method|escape:'javascript'}"{literal};

  function init() {
    if (!window.Stripe) {
      document.getElementById('stripe-error').textContent = 'Erreur : Stripe.js n\'a pas pu être chargé.';
      return;
    }
    var stripe = Stripe(pk);
    var elements = stripe.elements({
      clientSecret: clientSecret,
      appearance: { theme: 'stripe', variables: { colorPrimary: '#004774' } }
    });
    var paymentElement = elements.create('payment', {
      layout: { type: 'tabs' },
      wallets: { link: 'never' }
    });
    paymentElement.mount('#stripe-payment-element');

    var form = document.getElementById('stripe-payment-form');
    var btn = document.getElementById('stripe-submit-btn');
    var btnText = document.getElementById('stripe-btn-text');
    var spinner = document.getElementById('stripe-spinner');
    var errBox = document.getElementById('stripe-error');

    function showError(msg) {
      errBox.textContent = msg || 'Erreur de paiement';
      btn.disabled = false;
      btnText.style.display = 'inline-flex';
      spinner.style.display = 'none';
    }

    function escapeHtml(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
      });
    }

    function renderSuccess(data) {
      var card = document.querySelector('.stripe-payment-card');
      if (!card) return;
      var name = data.firstname ? ' ' + escapeHtml(data.firstname) : '';
      card.innerHTML =
        '<div class="stripe-success-block">' +
          '<div class="stripe-success-icon">' +
            '<svg viewBox="0 0 64 64" width="72" height="72" xmlns="http://www.w3.org/2000/svg">' +
              '<circle cx="32" cy="32" r="30" fill="#16a34a"/>' +
              '<path d="M20 33 L29 42 L46 24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>' +
          '</div>' +
          '<h2 class="stripe-success-title">Merci' + name + ', votre commande est confirmée&nbsp;!</h2>' +
          '<p class="stripe-success-message">Votre paiement de <strong>' + escapeHtml(data.total) + '</strong> a bien été traité. Nous préparons votre commande avec le plus grand soin.</p>' +
          '<div class="stripe-success-info">' +
            '<div class="stripe-success-info-row"><span class="stripe-info-label">Numéro de commande</span><span class="stripe-info-value">#' + escapeHtml(String(data.id_order)) + '</span></div>' +
            '<div class="stripe-success-info-row"><span class="stripe-info-label">Référence</span><span class="stripe-info-value">' + escapeHtml(data.reference) + '</span></div>' +
          '</div>' +
          '<p class="stripe-success-email">Un email de confirmation vient d\'être envoyé à <strong>' + escapeHtml(data.email) + '</strong>.<br>Pensez à vérifier vos spams si vous ne le voyez pas dans quelques minutes.</p>' +
          '<a href="' + escapeHtml(homeUrl) + '" class="stripe-success-home">Accueil du site</a>' +
        '</div>';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function finalizeOrder(paymentIntentId) {
      var sep = ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      fetch(ajaxUrl + sep + 'payment_intent=' + encodeURIComponent(paymentIntentId), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(function(r){ return r.json(); }).then(function(data){
        if (!data || data.error) {
          showError(data && data.error ? data.error : 'Finalisation impossible');
          return;
        }
        renderSuccess(data);
      }).catch(function(e){
        showError('Erreur réseau : ' + e.message);
      });
    }

    form.addEventListener('submit', function(e){
      e.preventDefault();
      btn.disabled = true;
      btnText.style.display = 'none';
      spinner.style.display = 'inline-flex';
      errBox.textContent = '';

      stripe.confirmPayment({
        elements: elements,
        confirmParams: { return_url: returnUrl },
        redirect: 'if_required'
      }).then(function(result){
        if (result.error) {
          showError(result.error.message);
          return;
        }
        if (result.paymentIntent && (result.paymentIntent.status === 'succeeded' || result.paymentIntent.status === 'processing')) {
          finalizeOrder(result.paymentIntent.id);
        }
      }).catch(function(e){
        showError(e.message);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
{/literal}
{/block}
