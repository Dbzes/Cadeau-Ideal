<?php
/**
 * Front controller AJAX — upload/delete fond client (Mug)
 */
class MugeditorUploadModuleFrontController extends ModuleFrontController
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
        if (empty($ctx->cookie->mue_guest_hash)) {
            $ctx->cookie->mue_guest_hash = bin2hex(random_bytes(16));
            $ctx->cookie->write();
        }
        return 'g_' . $ctx->cookie->mue_guest_hash;
    }

    protected function getCustomerDir($key)
    {
        $dir = _PS_MODULE_DIR_ . 'mugeditor/uploads/customer/' . $key . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    protected function getCustomerUrl($key)
    {
        return _MODULE_DIR_ . 'mugeditor/uploads/customer/' . $key . '/';
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

        foreach (glob($dir . 'bg.*') as $old) {
            @unlink($old);
        }

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

        $this->downsizeIfNeeded($dest, 2000);
        $this->preGenerateHdCache($dest, 1299, 1063);

        $this->json([
            'success' => true,
            'url' => $this->getCustomerUrl($key) . 'bg.' . $finalExt . '?t=' . time(),
        ]);
    }

    protected function preGenerateHdCache($src, $W, $H)
    {
        if (!extension_loaded('imagick') || !file_exists($src)) return;
        try {
            $info = pathinfo($src);
            $cache = $info['dirname'] . '/' . $info['filename'] . '_hd.jpg';
            $im = new Imagick($src);
            $baseScale = max($W / $im->getImageWidth(), $H / $im->getImageHeight());
            $nw = (int) ($im->getImageWidth() * $baseScale);
            $nh = (int) ($im->getImageHeight() * $baseScale);
            $im->resizeImage($nw, $nh, Imagick::FILTER_LANCZOS, 1);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(100);
            $im->stripImage();
            $im->writeImage($cache);
            $im->clear();
        } catch (Exception $e) {}
    }

    protected function downsizeIfNeeded($path, $maxPx)
    {
        if (!extension_loaded('imagick') || !file_exists($path)) return;
        try {
            $im = new \Imagick($path);
            $w = $im->getImageWidth();
            $h = $im->getImageHeight();
            if ($w <= $maxPx && $h <= $maxPx) { $im->clear(); return; }
            $im->thumbnailImage($maxPx, $maxPx, true);
            $im->setImageCompressionQuality(92);
            $im->stripImage();
            $im->writeImage($path);
            $im->clear();
        } catch (\Exception $e) {}
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
