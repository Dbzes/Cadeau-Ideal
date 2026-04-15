<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentAjaxModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $auth = false;
    public $display_header = false;
    public $display_footer = false;

    public function postProcess()
    {
        header('Content-Type: application/json');

        try {
            echo json_encode($this->handle());
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe ajax finalize error: ' . $e->getMessage(), 3);
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    public function initContent() { $this->postProcess(); }

    private function handle()
    {
        $idCart = (int) Tools::getValue('id_cart');
        $key = Tools::getValue('key');
        $piId = Tools::getValue('payment_intent');

        if (!$idCart || !$key || !$piId) {
            throw new Exception('Paramètres manquants');
        }

        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) {
            throw new Exception('Panier introuvable');
        }

        $customer = new Customer((int) $cart->id_customer);
        if (!Validate::isLoadedObject($customer) || $customer->secure_key !== $key) {
            throw new Exception('Accès refusé');
        }

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        $stripe = new StripeClient($this->module->getSecretKey());
        $intent = $stripe->retrievePaymentIntent($piId);

        if (!in_array($intent['status'], ['succeeded', 'processing'], true)) {
            throw new Exception('Paiement non confirmé (statut: ' . $intent['status'] . ')');
        }

        $idOrder = $this->module->createOrderFromIntent($cart, $intent);
        if (!$idOrder) {
            throw new Exception('Création de commande impossible');
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
            PrestaShopLogger::addLog('Stripe ajax update PI description: ' . $e->getMessage(), 2);
        }

        return [
            'success' => true,
            'id_order' => (int) $order->id,
            'reference' => $order->reference,
            'total' => Tools::displayPrice((float) $order->total_paid),
            'email' => $customer->email,
            'firstname' => $customer->firstname,
        ];
    }
}
