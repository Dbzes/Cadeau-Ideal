<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        $idCart = (int) Tools::getValue('id_cart');
        $key = Tools::getValue('skey');
        if (!$key) { $key = Tools::getValue('key'); }
        $piId = Tools::getValue('payment_intent');
        $redirectStatus = Tools::getValue('redirect_status');

        PrestaShopLogger::addLog(
            sprintf(
                '[Stripe validation] start id_cart=%d pi=%s redirect_status=%s skey=%s request_uri=%s',
                $idCart, $piId, $redirectStatus, $key,
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''
            ),
            1
        );

        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) {
            $this->hardRedirect($this->homeUrl(), 'Panier introuvable id_cart=' . $idCart);
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->hardRedirect($this->homeUrl(), 'Customer introuvable');
        }
        if ($customer->secure_key !== $key) {
            $this->hardRedirect($this->homeUrl(), 'Secure key mismatch');
        }

        // Court-circuit : si la commande existe déjà (webhook l'a créée), on redirige direct vers succès
        $existingOrderId = (int) Order::getIdByCartId((int) $cart->id);
        if ($existingOrderId) {
            PrestaShopLogger::addLog('[Stripe validation] existing order ' . $existingOrderId . ' → success', 1);
            $this->hardRedirect($this->successUrl($existingOrderId, $customer->secure_key), 'redirect to success (existing order)');
        }

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        $stripe = new StripeClient($this->module->getSecretKey());

        if (!$piId) {
            $row = Db::getInstance()->getRow('SELECT payment_intent_id FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE id_cart = ' . $idCart . ' ORDER BY id_stripe_payment DESC LIMIT 1');
            $piId = $row ? $row['payment_intent_id'] : null;
        }
        if (!$piId) {
            $this->hardRedirect($this->homeUrl(), 'PaymentIntent ID absent');
        }

        try {
            $intent = $stripe->retrievePaymentIntent($piId);
        } catch (Exception $e) {
            $this->hardRedirect($this->homeUrl(), 'retrievePaymentIntent failed: ' . $e->getMessage());
        }

        if (!in_array($intent['status'], ['succeeded', 'processing'], true)) {
            $this->hardRedirect($this->homeUrl(), 'Paiement non confirmé statut=' . $intent['status']);
        }

        $idOrder = 0;
        try {
            $idOrder = (int) $this->module->createOrderFromIntent($cart, $intent);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('[Stripe validation] createOrderFromIntent exception: ' . $e->getMessage(), 3);
            $idOrder = (int) Order::getIdByCartId((int) $cart->id);
        }

        if (!$idOrder) {
            $this->hardRedirect($this->homeUrl(), 'Pas d\'id_order après tentatives');
        }

        $order = new Order($idOrder);
        try {
            $stripe->updatePaymentIntent($intent['id'], [
                'description' => 'LCI-C#' . (int) $order->id . '-' . $order->reference,
                'metadata' => [
                    'id_order' => (int) $order->id,
                    'order_reference' => $order->reference,
                ],
            ]);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('[Stripe validation] update PI description failed: ' . $e->getMessage(), 2);
        }

        $this->hardRedirect($this->successUrl((int) $order->id, $customer->secure_key), 'redirect to success (new order ' . $idOrder . ')');
    }

    private function successUrl($idOrder, $secureKey)
    {
        return str_replace('&amp;', '&', $this->context->link->getModuleLink('stripepayment', 'success', [
            'id_order' => (int) $idOrder,
            'skey' => $secureKey,
        ], true));
    }

    private function homeUrl()
    {
        return str_replace('&amp;', '&', $this->context->link->getPageLink('index', true));
    }

    /**
     * Redirect bulletproof : nettoie le buffer de sortie, envoie le header et exit.
     * Contourne les situations où Tools::redirect bascule en meta-refresh à cause
     * d'un output déjà émis (BOM, hook, debug).
     */
    private function hardRedirect($url, $reason = '')
    {
        if ($reason) {
            PrestaShopLogger::addLog('[Stripe validation] hardRedirect: ' . $reason . ' → ' . $url, 1);
        }
        while (ob_get_level() > 0) { @ob_end_clean(); }
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        } else {
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"><script>window.location.replace(' . json_encode($url) . ');</script></head><body></body></html>';
        }
        exit;
    }
}
