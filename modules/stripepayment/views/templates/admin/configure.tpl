<div class="panel">
  <div class="panel-heading"><i class="icon-credit-card"></i> Configuration Stripe</div>
  <form method="post" action="{$FORM_ACTION}">
    <div class="form-group">
      <label><strong>Mode</strong></label>
      <select name="STRIPE_MODE" class="form-control" style="max-width:300px;">
        <option value="test" {if $MODE == 'test'}selected{/if}>Test (bac à sable)</option>
        <option value="live" {if $MODE == 'live'}selected{/if}>Production (Live)</option>
      </select>
      <p class="help-block">Les clés utilisées sont celles du mode sélectionné.</p>
    </div>

    <fieldset style="border:1px solid #eee;padding:15px;margin-bottom:20px;">
      <legend style="width:auto;padding:0 8px;">Clés TEST</legend>
      <div class="form-group">
        <label>Clé publique (pk_test_…)</label>
        <input type="text" name="STRIPE_TEST_PK" value="{$TEST_PK|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="form-group">
        <label>Clé secrète (sk_test_…)</label>
        <input type="password" name="STRIPE_TEST_SK" value="{$TEST_SK|escape:'htmlall'}" class="form-control" autocomplete="off" />
      </div>
      <div class="form-group">
        <label>Secret webhook TEST (whsec_…)</label>
        <input type="password" name="STRIPE_TEST_WHSEC" value="{$TEST_WHSEC|escape:'htmlall'}" class="form-control" autocomplete="off" />
      </div>
    </fieldset>

    <fieldset style="border:1px solid #eee;padding:15px;margin-bottom:20px;">
      <legend style="width:auto;padding:0 8px;">Clés LIVE</legend>
      <div class="form-group">
        <label>Clé publique (pk_live_…)</label>
        <input type="text" name="STRIPE_LIVE_PK" value="{$LIVE_PK|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="form-group">
        <label>Clé secrète (sk_live_…)</label>
        <input type="password" name="STRIPE_LIVE_SK" value="{$LIVE_SK|escape:'htmlall'}" class="form-control" autocomplete="off" />
      </div>
      <div class="form-group">
        <label>Secret webhook LIVE (whsec_…)</label>
        <input type="password" name="STRIPE_LIVE_WHSEC" value="{$LIVE_WHSEC|escape:'htmlall'}" class="form-control" autocomplete="off" />
      </div>
    </fieldset>

    <div class="form-group">
      <label>Méthodes de paiement activées</label>
      <div><label><input type="checkbox" name="STRIPE_ENABLE_CARD" value="1" {if $ENABLE_CARD}checked{/if}> Carte bancaire</label></div>
      <div><label><input type="checkbox" name="STRIPE_ENABLE_PAYPAL" value="1" {if $ENABLE_PAYPAL}checked{/if}> PayPal (à activer côté dashboard Stripe aussi)</label></div>
    </div>

    <div class="form-group">
      <label><strong>URL Webhook à configurer dans Stripe</strong></label>
      <input type="text" value="{$WEBHOOK_URL|escape:'htmlall'}" readonly class="form-control" onclick="this.select();" />
      <p class="help-block">Dans le dashboard Stripe : Developers → Webhooks → Add endpoint. Événements à écouter : <code>payment_intent.succeeded</code>, <code>payment_intent.payment_failed</code>, <code>charge.refunded</code>.</p>
    </div>

    <button type="submit" name="submit_stripe_config" class="btn btn-primary">Enregistrer</button>
  </form>
</div>
