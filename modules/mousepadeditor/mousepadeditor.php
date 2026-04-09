<?php
/**
 * Editor Mouse Pad - Personnalisation de tapis de souris
 *
 * @author    Claude
 * @copyright 2026
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mousepadeditor extends Module
{
    const UPLOAD_DIR = 'uploads/backgrounds/';
    const FONT_DIR = 'uploads/fonts/';
    const TEMPLATE_DIR = 'uploads/template/';
    const MAX_SIZE = 5242880; // 5 Mo
    const FONT_MAX_SIZE = 2097152; // 2 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];
    const FONT_ALLOWED = ['ttf', 'otf', 'woff', 'woff2'];

    const WEB_SAFE_FONTS = ['Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Courier New', 'Impact', 'Comic Sans MS'];
    const THEME_FONTS = ['Manrope'];
    const GOOGLE_FONTS = ['Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Raleway', 'Poppins', 'Merriweather', 'Ubuntu', 'Playfair Display', 'Nunito', 'Bebas Neue', 'Dancing Script', 'Pacifico', 'Lobster', 'Anton', 'Caveat', 'Quicksand', 'Inter', 'Work Sans', 'Comfortaa', 'Abril Fatface', 'Permanent Marker', 'Indie Flower', 'Yanone Kaffeesatz', 'Amatic SC', 'Archivo', 'Karla', 'Satisfy', 'Great Vibes'];

    public function __construct()
    {
        $this->name = 'mousepadeditor';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Editor Mouse Pad');
        $this->description = $this->l('Affiche une zone de personnalisation sur les fiches produits sélectionnés.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return parent::install()
            && $this->registerHook('displayMousepadEditor')
            && $this->registerHook('header')
            && $this->registerHook('actionPurgatorRegister')
            && Configuration::updateValue('MOUSEPAD_PRODUCT_IDS', '')
            && Configuration::updateValue('MOUSEPAD_BACKGROUNDS', json_encode([]))
            && Configuration::updateValue('MOUSEPAD_FONTS', json_encode([]))
            && Configuration::updateValue('MOUSEPAD_ENABLED_FONTS', json_encode(['Arial' => true, 'Open Sans' => true, 'Bebas Neue' => true]));
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOUSEPAD_PRODUCT_IDS');
        Configuration::deleteByName('MOUSEPAD_BACKGROUNDS');
        Configuration::deleteByName('MOUSEPAD_FONTS');
        Configuration::deleteByName('MOUSEPAD_ENABLED_FONTS');

        return parent::uninstall();
    }

    protected function getCustomerBackground()
    {
        $ctx = Context::getContext();
        if ($ctx->customer && $ctx->customer->isLogged()) {
            $key = 'c_' . (int) $ctx->customer->id;
        } elseif (!empty($ctx->cookie->mpe_guest_hash)) {
            $key = 'g_' . $ctx->cookie->mpe_guest_hash;
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

        // Source 1 : fonds clients
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
            'source_id' => 'mousepadeditor_customer_bg',
            'source_name' => $this->l('Editor Mouse Pad — Fonds clients'),
            'files' => $files,
        ]);

        // Source 2 : aperçus HD générés (orphelins en /uploads/previews/)
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
            'source_id' => 'mousepadeditor_previews',
            'source_name' => $this->l('Editor Mouse Pad — Aperçus HD non finalisés'),
            'files' => $files2,
        ]);
    }

    public function hookHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/mousepadeditor.css');
        $this->context->controller->addJS($this->_path . 'views/js/fabric.min.js');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMousepadeditorModule')) {
            $ids = Tools::getValue('MOUSEPAD_PRODUCT_IDS');
            $ids = preg_replace('/[^0-9,]/', '', $ids);
            Configuration::updateValue('MOUSEPAD_PRODUCT_IDS', $ids);
            $output .= $this->displayConfirmation($this->l('Paramètres mis à jour.'));
        }

        if (Tools::isSubmit('submitMousepadBackgroundUpload')) {
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

        if (Tools::isSubmit('submitMousepadFontUpload')) {
            $output .= $this->handleFontUpload();
        }
        if (Tools::getValue('toggleFont')) {
            $output .= $this->handleToggleFont(Tools::getValue('toggleFont'));
        }

        if (Tools::isSubmit('submitMousepadTemplateUpload')) {
            $output .= $this->handleTemplateUpload();
        }
        if (Tools::getValue('deleteTemplate')) {
            $output .= $this->handleTemplateDelete();
        }

        return $output . $this->renderForm() . $this->renderTemplateManager() . $this->renderBackgroundsManager() . $this->renderFontsManager();
    }

    protected function getBackgrounds()
    {
        $raw = Configuration::get('MOUSEPAD_BACKGROUNDS');
        $list = json_decode($raw, true);
        return is_array($list) ? $list : [];
    }

    protected function saveBackgrounds(array $list)
    {
        Configuration::updateValue('MOUSEPAD_BACKGROUNDS', json_encode(array_values($list)));
    }

    protected function handleUpload()
    {
        if (empty($_FILES['mousepad_bg']) || empty($_FILES['mousepad_bg']['name'][0])) {
            return $this->displayWarning($this->l('Aucun fichier sélectionné.'));
        }

        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $list = $this->getBackgrounds();
        $errors = [];
        $count = 0;

        $files = $_FILES['mousepad_bg'];
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
        $productIds = Configuration::get('MOUSEPAD_PRODUCT_IDS');
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
            'Recomposition serveur HD (PNG 1299×1063 px @ 150dpi)',
            'Sauvegarde de la création dans le panier (customization PrestaShop)',
            'Aperçu HD téléchargeable depuis la fiche commande BO',
            'Module Purgator (purge fichiers orphelins > 90j)',
        ];

        $html = '<div class="panel" style="border-left:4px solid #ee7a03;">';
        $html .= '<h3><i class="icon-dashboard"></i> ' . $this->l('État du module Editor Mouse Pad') . '</h3>';
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">';

        // Colonne 1 : ce qui est dispo
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

        // Colonne 2 : à venir
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
        $raw = Configuration::get('MOUSEPAD_FONTS');
        $list = json_decode($raw, true);
        return is_array($list) ? $list : [];
    }

    protected function saveFonts(array $list)
    {
        Configuration::updateValue('MOUSEPAD_FONTS', json_encode(array_values($list)));
    }

    protected function handleFontUpload()
    {
        if (empty($_FILES['mousepad_font']) || empty($_FILES['mousepad_font']['name'][0])) {
            return $this->displayWarning($this->l('Aucun fichier sélectionné.'));
        }
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::FONT_DIR;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

        $list = $this->getFonts();
        $errors = [];
        $count = 0;
        $files = $_FILES['mousepad_font'];
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
        $raw = Configuration::get('MOUSEPAD_ENABLED_FONTS');
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
            // Télécharger Google Font pour recomposition serveur
            if (in_array($name, self::GOOGLE_FONTS) || in_array($name, self::THEME_FONTS)) {
                $this->downloadGoogleFont($name);
            }
        }
        Configuration::updateValue('MOUSEPAD_ENABLED_FONTS', json_encode($list));
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

        // Parse chaque @font-face
        if (!preg_match_all('/@font-face\s*\{[^}]*?font-weight:\s*(\d+)[^}]*?src:\s*url\(([^)]+)\)\s*format\([\'"]?(truetype|woff2?|opentype)[\'"]?\)/is', $css, $matches, PREG_SET_ORDER)) {
            // Fallback plus simple
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
            if ($format !== 'truetype') continue; // on ne garde que TTF
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

        // URL Google Fonts pour preview BO de toutes les Google Fonts du catalogue
        $googleParams = [];
        foreach (self::GOOGLE_FONTS as $g) {
            $googleParams[] = 'family=' . str_replace(' ', '+', $g);
        }
        $googleUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $googleParams) . '&display=swap';

        $html = '<div class="panel"><h3><i class="icon-font"></i> ' . $this->l('Gestion des polices') . '</h3>';
        $html .= '<p style="color:#888;font-size:13px;">' . $this->l('Cliquez sur une police pour l\'activer ou la désactiver côté client. Aucune suppression définitive.') . '</p>';

        // Charger Google Fonts catalogue pour preview BO
        $html .= '<link href="' . $googleUrl . '" rel="stylesheet">';

        // Charger custom fonts pour preview BO
        if (!empty($list)) {
            $html .= '<style>';
            foreach ($list as $f) {
                $fmt = $f['ext'] === 'ttf' ? 'truetype' : ($f['ext'] === 'otf' ? 'opentype' : $f['ext']);
                $html .= '@font-face{font-family:"' . htmlspecialchars($f['family']) . '";src:url("' . $url . $f['file'] . '") format("' . $fmt . '");}';
            }
            $html .= '</style>';
        }

        $html .= '<style>
            .mpe-tag{display:inline-flex;align-items:center;background:#fff;padding:8px 16px;border-radius:20px;font-size:14px;color:#004774;cursor:pointer;text-decoration:none;transition:all .2s;}
            .mpe-tag:hover{transform:translateY(-1px);box-shadow:0 2px 6px rgba(0,0,0,.1);text-decoration:none;}
            .mpe-tag-on{border:2px solid #27ae60;background:#eafaf0;color:#0f6b3a;}
            .mpe-tag-on::after{content:" ✓";font-weight:700;color:#27ae60;}
            .mpe-tag-off{border:2px solid #e0e0e0;opacity:.55;}
            .mpe-tag-off::after{content:" +";color:#999;font-size:16px;}
            .mpe-cat{margin-bottom:22px;}
            .mpe-cat-title{font-size:14px;font-weight:700;color:#004774;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
            .mpe-cat-title .mpe-cat-count{background:#004774;color:#fff;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:600;letter-spacing:0;text-transform:none;}
            .mpe-tags-grid{display:flex;flex-wrap:wrap;gap:8px;}
        </style>';

        // Résumé global
        $html .= '<div style="background:#f0f7fc;border:1px solid #cfe2f0;border-radius:4px;padding:12px 18px;margin-bottom:18px;font-size:14px;color:#004774;">';
        $html .= '<strong>' . sprintf($this->l('%d police(s) actuellement disponible(s) côté client'), $totalActive) . '</strong>';
        $html .= '</div>';

        $renderCategory = function($title, $fonts, $type) use (&$html, $enabled, $base) {
            $activeCount = 0;
            foreach ($fonts as $f) { if (isset($enabled[$f])) $activeCount++; }
            $html .= '<div class="mpe-cat">';
            $html .= '<div class="mpe-cat-title">' . $title . ' <span class="mpe-cat-count">' . $activeCount . ' / ' . count($fonts) . '</span></div>';
            $html .= '<div class="mpe-tags-grid">';
            foreach ($fonts as $f) {
                $isOn = isset($enabled[$f]);
                $cls = 'mpe-tag ' . ($isOn ? 'mpe-tag-on' : 'mpe-tag-off');
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
        $html .= '<form method="post" enctype="multipart/form-data" id="mpe-font-form">';
        $html .= '<label class="mpe-dropzone" id="mpe-fdz">
            <div class="mpe-dropzone-icon">🔤</div>
            <div class="mpe-dropzone-title">' . $this->l('Glissez vos polices ici') . '</div>
            <div class="mpe-dropzone-sub">' . $this->l('ou cliquez pour parcourir — ttf, otf, woff, woff2 · max 2 Mo') . '</div>
            <input type="file" name="mousepad_font[]" id="mpe-ffile" multiple accept=".ttf,.otf,.woff,.woff2" />
            <div class="mpe-preview" id="mpe-fpreview"></div>
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMousepadFontUpload" class="btn btn-primary"><i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mpe-fdz"),inp=document.getElementById("mpe-ffile"),pv=document.getElementById("mpe-fpreview");
                if(!dz)return;
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mpe-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mpe-drag");});});
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
                        d.className="mpe-preview-item";
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
        $file = Configuration::get('MOUSEPAD_TEMPLATE');
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
        if (empty($_FILES['mousepad_template']) || $_FILES['mousepad_template']['error'] !== UPLOAD_ERR_OK) {
            return $this->displayWarning($this->l('Aucun fichier reçu.'));
        }
        $f = $_FILES['mousepad_template'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return $this->displayWarning($this->l('Format PNG requis uniquement.'));
        }
        if ($f['size'] > 5242880) {
            return $this->displayWarning($this->l('Fichier trop volumineux (max 5 Mo).'));
        }
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        // Purge ancien
        foreach (glob($dir . 'template.*') as $old) { @unlink($old); }
        $dest = $dir . 'template.png';
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            return $this->displayWarning($this->l('Échec écriture du fichier.'));
        }
        Configuration::updateValue('MOUSEPAD_TEMPLATE', 'template.png');
        return $this->displayConfirmation($this->l('Gabarit mis à jour.'));
    }

    protected function handleTemplateDelete()
    {
        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::TEMPLATE_DIR;
        foreach (glob($dir . 'template.*') as $f) { @unlink($f); }
        Configuration::updateValue('MOUSEPAD_TEMPLATE', '');
        return $this->displayConfirmation($this->l('Gabarit supprimé.'));
    }

    protected function renderTemplateManager()
    {
        $tpl = $this->getTemplate();
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $html = '<div class="panel"><h3><i class="icon-crop"></i> ' . $this->l('Gabarit du tapis (template)') . '</h3>';
        $html .= '<p style="color:#888;font-size:13px;">' . $this->l('Image PNG avec zone centrale transparente. Dicte la forme et le ratio de la zone de personnalisation côté client.') . '</p>';

        if ($tpl) {
            $html .= '<div style="display:flex;gap:20px;align-items:center;background:#f0f7fc;border:1px solid #cfe2f0;border-radius:4px;padding:16px;margin-bottom:15px;">';
            $html .= '<div style="width:200px;height:160px;background-image:url(\'data:image/svg+xml;utf8,<svg xmlns=\\"http://www.w3.org/2000/svg\\" width=\\"20\\" height=\\"20\\"><rect width=\\"10\\" height=\\"10\\" fill=\\"%23ddd\\"/><rect x=\\"10\\" y=\\"10\\" width=\\"10\\" height=\\"10\\" fill=\\"%23ddd\\"/></svg>\'),url(\'' . $tpl['url'] . '\');background-repeat:repeat,no-repeat;background-size:20px,contain;background-position:center;border:1px solid #ddd;"></div>';
            $html .= '<div style="flex:1;">';
            $html .= '<div style="font-weight:600;color:#004774;font-size:14px;">' . $this->l('Gabarit actif') . '</div>';
            $html .= '<div style="font-size:13px;color:#666;margin:4px 0;">' . $tpl['width'] . ' × ' . $tpl['height'] . ' px · ratio ' . number_format($tpl['width'] / max($tpl['height'], 1), 2) . ':1</div>';
            $html .= '<a href="' . $base . '&deleteTemplate=1" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer ce gabarit ?\')">✕ ' . $this->l('Supprimer') . '</a>';
            $html .= '</div></div>';
        }

        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<label class="mpe-dropzone" id="mpe-tdz">
            <div class="mpe-dropzone-icon">🖼</div>
            <div class="mpe-dropzone-title">' . ($tpl ? $this->l('Remplacer le gabarit') : $this->l('Glissez le gabarit ici')) . '</div>
            <div class="mpe-dropzone-sub">' . $this->l('ou cliquez pour parcourir — PNG uniquement · max 5 Mo') . '</div>
            <input type="file" name="mousepad_template" id="mpe-tfile" accept="image/png" />
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMousepadTemplateUpload" class="btn btn-primary"><i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<div id="mpe-tpl-feedback" style="display:none;margin-top:10px;padding:12px;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:4px;font-size:13px;color:#2e7d32;">
            <strong>Fichier sélectionné :</strong> <span id="mpe-tpl-fname"></span>
            <div id="mpe-tpl-fpreview" style="margin-top:8px;max-width:200px;max-height:120px;overflow:hidden;"></div>
        </div>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mpe-tdz"),inp=document.getElementById("mpe-tfile"),
                    fb=document.getElementById("mpe-tpl-feedback"),fname=document.getElementById("mpe-tpl-fname"),
                    fprev=document.getElementById("mpe-tpl-fpreview");
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
                    dz.querySelector(".mpe-dropzone-title").textContent="Fichier prêt — cliquez Uploader";
                    dz.style.borderColor="#4caf50";
                    dz.style.background="#f1f8e9";
                }
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mpe-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mpe-drag");});});
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
            .mpe-dropzone{border:3px dashed #ccd5e0;border-radius:8px;padding:40px 20px;text-align:center;background:#fafbfc;cursor:pointer;transition:all .2s;}
            .mpe-dropzone:hover,.mpe-dropzone.mpe-drag{border-color:#ee7a03;background:#fff7ee;}
            .mpe-dropzone-icon{font-size:54px;color:#004774;line-height:1;margin-bottom:12px;}
            .mpe-dropzone-title{font-size:18px;font-weight:600;color:#004774;margin-bottom:6px;}
            .mpe-dropzone-sub{font-size:13px;color:#888;}
            .mpe-dropzone input[type=file]{display:none;}
            .mpe-preview{display:flex;flex-wrap:wrap;gap:10px;margin-top:15px;}
            .mpe-preview-item{position:relative;width:90px;height:90px;border-radius:4px;background-size:cover;background-position:center;border:1px solid #ddd;}
            .mpe-preview-item .mpe-rm{position:absolute;top:-6px;right:-6px;width:22px;height:22px;border-radius:50%;background:#e74c3c;color:#fff;border:none;cursor:pointer;font-size:14px;line-height:1;}
        </style>';
        $html .= '<div class="panel"><h3><i class="icon-picture"></i> ' . $this->l('Gestion des fonds') . '</h3>';
        $html .= '<form method="post" enctype="multipart/form-data" id="mpe-upload-form">';
        $html .= '<label class="mpe-dropzone" id="mpe-dropzone">
            <div class="mpe-dropzone-icon">⬆</div>
            <div class="mpe-dropzone-title">' . $this->l('Glissez-déposez vos images ici') . '</div>
            <div class="mpe-dropzone-sub">' . $this->l('ou cliquez pour parcourir — jpg, png, webp · max 5 Mo') . '</div>
            <input type="file" name="mousepad_bg[]" id="mpe-file" multiple accept="image/jpeg,image/png,image/webp" />
            <div class="mpe-preview" id="mpe-preview"></div>
        </label>';
        $html .= '<div style="margin-top:15px;text-align:right;"><button type="submit" name="submitMousepadBackgroundUpload" class="btn btn-primary">'
            . '<i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button></div>';
        $html .= '</form>';
        $html .= '<script>
            (function(){
                var dz=document.getElementById("mpe-dropzone"),inp=document.getElementById("mpe-file"),pv=document.getElementById("mpe-preview");
                if(!dz)return;
                ["dragenter","dragover"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.add("mpe-drag");});});
                ["dragleave","drop"].forEach(function(e){dz.addEventListener(e,function(ev){ev.preventDefault();ev.stopPropagation();dz.classList.remove("mpe-drag");});});
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
                            d.className="mpe-preview-item";
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
            $html .= '<div id="mpe-bg-list" style="display:flex;flex-wrap:wrap;gap:15px;">';
            foreach ($list as $f) {
                $html .= '<div class="mpe-bg-card" draggable="true" data-file="' . htmlspecialchars($f) . '" style="border:1px solid #ddd;padding:8px;width:160px;text-align:center;background:#fafafa;cursor:move;border-radius:4px;transition:all .15s;">';
                $html .= '<img src="' . $url . $f . '" style="max-width:100%;height:100px;object-fit:cover;display:block;margin-bottom:6px;pointer-events:none;" />';
                $html .= '<a href="' . $base . '&deleteBg=' . urlencode($f) . '" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer ce fond ?\')">✕ ' . $this->l('Supprimer') . '</a>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '<style>
                .mpe-bg-card.mpe-dragging{opacity:.4;}
                .mpe-bg-card.mpe-over{border-color:#ee7a03 !important;background:#fff7ee !important;transform:scale(1.03);}
            </style>';
            $html .= '<script>
                (function(){
                    var list=document.getElementById("mpe-bg-list");
                    if(!list)return;
                    var dragEl=null;
                    var cards=list.querySelectorAll(".mpe-bg-card");
                    cards.forEach(function(c){
                        c.addEventListener("dragstart",function(e){dragEl=c;c.classList.add("mpe-dragging");e.dataTransfer.effectAllowed="move";});
                        c.addEventListener("dragend",function(){c.classList.remove("mpe-dragging");cards.forEach(function(x){x.classList.remove("mpe-over");});save();});
                        c.addEventListener("dragover",function(e){e.preventDefault();e.dataTransfer.dropEffect="move";});
                        c.addEventListener("dragenter",function(){if(c!==dragEl)c.classList.add("mpe-over");});
                        c.addEventListener("dragleave",function(){c.classList.remove("mpe-over");});
                        c.addEventListener("drop",function(e){
                            e.preventDefault();
                            if(c===dragEl)return;
                            var rect=c.getBoundingClientRect();
                            var after=(e.clientX-rect.left)>rect.width/2;
                            list.insertBefore(dragEl,after?c.nextSibling:c);
                        });
                    });
                    function save(){
                        var order=Array.from(list.querySelectorAll(".mpe-bg-card")).map(function(c){return c.dataset.file;}).join(",");
                        var url="' . $base . '&reorderBgs="+encodeURIComponent(order)+"&ajax=1";
                        fetch(url,{credentials:"same-origin"});
                    }
                })();
            </script>';
        }

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
                        'name' => 'MOUSEPAD_PRODUCT_IDS',
                        'desc' => $this->l('Liste des IDs produits séparés par des virgules (ex: 20,21,25).'),
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
        $helper->submit_action = 'submitMousepadeditorModule';
        $helper->fields_value['MOUSEPAD_PRODUCT_IDS'] = Configuration::get('MOUSEPAD_PRODUCT_IDS');

        return $helper->generateForm([$fields_form]);
    }

    public function hookDisplayMousepadEditor($params)
    {
        $productId = (int) Tools::getValue('id_product');

        if (!$productId) {
            return '';
        }

        $configIds = Configuration::get('MOUSEPAD_PRODUCT_IDS');
        if (empty($configIds)) {
            return '';
        }

        $allowedIds = array_map('intval', array_filter(explode(',', $configIds)));

        if (!in_array($productId, $allowedIds)) {
            return '';
        }

        $backgrounds = $this->getBackgrounds();
        $bgUrl = $this->_path . self::UPLOAD_DIR;

        // Détection fond client existant
        $customerBg = $this->getCustomerBackground();

        $customFonts = $this->getFonts();
        $fontUrl = $this->_path . self::FONT_DIR;
        $enabled = $this->getEnabledFonts();

        // Construire la liste des polices actives, par catégorie
        $activeWebsafe = array_values(array_filter(self::WEB_SAFE_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeTheme = array_values(array_filter(self::THEME_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeGoogle = array_values(array_filter(self::GOOGLE_FONTS, function($f) use ($enabled){ return isset($enabled[$f]); }));
        $activeCustom = array_values(array_filter($customFonts, function($f) use ($enabled){ return isset($enabled[$f['family']]); }));

        // URL Google Fonts uniquement pour celles activées
        $googleFrontUrl = '';
        if (!empty($activeGoogle)) {
            $params = [];
            foreach ($activeGoogle as $g) { $params[] = 'family=' . str_replace(' ', '+', $g) . ':wght@400;700'; }
            $googleFrontUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $params) . '&display=swap';
        }

        $template = $this->getTemplate();
        $composeUrl = $this->context->link->getModuleLink('mousepadeditor', 'compose', [], true);
        $attachUrl = $this->context->link->getModuleLink('mousepadeditor', 'attachcustom', [], true);

        $this->context->smarty->assign([
            'mpe_backgrounds' => $backgrounds,
            'mpe_bg_url' => $bgUrl,
            'mpe_customer_bg' => $customerBg,
            'mpe_upload_url' => $this->context->link->getModuleLink('mousepadeditor', 'upload', [], true),
            'mpe_uploadimage_url' => $this->context->link->getModuleLink('mousepadeditor', 'uploadimage', [], true),
            'mpe_fonts' => $activeCustom,
            'mpe_font_url' => $fontUrl,
            'mpe_default_fonts' => array_merge($activeWebsafe, $activeTheme, $activeGoogle),
            'mpe_google_url' => $googleFrontUrl,
            'mpe_template' => $template,
            'mpe_compose_url' => $composeUrl,
            'mpe_attach_url' => $attachUrl,
            'mpe_product_id' => $productId,
            'mpe_lsv_blocs' => class_exists('LeSaviezVous') ? LeSaviezVous::getBlocs() : [],
        ]);

        return $this->display(__FILE__, 'views/templates/hook/editor.tpl');
    }
}
