<?php
/**
 * AJAX endpoint : ajoute / retire la cart rule du module quand le
 * client coche / décoche la case "carton de seconde main".
 */
class CheckoutecologieToggleModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $active = (bool) (int) Tools::getValue('active');
        $cart = $this->context->cart;
        $cartRuleId = (int) Configuration::get('CECO_CART_RULE_ID');

        if (!Validate::isLoadedObject($cart) || !$cartRuleId) {
            $this->ajaxReturn(['success' => false, 'error' => 'invalid_state']);
        }

        $current = [];
        foreach ($cart->getCartRules() as $cr) {
            $current[(int) $cr['id_cart_rule']] = true;
        }

        if ($active) {
            if (!isset($current[$cartRuleId])) {
                $cr = new CartRule($cartRuleId);
                if (Validate::isLoadedObject($cr) && $cr->active) {
                    // Vérifier l'éligibilité (date, minimum, etc.)
                    $check = $cr->checkValidity($this->context, false, false);
                    if (true === $check) {
                        $cart->addCartRule($cartRuleId);
                    } else {
                        $this->ajaxReturn(['success' => false, 'error' => 'rule_invalid', 'reason' => is_string($check) ? $check : 'unknown']);
                    }
                }
            }
        } else {
            if (isset($current[$cartRuleId])) {
                $cart->removeCartRule($cartRuleId);
            }
        }

        $cart->update();

        $this->ajaxReturn([
            'success' => true,
            'active' => $active,
        ]);
    }

    private function ajaxReturn($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
