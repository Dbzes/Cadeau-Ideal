<?php
/**
 * Front controller AJAX — recomposition serveur HD
 */
class MousepadeditorComposeModuleFrontController extends ModuleFrontController
{
    const DPI = 150;
    const MM_TO_INCH = 0.0393701;

    public $ajax = true;

    public function postProcess()
    {
        header('Content-Type: application/json');
        try {
            $raw = file_get_contents('php://input');
            $state = json_decode($raw, true);
            if (!is_array($state)) {
                throw new Exception('Payload invalide');
            }
            if (!extension_loaded('imagick')) {
                throw new Exception('Imagick indisponible');
            }
            $result = $this->composeMockup($state);
            echo json_encode(['success' => true] + $result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    protected function composeMockup(array $state)
    {
        // Dimensions cible HD
        $canvasW = isset($state['canvasW']) ? (float) $state['canvasW'] : 600;
        $canvasH = isset($state['canvasH']) ? (float) $state['canvasH'] : 491;
        $targetW = isset($state['targetW']) ? (int) $state['targetW'] : 1299;
        $targetH = isset($state['targetH']) ? (int) $state['targetH'] : 1063;

        // Ratio canvas éditeur → HD
        $ratio = $targetW / $canvasW;

        $img = new Imagick();
        $img->newImage($targetW, $targetH, new ImagickPixel('#ffffff'), 'png');
        $img->setImageFormat('png');

        // Fond
        if (!empty($state['bg'])) {
            $this->drawBackground($img, $state['bg'], $targetW, $targetH, $ratio);
        }

        // Images
        if (!empty($state['images']) && is_array($state['images'])) {
            foreach ($state['images'] as $imgData) {
                $this->drawImage($img, $imgData, $ratio);
            }
        }

        // Textes
        if (!empty($state['texts']) && is_array($state['texts'])) {
            foreach ($state['texts'] as $txtData) {
                $this->drawText($img, $txtData, $ratio);
            }
        }

        $hash = md5(json_encode($state) . microtime(true));
        $dir = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/previews/';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

        // 1. Fichier HD CLEAN pour impression (sans overlay)
        $cleanFile = $dir . $hash . '.jpg';
        $clone = clone $img;
        $clone->setImageFormat('jpeg');
        $clone->setImageCompressionQuality(100);
        $clone->setImageCompression(Imagick::COMPRESSION_JPEG);
        $clone->stripImage();
        $clone->writeImage($cleanFile);
        $clone->clear();

        // 2. Fichier PREVIEW avec template overlay pour affichage panier/modal
        $this->drawTemplateOverlay($img, $targetW, $targetH);
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(100);
        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
        $img->stripImage();
        $previewFile = $dir . $hash . '_preview.jpg';
        $img->writeImage($previewFile);
        $img->clear();

        return [
            'previewUrl' => _MODULE_DIR_ . 'mousepadeditor/uploads/previews/' . $hash . '_preview.jpg',
            'cleanUrl' => _MODULE_DIR_ . 'mousepadeditor/uploads/previews/' . $hash . '.jpg',
            'hash' => $hash,
            'width' => $targetW,
            'height' => $targetH,
        ];
    }

    protected function drawTemplateOverlay(Imagick $canvas, $W, $H)
    {
        // Récupère le template configuré dans le BO
        $file = Configuration::get('MOUSEPAD_TEMPLATE');
        if (!$file) return;
        $path = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/template/' . $file;
        if (!file_exists($path)) return;
        try {
            $overlay = new Imagick($path);
            // Redimensionne pour couvrir toute la surface
            $overlay->resizeImage($W, $H, Imagick::FILTER_LANCZOS, 1);
            $canvas->compositeImage($overlay, Imagick::COMPOSITE_OVER, 0, 0);
            $overlay->clear();
        } catch (Exception $e) {
            // silencieux
        }
    }

    protected function drawBackground(Imagick $canvas, array $bg, $W, $H, $ratio)
    {
        if (!empty($bg['color'])) {
            $canvas->setImageBackgroundColor(new ImagickPixel($bg['color']));
            $canvas->compositeImage(
                $this->createSolidLayer($W, $H, $bg['color']),
                Imagick::COMPOSITE_OVER, 0, 0
            );
            return;
        }
        if (empty($bg['url'])) return;

        $src = $this->resolveLocalPath($bg['url']);
        if (!$src || !file_exists($src)) return;

        // Utiliser la version HD pré-cachée si disponible
        $hdCache = $this->ensureHdCache($src, $W, $H);
        $isCached = $hdCache && file_exists($hdCache);
        $bgImg = new Imagick($isCached ? $hdCache : $src);
        // Échelle : base cover + zoom user
        $baseScale = max($W / $bgImg->getImageWidth(), $H / $bgImg->getImageHeight());
        $zoom = isset($bg['zoom']) ? (float) $bg['zoom'] : 1;
        $finalScale = $baseScale * $zoom;
        $newW = $bgImg->getImageWidth() * $finalScale;
        $newH = $bgImg->getImageHeight() * $finalScale;
        // Skip resize si déjà à la bonne taille (cache hit + zoom 1)
        if (abs($finalScale - 1.0) > 0.01) {
            $bgImg->resizeImage((int) $newW, (int) $newH, Imagick::FILTER_LANCZOS, 1);
        }

        // Position : left/top du centre de l'image (en coordonnées éditeur), convertie
        $cx = isset($bg['left']) ? $bg['left'] * $ratio : $W / 2;
        $cy = isset($bg['top']) ? $bg['top'] * $ratio : $H / 2;
        $x = (int) ($cx - $newW / 2);
        $y = (int) ($cy - $newH / 2);

        $canvas->compositeImage($bgImg, Imagick::COMPOSITE_OVER, $x, $y);
        $bgImg->clear();
    }

    /**
     * Génère (ou réutilise) une version HD pré-dimensionnée du fond.
     * Stockée à côté du fichier source avec suffixe _hd.jpg
     * Taille : cover de W x H (=taille cible HD du canvas)
     */
    protected function ensureHdCache($src, $W, $H)
    {
        $info = pathinfo($src);
        $cache = $info['dirname'] . '/' . $info['filename'] . '_hd.jpg';
        if (file_exists($cache) && filemtime($cache) >= filemtime($src)) {
            return $cache;
        }
        try {
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
            return $cache;
        } catch (Exception $e) {
            return null;
        }
    }

    protected function createSolidLayer($w, $h, $color)
    {
        $layer = new Imagick();
        $layer->newImage($w, $h, new ImagickPixel($color), 'png');
        return $layer;
    }

    protected function drawImage(Imagick $canvas, array $data, $ratio)
    {
        if (empty($data['url'])) return;
        $src = $this->resolveLocalPath($data['url']);
        if (!$src || !file_exists($src)) return;

        $el = new Imagick($src);
        $scaleX = isset($data['scaleX']) ? (float) $data['scaleX'] : 1;
        $scaleY = isset($data['scaleY']) ? (float) $data['scaleY'] : 1;
        $angle = isset($data['angle']) ? (float) $data['angle'] : 0;

        $newW = $el->getImageWidth() * $scaleX * $ratio;
        $newH = $el->getImageHeight() * $scaleY * $ratio;
        $el->resizeImage((int) $newW, (int) $newH, Imagick::FILTER_LANCZOS, 1);

        if ($angle != 0) {
            $el->rotateImage(new ImagickPixel('transparent'), $angle);
        }

        $cx = isset($data['left']) ? $data['left'] * $ratio : 0;
        $cy = isset($data['top']) ? $data['top'] * $ratio : 0;
        $x = (int) ($cx - $el->getImageWidth() / 2);
        $y = (int) ($cy - $el->getImageHeight() / 2);

        $canvas->compositeImage($el, Imagick::COMPOSITE_OVER, $x, $y);
        $el->clear();
    }

    protected function drawText(Imagick $canvas, array $data, $ratio)
    {
        $text = isset($data['text']) ? (string) $data['text'] : '';
        if ($text === '') return;

        $family = isset($data['fontFamily']) ? $data['fontFamily'] : 'DejaVu Sans';
        $size = isset($data['fontSize']) ? (float) $data['fontSize'] : 32;
        $color = isset($data['fill']) ? $data['fill'] : '#000000';
        $bold = !empty($data['bold']);
        $italic = !empty($data['italic']);
        $angle = isset($data['angle']) ? (float) $data['angle'] : 0;

        $fontPath = $this->resolveFontPath($family, $bold, $italic);

        $draw = new ImagickDraw();
        if ($fontPath) { $draw->setFont($fontPath); }
        else { $draw->setFontFamily($family); }
        $draw->setFontSize($size * $ratio);
        $draw->setFillColor(new ImagickPixel($color));
        if ($bold) { $draw->setFontWeight(700); }
        if ($italic) { $draw->setFontStyle(Imagick::STYLE_ITALIC); }
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        $cx = isset($data['left']) ? $data['left'] * $ratio : 0;
        $cy = isset($data['top']) ? $data['top'] * $ratio : 0;

        // Dessin sur un layer transparent pour permettre rotation
        $metrics = $canvas->queryFontMetrics($draw, $text);
        $layerW = (int) ($metrics['textWidth'] + 20);
        $layerH = (int) ($metrics['textHeight'] + 20);

        $layer = new Imagick();
        $layer->newImage($layerW, $layerH, new ImagickPixel('transparent'), 'png');
        $layer->annotateImage($draw, $layerW / 2, $layerH / 2 + $metrics['ascender'] / 2, 0, $text);

        if ($angle != 0) {
            $layer->rotateImage(new ImagickPixel('transparent'), $angle);
        }

        $x = (int) ($cx - $layer->getImageWidth() / 2);
        $y = (int) ($cy - $layer->getImageHeight() / 2);
        $canvas->compositeImage($layer, Imagick::COMPOSITE_OVER, $x, $y);
        $layer->clear();
    }

    protected function resolveLocalPath($url)
    {
        // Convertit une URL module en chemin absolu local
        $parsed = parse_url($url);
        $path = isset($parsed['path']) ? $parsed['path'] : $url;
        // Retire le prefix shop si présent
        if (strpos($path, '/modules/mousepadeditor/') !== false) {
            $rel = substr($path, strpos($path, '/modules/mousepadeditor/') + strlen('/modules/mousepadeditor/'));
            return _PS_MODULE_DIR_ . 'mousepadeditor/' . $rel;
        }
        if (strpos($path, '/') === 0) {
            return _PS_ROOT_DIR_ . $path;
        }
        return null;
    }

    protected function resolveFontPath($family, $bold = false, $italic = false)
    {
        // 1. Polices custom uploadées
        $customDir = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/fonts/';
        $fontsConfig = Configuration::get('MOUSEPAD_FONTS');
        $customList = json_decode($fontsConfig, true) ?: [];
        foreach ($customList as $f) {
            if ($f['family'] === $family) {
                return $customDir . $f['file'];
            }
        }

        // 2. Cache Google Fonts
        $cacheDir = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/fonts_cache/';
        $suffix = ($bold && $italic) ? '-BoldItalic' : ($bold ? '-Bold' : ($italic ? '-Italic' : ''));
        $cacheFile = $cacheDir . str_replace(' ', '', $family) . $suffix . '.ttf';
        if (file_exists($cacheFile)) return $cacheFile;
        $cacheFileRegular = $cacheDir . str_replace(' ', '', $family) . '.ttf';
        if (file_exists($cacheFileRegular)) return $cacheFileRegular;

        // 3. fc-match via shell
        $out = [];
        @exec('fc-match -f "%{file}" ' . escapeshellarg($family), $out);
        if (!empty($out[0]) && file_exists($out[0])) {
            return $out[0];
        }

        return null;
    }
}
