<?php
/**
 * Front controller — sert le fichier _full (HD avec overlay) d'une customization
 */
class MousepadeditorPreviewModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function init()
    {
        @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' ENTER init hash=' . Tools::getValue('hash') . PHP_EOL, FILE_APPEND);

        try {
            parent::init();
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' after parent::init' . PHP_EOL, FILE_APPEND);

            $hash = (string) Tools::getValue('hash');
            if ($hash === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $hash)) {
                return $this->serveText(404, 'invalid hash: ' . $hash);
            }

            $ctx = Context::getContext();
            $cart = ($ctx && $ctx->cart instanceof Cart) ? $ctx->cart : null;
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' cart='
                . ($cart ? ($cart->id . ' customer=' . (int)$cart->id_customer . ' guest=' . (int)$cart->id_guest) : 'NULL') . PHP_EOL, FILE_APPEND);

            if (!$cart) {
                return $this->serveText(403, 'no cart');
            }

            $sql = 'SELECT 1
                FROM ' . _DB_PREFIX_ . 'cart c
                INNER JOIN ' . _DB_PREFIX_ . 'customization cu ON c.id_cart = cu.id_cart
                INNER JOIN ' . _DB_PREFIX_ . 'customized_data cd ON cd.id_customization = cu.id_customization
                WHERE (c.id_customer = ' . (int) $cart->id_customer . '
                   OR c.id_guest = ' . (int) $cart->id_guest . ')
                  AND cd.type = 0
                  AND cd.value = \'' . pSQL($hash) . '\'
                LIMIT 1';
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' SQL=[' . $sql . ']' . PHP_EOL, FILE_APPEND);
            $exists = Db::getInstance()->getValue($sql);
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' exists=' . var_export($exists, true) . PHP_EOL, FILE_APPEND);

            if (!$exists) {
                return $this->serveText(403, 'hash not in cart');
            }

            $file = _PS_UPLOAD_DIR_ . $hash . '_full';
            if (!file_exists($file)) {
                $file = _PS_UPLOAD_DIR_ . $hash;
                if (!file_exists($file)) {
                    return $this->serveText(404, 'file missing');
                }
            }

            while (ob_get_level() > 0) { @ob_end_clean(); }
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: private, max-age=3600');
            readfile($file);
            exit;
        } catch (\Throwable $e) {
            @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' EX: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL, FILE_APPEND);
            while (ob_get_level() > 0) { @ob_end_clean(); }
            header('HTTP/1.1 500 Internal Server Error');
            echo 'MPE exception: ' . $e->getMessage();
            exit;
        }
    }

    protected function serveText($code, $msg)
    {
        @file_put_contents('/tmp/mpe_preview.log', date('H:i:s') . ' FAIL ' . $code . ' ' . $msg . PHP_EOL, FILE_APPEND);
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('HTTP/1.1 ' . $code . ' Error');
        echo $msg;
        exit;
    }
}
