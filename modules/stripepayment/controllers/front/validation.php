<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        $idCart = (int) Tools::getValue('id_cart');
        $key = Tools::getValue('skey');
        if (!$key) { $key = Tools::getValue('key'); } // backward compat
        $piId = Tools::getValue('payment_intent');
        $redirectStatus = Tools::getValue('redirect_status');

        PrestaShopLogger::addLog(
            sprintf(
                '[Stripe validation] start id_cart=%d pi=%s redirect_status=%s skey=%s key=%s request_uri=%s',
                $idCart, $piId, $redirectStatus,
                Tools::getValue('skey'),
                Tools::getValue('key'),
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''
            ),
            1
        );

        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) {
            return $this->bailToCart('Panier introuvable (id_cart=' . $idCart . ')');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return $this->bailToCart('Client introuvable');
        }
        if ($customer->secure_key !== $key) {
            return $this->bailToCart('Secure key mismatch (expected=' . $customer->secure_key . ' got=' . $key . ')');
        }

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        $stripe = new StripeClient($this->module->getSecretKey());

        if (!$piId) {
            $row = Db::getInstance()->getRow('SELECT payment_intent_id FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE id_cart = ' . $idCart . ' ORDER BY id_stripe_payment DESC LIMIT 1');
            $piId = $row ? $row['payment_intent_id'] : null;
        }

        // Si la commande existe déjà (webhook l'a créée) → aller directement à la page de succès
        $existingOrderId = (int) Order::getIdByCartId((int) $cart->id);
        if ($existingOrderId) {
            PrestaShopLogger::addLog('[Stripe validation] order already exists id=' . $existingOrderId . ' → success', 1);
            return $this->redirectToSuccess($existingOrderId, $customer->secure_key);
        }

        if (!$piId) {
            return $this->bailToCart('PaymentIntent ID absent');
        }

        try {
            $intent = $stripe->retrievePaymentIntent($piId);
        } catch (Exception $e) {
            return $this->bailToCart('Stripe retrievePaymentIntent failed: ' . $e->getMessage());
        }

        if (!in_array($intent['status'], ['succeeded', 'processing'], true)) {
            return $this->bailToCart('Paiement non confirmé (statut Stripe: ' . $intent['status'] . ')');
        }

        try {
            $idOrder = $this->module->createOrderFromIntent($cart, $intent);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('[Stripe validation] createOrderFromIntent exception: ' . $e->getMessage(), 3);
            $idOrder = (int) Order::getIdByCartId((int) $cart->id);
            if (!$idOrder) {
                return $this->bailToCart('Création commande échouée: ' . $e->getMessage());
            }
        }

        if (!$idOrder) {
            return $this->bailToCart('Création commande : id_order vide après validateOrder');
        }

        $order = new Order((int) $idOrder);

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

        return $this->redirectToSuccess((int) $order->id, $customer->secure_key);
    }

    private function redirectToSuccess($idOrder, $secureKey)
    {
        $url = str_replace('&amp;', '&', $this->context->link->getModuleLink('stripepayment', 'success', [
            'id_order' => (int) $idOrder,
            'skey' => $secureKey,
        ], true));
        PrestaShopLogger::addLog('[Stripe validation] redirect → ' . $url, 1);
        Tools::redirect($url);
    }

    private function bailToCart($reason)
    {
        PrestaShopLogger::addLog('[Stripe validation] bail: ' . $reason, 3);
        Tools::redirect('index.php?controller=order&step=3');
    }
}
