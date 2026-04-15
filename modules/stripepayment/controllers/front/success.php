<?php
if (!defined('_PS_VERSION_')) { exit; }

class StripepaymentSuccessModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $auth = false;

    public function initContent()
    {
        parent::initContent();

        $idOrder = (int) Tools::getValue('id_order');
        $key = Tools::getValue('key');

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            Tools::redirect('index.php');
        }

        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer) || $customer->secure_key !== $key) {
            Tools::redirect('index.php');
        }

        $this->context->smarty->assign([
            'order_reference' => $order->reference,
            'order_id' => (int) $order->id,
            'order_total' => Tools::displayPrice((float) $order->total_paid),
            'customer_email' => $customer->email,
            'customer_firstname' => $customer->firstname,
            'home_url' => $this->context->link->getPageLink('index'),
        ]);

        $this->setTemplate('module:stripepayment/views/templates/front/success.tpl');
    }
}
