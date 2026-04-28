<?php
/**
 * AJAX endpoint : ajoute / retire la cart rule du module quand le
 * client coche / décoche la case "carton de seconde main".
 */
class CheckoutecologieToggleModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $startTime = microtime(true);
        $active = (bool) (int) Tools::getValue('active');
        $cart = $this->context->cart;
        $cartRuleId = (int) Configuration::get('CECO_CART_RULE_ID');

        $log = [
            'time' => date('c'),
            'active_param' => $active,
            'cart_id' => $cart ? (int) $cart->id : 0,
            'cart_loaded' => $cart ? Validate::isLoadedObject($cart) : false,
            'cart_rule_id' => $cartRuleId,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '?',
            'request_keys' => array_keys($_REQUEST),
        ];

        if (!Validate::isLoadedObject($cart) || !$cartRuleId) {
            $log['stage'] = 'rejected_invalid_state';
            $this->writeLog($log);
            $this->ajaxReturn(['success' => false, 'error' => 'invalid_state', 'debug' => $log]);
        }

        // Snapshot des cart rules avant action — désactive autoAdd pour ne pas
        // perturber l'état avant qu'on ait pris la décision.
        $rulesBefore = [];
        try {
            $rawBefore = $cart->getCartRules(CartRule::FILTER_ACTION_ALL, false);
            foreach ($rawBefore as $cr) {
                $rulesBefore[(int) $cr['id_cart_rule']] = true;
            }
        } catch (\Throwable $e) {
            $log['err_get_rules_before'] = $e->getMessage();
            // fallback : lecture directe DB
            $rawBefore = Db::getInstance()->executeS(
                'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_cart_rule` WHERE id_cart = ' . (int) $cart->id
            ) ?: [];
            foreach ($rawBefore as $cr) {
                $rulesBefore[(int) $cr['id_cart_rule']] = true;
            }
        }
        $log['rules_before'] = array_keys($rulesBefore);

        try {
            if ($active) {
                if (!isset($rulesBefore[$cartRuleId])) {
                    $cr = new CartRule($cartRuleId);
                    if (Validate::isLoadedObject($cr) && $cr->active) {
                        $check = $cr->checkValidity($this->context, false, false);
                        $log['check_validity'] = (true === $check) ? 'ok' : (is_string($check) ? $check : 'unknown');
                        if (true === $check) {
                            $log['action'] = 'addCartRule';
                            $cart->addCartRule($cartRuleId);
                        } else {
                            $log['stage'] = 'rejected_check_failed';
                            $this->writeLog($log);
                            $this->ajaxReturn(['success' => false, 'error' => 'rule_invalid', 'reason' => $log['check_validity']]);
                        }
                    } else {
                        $log['stage'] = 'rejected_rule_inactive_or_unknown';
                        $this->writeLog($log);
                        $this->ajaxReturn(['success' => false, 'error' => 'rule_inactive']);
                    }
                } else {
                    $log['action'] = 'already_present';
                }
            } else {
                if (isset($rulesBefore[$cartRuleId])) {
                    $log['action'] = 'removeCartRule';
                    $cart->removeCartRule($cartRuleId);
                    // Suppression directe DB en sécurité supplémentaire
                    Db::getInstance()->execute(
                        'DELETE FROM `' . _DB_PREFIX_ . 'cart_cart_rule`
                         WHERE id_cart_rule = ' . (int) $cartRuleId . '
                         AND id_cart = ' . (int) $cart->id
                    );
                } else {
                    $log['action'] = 'already_absent';
                }
            }

            $cart->update();
        } catch (\Throwable $e) {
            $log['exception'] = $e->getMessage();
            $log['exception_file'] = $e->getFile() . ':' . $e->getLine();
            $log['stage'] = 'exception';
            $this->writeLog($log);
            $this->ajaxReturn(['success' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
        }

        // Snapshot des cart rules APRÈS action (pour vérifier si auto-add a recreé la rule)
        $rulesAfter = [];
        $rawAfter = Db::getInstance()->executeS(
            'SELECT id_cart_rule FROM `' . _DB_PREFIX_ . 'cart_cart_rule` WHERE id_cart = ' . (int) $cart->id
        ) ?: [];
        foreach ($rawAfter as $cr) {
            $rulesAfter[(int) $cr['id_cart_rule']] = true;
        }
        $log['rules_after'] = array_keys($rulesAfter);
        $log['rule_now_present'] = isset($rulesAfter[$cartRuleId]);
        $log['stage'] = 'success';
        $log['duration_ms'] = round((microtime(true) - $startTime) * 1000, 1);
        $this->writeLog($log);

        $this->ajaxReturn([
            'success' => true,
            'active' => $active,
            'rule_now_present' => isset($rulesAfter[$cartRuleId]),
        ]);
    }

    private function writeLog(array $data)
    {
        @file_put_contents(
            _PS_ROOT_DIR_ . '/var/logs/checkoutecologie_debug.log',
            json_encode($data, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND
        );
    }

    private function ajaxReturn($data)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data);
        exit;
    }
}
