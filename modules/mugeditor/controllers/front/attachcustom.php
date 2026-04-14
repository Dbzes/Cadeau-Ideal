<?php
/**
 * Front controller AJAX — attache une customization au panier (Mug)
 */
class MugeditorAttachcustomModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        try {
            $pid = (int) Tools::getValue('id_product');
            $stateJson = Tools::getValue('state_json');
            $lowres = Tools::getValue('lowres');

            if (!$pid || !$stateJson) {
                throw new Exception('Paramètres manquants');
            }

            $fieldId = $this->ensureCustomField($pid);

            $hash = md5($stateJson . microtime(true)) . '_' . substr(md5(uniqid('', true)), 0, 6);
            $dest = _PS_UPLOAD_DIR_ . $hash;

            if ($lowres && strpos($lowres, 'data:') === 0) {
                $parts = explode(',', $lowres, 2);
                if (count($parts) === 2) {
                    $binary = base64_decode($parts[1]);
                    if ($binary !== false) {
                        file_put_contents($dest, $binary);
                        file_put_contents($dest . '_small', $binary);
                    }
                }
            }

            if (!file_exists($dest)) {
                $placeholder = _PS_MODULE_DIR_ . 'mugeditor/views/img/placeholder.jpg';
                if (file_exists($placeholder)) {
                    copy($placeholder, $dest);
                    copy($placeholder, $dest . '_small');
                } else {
                    file_put_contents($dest, '');
                    file_put_contents($dest . '_small', '');
                }
            }

            $ctx = Context::getContext();
            if (empty($ctx->cart->id)) {
                $ctx->cart->id_lang = $ctx->language->id;
                $ctx->cart->id_currency = $ctx->currency->id;
                $ctx->cart->id_guest = $ctx->cookie->id_guest ? $ctx->cookie->id_guest : 0;
                $ctx->cart->id_shop_group = $ctx->shop->id_shop_group;
                $ctx->cart->id_shop = $ctx->shop->id;
                if ($ctx->customer->id) { $ctx->cart->id_customer = $ctx->customer->id; }
                $ctx->cart->add();
                $ctx->cookie->id_cart = (int) $ctx->cart->id;
                $ctx->cookie->write();
            }

            $customization = new Customization();
            $customization->id_cart = (int) $ctx->cart->id;
            $customization->id_product = $pid;
            $customization->id_product_attribute = 0;
            $customization->id_address_delivery = 0;
            $customization->quantity = 0;
            $customization->quantity_refunded = 0;
            $customization->quantity_returned = 0;
            $customization->in_cart = 0;
            $customization->add();

            Db::getInstance()->insert('customized_data', [
                'id_customization' => (int) $customization->id,
                'type' => 0,
                'index' => (int) $fieldId,
                'value' => pSQL($hash),
            ]);

            Db::getInstance()->insert('mue_compose_queue', [
                'id_customization' => (int) $customization->id,
                'hash' => pSQL($hash),
                'state_json' => pSQL($stateJson, true),
                'status' => 'pending',
            ]);

            echo json_encode([
                'success' => true,
                'id_customization' => (int) $customization->id,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    protected function ensureCustomField($pid)
    {
        $db = Db::getInstance();

        $db->update('product', ['customizable' => 2, 'uploadable_files' => 1], 'id_product = ' . (int) $pid);
        $db->update('product_shop', ['customizable' => 2, 'uploadable_files' => 1], 'id_product = ' . (int) $pid);

        $confKey = 'MUG_FIELD_' . (int) $pid;
        $fieldId = (int) Configuration::get($confKey);
        if ($fieldId) {
            $exists = $db->getValue('SELECT id_customization_field FROM ' . _DB_PREFIX_ . 'customization_field WHERE id_customization_field = ' . $fieldId . ' AND is_deleted = 0');
            if ($exists) {
                $db->update('customization_field_lang', ['name' => pSQL('Aperçu de la création')], 'id_customization_field = ' . $fieldId);
                return (int) $fieldId;
            }
        }

        $label = 'Aperçu de la création';

        $db->insert('customization_field', [
            'id_product' => (int) $pid,
            'type' => 0,
            'required' => 0,
            'is_module' => 0,
            'is_deleted' => 0,
        ]);
        $newId = (int) $db->Insert_ID();

        try {
            $db->insert('customization_field_shop', [
                'id_customization_field' => $newId,
                'id_shop' => (int) Context::getContext()->shop->id,
            ]);
        } catch (Exception $e) {}

        foreach (Language::getLanguages() as $lang) {
            $db->insert('customization_field_lang', [
                'id_customization_field' => $newId,
                'id_lang' => (int) $lang['id_lang'],
                'id_shop' => (int) Context::getContext()->shop->id,
                'name' => pSQL($label),
            ]);
        }

        Configuration::updateValue($confKey, $newId);
        return $newId;
    }
}
