{extends file='page.tpl'}
{block name='page_content'}

<div class="stripe-success-wrapper">
  <div class="stripe-success-card">

    <div class="stripe-success-icon">
      <svg viewBox="0 0 64 64" width="72" height="72" xmlns="http://www.w3.org/2000/svg">
        <circle cx="32" cy="32" r="30" fill="#16a34a"/>
        <path d="M20 33 L29 42 L46 24" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>

    <h1 class="stripe-success-title">Merci{if $customer_firstname} {$customer_firstname}{/if}, votre commande est confirmée&nbsp;!</h1>

    <p class="stripe-success-message">
      Votre paiement de <strong>{$order_total}</strong> a bien été traité.
      Nous préparons votre commande avec le plus grand soin.
    </p>

    <div class="stripe-success-info">
      <div class="stripe-success-info-row">
        <span class="stripe-info-label">Numéro de commande</span>
        <span class="stripe-info-value">#{$order_id}</span>
      </div>
      <div class="stripe-success-info-row">
        <span class="stripe-info-label">Référence</span>
        <span class="stripe-info-value">{$order_reference}</span>
      </div>
    </div>

    <p class="stripe-success-email">
      Un email de confirmation vient d'être envoyé à <strong>{$customer_email}</strong>.<br>
      Pensez à vérifier vos spams si vous ne le voyez pas dans quelques minutes.
    </p>

    <a href="{$home_url}" class="stripe-success-home">Accueil du site</a>

  </div>
</div>

{literal}
<style>
.stripe-success-wrapper {
  max-width: 620px;
  margin: 30px auto;
  padding: 0 16px;
}
.stripe-success-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 16px rgba(0,71,116,0.08);
  padding: 40px 36px;
  text-align: center;
}
.stripe-success-icon {
  margin-bottom: 18px;
  display: flex;
  justify-content: center;
}
.stripe-success-title {
  font-family: 'Bebas Neue', sans-serif;
  color: #004774;
  font-size: 32px;
  margin: 0 0 16px;
  letter-spacing: 0.5px;
  line-height: 1.2;
}
.stripe-success-message {
  color: #444;
  font-size: 16px;
  line-height: 1.55;
  margin: 0 0 26px;
}
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
.stripe-success-email {
  color: #555;
  font-size: 14px;
  line-height: 1.5;
  margin: 0 0 28px;
}
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
.stripe-success-home:hover {
  background: #003355;
  text-decoration: none;
  color: #fff;
}
.stripe-success-home:active { transform: translateY(1px); }

@media (max-width: 600px) {
  .stripe-success-wrapper { margin: 16px auto; padding: 0 12px; }
  .stripe-success-card { padding: 28px 20px; }
  .stripe-success-title { font-size: 26px; }
  .stripe-success-message { font-size: 15px; }
  .stripe-success-info { padding: 12px 16px; }
  .stripe-success-info-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  .stripe-success-home { padding: 14px 28px; font-size: 14px; width: 100%; box-sizing: border-box; }
}
</style>
{/literal}

{/block}
