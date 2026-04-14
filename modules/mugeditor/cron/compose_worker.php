<?php
/**
 * Worker cron — traite la queue de composition HD en arrière-plan (Mug)
 * Lancé toutes les 30s via cron Docker
 * Usage : php /var/www/html/modules/mugeditor/cron/compose_worker.php
 */

$psRoot = dirname(dirname(dirname(__DIR__)));
require_once $psRoot . '/config/config.inc.php';

$lockFile = sys_get_temp_dir() . '/mue_compose_worker.lock';
$fp = fopen($lockFile, 'w');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$db = Db::getInstance();

$jobs = $db->executeS('
    SELECT * FROM ' . _DB_PREFIX_ . 'mue_compose_queue
    WHERE status = "pending"
    ORDER BY created_at ASC
    LIMIT 3
');

if (!$jobs) {
    flock($fp, LOCK_UN);
    fclose($fp);
    @unlink($lockFile);
    exit(0);
}

@set_time_limit(600);
Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 1024 * 1024 * 1024);
Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, 1024 * 1024 * 1024);
Imagick::setResourceLimit(Imagick::RESOURCETYPE_DISK, 2 * 1024 * 1024 * 1024);
Imagick::setResourceLimit(Imagick::RESOURCETYPE_AREA, 100 * 1024 * 1024);

foreach ($jobs as $job) {
    $id = (int) $job['id_queue'];
    $hash = $job['hash'];

    $db->update('mue_compose_queue', ['status' => 'processing'], 'id_queue = ' . $id);

    try {
        $state = json_decode($job['state_json'], true);
        if (!is_array($state)) {
            throw new Exception('JSON invalide');
        }

        $result = composeMockup($state);

        $dest = _PS_UPLOAD_DIR_ . $hash;
        $cleanFile = $result['cleanFile'];
        $previewFile = $result['previewFile'];

        copy($cleanFile, $dest);

        $thumbSource = file_exists($previewFile) ? $previewFile : $cleanFile;
        try {
            $thumb = new Imagick($thumbSource);
            $thumb->thumbnailImage(1200, 0);
            $thumb->setImageCompressionQuality(90);
            $thumb->setImageFormat('jpeg');
            $thumb->stripImage();
            $thumb->writeImage($dest . '_small');
            $thumb->clear();
        } catch (Exception $e) {
            @copy($thumbSource, $dest . '_small');
        }

        @unlink($cleanFile);
        @unlink($previewFile);

        $db->update('mue_compose_queue', [
            'status' => 'done',
            'processed_at' => date('Y-m-d H:i:s'),
        ], 'id_queue = ' . $id);

        echo date('H:i:s') . " OK job #$id hash=$hash\n";

    } catch (Throwable $e) {
        $db->update('mue_compose_queue', [
            'status' => 'error',
            'error_msg' => pSQL(substr($e->getMessage(), 0, 250)),
            'processed_at' => date('Y-m-d H:i:s'),
        ], 'id_queue = ' . $id);

        echo date('H:i:s') . " ERR job #$id: " . $e->getMessage() . "\n";
    }
}

flock($fp, LOCK_UN);
fclose($fp);
@unlink($lockFile);

function composeMockup(array $state)
{
    $canvasW = isset($state['canvasW']) ? (float) $state['canvasW'] : 600;
    $canvasH = isset($state['canvasH']) ? (float) $state['canvasH'] : 491;
    $targetW = isset($state['targetW']) ? (int) $state['targetW'] : 1299;
    $targetH = isset($state['targetH']) ? (int) $state['targetH'] : 1063;
    $ratio = $targetW / $canvasW;

    $img = new Imagick();
    $img->newImage($targetW, $targetH, new ImagickPixel('#ffffff'), 'png');
    $img->setImageFormat('png');

    if (!empty($state['bg'])) {
        drawBackground($img, $state['bg'], $targetW, $targetH, $ratio);
    }

    if (!empty($state['images']) && is_array($state['images'])) {
        foreach ($state['images'] as $imgData) {
            drawImage($img, $imgData, $ratio);
        }
    }

    if (!empty($state['texts']) && is_array($state['texts'])) {
        foreach ($state['texts'] as $txtData) {
            drawText($img, $txtData, $ratio);
        }
    }

    $hash = md5(json_encode($state) . microtime(true));
    $dir = _PS_MODULE_DIR_ . 'mugeditor/uploads/previews/';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

    $cleanFile = $dir . $hash . '.jpg';
    $clone = clone $img;
    $clone->setImageFormat('jpeg');
    $clone->setImageCompressionQuality(100);
    $clone->setImageCompression(Imagick::COMPRESSION_JPEG);
    $clone->stripImage();
    $clone->writeImage($cleanFile);
    $clone->clear();

    drawTemplateOverlay($img, $targetW, $targetH);
    $img->setImageFormat('jpeg');
    $img->setImageCompressionQuality(100);
    $img->setImageCompression(Imagick::COMPRESSION_JPEG);
    $img->stripImage();
    $previewFile = $dir . $hash . '_preview.jpg';
    $img->writeImage($previewFile);
    $img->clear();

    return [
        'cleanFile' => $cleanFile,
        'previewFile' => $previewFile,
    ];
}

function drawBackground(Imagick $canvas, array $bg, $W, $H, $ratio)
{
    if (!empty($bg['color'])) {
        $canvas->compositeImage(createSolidLayer($W, $H, $bg['color']), Imagick::COMPOSITE_OVER, 0, 0);
        return;
    }
    if (empty($bg['url'])) return;

    $src = resolveLocalPath($bg['url']);
    if (!$src || !file_exists($src)) return;

    $hdCache = ensureHdCache($src, $W, $H);
    $bgImg = new Imagick(($hdCache && file_exists($hdCache)) ? $hdCache : $src);

    $baseScale = max($W / $bgImg->getImageWidth(), $H / $bgImg->getImageHeight());
    $zoom = isset($bg['zoom']) ? (float) $bg['zoom'] : 1;
    $finalScale = $baseScale * $zoom;
    $newW = $bgImg->getImageWidth() * $finalScale;
    $newH = $bgImg->getImageHeight() * $finalScale;

    if (abs($finalScale - 1.0) > 0.01) {
        $bgImg->resizeImage((int) $newW, (int) $newH, Imagick::FILTER_LANCZOS, 1);
    }

    $cx = isset($bg['left']) ? $bg['left'] * $ratio : $W / 2;
    $cy = isset($bg['top']) ? $bg['top'] * $ratio : $H / 2;
    $x = (int) ($cx - $newW / 2);
    $y = (int) ($cy - $newH / 2);

    $canvas->compositeImage($bgImg, Imagick::COMPOSITE_OVER, $x, $y);
    $bgImg->clear();
}

function drawImage(Imagick $canvas, array $data, $ratio)
{
    if (empty($data['url'])) return;

    $url = $data['url'];
    $el = new Imagick();

    if (strpos($url, 'data:') === 0) {
        $parts = explode(',', $url, 2);
        if (count($parts) !== 2) return;
        $binary = base64_decode($parts[1]);
        if ($binary === false) return;
        try { $el->readImageBlob($binary); } catch (Exception $e) { return; }
    } else {
        $src = resolveLocalPath($url);
        if (!$src || !file_exists($src)) return;
        $el = new Imagick($src);
    }

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

function drawText(Imagick $canvas, array $data, $ratio)
{
    if (empty($data['text'])) return;
    $text = $data['text'];
    $fontSize = (isset($data['fontSize']) ? (float) $data['fontSize'] : 32) * $ratio;
    $fill = isset($data['fill']) ? $data['fill'] : '#000000';
    $fontFamily = isset($data['fontFamily']) ? $data['fontFamily'] : 'Arial';
    $bold = !empty($data['bold']);
    $italic = !empty($data['italic']);
    $angle = isset($data['angle']) ? (float) $data['angle'] : 0;

    $draw = new ImagickDraw();
    $fontPath = resolveFontPath($fontFamily, $bold, $italic);
    if ($fontPath) {
        $draw->setFont($fontPath);
    }
    $draw->setFontSize($fontSize);
    $draw->setFillColor(new ImagickPixel($fill));
    $draw->setGravity(Imagick::GRAVITY_NORTHWEST);

    $metrics = $canvas->queryFontMetrics($draw, $text);
    $cx = isset($data['left']) ? $data['left'] * $ratio : 0;
    $cy = isset($data['top']) ? $data['top'] * $ratio : 0;
    $x = $cx - $metrics['textWidth'] / 2;
    $y = $cy - $metrics['textHeight'] / 2;

    $canvas->annotateImage($draw, (int) $x, (int) ($y + $metrics['ascender']), $angle, $text);
}

function drawTemplateOverlay(Imagick $canvas, $W, $H)
{
    $file = Configuration::get('MUG_TEMPLATE');
    if (!$file) return;
    $path = _PS_MODULE_DIR_ . 'mugeditor/uploads/template/' . $file;
    if (!file_exists($path)) return;
    try {
        $overlay = new Imagick($path);
        $overlay->resizeImage($W, $H, Imagick::FILTER_LANCZOS, 1);
        $canvas->compositeImage($overlay, Imagick::COMPOSITE_OVER, 0, 0);
        $overlay->clear();
    } catch (Exception $e) {}
}

function createSolidLayer($w, $h, $color)
{
    $layer = new Imagick();
    $layer->newImage($w, $h, new ImagickPixel($color), 'png');
    return $layer;
}

function ensureHdCache($src, $W, $H)
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

function resolveLocalPath($url)
{
    $parsed = parse_url($url);
    $path = isset($parsed['path']) ? $parsed['path'] : $url;
    if (strpos($path, '/modules/mugeditor/') !== false) {
        $rel = substr($path, strpos($path, '/modules/mugeditor/') + strlen('/modules/mugeditor/'));
        return _PS_MODULE_DIR_ . 'mugeditor/' . $rel;
    }
    if (strpos($path, '/') === 0) {
        return _PS_ROOT_DIR_ . $path;
    }
    return null;
}

function resolveFontPath($family, $bold = false, $italic = false)
{
    $customDir = _PS_MODULE_DIR_ . 'mugeditor/uploads/fonts/';
    $fontsConfig = Configuration::get('MUG_FONTS');
    $customList = json_decode($fontsConfig, true) ?: [];
    foreach ($customList as $f) {
        if ($f['family'] === $family) {
            return $customDir . $f['file'];
        }
    }

    $cacheDir = _PS_MODULE_DIR_ . 'mugeditor/uploads/fonts_cache/';
    $slug = preg_replace('/[^a-z0-9]/i', '_', $family);
    foreach (['ttf', 'otf', 'woff', 'woff2'] as $ext) {
        $p = $cacheDir . $slug . '.' . $ext;
        if (file_exists($p)) return $p;
    }

    $result = @shell_exec('fc-match "' . addslashes($family) . '" --format="%{file}"');
    if ($result && file_exists(trim($result))) {
        return trim($result);
    }

    return null;
}
