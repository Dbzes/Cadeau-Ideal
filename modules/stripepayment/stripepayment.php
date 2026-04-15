<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once __DIR__ . '/lib/StripeClient.php';

class Stripepayment extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'stripepayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Cadeau Idéal';
        $this->controllers = ['payment', 'validation', 'webhook'];
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'Stripe Payment';
        $this->description = 'Paiement Stripe — Carte bancaire + PayPal, remboursements depuis le BO.';
        $this->confirmUninstall = 'Êtes-vous sûr ? Les clés et l\'historique seront supprimés.';
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayAdminOrderMainBottom')
            || !$this->registerHook('header')
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->installSql()
            || !$this->installAdminTab()
        ) {
            return false;
        }

        Configuration::updateValue('STRIPE_MODE', 'test');
        Configuration::updateValue('STRIPE_TEST_PK', '');
        Configuration::updateValue('STRIPE_TEST_SK', '');
        Configuration::updateValue('STRIPE_TEST_WHSEC', '');
        Configuration::updateValue('STRIPE_LIVE_PK', '');
        Configuration::updateValue('STRIPE_LIVE_SK', '');
        Configuration::updateValue('STRIPE_LIVE_WHSEC', '');
        Configuration::updateValue('STRIPE_ENABLE_CARD', 1);
        Configuration::updateValue('STRIPE_ENABLE_PAYPAL', 1);

        return true;
    }

    private function installAdminTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminStripeRefund';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Stripe Refund';
        }
        $tab->id_parent = -1; // hidden
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallAdminTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminStripeRefund');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    public function uninstall()
    {
        $this->uninstallAdminTab();
        $this->uninstallSql();
        foreach (['STRIPE_MODE', 'STRIPE_TEST_PK', 'STRIPE_TEST_SK', 'STRIPE_TEST_WHSEC',
                 'STRIPE_LIVE_PK', 'STRIPE_LIVE_SK', 'STRIPE_LIVE_WHSEC',
                 'STRIPE_ENABLE_CARD', 'STRIPE_ENABLE_PAYPAL'] as $k) {
            Configuration::deleteByName($k);
        }
        return parent::uninstall();
    }

    private function installSql()
    {
        $sql = file_get_contents(__DIR__ . '/sql/install.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
            if (!Db::getInstance()->execute($q)) { return false; }
        }
        return true;
    }

    private function uninstallSql()
    {
        $sql = file_get_contents(__DIR__ . '/sql/uninstall.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
            Db::getInstance()->execute($q);
        }
        return true;
    }

    // ---- CONFIG PAGE BO ----
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit_stripe_config')) {
            Configuration::updateValue('STRIPE_MODE', Tools::getValue('STRIPE_MODE') === 'live' ? 'live' : 'test');
            Configuration::updateValue('STRIPE_TEST_PK', trim(Tools::getValue('STRIPE_TEST_PK')));
            Configuration::updateValue('STRIPE_TEST_SK', trim(Tools::getValue('STRIPE_TEST_SK')));
            Configuration::updateValue('STRIPE_TEST_WHSEC', trim(Tools::getValue('STRIPE_TEST_WHSEC')));
            Configuration::updateValue('STRIPE_LIVE_PK', trim(Tools::getValue('STRIPE_LIVE_PK')));
            Configuration::updateValue('STRIPE_LIVE_SK', trim(Tools::getValue('STRIPE_LIVE_SK')));
            Configuration::updateValue('STRIPE_LIVE_WHSEC', trim(Tools::getValue('STRIPE_LIVE_WHSEC')));
            Configuration::updateValue('STRIPE_ENABLE_CARD', (int) Tools::getValue('STRIPE_ENABLE_CARD'));
            Configuration::updateValue('STRIPE_ENABLE_PAYPAL', (int) Tools::getValue('STRIPE_ENABLE_PAYPAL'));
            $output .= $this->displayConfirmation('Configuration enregistrée.');
        }

        $webhookUrl = $this->context->link->getModuleLink($this->name, 'webhook', [], true);

        $mode = Configuration::get('STRIPE_MODE') ?: 'test';
        $fields = [
            'MODE' => $mode,
            'TEST_PK' => Configuration::get('STRIPE_TEST_PK'),
            'TEST_SK' => Configuration::get('STRIPE_TEST_SK'),
            'TEST_WHSEC' => Configuration::get('STRIPE_TEST_WHSEC'),
            'LIVE_PK' => Configuration::get('STRIPE_LIVE_PK'),
            'LIVE_SK' => Configuration::get('STRIPE_LIVE_SK'),
            'LIVE_WHSEC' => Configuration::get('STRIPE_LIVE_WHSEC'),
            'ENABLE_CARD' => (int) Configuration::get('STRIPE_ENABLE_CARD'),
            'ENABLE_PAYPAL' => (int) Configuration::get('STRIPE_ENABLE_PAYPAL'),
            'WEBHOOK_URL' => $webhookUrl,
            'FORM_ACTION' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
        ];

        $this->context->smarty->assign($fields);
        return $output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    // ---- HOOK header : inject Stripe.js + public key on checkout ----
    public function hookHeader($params)
    {
        if ($this->context->controller->php_self !== 'order') { return; }
        $pk = $this->getPublicKey();
        if (!$pk) { return; }
        Media::addJsDef([
            'stripePaymentConfig' => [
                'pk' => $pk,
                'createIntentUrl' => $this->context->link->getModuleLink($this->name, 'payment', ['action' => 'createIntent'], true),
                'validationUrl' => $this->context->link->getModuleLink($this->name, 'validation', [], true),
            ],
        ]);
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller->php_self !== 'order') { return; }
        $this->context->controller->registerJavascript(
            'stripe-js-v3',
            'https://js.stripe.com/v3/',
            ['server' => 'remote', 'position' => 'head', 'priority' => 100]
        );
    }

    // ---- HOOK paymentOptions : 2 choix CB + PayPal ----
    public function hookPaymentOptions($params)
    {
        if (!$this->active || !$this->getPublicKey() || !$this->getSecretKey()) { return []; }
        $options = [];

        if ((int) Configuration::get('STRIPE_ENABLE_CARD')) {
            $card = new PaymentOption();
            $card->setCallToActionText('Carte bancaire')
                ->setModuleName($this->name)
                ->setAction($this->context->link->getModuleLink($this->name, 'payment', ['method' => 'card'], true))
                ->setAdditionalInformation($this->fetch('module:stripepayment/views/templates/hook/option_card.tpl'));
            $options[] = $card;
        }

        if ((int) Configuration::get('STRIPE_ENABLE_PAYPAL')) {
            $pp = new PaymentOption();
            $pp->setCallToActionText('PayPal')
                ->setModuleName($this->name)
                ->setAction($this->context->link->getModuleLink($this->name, 'payment', ['method' => 'paypal'], true))
                ->setAdditionalInformation($this->fetch('module:stripepayment/views/templates/hook/option_paypal.tpl'));
            $options[] = $pp;
        }

        return $options;
    }

    // ---- HOOK BO order : bouton remboursement ----
    public function hookDisplayAdminOrderMainBottom($params)
    {
        $idOrder = (int) $params['id_order'];
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order) || $order->module !== $this->name) { return ''; }

        $row = Db::getInstance()->getRow('SELECT payment_intent_id, amount, currency FROM ' . _DB_PREFIX_ . 'stripe_payment WHERE id_order = ' . $idOrder . ' ORDER BY id_stripe_payment DESC LIMIT 1');
        if (!$row) { return ''; }

        $refunds = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'stripe_refund WHERE id_order = ' . $idOrder . ' ORDER BY created_at DESC');
        $refundedTotal = 0;
        foreach ($refunds as $r) { $refundedTotal += (float) $r['amount']; }

        $products = OrderDetail::getList($idOrder);
        $lines = [];
        foreach ($products as $p) {
            $lines[] = [
                'id' => (int) $p['id_order_detail'],
                'name' => $p['product_name'],
                'qty' => (int) $p['product_quantity'],
                'price' => (float) $p['unit_price_tax_incl'],
                'line_total' => (float) $p['total_price_tax_incl'],
            ];
        }

        $this->context->smarty->assign([
            'stripe_order_id' => $idOrder,
            'stripe_pi' => $row['payment_intent_id'],
            'stripe_amount' => (float) $row['amount'],
            'stripe_currency' => $row['currency'] ?: 'EUR',
            'stripe_refunded' => $refundedTotal,
            'stripe_refundable' => (float) $row['amount'] - $refundedTotal,
            'stripe_shipping' => (float) $order->total_shipping_tax_incl,
            'stripe_refunds' => $refunds,
            'stripe_lines' => $lines,
            'stripe_refund_url' => $this->context->link->getAdminLink('AdminStripeRefund'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_order_refund.tpl');
    }

    // ---- Utils ----
    public function getMode() { return Configuration::get('STRIPE_MODE') === 'live' ? 'live' : 'test'; }
    public function getPublicKey() { return $this->getMode() === 'live' ? Configuration::get('STRIPE_LIVE_PK') : Configuration::get('STRIPE_TEST_PK'); }
    public function getSecretKey() { return $this->getMode() === 'live' ? Configuration::get('STRIPE_LIVE_SK') : Configuration::get('STRIPE_TEST_SK'); }
    public function getWebhookSecret() { return $this->getMode() === 'live' ? Configuration::get('STRIPE_LIVE_WHSEC') : Configuration::get('STRIPE_TEST_WHSEC'); }
}
