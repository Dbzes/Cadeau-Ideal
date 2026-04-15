<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$cart->id || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $method = Tools::getValue('method', 'card');
        if (!in_array($method, ['card', 'paypal'], true)) { $method = 'card'; }
        if ($method === 'card' && !(int) Configuration::get('STRIPE_ENABLE_CARD')) { $method = 'paypal'; }
        if ($method === 'paypal' && !(int) Configuration::get('STRIPE_ENABLE_PAYPAL')) { $method = 'card'; }

        $currency = new Currency((int) $cart->id_currency);
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $amountCents = (int) round($total * 100);

        $sk = $this->module->getSecretKey();
        $pk = $this->module->getPublicKey();
        if (!$sk || !$pk) {
            $this->errors[] = 'Module Stripe non configuré.';
            $this->setTemplate('module:stripepayment/views/templates/front/error.tpl');
            return;
        }

        require_once _PS_MODULE_DIR_ . 'stripepayment/lib/StripeClient.php';
        $stripe = new StripeClient($sk);

        $params = [
            'amount' => $amountCents,
            'currency' => strtolower($currency->iso_code),
            'capture_method' => 'automatic',
            'payment_method_types' => [$method],
            'metadata' => [
                'id_cart' => (int) $cart->id,
                'id_customer' => (int) $cart->id_customer,
                'email' => $customer->email,
            ],
        ];

        try {
            $intent = $stripe->createPaymentIntent($params);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Stripe create PI error: ' . $e->getMessage(), 3);
            $this->errors[] = 'Erreur lors de la préparation du paiement : ' . $e->getMessage();
            $this->setTemplate('module:stripepayment/views/templates/front/error.tpl');
            return;
        }

        // Persist pending payment
        Db::getInstance()->insert('stripe_payment', [
            'id_cart' => (int) $cart->id,
            'id_customer' => (int) $cart->id_customer,
            'payment_intent_id' => pSQL($intent['id']),
            'client_secret' => pSQL($intent['client_secret']),
            'status' => pSQL($intent['status']),
            'amount' => $amountCents / 100,
            'currency' => pSQL(strtoupper($currency->iso_code)),
            'method' => pSQL($method),
            'mode' => pSQL($this->module->getMode()),
        ]);

        $returnUrl = $this->context->link->getModuleLink($this->module->name, 'validation', [
            'id_cart' => (int) $cart->id,
            'method' => $method,
            'key' => $customer->secure_key,
        ], true);

        $ajaxUrl = $this->context->link->getModuleLink($this->module->name, 'ajax', [
            'id_cart' => (int) $cart->id,
            'key' => $customer->secure_key,
        ], true);

        $homeUrl = $this->context->link->getPageLink('index', true);

        $this->context->smarty->assign([
            'stripe_pk' => $pk,
            'stripe_client_secret' => $intent['client_secret'],
            'stripe_payment_method' => $method,
            'stripe_return_url' => $returnUrl,
            'stripe_ajax_url' => $ajaxUrl,
            'stripe_home_url' => $homeUrl,
            'stripe_amount_display' => Tools::displayPrice($total),
        ]);

        $this->setTemplate('module:stripepayment/views/templates/front/payment.tpl');
    }
}
