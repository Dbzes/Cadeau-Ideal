<?php
/**
 * Front controller AJAX — upload/delete fond client
 */
class MousepadeditorUploadModuleFrontController extends ModuleFrontController
{
    const MAX_SIZE = 10485760; // 10 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

    public $ajax = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        $action = Tools::getValue('action');

        try {
            if ($action === 'upload') {
                $this->handleUpload();
            } elseif ($action === 'delete') {
                $this->handleDelete();
            } else {
                $this->json(['success' => false, 'error' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
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

    protected function getCustomerDir($key)
    {
        $dir = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/customer/' . $key . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    protected function getCustomerUrl($key)
    {
        return _MODULE_DIR_ . 'mousepadeditor/uploads/customer/' . $key . '/';
    }

    protected function handleUpload()
    {
        if (empty($_FILES['file'])) {
            $this->json(['success' => false, 'error' => 'Aucun fichier reçu']);
        }
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'Erreur upload (' . $f['error'] . ')']);
        }
        if ($f['size'] > self::MAX_SIZE) {
            $this->json(['success' => false, 'error' => 'Fichier trop volumineux (max 10 Mo)']);
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED)) {
            $this->json(['success' => false, 'error' => 'Format non autorisé']);
        }

        $key = $this->getCustomerKey();
        $dir = $this->getCustomerDir($key);

        // Purge ancien fond
        foreach (glob($dir . 'bg.*') as $old) {
            @unlink($old);
        }

        // Conversion HEIC → JPG si nécessaire
        if (in_array($ext, ['heic', 'heif'])) {
            if (!extension_loaded('imagick')) {
                $this->json(['success' => false, 'error' => 'Conversion HEIC indisponible']);
            }
            try {
                $im = new Imagick($f['tmp_name']);
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(92);
                $dest = $dir . 'bg.jpg';
                $im->writeImage($dest);
                $im->clear();
                $finalExt = 'jpg';
            } catch (Exception $e) {
                $this->json(['success' => false, 'error' => 'Conversion HEIC échouée : ' . $e->getMessage()]);
            }
        } else {
            $finalExt = $ext === 'jpeg' ? 'jpg' : $ext;
            $dest = $dir . 'bg.' . $finalExt;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                $this->json(['success' => false, 'error' => 'Échec écriture fichier']);
            }
        }

        $this->json([
            'success' => true,
            'url' => $this->getCustomerUrl($key) . 'bg.' . $finalExt . '?t=' . time(),
        ]);
    }

    protected function handleDelete()
    {
        $key = $this->getCustomerKey();
        $dir = $this->getCustomerDir($key);
        foreach (glob($dir . 'bg.*') as $f) {
            @unlink($f);
        }
        $this->json(['success' => true]);
    }

    protected function json($data)
    {
        echo json_encode($data);
        exit;
    }
}
