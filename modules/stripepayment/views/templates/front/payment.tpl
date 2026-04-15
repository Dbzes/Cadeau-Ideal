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
}
</style>
{/literal}

{literal}
<script>
(function(){
  var pk = {/literal}"{$stripe_pk|escape:'javascript'}"{literal};
  var clientSecret = {/literal}"{$stripe_client_secret|escape:'javascript'}"{literal};
  var returnUrl = {/literal}"{$stripe_return_url|escape:'javascript'}"{literal};
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

    form.addEventListener('submit', function(e){
      e.preventDefault();
      btn.disabled = true;
      btnText.style.display = 'none';
      spinner.style.display = 'inline-flex';
      errBox.textContent = '';

      stripe.confirmPayment({
        elements: elements,
        confirmParams: { return_url: returnUrl }
      }).then(function(result){
        if (result.error) {
          errBox.textContent = result.error.message || 'Erreur de paiement';
          btn.disabled = false;
          btnText.style.display = 'inline-flex';
          spinner.style.display = 'none';
        }
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
