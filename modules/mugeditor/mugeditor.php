<?php
/**
 * Editor Mug - Personnalisation de mugs
 *
 * @author    Claude
 * @copyright 2026
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mugeditor extends Module
{
    const UPLOAD_DIR = 'uploads/backgrounds/';
    const FONT_DIR = 'uploads/fonts/';
    const TEMPLATE_DIR = 'uploads/template/';
    const RENDER_DIR = 'uploads/render/';
    const MAX_SIZE = 5242880; // 5 Mo
    const FONT_MAX_SIZE = 2097152; // 2 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];
    const FONT_ALLOWED = ['ttf', 'otf', 'woff', 'woff2'];

    const WEB_SAFE_FONTS = ['Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Courier New', 'Impact', 'Comic Sans MS'];
    const THEME_FONTS = ['Manrope'];
    const GOOGLE_FONTS = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Raleway', 'Poppins', 'Merriweather', 'Ubuntu', 'Playfair Display', 'Nunito', 'Bebas Neue', 'Dancing Script', 'Pacifico', 'Lobster', 'Anton', 'Caveat', 'Quicksand', 'Inter', 'Work Sans', 'Comfortaa', 'Abril Fatface', 'Permanent Marker', 'Indie Flower', 'Yanone Kaffeesatz', 'Amatic SC', 'Archivo', 'Karla', 'Satisfy', 'Great Vibes'];

    public function __construct()
    {
        $this->name = 'mugeditor';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Editor Mug');
        $this->description = $this->l('Affiche une zone de personnalisation de mug sur les fiches produits sélectionnés.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Table queue de composition HD async
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mue_compose_queue` (
            `id_queue` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_customization` INT UNSIGNED NOT NULL DEFAULT 0,
            `hash` VARCHAR(64) NOT NULL,
            `state_json` LONGTEXT NOT NULL,
            `status` ENUM("pending","processing","done","error") NOT NULL DEFAULT "pending",
            `error_msg` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `processed_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id_queue`),
            KEY `status` (`status`),
            KEY `hash` (`hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        return parent::install()
            && $this->registerHook('displayMugEditor')
            && $this->registerHook('header')
            && $this->registerHook('actionPurgatorRegister')
            && Configuration::updateValue('MUG_PRODUCT_IDS', '')
            && Configuration::updateValue('MUG_BACKGROUNDS', json_encode([]))
            && Configuration::updateValue('MUG_FONTS', json_encode([]))
            && Configuration::updateValue('MUG_ENABLED_FONTS', json_encode(['Arial' => true, 'Open Sans' => true, 'Bebas Neue' => true]));
    }

    public function uninstall()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mue_compose_queue`');
        Configuration::deleteByName('MUG_PRODUCT_IDS');
        Configuration::deleteByName('MUG_BACKGROUNDS');
        Configuration::deleteByName('MUG_FONTS');
        Configuration::deleteByName('MUG_ENABLED_FONTS');

        return parent::uninstall();
    }

    protected function getCustomerBackground()
    {
        $ctx = Context::getContext();
        if ($ctx->customer && $ctx->customer->isLogged()) {
            $key = 'c_' . (int) $ctx->customer->id;
        } elseif (!empty($ctx->cookie->mue_guest_hash)) {
            $key = 'g_' . $ctx->cookie->mue_guest_hash;
        } else {
            return null;
        }
        $dir = _PS_MODULE_DIR_ . $this->name . '/uploads/customer/' . $key . '/';
        foreach (['jpg', 'png', 'webp'] as $ext) {
            if (file_exists($dir . 'bg.' . $ext)) {
                return _MODULE_DIR_ . $this->name . '/uploads/customer/' . $key . '/bg.' . $ext . '?t=' . filemtime($dir . 'bg.' . $ext);
            }
        }
        return null;
    }

    public function hookActionPurgatorRegister($params)
    {
        if (!class_exists('Purgator')) return;
        $delay = isset($params['delay']) ? (int) $params['delay'] : 90;
        $cutoff = time() - ($delay * 86400);

        $files = [];
        $base = _PS_MODULE_DIR_ . $this->name . '/uploads/customer/';
        if (is_dir($base)) {
            foreach (glob($base . '*/bg.*') as $f) {
                if (filemtime($f) < $cutoff) {
                    $rel = str_replace(_PS_MODULE_DIR_, '', $f);
                    $files[] = [
                        'path' => $f,
                        'name' => basename(dirname($f)) . '/' . basename($f),
                        'size' => filesize($f),
                        'mtime' => filemtime($f),
                        'preview_url' => _MODULE_DIR_ . $rel,
                    ];
                }
            }
        }
        Purgator::register([
            'source_id' => 'mugeditor_customer_bg',
            'source_name' => $this->l('Editor Mug — Fonds clients'),
            'files' => $files,
        ]);

        $files2 = [];
        $previewDir = _PS_MODULE_DIR_ . $this->name . '/uploads/previews/';
        if (is_dir($previewDir)) {
            foreach (glob($previewDir . '*.png') as $f) {
                if (filemtime($f) < $cutoff) {
                    $rel = str_replace(_PS_MODULE_DIR_, '', $f);
                    $files2[] = [
                        'path' => $f,
                        'name' => basename($f),
                        'size' => filesize($f),
                        'mtime' => filemtime($f),
                        'preview_url' => _MODULE_DIR_ . $rel,
                    ];
                }
            }
        }
        Purgator::register([
            'source_id' => 'mugeditor_previews',
            'source_name' => $this->l('Editor Mug — Aperçus HD non finalisés'),
            'files' => $files2,
        ]);
    }

    public function hookHeader($params)
    {
        $productId = (int) Tools::getValue('id_product');
        if (!$productId) {
            return;
        }
        $configIds = Configuration::get('MUG_PRODUCT_IDS');
        if (empty($configIds)) {
            return;
        }
        $allowedIds = array_map('intval', array_filter(explode(',', $configIds)));
        if (!in_array($productId, $allowedIds)) {
            return;
        }
        $mousepadIds = Configuration::get('MOUSEPAD_PRODUCT_IDS');
        if (!empty($mousepadIds) && in_array($productId, array_map('intval', array_filter(explode(',', $mousepadIds))))) {
            return;
        }
        $this->context->controller->addCSS($this->_path . 'views/css/mugeditor.css');
        $this->context->controller->addJS($this->_path . 'views/js/fabric.min.js');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMugeditorModule')) {
            $ids = Tools::getValue('MUG_PRODUCT_IDS');
            $ids = preg_replace('/[^0-9,]/', '', $ids);
            Configuration::updateValue('MUG_PRODUCT_IDS', $ids);
            $output .= $this->displayConfirmation($this->l('Paramètres mis à jour.'));
        }

        if (Tools::isSubmit('submitMugBackgroundUpload')) {
            $output .= $this->handleUpload();
        }

        if (Tools::getValue('deleteBg')) {
            $output .= $this->handleDelete(Tools::getValue('deleteBg'));
        }

        if (Tools::getValue('moveBg') && Tools::getValue('dir')) {
            $output .= $this->handleMove(Tools::getValue('moveBg'), Tools::getValue('dir'));
        }

        if (Tools::getValue('reorderBgs')) {
            $order = explode(',', Tools::getValue('reorderBgs'));
            $current = $this->getBackgrounds();
            $new = [];
            foreach ($order as $f) {
                if (in_array($f, $current)) {
                    $new[] = $f;
                }
            }
            if (count($new) === count($current)) {
                $this->saveBackgrounds($new);
            }
            if (Tools::getValue('ajax')) {
                die('OK');
            }
        }

        if (Tools::isSubmit('submitMugFontUpload')) {
            $output .= $this->handleFontUpload();
        }
        if (Tools::getValue('toggleFont')) {
            $output .= $this->handleToggleFont(Tools::getValue('toggleFont'));
        }

        if (Tools::isSubmit('submitMugTemplateUpload')) {
            $output .= $this->handleTemplateUpload();
        }
        if (Tools::getValue('deleteTemplate')) {
            $output .= $this->handleTemplateDelete();
        }

        if (Tools::isSubmit('submitMugRenderUpload')) {
            $output .= $this->handleRenderUpload();
        }
        if (Tools::getValue('deleteRenderBase')) {
            $output .= $this->handleRenderDelete('base');
        }
        if (Tools::getValue('deleteRenderLighting')) {
            $output .= $this->handleRenderDelete('lighting');
        }

        return $output . $this->renderForm() . $this->renderMugRenderManager() . $this->renderTemplateManager() . $this->renderBackgroundsManager() . $this->renderFontsManager();
    }

    protected function getBackgrounds()
    {
        $raw = Configuration::get('MUG_BACKGROUNDS');
        $list = json_decode($raw, true);
        return is_array($list) ? $list : [];
    }

    protected function saveBackgrounds(array $list)
    {
        Configuration::updateValue('MUG_BACKGROUNDS', json_encode(array_values($list)));
    }

    protected function handleUpload()
    {
        if (empty($_FILES['mug_bg']) || empty($_FILES['mug_bg']['name'][0])) {
            return $this->displayWarning($this->l('Aucun fichier sélectionné.'));
        }

        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $list = $this->getBackgrounds();
        $errors = [];
        $count = 0;

        $files = $_FILES['mug_bg'];
        $total = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $size = $files['size'][$i];
            $name = $files['name'][$i];
            $tmp = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, self::ALLOWED)) {
                $errors[] = sprintf($this->l('%s : format non autorisé.'), $name);
                continue;
            }
            if ($size > self::MAX_SIZE) {
                $errors[] = sprintf($this->l('%s : dépasse 5 Mo.'), $name);
                continue;
            }

            $newName = uniqid('bg_', true) . '.' . $ext;
            if (move_uploaded_file($tmp, $dir . $newName)) {
                if (extension_loaded('imagick')) {
                    try {
                        $im = new \Imagick($dir . $newName);
                        $w = $im->getImageWidth();
                        $h = $im->getImageHeight();
                        if ($w > 2000 || $h > 2000) {
                            $im->thumbnailImage(2000, 2000, true);
                            $im->setImageCompressionQuality(92);
                            $im->stripImage();
                            $im->writeImage($dir . $newName);
                        }
                        $im->clear();
                    } catch (\Exception $e) {}
                }
                $list[] = $newName;
                $count++;
            }
        }

        $this->saveBackgrounds($list);

        $msg = '';
        if ($count > 0) {
            $msg .= $this->displayConfirmation(sprintf($this->l('%d fond(s) ajouté(s).'), $count));
        }
        if (!empty($errors)) {
            $msg .= $this->displayWarning(implode('<br>', $errors));
        }
        return $msg;
    }

    protected function handleDelete($filename)
    {
        $list = $this->getBackgrounds();
        $idx = array_search($filename, $list);
        if ($idx === false) {
            return '';
        }
        $path = _PS_MODULE_DIR_ . $this->name . '/' . self::UPLOAD_DIR . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
        unset($list[$idx]);
        $this->saveBackgrounds($list);
        return $this->displayConfirmation($this->l('Fond supprimé.'));
    }

    protected function handleMove($filename, $dir)
    {
        $list = $this->getBackgrounds();
        $idx = array_search($filename, $list);
        if ($idx === false) {
            return '';
        }
        $newIdx = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($newIdx < 0 || $newIdx >= count($list)) {
            return '';
        }
        $tmp = $list[$idx];
        $list[$idx] = $list[$newIdx];
        $list[$newIdx] = $tmp;
        $this->saveBackgrounds($list);
        return '';
    }

    protected function renderStatusPanel()
    {
        $bgs = count($this->getBackgrounds());
        $fonts = count($this->getFonts());
        $defaultFonts = 3;
        $totalFonts = $defaultFonts + $fonts;
        $productIds = Configuration::get('MUG_PRODUCT_IDS');
        $productCount = empty($productIds) ? 0 : count(array_filter(explode(',', $productIds)));

        $features = [
            ['icon' => '🖼', 'label' => 'Sélection de fonds catalogue', 'active' => $bgs > 0, 'value' => $bgs . ' fond(s) disponible(s)'],
            ['icon' => '⬆', 'label' => 'Upload fond client (drag-drop)', 'active' => true, 'value' => 'Actif · jpg/png/webp/heic · max 10 Mo'],
            ['icon' => '🔄', 'label' => 'Conversion HEIC automatique', 'active' => extension_loaded('imagick'), 'value' => extension_loaded('imagick') ? 'Imagick OK' : 'Imagick KO'],
            ['icon' => '🔍', 'label' => 'Zoom du fond (slider 100-300%)', 'active' => true, 'value' => 'Actif'],
            ['icon' => '🖌', 'label' => 'Ajout d\'images (drag/resize/rotate)', 'active' => true, 'value' => 'Max 3 par création'],
            ['icon' => '🔤', 'label' => 'Ajout de texte personnalisé', 'active' => true, 'value' => $totalFonts . ' police(s) disponible(s)'],
            ['icon' => '𝐁', 'label' => 'Texte gras / italique', 'active' => true, 'value' => 'Actif'],
            ['icon' => '🎨', 'label' => 'Personnalisation taille/couleur texte', 'active' => true, 'value' => 'Actif'],
            ['icon' => '🗑', 'label' => 'Suppression sélective + reset global', 'active' => true, 'value' => 'Actif'],
            ['icon' => '📦', 'label' => 'Produits avec éditeur activé', 'active' => $productCount > 0, 'value' => $productCount . ' produit(s) configuré(s)'],
        ];

        $todo = [
            'Recomposition serveur HD (PNG @ 150dpi)',
            'Sauvegarde de la création dans le panier (customization PrestaShop)',
            'Aperçu HD téléchargeable depuis la fiche commande BO',
            'Module Purgator (purge fichiers orphelins > 90j)',
        ];

        $html = '<div class="panel" style="border-left:4px solid #ee7a03;">';
        $html .= '<h3><i class="icon-dashboard"></i> ' . $this->l('État du module Editor Mug') . '</h3>';
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">';

        $html .= '<div><h4 style="color:#004774;margin-top:0;">✅ ' . $this->l('Disponible côté client') . '</h4>';
        $html .= '<ul style="list-style:none;padding:0;margin:0;">';
        foreach ($features as $f) {
            $color = $f['active'] ? '#27ae60' : '#bbb';
            $html .= '<li style="padding:8px 0;border-bottom:1px solid #eee;display:flex;align-items:center;gap:10px;">';
            $html .= '<span style="font-size:18px;width:24px;text-align:center;">' . $f['icon'] . '</span>';
            $html .= '<div style="flex:1;"><div style="font-weight:600;color:#004774;font-size:13px;">' . $f['label'] . '</div>';
            $html .= '<div style="font-size:11px;color:#888;">' . $f['value'] . '</div></div>';
            $html .= '<span style="color:' . $color . ';font-size:18px;">' . ($f['active'] ? '●' : '○') . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';

        $html .= '<div><h4 style="color:#004774;margin-top:0;">🚧 ' . $this->l('Prochaines fonctionnalités') . '</h4>';
        $html .= '<ul style="list-style:none;padding:0;margin:0;">';
        foreach ($todo as $t) {
            $html .= '<li style="padding:8px 0;border-bottom:1px solid #eee;display:flex;align-items:center;gap:10px;">';
            $html .= '<span style="color:#ee7a03;font-size:14px;">▸</span>';
            $html .= '<span style="font-size:13px;color:#666;">' . $t . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';

        $html .= '</div></div>';
        return $html;
    }

    protected function getFonts()
    {
        $raw = Configuration::get('MUG_FONTS');
        $list = json_decode($raw, true);
        return is_array($list) ? $list : [];
    }

    protected function saveFonts(array $list)
    {
        Configuration::updateValue('MUG_FONTS', json_encode(array_values($list)));
    }

    protected function handleFontUpload()
    {
        if (empty($_FILES['mug_font']) || empty($_FILES['mug_font']['name'][0])) {
            return $this->displayWarning($this->l('Aucun fichier sélectionné.'));
        }
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::FONT_DIR;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

        $list = $this->getFonts();
        $errors = [];
        $count = 0;
        $files = $_FILES['mug_font'];
        $total = count($files['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = $files['name'][$i];
            $size = $files['size'][$i];
            $tmp = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, self::FONT_ALLOWED)) {
                $errors[] = sprintf($this->l('%s : format non autorisé.'), $name);
                continue;
            }
            if ($size > self::FONT_MAX_SIZE) {
                $errors[] = sprintf($this->l('%s : dépasse 2 Mo.'), $name);
                continue;
            }
            $family = preg_replace('/\.[^.]+$/', '', $name);
            $family = preg_replace('/[^a-zA-Z0-9 _-]/', '', $family);
            $newFile = uniqid('font_', true) . '.' . $ext;
            if (move_uploaded_file($tmp, $dir . $newFile)) {
                $list[] = ['family' => $family, 'file' => $newFile, 'ext' => $ext];
                $count++;
            }
        }
        $this->saveFonts($list);
        $msg = '';
        if ($count > 0) $msg .= $this->displayConfirmation(sprintf($this->l('%d police(s) ajoutée(s).'), $count));
        if (!empty($errors)) $msg .= $this->displayWarning(implode('<br>', $errors));
        return $msg;
    }

    protected function handleFontDelete($file)
    {
        $list = $this->getFonts();
        $new = [];
        foreach ($list as $f) {
            if ($f['file'] === $file) {
                $path = _PS_MODULE_DIR_ . $this->name . '/' . self::FONT_DIR . $file;
                if (file_exists($path)) @unlink($path);
            } else {
                $new[] = $f;
            }
        }
        $this->saveFonts($new);
        return $this->displayConfirmation($this->l('Police supprimée.'));
    }

    protected function getEnabledFonts()
    {
        $raw = Configuration::get('MUG_ENABLED_FONTS');
        $list = json_decode($raw, true);
        if (!is_array($list)) {
            return ['Arial' => true, 'Open Sans' => true, 'Bebas Neue' => true];
        }
        return $list;
    }

    protected function isFontEnabled($name)
    {
        $list = $this->getEnabledFonts();
        return isset($list[$name]);
    }

    protected function handleToggleFont($name)
    {
        $allValid = array_merge(self::WEB_SAFE_FONTS, self::THEME_FONTS, self::GOOGLE_FONTS);
        foreach ($this->getFonts() as $f) { $allValid[] = $f['family']; }
        if (!in_array($name, $allValid)) return '';

        $list = $this->getEnabledFonts();
        if (isset($list[$name])) {
            unset($list[$name]);
            $msg = sprintf($this->l('Police "%s" désactivée.'), $name);
        } else {
            $list[$name] = true;
            $msg = sprintf($this->l('Police "%s" activée.'), $name);
            if (in_array($name, self::GOOGLE_FONTS) || in_array($name, self::THEME_FONTS)) {
                $this->downloadGoogleFont($name);
            }
        }
        Configuration::updateValue('MUG_ENABLED_FONTS', json_encode($list));
        return $this->displayConfirmation($msg);
    }

    protected function downloadGoogleFont($family)
    {
        $cacheDir = _PS_MODULE_DIR_ . $this->name . '/uploads/fonts_cache/';
        if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

        $cssUrl = 'https://fonts.googleapis.com/css?family=' . urlencode($family) . ':400,700&display=swap';
        $ctx = stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 10]]);
        $css = @file_get_contents($cssUrl, false, $ctx);
        if (!$css) return false;

        if (!preg_match_all('/@font-face\s*\{[^}]*?font-weight:\s*(\d+)[^}]*?src:\s*url\(([^)]+)\)\s*format\([\'"]?(truetype|woff2?|opentype)[\'"]?\)/is', $css, $matches, PREG_SET_ORDER)) {
            preg_match_all('/src:\s*url\(([^)]+)\)/i', $css, $m2);
            if (!empty($m2[1])) {
                $url = trim($m2[1][0], '"\'');
                $data = @file_get_contents($url, false, $ctx);
                if ($data) {
                    $ext = strpos($url, '.woff2') ? 'woff2' : (strpos($url, '.ttf') ? 'ttf' : 'woff');
                    if ($ext === 'ttf') {
                        file_put_contents($cacheDir . str_replace(' ', '', $family) . '.ttf', $data);
                        return true;
                    }
                }
            }
            return false;
        }

        foreach ($matches as $m) {
            $weight = (int) $m[1];
            $url = trim($m[2], '"\'');
            $format = $m[3];
            if ($format !== 'truetype') continue;
            $data = @file_get_contents($url, false, $ctx);
            if (!$data) continue;
            $suffix = $weight >= 700 ? '-Bold' : '';
            file_put_contents($cacheDir . str_replace(' ', '', $family) . $suffix . '.ttf', $data);
        }
        return true;
    }

    protected function getActiveGoogleFonts()
    {
        $enabled = $this->getEnabledFonts();
        $active = [];
        foreach (self::GOOGLE_FONTS as $g) {
            if (isset($enabled[$g])) $active[] = $g;
        }
        return $active;
    }

    protected function renderFontsManager()
    {
        $list = $this->getFonts();
        $url = $this->_path . self::FONT_DIR;
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $enabled = $this->getEnabledFonts();
        $totalActive = count($enabled);

        $googleParams = [];
        foreach (self::GOOGLE_FONTS as $g) {
            $googleParams[] = 'family=' . str_replace(' ', '+', $g);
        }
        $googleUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $googleParams) . '&display=swap';

        $html = '<div class="panel"><h3><i class="icon-font"></i> ' . $this->l('Gestion des polices') . '</h3>';
        $html .= '<p style="color:#888;font-size:13px;">' . $this->l('Cliquez sur une police pour l\'activer ou la désactiver côté client. Aucune suppression définitive.') . '</p>';

        $html .= '<link href="' . $googleUrl . '" rel="stylesheet">';

        if (!empty($list)) {
            $html .= '<style>';
            foreach ($list as $f) {
                $fmt = $f['ext'] === 'ttf' ? 'truetype' : ($f['ext'] === 'otf' ? 'opentype' : $f['ext']);
                $html .= '@font-face{font-family:"' . htmlspecialchars($f['family']) . '";src:url("' . $url . $f['file'] . '") format("' . $fmt . '");}';
            }
            $html .= '</style>';
        }

        $html .= '<style>
            .mue-tag{display:inline-flex;align-items:center;background:#fff;padding:8px 16px;border-radius:20px;font-size:14px;color:#004774;cursor:pointer;text-decoration:none;transition:all .2s;}
            .mue-tag:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.1);text-decoration:none;}
            .mue-tag-on{border:2px solid #27ae60;background:#eafaf0;color:#0f6b3a;}
            .mue-tag-on::after{content:" ✓";font-weight:700;color:#27ae60;}
            .mue-tag-off{border:2px solid #e0e0e0;opacity:.55;}
            .mue-tag-off::after{content:" +";color:#999;font-size:16px;}
            .mue-cat{margin-bottom:22px;}
            .mue-cat-title{font-size:14px;font-weight:700;color:#004774;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
            .mue-cat-title .mue-cat-count{background:#004774;color:#fff;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:600;letter-spacing:0;text-transform:none;}
            .mue-tags-grid{display:flex;flex-wrap:wrap;gap:8px;}
        </style>';

        $html .= '<div style="background:#f0f7fc;border:1px solid #cfe2f0;border-radius:4px;padding:12px 18px;margin-bottom:18px;font-size:14px;color:#004774;">';
        $html .= '<strong>' . sprintf($this->l('%d police(s) actuellement disponible(s) côté client'), $totalActive) . '</strong>';
        $html .= '</div>';

        $renderCategory = function($title, $fonts, $type) use (&$html, $enabled, $base) {
            $activeCount = 0;
            foreach ($fonts as $f) { if (isset($enabled[$f])) $activeCount++; }
            $html .= '<div class="mue-cat">';
            $html .= '<div class="mue-cat-title">' . $title . ' <span class="mue-cat-count">' . $activeCount . ' / ' . count($fonts) . '</span></div>';
            $html .= '<div class="mue-tags-grid">';
            foreach ($fonts as $f) {
                $isOn = isset($enabled[$f]);
                $cls = 'mue-tag ' . ($isOn ? 'mue-tag-on' : 'mue-tag-off');
                $html .= '<a href="' . $base . '&toggleFont=' . urlencode($f) . '" class="' . $cls . '" style="font-family:\'' . htmlspecialchars($f) . '\',sans-serif;">' . htmlspecialchars($f) . '</a>';
            }
            $html .= '</div></div>';
        };

        $renderCategory($this->l('Polices Web-safe (système)'), self::WEB_SAFE_FONTS, 'websafe');
        $renderCategory($this->l('Polices du thème'), self::THEME_FONTS, 'theme');
        $renderCategory($this->l('Bibliothèque Google Fonts'), self::GOOGLE_FONTS, 'google');

        if (!empty($list)) {
            $customFamilies = array_map(function($f){ return $f['family']; }, $list);
            $renderCategory($this->l('Polices personnalisées (uploadées)'), $customFamilies, 'custom');
        }
        $html .= '<hr style="margin:25px 0;"/>';
        $html .= '<form method="post" enctype="multipart/form-data" id="mue-font-form">';
        $html .= '<label class="mue-dropzone" id="mue-fdz">
            <div class="mue-dropzone-icon">🔤</div>
            <div class="mue-dropzone-title">' . $this->l('Glissez vos polices ici') . '</div>
            <div class="mue-dropzone-sub">' . $this->l('ou cliquez pour parcourir — ttf, otf, woff, woff2 · max 2 Mo') . '</div>
            <input type="file" name="mug_font[]" id="mue-ffile" multiple accept=".ttf,.otf,.woff,.woff2" />
            <div class="mue-preview" id="mue-fpreview"></div>
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMugFontUpload" class="btn btn-primary"><i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mue-fdz"),inp=document.getElementById("mue-ffile"),pv=document.getElementById("mue-fpreview");
                if(!dz)return;
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mue-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mue-drag");});});
                dz.addEventListener("drop",function(ev){
                    var dt=new DataTransfer();
                    Array.from(ev.dataTransfer.files).forEach(function(f){dt.items.add(f);});
                    inp.files=dt.files;render();
                });
                inp.addEventListener("change",render);
                inp.addEventListener("click",function(e){e.stopPropagation();});
                function render(){
                    pv.innerHTML="";
                    Array.from(inp.files).forEach(function(f){
                        var d=document.createElement("div");
                        d.className="mue-preview-item";
                        d.style.display="flex";d.style.alignItems="center";d.style.justifyContent="center";
                        d.style.background="#fff";d.style.color="#004774";d.style.fontWeight="600";d.style.fontSize="11px";d.style.padding="4px";d.style.textAlign="center";
                        d.textContent=f.name;
                        pv.appendChild(d);
                    });
                }
            })();
        </script><hr/>';

        $html .= '</div>';
        return $html;
    }

    protected function getTemplate()
    {
        $file = Configuration::get('MUG_TEMPLATE');
        if (!$file) return null;
        $path = _PS_MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR . $file;
        if (!file_exists($path)) return null;
        $info = @getimagesize($path);
        return [
            'file' => $file,
            'url' => _MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR . $file . '?t=' . filemtime($path),
            'width' => $info ? $info[0] : 0,
            'height' => $info ? $info[1] : 0,
        ];
    }

    protected function handleTemplateUpload()
    {
        if (empty($_FILES['mug_template']) || $_FILES['mug_template']['error'] !== UPLOAD_ERR_OK) {
            return $this->displayWarning($this->l('Aucun fichier reçu.'));
        }
        $f = $_FILES['mug_template'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return $this->displayWarning($this->l('Format PNG requis uniquement.'));
        }
        if ($f['size'] > 5242880) {
            return $this->displayWarning($this->l('Fichier trop volumineux (max 5 Mo).'));
        }
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        foreach (glob($dir . 'template.*') as $old) { @unlink($old); }
        $dest = $dir . 'template.png';
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            return $this->displayWarning($this->l('Échec écriture du fichier.'));
        }
        Configuration::updateValue('MUG_TEMPLATE', 'template.png');
        return $this->displayConfirmation($this->l('Gabarit mis à jour.'));
    }

    protected function handleTemplateDelete()
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR;
        foreach (glob($dir . 'template.*') as $f) { @unlink($f); }
        Configuration::updateValue('MUG_TEMPLATE', '');
        return $this->displayConfirmation($this->l('Gabarit supprimé.'));
    }

    protected function renderTemplateManager()
    {
        $tpl = $this->getTemplate();
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $html = '<div class="panel"><h3><i class="icon-crop"></i> ' . $this->l('Gabarit du mug (template)') . '</h3>';
        $html .= '<p style="color:#888;font-size:13px;">' . $this->l('Image PNG avec zone centrale transparente. Dicte la forme et le ratio de la zone de personnalisation côté client.') . '</p>';

        if ($tpl) {
            $html .= '<div style="display:flex;gap:20px;align-items:center;background:#f0f7fc;border:1px solid #cfe2f0;border-radius:4px;padding:16px;margin-bottom:15px;">';
            $html .= '<img src="' . $tpl['url'] . '" style="max-width:300px;max-height:200px;border:1px solid #ddd;background:#e8e8e8;" />';
            $html .= '<div style="flex:1;">';
            $html .= '<div style="font-weight:600;color:#004774;font-size:14px;">' . $this->l('Gabarit actif') . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $tpl['width'] . ' × ' . $tpl['height'] . ' px · ratio ' . number_format($tpl['width'] / max($tpl['height'], 1), 2) . ':1</div>';
            $html .= '<a href="' . $base . '&deleteTemplate=1" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer ce gabarit ?\')">✕ ' . $this->l('Supprimer') . '</a>';
            $html .= '</div></div>';
        }

        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<label class="mue-dropzone" id="mue-tdz">
            <div class="mue-dropzone-icon">🖼</div>
            <div class="mue-dropzone-title">' . ($tpl ? $this->l('Remplacer le gabarit') : $this->l('Glissez le gabarit ici')) . '</div>
            <div class="mue-dropzone-sub">' . $this->l('ou cliquez pour parcourir — PNG uniquement · max 5 Mo') . '</div>
            <input type="file" name="mug_template" id="mue-tfile" accept="image/png" />
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMugTemplateUpload" class="btn btn-primary"><i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<div id="mue-tpl-feedback" style="display:none;margin-top:10px;padding:12px;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:4px;font-size:13px;color:#2e7d32;">
            <strong>Fichier sélectionné :</strong> <span id="mue-tpl-fname"></span>
            <div id="mue-tpl-fpreview" style="margin-top:8px;max-width:200px;max-height:120px;overflow:hidden;"></div>
        </div>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mue-tdz"),inp=document.getElementById("mue-tfile"),
                    fb=document.getElementById("mue-tpl-feedback"),fname=document.getElementById("mue-tpl-fname"),
                    fprev=document.getElementById("mue-tpl-fpreview");
                if(!dz)return;
                function showFeedback(){
                    if(!inp.files||!inp.files.length)return;
                    var f=inp.files[0];
                    fname.textContent=f.name+" ("+Math.round(f.size/1024)+" Ko)";
                    fprev.innerHTML="";
                    if(f.type.indexOf("image")===0){
                        var img=document.createElement("img");
                        img.style.cssText="max-width:200px;max-height:120px;border:1px solid #ccc;";
                        img.src=URL.createObjectURL(f);
                        fprev.appendChild(img);
                    }
                    fb.style.display="block";
                    dz.querySelector(".mue-dropzone-title").textContent="Fichier prêt — cliquez Uploader";
                    dz.style.borderColor="#4caf50";
                    dz.style.background="#f1f8e9";
                }
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mue-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mue-drag");});});
                dz.addEventListener("drop",function(ev){
                    var dt=new DataTransfer();
                    Array.from(ev.dataTransfer.files).forEach(function(f){dt.items.add(f);});
                    inp.files=dt.files;
                    showFeedback();
                });
                inp.addEventListener("change",function(){showFeedback();});
                inp.addEventListener("click",function(e){e.stopPropagation();});
            })();
        </script>';
        $html .= '</div>';
        return $html;
    }

    protected function renderBackgroundsManager()
    {
        $list = $this->getBackgrounds();
        $url = $this->_path . self::UPLOAD_DIR;
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $html = '<style>
            .mue-dropzone{border:3px dashed #ccd5e0;border-radius:8px;padding:40px 20px;text-align:center;background:#fafbfc;cursor:pointer;transition:all .2s;}
            .mue-dropzone:hover,.mue-dropzone.mue-drag{border-color:#ee7a03;background:#fff7ee;}
            .mue-dropzone-icon{font-size:54px;color:#004774;line-height:1;margin-bottom:12px;}
            .mue-dropzone-title{font-size:18px;font-weight:600;color:#004774;margin-bottom:6px;}
            .mue-dropzone-sub{font-size:13px;color:#888;}
            .mue-dropzone input[type=file]{display:none;}
            .mue-preview{display:flex;flex-wrap:wrap;gap:10px;margin-top:15px;}
            .mue-preview-item{position:relative;width:90px;height:90px;border-radius:4px;background-size:cover;background-position:center;border:1px solid #ddd;}
            .mue-preview-item .mue-rm{position:absolute;top:-6px;right:-6px;width:22px;height:22px;border-radius:50%;background:#e74c3c;color:#fff;border:none;cursor:pointer;font-size:14px;line-height:1;}
        </style>';
        $html .= '<div class="panel"><h3><i class="icon-picture"></i> ' . $this->l('Gestion des fonds') . '</h3>';
        $html .= '<form method="post" enctype="multipart/form-data" id="mue-upload-form">';
        $html .= '<label class="mue-dropzone" id="mue-dropzone">
            <div class="mue-dropzone-icon">⬆</div>
            <div class="mue-dropzone-title">' . $this->l('Glissez-déposez vos images ici') . '</div>
            <div class="mue-dropzone-sub">' . $this->l('ou cliquez pour parcourir — jpg, png, webp · max 5 Mo') . '</div>
            <input type="file" name="mug_bg[]" id="mue-file" multiple accept="image/jpeg,image/png,image/webp" />
            <div class="mue-preview" id="mue-preview"></div>
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMugBackgroundUpload" class="btn btn-primary">'
            . '<i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mue-dropzone"),inp=document.getElementById("mue-file"),pv=document.getElementById("mue-preview");
                if(!dz)return;
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mue-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mue-drag");});});
                dz.addEventListener("drop",function(ev){
                    var dt=new DataTransfer();
                    Array.from(ev.dataTransfer.files).forEach(function(f){dt.items.add(f);});
                    inp.files=dt.files;render();
                });
                inp.addEventListener("change",render);
                inp.addEventListener("click",function(e){e.stopPropagation();});
                function render(){
                    pv.innerHTML="";
                    Array.from(inp.files).forEach(function(f,i){
                        var r=new FileReader();
                        r.onload=function(e){
                            var d=document.createElement("div");
                            d.className="mue-preview-item";
                            d.style.backgroundImage="url("+e.target.result+")";
                            pv.appendChild(d);
                        };
                        r.readAsDataURL(f);
                    });
                }
            })();
        </script><hr/>';

        if (empty($list)) {
            $html .= '<p>' . $this->l('Aucun fond pour le moment.') . '</p>';
        } else {
            $html .= '<p style="color:#888;font-size:13px;margin-bottom:10px;"><i class="icon-info-circle"></i> ' . $this->l('Glissez-déposez les vignettes pour réorganiser l\'ordre d\'affichage.') . '</p>';
            $html .= '<div id="mue-bg-list" style="display:flex;flex-wrap:wrap;gap:15px;">';
            foreach ($list as $f) {
                $html .= '<div class="mue-bg-card" draggable="true" data-file="' . htmlspecialchars($f) . '" style="border:1px solid #ddd;padding:8px;width:160px;text-align:center;background:#fafafa;cursor:move;border-radius:4px;transition:all .15s;">';
                $html .= '<img src="' . $url . $f . '" style="max-width:100%;height:100px;object-fit:cover;display:block;margin-bottom:6px;pointer-events:none;" />';
                $html .= '<a href="' . $base . '&deleteBg=' . urlencode($f) . '" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer ce fond ?\')">✕ ' . $this->l('Supprimer') . '</a>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '<style>
                .mue-bg-card.mue-dragging{opacity:.4;}
                .mue-bg-card.mue-over{border-color:#ee7a03 !important;background:#fff7ee !important;transform:scale(1.03);}
            </style>';
            $html .= '<script>
                (function(){
                    var list=document.getElementById("mue-bg-list");
                    if(!list)return;
                    var dragEl=null;
                    var cards=list.querySelectorAll(".mue-bg-card");
                    cards.forEach(function(c){
                        c.addEventListener("dragstart",function(e){dragEl=c;c.classList.add("mue-dragging");e.dataTransfer.effectAllowed="move";});
                        c.addEventListener("dragend",function(){c.classList.remove("mue-dragging");cards.forEach(function(x){x.classList.remove("mue-over");});save();});
                        c.addEventListener("dragover",function(e){e.preventDefault();e.dataTransfer.dropEffect="move";});
                        c.addEventListener("dragenter",function(){if(c!==dragEl)c.classList.add("mue-over");});
                        c.addEventListener("dragleave",function(){c.classList.remove("mue-over");});
                        c.addEventListener("drop",function(e){
                            e.preventDefault();
                            if(c===dragEl)return;
                            var rect=c.getBoundingClientRect();
                            var after=(e.clientX-rect.left)>rect.width/2;
                            list.insertBefore(dragEl,after?c.nextSibling:c);
                        });
                    });
                    function save(){
                        var order=Array.from(list.querySelectorAll(".mue-bg-card")).map(function(c){return c.dataset.file;}).join(",");
                        var url="' . $base . '&reorderBgs="+encodeURIComponent(order)+"&ajax=1";
                        fetch(url,{credentials:"same-origin"});
                    }
                })();
            </script>';
        }

        $html .= '</div>';
        return $html;
    }

    protected function getRenderImage($type)
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::RENDER_DIR;
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = $dir . $type . '.' . $ext;
            if (file_exists($file)) {
                $info = @getimagesize($file);
                return [
                    'path' => $file,
                    'url' => _MODULE_DIR_ . $this->name . '/' . self::RENDER_DIR . $type . '.' . $ext . '?t=' . filemtime($file),
                    'ext' => $ext,
                    'filename' => $type . '.' . $ext,
                    'width' => $info ? $info[0] : 0,
                    'height' => $info ? $info[1] : 0,
                    'filesize' => filesize($file),
                ];
            }
        }
        return null;
    }

    protected function handleRenderUpload()
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::RENDER_DIR;
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }

        $output = '';
        foreach (['mug_render_base' => 'base', 'mug_render_lighting' => 'lighting'] as $field => $type) {
            if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $f = $_FILES[$field];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $output .= $this->displayError($this->l('Erreur upload ' . $type . ' : code ' . $f['error']));
                continue;
            }
            if ($f['size'] > self::MAX_SIZE) {
                $output .= $this->displayError($this->l('Fichier ' . $type . ' trop volumineux (max 5 Mo).'));
                continue;
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $output .= $this->displayError($this->l('Format ' . $type . ' non supporté (PNG, JPG, WEBP).'));
                continue;
            }
            // Supprimer l'ancien fichier
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $oldExt) {
                $old = $dir . $type . '.' . $oldExt;
                if (file_exists($old)) { unlink($old); }
            }
            $dest = $dir . $type . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                $output .= $this->displayError($this->l('Impossible de sauvegarder ' . $type . '.'));
                continue;
            }
            $label = $type === 'base' ? 'Image de base' : 'Image éclairages';
            $output .= $this->displayConfirmation($this->l($label . ' uploadée.'));
        }
        return $output;
    }

    protected function handleRenderDelete($type)
    {
        $img = $this->getRenderImage($type);
        if ($img && file_exists($img['path'])) {
            unlink($img['path']);
            return $this->displayConfirmation($this->l('Image supprimée.'));
        }
        return $this->displayError($this->l('Image introuvable.'));
    }

    protected function renderMugRenderManager()
    {
        $baseImg = $this->getRenderImage('base');
        $lightImg = $this->getRenderImage('lighting');
        $adminUrl = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $checkerBg = 'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22><rect width=%2210%22 height=%2210%22 fill=%22%23ddd%22/><rect x=%2210%22 y=%2210%22 width=%2210%22 height=%2210%22 fill=%22%23ddd%22/></svg>';

        $html = '<div class="panel"><h3><i class="icon-image"></i> ' . $this->l('Rendu du mug (aperçu)') . '</h3>';
        $html .= '<p style="color:#555;margin-bottom:20px;">'
            . $this->l('Uploadez les 2 images pour le rendu visuel du mug. L\'image de base sert de fond, l\'image éclairages se superpose pour les effets de lumière.')
            . '<br><strong>Ordre des calques :</strong> Image de base → Création du client → Image éclairages'
            . '</p>';

        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<div style="display:flex;gap:30px;flex-wrap:wrap;margin-bottom:20px;">';

        // ---- Image de base ----
        $html .= '<div style="flex:1;min-width:280px;">';
        $html .= '<h4 style="color:#004774;margin-bottom:10px;">1. Image de base</h4>';
        if ($baseImg) {
            $html .= '<div style="display:flex;gap:20px;align-items:center;background:#f0f7fc;border:1px solid #cfe2f0;padding:16px;margin-bottom:12px;">';
            $html .= '<img src="' . $baseImg['url'] . '" style="max-width:300px;max-height:250px;border:1px solid #ddd;background:#e8e8e8;" />';
            $html .= '<div style="flex:1;">';
            $html .= '<div style="font-weight:600;color:#004774;font-size:14px;">' . $this->l('Image active') . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $baseImg['filename'] . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $baseImg['width'] . ' × ' . $baseImg['height'] . ' px · ' . round($baseImg['filesize'] / 1024) . ' Ko</div>';
            $html .= '<a href="' . $adminUrl . '&deleteRenderBase=1" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer l\\\'image de base ?\')">✕ ' . $this->l('Supprimer') . '</a>';
            $html .= '</div></div>';
        }
        $html .= '<label class="mue-dropzone" id="mue-rdz-base">
            <div class="mue-dropzone-icon">🖼</div>
            <div class="mue-dropzone-title">' . ($baseImg ? $this->l('Remplacer l\'image de base') : $this->l('Glissez l\'image de base ici')) . '</div>
            <div class="mue-dropzone-sub">' . $this->l('ou cliquez pour parcourir — PNG, JPG, WEBP · max 5 Mo') . '</div>
            <input type="file" name="mug_render_base" id="mue-rfile-base" accept="image/png,image/jpeg,image/webp" />
        </label>';
        $html .= '<div id="mue-rfb-base" style="display:none;margin-top:10px;padding:12px;background:#e8f5e9;border:1px solid #a5d6a7;font-size:13px;color:#2e7d32;">
            <strong>Fichier sélectionné :</strong> <span id="mue-rfname-base"></span>
            <div id="mue-rfprev-base" style="margin-top:8px;max-width:200px;max-height:120px;overflow:hidden;"></div>
        </div>';
        $html .= '</div>';

        // ---- Image éclairages ----
        $html .= '<div style="flex:1;min-width:280px;">';
        $html .= '<h4 style="color:#004774;margin-bottom:10px;">2. Image éclairages</h4>';
        if ($lightImg) {
            $html .= '<div style="display:flex;gap:20px;align-items:center;background:#f0f7fc;border:1px solid #cfe2f0;padding:16px;margin-bottom:12px;">';
            $html .= '<img src="' . $lightImg['url'] . '" style="max-width:300px;max-height:250px;border:1px solid #ddd;background-image:url(\'' . $checkerBg . '\');background-repeat:repeat;background-size:20px;" />';
            $html .= '<div style="flex:1;">';
            $html .= '<div style="font-weight:600;color:#004774;font-size:14px;">' . $this->l('Image active') . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $lightImg['filename'] . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $lightImg['width'] . ' × ' . $lightImg['height'] . ' px · ' . round($lightImg['filesize'] / 1024) . ' Ko</div>';
            $html .= '<a href="' . $adminUrl . '&deleteRenderLighting=1" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer l\\\'image éclairages ?\')">✕ ' . $this->l('Supprimer') . '</a>';
            $html .= '</div></div>';
        }
        $html .= '<label class="mue-dropzone" id="mue-rdz-light">
            <div class="mue-dropzone-icon">💡</div>
            <div class="mue-dropzone-title">' . ($lightImg ? $this->l('Remplacer l\'image éclairages') : $this->l('Glissez l\'image éclairages ici')) . '</div>
            <div class="mue-dropzone-sub">' . $this->l('ou cliquez pour parcourir — PNG transparent recommandé · max 5 Mo') . '</div>
            <input type="file" name="mug_render_lighting" id="mue-rfile-light" accept="image/png,image/jpeg,image/webp" />
        </label>';
        $html .= '<div id="mue-rfb-light" style="display:none;margin-top:10px;padding:12px;background:#e8f5e9;border:1px solid #a5d6a7;font-size:13px;color:#2e7d32;">
            <strong>Fichier sélectionné :</strong> <span id="mue-rfname-light"></span>
            <div id="mue-rfprev-light" style="margin-top:8px;max-width:200px;max-height:120px;overflow:hidden;"></div>
        </div>';
        $html .= '</div>';

        $html .= '</div>'; // flex container
        $html .= '<div style="text-align:right;"><button type="submit" name="submitMugRenderUpload" class="btn btn-primary"><i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';

        // JS drag & drop pour les 2 zones
        $html .= '<script>
        (function(){
            function setupDZ(dzId, inpId, fbId, fnameId, fprevId) {
                var dz=document.getElementById(dzId), inp=document.getElementById(inpId),
                    fb=document.getElementById(fbId), fname=document.getElementById(fnameId),
                    fprev=document.getElementById(fprevId);
                if(!dz||!inp)return;
                function showFeedback(){
                    if(!inp.files||!inp.files.length)return;
                    var f=inp.files[0];
                    fname.textContent=f.name+" ("+Math.round(f.size/1024)+" Ko)";
                    fprev.innerHTML="";
                    if(f.type.indexOf("image")===0){
                        var img=document.createElement("img");
                        img.style.cssText="max-width:200px;max-height:120px;border:1px solid #ccc;";
                        img.src=URL.createObjectURL(f);
                        fprev.appendChild(img);
                    }
                    fb.style.display="block";
                    dz.querySelector(".mue-dropzone-title").textContent="Fichier prêt — cliquez Uploader";
                    dz.style.borderColor="#4caf50";
                    dz.style.background="#f1f8e9";
                }
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mue-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mue-drag");});});
                dz.addEventListener("drop",function(ev){
                    var dt=new DataTransfer();
                    Array.from(ev.dataTransfer.files).forEach(function(f){dt.items.add(f);});
                    inp.files=dt.files;
                    showFeedback();
                });
                inp.addEventListener("change",function(){showFeedback();});
                inp.addEventListener("click",function(e){e.stopPropagation();});
            }
            setupDZ("mue-rdz-base","mue-rfile-base","mue-rfb-base","mue-rfname-base","mue-rfprev-base");
            setupDZ("mue-rdz-light","mue-rfile-light","mue-rfb-light","mue-rfname-light","mue-rfprev-light");
        })();
        </script>';

        $html .= '</div>';
        return $html;
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('IDs des produits'),
                        'name' => 'MUG_PRODUCT_IDS',
                        'desc' => $this->l('Liste des IDs produits séparés par des virgules (ex: 30,31,35).'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->submit_action = 'submitMugeditorModule';
        $helper->fields_value['MUG_PRODUCT_IDS'] = Configuration::get('MUG_PRODUCT_IDS');

        return $helper->generateForm([$fields_form]);
    }

    public function hookDisplayMugEditor($params)
    {
        $productId = (int) Tools::getValue('id_product');

        if (!$productId) {
            return '';
        }

        $configIds = Configuration::get('MUG_PRODUCT_IDS');
        if (empty($configIds)) {
            return '';
        }

        $allowedIds = array_map('intval', array_filter(explode(',', $configIds)));

        if (!in_array($productId, $allowedIds)) {
            return '';
        }

        // Protection : si déjà pris par mousepadeditor, on ne s'affiche pas
        $mousepadIds = Configuration::get('MOUSEPAD_PRODUCT_IDS');
        if (!empty($mousepadIds)) {
            $mousepadList = array_map('intval', array_filter(explode(',', $mousepadIds)));
            if (in_array($productId, $mousepadList)) {
                return '';
            }
        }

        $backgrounds = $this->getBackgrounds();
        $bgUrl = $this->_path . self::UPLOAD_DIR;

        $customerBg = $this->getCustomerBackground();

        $customFonts = $this->getFonts();
        $fontUrl = $this->_path . self::FONT_DIR;
        $enabled = $this->getEnabledFonts();

        $activeWebsafe = array_values(array_filter(self::WEB_SAFE_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeTheme = array_values(array_filter(self::THEME_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeGoogle = array_values(array_filter(self::GOOGLE_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeCustom = array_values(array_filter($customFonts, function($f) use ($enabled){ return isset($enabled[$f['family']]); }));

        $googleFrontUrl = '';
        if (!empty($activeGoogle)) {
            $params = [];
            foreach ($activeGoogle as $g) { $params[] = 'family=' . str_replace(' ', '+', $g) . ':wght@400;700'; }
            $googleFrontUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $params) . '&display=swap';
        }

        $template = $this->getTemplate();
        $composeUrl = $this->context->link->getModuleLink('mugeditor', 'compose', [], true);
        $attachUrl = $this->context->link->getModuleLink('mugeditor', 'attachcustom', [], true);

        $this->context->smarty->assign([
            'mue_backgrounds' => $backgrounds,
            'mue_bg_url' => $bgUrl,
            'mue_customer_bg' => $customerBg,
            'mue_upload_url' => $this->context->link->getModuleLink('mugeditor', 'upload', [], true),
            'mue_uploadimage_url' => $this->context->link->getModuleLink('mugeditor', 'uploadimage', [], true),
            'mue_fonts' => $activeCustom,
            'mue_font_url' => $fontUrl,
            'mue_default_fonts' => array_merge($activeWebsafe, $activeTheme, $activeGoogle),
            'mue_google_url' => $googleFrontUrl,
            'mue_template' => $template,
            'mue_compose_url' => $composeUrl,
            'mue_attach_url' => $attachUrl,
            'mue_product_id' => $productId,
            'mue_lsv_blocs' => class_exists('LeSaviezVous') ? LeSaviezVous::getBlocs() : [],
            'mue_render_base' => $this->getRenderImage('base'),
            'mue_render_lighting' => $this->getRenderImage('lighting'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/editor.tpl');
    }
}
