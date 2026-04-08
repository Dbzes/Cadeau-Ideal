<?php
/**
 * Front controller AJAX — attache l'aperçu HD à une customization PrestaShop
 */
class MousepadeditorAttachcustomModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        @file_put_contents('/tmp/mpe_attach.log', '--- attach ' . date('H:i:s') . ' ---' . PHP_EOL, FILE_APPEND);
        try {
            $pid = (int) Tools::getValue('id_product');
            $hdUrl = Tools::getValue('hd_url');
            if (!$pid || !$hdUrl) {
                throw new Exception('Paramètres manquants');
            }

            // Résoudre le fichier HD local
            $src = $this->resolveLocal($hdUrl);
            if (!$src || !file_exists($src)) {
                throw new Exception('Aperçu HD introuvable');
            }

            @file_put_contents('/tmp/mpe_attach.log', 'pid=' . $pid . ' hdUrl=' . $hdUrl . PHP_EOL, FILE_APPEND);
            // S'assurer que le produit a un champ de personnalisation
            $fieldId = $this->ensureCustomField($pid);
            @file_put_contents('/tmp/mpe_attach.log', 'fieldId=' . $fieldId . PHP_EOL, FILE_APPEND);

            // Copier dans /upload/ avec nom hashé
            $hash = md5_file($src) . '_' . substr(md5(microtime(true)), 0, 6);
            $dest = _PS_UPLOAD_DIR_ . $hash;
            copy($src, $dest);

            // Créer un thumbnail pour affichage panier/commande
            if (extension_loaded('imagick')) {
                try {
                    $thumb = new Imagick($src);
                    $thumb->thumbnailImage(300, 0);
                    $thumb->writeImage($dest . '_small');
                    $thumb->clear();
                } catch (Exception $e) {
                    @copy($src, $dest . '_small');
                }
            } else {
                @copy($src, $dest . '_small');
            }

            // Contexte / panier
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

            @file_put_contents('/tmp/mpe_attach.log', 'cart=' . $ctx->cart->id . PHP_EOL, FILE_APPEND);
            // Créer la customization
            $customization = new Customization();
            $customization->id_cart = (int) $ctx->cart->id;
            $customization->id_product = $pid;
            $customization->id_product_attribute = 0;
            $customization->id_address_delivery = 0;
            $customization->quantity = 0;
            $customization->in_cart = 0;
            $customization->add();
            @file_put_contents('/tmp/mpe_attach.log', 'customization added id=' . $customization->id . PHP_EOL, FILE_APPEND);

            // Ajouter l'entrée customized_data
            Db::getInstance()->insert('customized_data', [
                'id_customization' => (int) $customization->id,
                'type' => 1, // file
                'index' => (int) $fieldId,
                'value' => pSQL($hash),
            ]);

            echo json_encode([
                'success' => true,
                'id_customization' => (int) $customization->id,
            ]);
        } catch (Exception $e) {
            @file_put_contents('/tmp/mpe_attach.log', 'EXCEPTION: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        // Aussi capturer les erreurs DB silencieuses
        $dbErr = Db::getInstance()->getMsgError();
        if ($dbErr) {
            @file_put_contents('/tmp/mpe_attach.log', 'DB ERROR: ' . $dbErr . PHP_EOL, FILE_APPEND);
        }
        exit;
    }

    protected function resolveLocal($url)
    {
        $parsed = parse_url($url);
        $path = isset($parsed['path']) ? $parsed['path'] : $url;
        if (strpos($path, '/modules/mousepadeditor/') !== false) {
            $rel = substr($path, strpos($path, '/modules/mousepadeditor/') + strlen('/modules/mousepadeditor/'));
            return _PS_MODULE_DIR_ . 'mousepadeditor/' . $rel;
        }
        if (strpos($path, '/') === 0) {
            return _PS_ROOT_DIR_ . $path;
        }
        return null;
    }

    protected function ensureCustomField($pid)
    {
        $db = Db::getInstance();

        // Activer customizable
        $db->update('product', ['customizable' => 2, 'uploadable_files' => 1], 'id_product = ' . (int) $pid);
        $db->update('product_shop', ['customizable' => 2, 'uploadable_files' => 1], 'id_product = ' . (int) $pid);

        // Chercher champ existant "Aperçu personnalisé"
        $label = 'Aperçu personnalisé';
        $fieldId = $db->getValue('
            SELECT cf.id_customization_field
            FROM ' . _DB_PREFIX_ . 'customization_field cf
            JOIN ' . _DB_PREFIX_ . 'customization_field_lang cfl ON cf.id_customization_field = cfl.id_customization_field
            WHERE cf.id_product = ' . (int) $pid . '
              AND cf.type = 1
              AND cfl.name = \'' . pSQL($label) . '\'
            LIMIT 1
        ');

        if ($fieldId) return (int) $fieldId;

        // Créer le champ
        $db->insert('customization_field', [
            'id_product' => (int) $pid,
            'type' => 1, // file
            'required' => 0,
            'is_module' => 0,
            'is_deleted' => 0,
        ]);
        $newId = (int) $db->Insert_ID();

        // product_shop link
        $db->insert('customization_field_shop', [
            'id_customization_field' => $newId,
            'id_shop' => (int) Context::getContext()->shop->id,
        ]);

        // Labels par langue
        foreach (Language::getLanguages() as $lang) {
            $db->insert('customization_field_lang', [
                'id_customization_field' => $newId,
                'id_lang' => (int) $lang['id_lang'],
                'id_shop' => (int) Context::getContext()->shop->id,
                'name' => pSQL($label),
            ]);
        }

        return $newId;
    }
}
