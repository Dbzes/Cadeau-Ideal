<?php
/**
 * Front controller — sert le fichier _full (HD avec overlay) d'une customization
 * après vérification que le hash appartient bien au panier/guest courant.
 */
class MousepadeditorPreviewModuleFrontController extends ModuleFrontController
{
    public $ajax = true;
    public $ssl = false;

    public function init()
    {
        try {
            $hash = Tools::getValue('hash');
            if (!$hash || !preg_match('/^[a-zA-Z0-9_]+$/', $hash)) {
                $this->fail(404, 'invalid hash');
            }

            $ctx = Context::getContext();
            if (!$ctx || !($ctx->cart instanceof Cart)) {
                $this->fail(403, 'no cart context');
            }

            // Vérifier que ce hash appartient à une customization du panier courant
            $sql = 'SELECT 1
                FROM ' . _DB_PREFIX_ . 'cart c
                INNER JOIN ' . _DB_PREFIX_ . 'customization cu ON c.id_cart = cu.id_cart
                INNER JOIN ' . _DB_PREFIX_ . 'customized_data cd ON cd.id_customization = cu.id_customization
                WHERE (c.id_customer = ' . (int) $ctx->cart->id_customer . '
                   OR c.id_guest = ' . (int) $ctx->cart->id_guest . ')
                  AND cd.type = 0
                  AND cd.value = "' . pSQL($hash) . '"
                LIMIT 1';
            $exists = Db::getInstance()->getValue($sql);

            if (!$exists) {
                $this->fail(403, 'hash not in current cart');
            }

            $file = _PS_UPLOAD_DIR_ . $hash . '_full';
            if (!file_exists($file)) {
                $file = _PS_UPLOAD_DIR_ . $hash;
                if (!file_exists($file)) {
                    $this->fail(404, 'file not found');
                }
            }

            while (ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: private, max-age=3600');
            readfile($file);
            exit;
        } catch (Exception $e) {
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' EX: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            $this->fail(500, 'exception: ' . $e->getMessage());
        }
    }

    protected function fail($code, $msg = '')
    {
        @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' FAIL ' . $code . ' ' . $msg . PHP_EOL, FILE_APPEND);
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('HTTP/1.1 ' . $code . ' ' . ($code === 403 ? 'Forbidden' : ($code === 404 ? 'Not Found' : 'Error')));
        echo $msg;
        exit;
    }
}
