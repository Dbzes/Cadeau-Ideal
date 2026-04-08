<?php
/**
 * Front controller — sert le fichier _full (HD avec overlay) d'une customization
 * après vérification que le hash appartient bien au panier/guest courant.
 */
class MousepadeditorPreviewModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $hash = Tools::getValue('hash');
        if (!$hash || !preg_match('/^[a-f0-9_]+$/', $hash)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $ctx = Context::getContext();
        if (!$ctx->cart instanceof Cart) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        // Vérifier que ce hash appartient à une customization du panier courant
        $exists = Db::getInstance()->getValue('
            SELECT 1
            FROM ' . _DB_PREFIX_ . 'cart c
            INNER JOIN ' . _DB_PREFIX_ . 'customization cu ON c.id_cart = cu.id_cart
            INNER JOIN ' . _DB_PREFIX_ . 'customized_data cd ON cd.id_customization = cu.id_customization
            WHERE (c.id_customer = ' . (int) $ctx->cart->id_customer . '
               OR c.id_guest = ' . (int) $ctx->cart->id_guest . ')
              AND cd.type = ' . (int) Product::CUSTOMIZE_FILE . '
              AND cd.value = "' . pSQL($hash) . '"
            LIMIT 1
        ');

        if (!$exists) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $file = _PS_UPLOAD_DIR_ . $hash . '_full';
        if (!file_exists($file)) {
            // fallback sur le fichier clean si _full absent
            $file = _PS_UPLOAD_DIR_ . $hash;
            if (!file_exists($file)) {
                header('HTTP/1.1 404 Not Found');
                exit;
            }
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=3600');
        readfile($file);
        exit;
    }
}
