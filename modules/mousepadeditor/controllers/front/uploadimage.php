<?php
/**
 * Front controller AJAX — upload d'une image canvas client
 */
class MousepadeditorUploadimageModuleFrontController extends ModuleFrontController
{
    const MAX_SIZE = 5242880; // 5 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];

    public $ajax = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        try {
            if (empty($_FILES['file'])) {
                throw new Exception('Aucun fichier reçu');
            }
            $f = $_FILES['file'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erreur upload (' . $f['error'] . ')');
            }
            if ($f['size'] > self::MAX_SIZE) {
                throw new Exception('Fichier trop volumineux (max 5 Mo)');
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext === 'jpeg') $ext = 'jpg';
            if (!in_array($ext, self::ALLOWED)) {
                throw new Exception('Format non autorisé');
            }

            $key = $this->getCustomerKey();
            $dir = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/customer/' . $key . '/images/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);

            $name = uniqid('img_', true) . '.' . $ext;
            $dest = $dir . $name;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                throw new Exception('Échec écriture fichier');
            }

            // Réduire à 2000px max si nécessaire
            if (extension_loaded('imagick')) {
                try {
                    $im = new \Imagick($dest);
                    $w = $im->getImageWidth();
                    $h = $im->getImageHeight();
                    if ($w > 2000 || $h > 2000) {
                        $im->thumbnailImage(2000, 2000, true);
                        $im->setImageCompressionQuality(92);
                        $im->stripImage();
                        $im->writeImage($dest);
                    }
                    $im->clear();
                } catch (\Exception $e) {}
            }

            echo json_encode([
                'success' => true,
                'url' => _MODULE_DIR_ . 'mousepadeditor/uploads/customer/' . $key . '/images/' . $name,
                'name' => $name,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    protected function getCustomerKey()
    {
        $ctx = Context::getContext();
        if ($ctx->customer && $ctx->customer->isLogged()) {
            return 'c_' . (int) $ctx->customer->id;
        }
        if (empty($ctx->cookie->mpe_guest_hash)) {
            $ctx->cookie->mpe_guest_hash = bin2hex(random_bytes(16));
            $ctx->cookie->write();
        }
        return 'g_' . $ctx->cookie->mpe_guest_hash;
    }
}
