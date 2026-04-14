{extends file='page.tpl'}
{block name='page_content'}
<div class="stripe-payment-wrapper" style="max-width:600px;margin:20px auto;">
  <h2 style="font-family:'Bebas Neue',sans-serif;color:#004774;">Paiement {if $stripe_payment_method == 'paypal'}PayPal{else}Carte bancaire{/if}</h2>
  <p>Montant à régler : <strong style="color:#ee7a03;">{$stripe_amount_display}</strong></p>

  <form id="stripe-payment-form" style="margin-top:20px;">
    <div id="stripe-payment-element"></div>
    <button id="stripe-submit-btn" type="submit" style="margin-top:20px;background:#004774;color:#fff;border:none;padding:14px 28px;font-weight:700;text-transform:uppercase;cursor:pointer;width:100%;">
      <span id="stripe-btn-text">Payer {$stripe_amount_display}</span>
      <span id="stripe-spinner" style="display:none;">Traitement…</span>
    </button>
    <div id="stripe-error" style="color:#c00;margin-top:12px;"></div>
  </form>
</div>

{literal}
<script>
(function(){
  var pk = {/literal}"{$stripe_pk|escape:'javascript'}"{literal};
  var clientSecret = {/literal}"{$stripe_client_secret|escape:'javascript'}"{literal};
  var returnUrl = {/literal}"{$stripe_return_url|escape:'javascript'}"{literal};
  var method = {/literal}"{$stripe_payment_method|escape:'javascript'}"{literal};

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
    layout: { type: 'tabs' }
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
    spinner.style.display = 'inline';
    errBox.textContent = '';

    stripe.confirmPayment({
      elements: elements,
      confirmParams: { return_url: returnUrl }
    }).then(function(result){
      if (result.error) {
        errBox.textContent = result.error.message || 'Erreur de paiement';
        btn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
      }
    });
  });
})();
</script>
{/literal}
{/block}
