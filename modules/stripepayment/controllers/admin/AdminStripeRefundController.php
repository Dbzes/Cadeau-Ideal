<?php
if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';

class AdminStripeRefundController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        header('Content-Type: application/json');

        $idOrder = (int) Tools::getValue('id_order');
        $amount = (float) Tools::getValue('amount');
        $reason = Tools::getValue('reason', 'requested_by_customer');
        $lineIds = Tools::getValue('line_ids');
        $restock = (int) Tools::getValue('restock');

        if (!in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
            $reason = 'requested_by_customer';
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            $this->respond(['success' => false, 'error' => 'Commande introuvable']);
        }

        $row = Db::getInstance()->getRow('SELECT payment_intent_id, amount, currency FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE id_order = ' . $idOrder . ' ORDER BY id_stripe_payment DESC LIMIT 1');
        if (!$row) {
            $this->respond(['success' => false, 'error' => 'Paiement Stripe introuvable pour cette commande']);
        }

        // Refundable restant
        $alreadyRefunded = (float) Db::getInstance()->getValue('SELECT COALESCE(SUM(amount),0) FROM ' . _DB_PREFIX_ . 'stripe_refund WHERE id_order = ' . $idOrder);
        $maxRefundable = (float) $row['amount'] - $alreadyRefunded;

        if ($amount <= 0) {
            $this->respond(['success' => false, 'error' => 'Montant invalide']);
        }
        if ($amount > $maxRefundable + 0.01) {
            $this->respond(['success' => false, 'error' => sprintf('Montant supérieur au remboursable (%.2f)', $maxRefundable)]);
        }

        /** @var Stripepayment $module */
        $module = Module::getInstanceByName('stripepayment');
        $stripe = new StripeClient($module->getSecretKey());

        try {
            $refund = $stripe->createRefund([
                'payment_intent' => $row['payment_intent_id'],
                'amount' => (int) round($amount * 100),
                'reason' => $reason,
                'metadata' => [
                    'id_order' => $idOrder,
                    'id_employee' => (int) $this->context->employee->id,
                ],
            ]);
        } catch (Exception $e) {
            $this->respond(['success' => false, 'error' => 'Stripe : ' . $e->getMessage()]);
        }

        Db::getInstance()->insert('stripe_refund', [
            'id_order' => $idOrder,
            'id_employee' => (int) $this->context->employee->id,
            'refund_id' => pSQL($refund['id']),
            'payment_intent_id' => pSQL($row['payment_intent_id']),
            'amount' => $amount,
            'currency' => pSQL($row['currency']),
            'reason' => pSQL($reason),
            'status' => pSQL($refund['status']),
            'mode' => pSQL($module->getMode()),
            'details_json' => pSQL(json_encode(['lines' => $lineIds, 'restock' => $restock]), true),
        ]);

        // Restock si demandé + lignes cochées
        if ($restock && is_array($lineIds)) {
            foreach ($lineIds as $idOrderDetail) {
                $od = new OrderDetail((int) $idOrderDetail);
                if (Validate::isLoadedObject($od)) {
                    StockAvailable::updateQuantity(
                        (int) $od->product_id,
                        (int) $od->product_attribute_id,
                        (int) $od->product_quantity,
                        (int) $order->id_shop
                    );
                }
            }
        }

        // Mise à jour statut commande si remboursement total
        $newTotalRefunded = $alreadyRefunded + $amount;
        if ($newTotalRefunded + 0.01 >= (float) $row['amount']) {
            $stateId = (int) Configuration::get('PS_OS_REFUND');
            if ($stateId && (int) $order->current_state !== $stateId) {
                $history = new OrderHistory();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($stateId, $order);
                $history->add();
            }
        }

        $this->respond([
            'success' => true,
            'refund_id' => $refund['id'],
            'amount' => $amount,
            'new_total_refunded' => $newTotalRefunded,
            'remaining' => (float) $row['amount'] - $newTotalRefunded,
        ]);
    }

    private function respond(array $data)
    {
        echo json_encode($data);
        exit;
    }
}
