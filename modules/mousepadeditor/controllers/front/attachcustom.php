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

            // Copier dans /upload/ avec nom hashé (fichier clean pour impression)
            $hash = md5_file($src) . '_' . substr(md5(microtime(true)), 0, 6);
            $dest = _PS_UPLOAD_DIR_ . $hash;
            copy($src, $dest);

            // Version preview avec template overlay → utilisée pour affichage panier
            $previewSrc = preg_replace('/\.jpg$/', '_preview.jpg', $src);
            $thumbSource = file_exists($previewSrc) ? $previewSrc : $src;

            if (extension_loaded('imagick')) {
                try {
                    $thumb = new Imagick($thumbSource);
                    $thumb->thumbnailImage(300, 0);
                    $thumb->writeImage($dest . '_small');
                    $thumb->clear();
                } catch (Exception $e) {
                    @copy($thumbSource, $dest . '_small');
                }
            } else {
                @copy($thumbSource, $dest . '_small');
            }

            // Copier aussi le preview pleine taille pour le modal
            if (file_exists($previewSrc)) {
                @copy($previewSrc, _PS_UPLOAD_DIR_ . $hash . '_full');
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
            $customization->quantity_refunded = 0;
            $customization->quantity_returned = 0;
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

        // Chercher champ existant via Configuration (évite charset issues avec l'accent)
        $confKey = 'MOUSEPAD_FIELD_' . (int) $pid;
        $fieldId = (int) Configuration::get($confKey);
        if ($fieldId) {
            // Vérifier qu'il existe toujours
            $exists = $db->getValue('SELECT id_customization_field FROM ' . _DB_PREFIX_ . 'customization_field WHERE id_customization_field = ' . $fieldId . ' AND is_deleted = 0');
            if ($exists) {
                // Sync du label (au cas où modifié)
                $db->update('customization_field_lang', ['name' => pSQL('Aperçu de la création')], 'id_customization_field = ' . $fieldId);
                return (int) $fieldId;
            }
        }
        $label = 'Aperçu de la création';

        // Créer le champ
        $db->insert('customization_field', [
            'id_product' => (int) $pid,
            'type' => 1, // file
            'required' => 0,
            'is_module' => 0,
            'is_deleted' => 0,
        ]);
        $newId = (int) $db->Insert_ID();

        // Shop link (table créée manuellement si absente)
        try {
            $db->insert('customization_field_shop', [
                'id_customization_field' => $newId,
                'id_shop' => (int) Context::getContext()->shop->id,
            ]);
        } catch (Exception $e) {
            // table peut ne pas exister sur certaines installs
        }

        // Labels par langue
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
