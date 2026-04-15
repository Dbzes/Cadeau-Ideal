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

        try {
            $idOrder = $this->module->createOrderFromIntent($cart, $intent);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe createOrderFromIntent error (validation): ' . $e->getMessage(), 3);
            $this->redirectToCart('Erreur lors de la création de la commande. Notre équipe a été notifiée.');
        }

        if (!$idOrder) {
            $this->redirectToCart('Création de commande impossible');
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
            PrestaShopLogger::addLog('Stripe update PI description error: ' . $e->getMessage(), 2);
        }

        Tools::redirect($this->context->link->getModuleLink('stripepayment', 'success', [
            'id_order' => (int) $order->id,
            'key' => $customer->secure_key,
        ], true));
    }

    private function redirectToCart($msg)
    {
        $this->errors[] = $msg;
        Tools::redirect('index.php?controller=order&step=3');
    }
}
