<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        $idCart = (int) Tools::getValue('id_cart');
        $key = Tools::getValue('key');
        $piId = Tools::getValue('payment_intent');

        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) { $this->redirectToCart('Panier introuvable'); }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer) || $customer->secure_key !== $key) {
            $this->redirectToCart('Accès refusé');
        }

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        $stripe = new StripeClient($this->module->getSecretKey());

        if (!$piId) {
            // Retrieve from DB
            $row = Db::getInstance()->getRow('SELECT payment_intent_id FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE id_cart = ' . $idCart . ' ORDER BY id_stripe_payment DESC LIMIT 1');
            $piId = $row ? $row['payment_intent_id'] : null;
        }
        if (!$piId) { $this->redirectToCart('Transaction introuvable'); }

        try {
            $intent = $stripe->retrievePaymentIntent($piId);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe retrieve PI error: ' . $e->getMessage(), 3);
            $this->redirectToCart('Erreur de vérification du paiement');
        }

        if (!in_array($intent['status'], ['succeeded', 'processing'], true)) {
            $this->redirectToCart('Paiement non confirmé (statut: ' . $intent['status'] . ')');
        }

        // Idempotence : si une commande existe déjà pour ce cart, on redirige
        $existingOrderId = (int) Order::getIdByCartId($idCart);
        if ($existingOrderId) {
            $order = new Order($existingOrderId);
            $this->redirectToConfirmation($cart, $order, $customer);
        }

        $currency = new Currency((int) $cart->id_currency);
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $this->module->validateOrder(
            (int) $cart->id,
            (int) Configuration::get('PS_OS_PAYMENT'),
            $total,
            'Stripe — ' . strtoupper(Tools::getValue('method', 'card')),
            null,
            ['transaction_id' => $intent['id']],
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        $order = new Order((int) $this->module->currentOrder);

        Db::getInstance()->update('stripe_payment', [
            'id_order' => (int) $order->id,
            'status' => pSQL($intent['status']),
        ], 'payment_intent_id = "' . pSQL($intent['id']) . '"');

        $this->redirectToConfirmation($cart, $order, $customer);
    }

    private function redirectToConfirmation(Cart $cart, Order $order, Customer $customer)
    {
        Tools::redirect($this->context->link->getPageLink('order-confirmation', true, null, [
            'id_cart' => (int) $cart->id,
            'id_module' => (int) $this->module->id,
            'id_order' => (int) $order->id,
            'key' => $customer->secure_key,
        ]));
    }

    private function redirectToCart($msg)
    {
        $this->errors[] = $msg;
        Tools::redirect('index.php?controller=order&step=3');
    }
}
