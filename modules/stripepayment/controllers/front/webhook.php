<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentWebhookModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $auth = false;
    public $display_header = false;
    public $display_footer = false;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $payload = @file_get_contents('php://input');
        $sig = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
        $secret = $this->module->getWebhookSecret();

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        try {
            StripeClient::verifyWebhookSignature($payload, $sig, $secret);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            PrestaShopLogger::addLog('Stripe webhook signature invalid: ' . $e->getMessage(), 2);
            exit;
        }

        $event = json_decode($payload, true);
        if (!$event || empty($event['type'])) {
            http_response_code(400); echo '{}'; exit;
        }

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                case 'charge.refunded':
                    $this->handleChargeRefunded($event['data']['object']);
                    break;
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe webhook handler error: ' . $e->getMessage(), 3);
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;
    }

    private function handlePaymentSucceeded(array $pi)
    {
        Db::getInstance()->update('stripe_payment', [
            'status' => pSQL($pi['status']),
        ], 'payment_intent_id = "' . pSQL($pi['id']) . '"');

        // Fallback : créer la commande si validation.php ne l'a pas fait
        // (utilisateur ferme la fenêtre, exception, timeout, etc.)
        $idCart = !empty($pi['metadata']['id_cart']) ? (int) $pi['metadata']['id_cart'] : 0;
        if (!$idCart) { return; }

        $cart = new Cart($idCart);
        if (!Validate::isLoadedObject($cart)) { return; }

        try {
            $idOrder = $this->module->createOrderFromIntent($cart, $pi);
            if ($idOrder) {
                $order = new Order((int) $idOrder);
                $stripe = new StripeClient($this->module->getSecretKey());
                try {
                    $stripe->updatePaymentIntent($pi['id'], [
                        'description' => 'LCI-C#' . (int) $order->id . '-' . $order->reference,
                        'metadata' => [
                            'id_order' => (int) $order->id,
                            'order_reference' => $order->reference,
                        ],
                    ]);
                } catch (Exception $e) {
                    PrestaShopLogger::addLog('Stripe webhook update PI description: ' . $e->getMessage(), 2);
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe webhook order creation error: ' . $e->getMessage(), 3);
        }
    }

    private function handlePaymentFailed(array $pi)
    {
        Db::getInstance()->update('stripe_payment', [
            'status' => pSQL($pi['status']),
        ], 'payment_intent_id = "' . pSQL($pi['id']) . '"');
    }

    private function handleChargeRefunded(array $charge)
    {
        $piId = $charge['payment_intent'];
        $row = Db::getInstance()->getRow('SELECT id_order FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE payment_intent_id = "' . pSQL($piId) . '" LIMIT 1');
        if (!$row || !$row['id_order']) { return; }
        $order = new Order((int) $row['id_order']);
        if (!Validate::isLoadedObject($order)) { return; }

        $totalRefunded = (float) $charge['amount_refunded'] / 100;
        $totalPaid = (float) $charge['amount'] / 100;

        if ($totalRefunded >= $totalPaid) {
            $newState = (int) Configuration::get('PS_OS_REFUND');
            if ($newState && $order->current_state !== $newState) {
                $history = new OrderHistory();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($newState, $order);
                $history->add();
            }
        }
    }
}
