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
    const MAX_SIZE = 5242880; // 5 Mo
    const FONT_MAX_SIZE = 2097152; // 2 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];
    const FONT_ALLOWED = ['ttf', 'otf', 'woff', 'woff2'];

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
            && Configuration::updateValue('MOUSEPAD_PRODUCT_IDS', '')
            && Configuration::updateValue('MOUSEPAD_BACKGROUNDS', json_encode([]))
            && Configuration::updateValue('MOUSEPAD_FONTS', json_encode([]))
            && Configuration::updateValue('MOUSEPAD_DISABLED_DEFAULTS', json_encode([]));
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOUSEPAD_PRODUCT_IDS');
        Configuration::deleteByName('MOUSEPAD_BACKGROUNDS');
        Configuration::deleteByName('MOUSEPAD_FONTS');
        Configuration::deleteByName('MOUSEPAD_DISABLED_DEFAULTS');

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

    public function hookHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/mousepadeditor.css');
        $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/fabric@5.3.1/dist/fabric.min.js', false);
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
        if (Tools::getValue('deleteFont')) {
            $output .= $this->handleFontDelete(Tools::getValue('deleteFont'));
        }
        if (Tools::getValue('toggleDefault')) {
            $output .= $this->handleToggleDefault(Tools::getValue('toggleDefault'));
        }

        return $output . $this->renderForm() . $this->renderBackgroundsManager() . $this->renderFontsManager();
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

    protected function getDisabledDefaults()
    {
        $raw = Configuration::get('MOUSEPAD_DISABLED_DEFAULTS');
        $list = json_decode($raw, true);
        return is_array($list) ? $list : [];
    }

    protected function handleToggleDefault($name)
    {
        $allowed = ['Open Sans', 'Bebas Neue', 'Arial'];
        if (!in_array($name, $allowed)) return '';
        $disabled = $this->getDisabledDefaults();
        if (in_array($name, $disabled)) {
            $disabled = array_values(array_diff($disabled, [$name]));
            $msg = sprintf($this->l('Police "%s" réactivée.'), $name);
        } else {
            $disabled[] = $name;
            $msg = sprintf($this->l('Police "%s" désactivée.'), $name);
        }
        Configuration::updateValue('MOUSEPAD_DISABLED_DEFAULTS', json_encode($disabled));
        return $this->displayConfirmation($msg);
    }

    protected function renderFontsManager()
    {
        $list = $this->getFonts();
        $url = $this->_path . self::FONT_DIR;
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $allDefaults = ['Open Sans', 'Bebas Neue', 'Arial'];
        $disabledDefaults = $this->getDisabledDefaults();
        $activeDefaults = array_values(array_diff($allDefaults, $disabledDefaults));
        $totalFonts = count($activeDefaults) + count($list);

        $html = '<div class="panel"><h3><i class="icon-font"></i> ' . $this->l('Gestion des polices') . '</h3>';

        // Liste des polices actuellement disponibles client
        $html .= '<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400&family=Bebas+Neue&display=swap" rel="stylesheet">';
        $html .= '<style>
            .mpe-tag{display:inline-flex;align-items:center;gap:8px;background:#fff;padding:6px 6px 6px 14px;border-radius:20px;font-size:14px;color:#004774;}
            .mpe-tag .mpe-tag-x{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#f4f4f4;color:#666;text-decoration:none;font-size:13px;transition:all .2s;}
            .mpe-tag .mpe-tag-x:hover{background:#e74c3c;color:#fff;}
            .mpe-tag-default{border:1px solid #cfe2f0;}
            .mpe-tag-custom{border:1px solid #ee7a03;}
            .mpe-tag-disabled{opacity:.45;border-style:dashed;}
            .mpe-tag-disabled .mpe-tag-x{background:#e8f4ea;color:#27ae60;}
            .mpe-tag-disabled .mpe-tag-x:hover{background:#27ae60;color:#fff;}
        </style>';
        if (!empty($list)) {
            $html .= '<style>';
            foreach ($list as $f) {
                $fmt = $f['ext'] === 'ttf' ? 'truetype' : ($f['ext'] === 'otf' ? 'opentype' : $f['ext']);
                $html .= '@font-face{font-family:"' . htmlspecialchars($f['family']) . '";src:url("' . $url . $f['file'] . '") format("' . $fmt . '");}';
            }
            $html .= '</style>';
        }
        $html .= '<div style="background:#f0f7fc;border:1px solid #cfe2f0;border-radius:4px;padding:14px 18px;margin-bottom:18px;">';
        $html .= '<div style="font-weight:600;color:#004774;margin-bottom:10px;font-size:14px;">' . sprintf($this->l('Polices actuellement disponibles côté client (%d active(s))'), $totalFonts) . '</div>';
        $html .= '<div style="display:flex;flex-wrap:wrap;gap:10px;">';
        foreach ($allDefaults as $df) {
            $isDisabled = in_array($df, $disabledDefaults);
            $cls = 'mpe-tag mpe-tag-default' . ($isDisabled ? ' mpe-tag-disabled' : '');
            $title = $isDisabled ? $this->l('Réactiver') : $this->l('Désactiver');
            $icon = $isDisabled ? '↻' : '✕';
            $html .= '<span class="' . $cls . '" style="font-family:\'' . $df . '\',sans-serif;">';
            $html .= $df . ' <span style="font-size:10px;color:#999;">(défaut)</span>';
            $html .= '<a href="' . $base . '&toggleDefault=' . urlencode($df) . '" class="mpe-tag-x" title="' . $title . '">' . $icon . '</a>';
            $html .= '</span>';
        }
        foreach ($list as $f) {
            $html .= '<span class="mpe-tag mpe-tag-custom" style="font-family:\'' . htmlspecialchars($f['family']) . '\',sans-serif;">';
            $html .= htmlspecialchars($f['family']) . ' <span style="font-size:10px;color:#ee7a03;">(custom)</span>';
            $html .= '<a href="' . $base . '&deleteFont=' . urlencode($f['file']) . '" class="mpe-tag-x" title="' . $this->l('Supprimer définitivement') . '" onclick="return confirm(\'Supprimer cette police ?\')">✕</a>';
            $html .= '</span>';
        }
        $html .= '</div>';
        if (count($disabledDefaults) > 0) {
            $html .= '<div style="margin-top:10px;font-size:12px;color:#888;">' . $this->l('Astuce : cliquez sur ↻ pour réactiver une police désactivée.') . '</div>';
        }
        $html .= '</div>';
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

        $fonts = $this->getFonts();
        $fontUrl = $this->_path . self::FONT_DIR;
        $allDefaults = ['Open Sans', 'Bebas Neue', 'Arial'];
        $disabled = $this->getDisabledDefaults();
        $activeDefaults = array_values(array_diff($allDefaults, $disabled));

        $this->context->smarty->assign([
            'mpe_backgrounds' => $backgrounds,
            'mpe_bg_url' => $bgUrl,
            'mpe_customer_bg' => $customerBg,
            'mpe_upload_url' => $this->context->link->getModuleLink('mousepadeditor', 'upload', [], true),
            'mpe_fonts' => $fonts,
            'mpe_font_url' => $fontUrl,
            'mpe_default_fonts' => $activeDefaults,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/editor.tpl');
    }
}
