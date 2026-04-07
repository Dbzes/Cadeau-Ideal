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
    const MAX_SIZE = 5242880; // 5 Mo
    const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];

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
            && Configuration::updateValue('MOUSEPAD_BACKGROUNDS', json_encode([]));
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOUSEPAD_PRODUCT_IDS');
        Configuration::deleteByName('MOUSEPAD_BACKGROUNDS');

        return parent::uninstall();
    }

    public function hookHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/mousepadeditor.css');
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

        return $output . $this->renderForm() . $this->renderBackgroundsManager();
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

    protected function renderBackgroundsManager()
    {
        $list = $this->getBackgrounds();
        $url = $this->_path . self::UPLOAD_DIR;
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $html = '<div class="panel"><h3><i class="icon-picture"></i> ' . $this->l('Gestion des fonds') . '</h3>';
        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<div class="form-group"><label>' . $this->l('Ajouter des fonds (jpg, png, webp — max 5 Mo)') . '</label>';
        $html .= '<input type="file" name="mousepad_bg[]" multiple accept="image/jpeg,image/png,image/webp" /></div>';
        $html .= '<button type="submit" name="submitMousepadBackgroundUpload" class="btn btn-default pull-right">'
            . '<i class="process-icon-save"></i> ' . $this->l('Uploader') . '</button>';
        $html .= '<div class="clearfix"></div></form><hr/>';

        if (empty($list)) {
            $html .= '<p>' . $this->l('Aucun fond pour le moment.') . '</p>';
        } else {
            $html .= '<div style="display:flex;flex-wrap:wrap;gap:15px;">';
            $total = count($list);
            foreach ($list as $i => $f) {
                $html .= '<div style="border:1px solid #ddd;padding:8px;width:160px;text-align:center;background:#fafafa;">';
                $html .= '<img src="' . $url . $f . '" style="max-width:100%;height:100px;object-fit:cover;display:block;margin-bottom:6px;" />';
                $html .= '<div style="display:flex;justify-content:space-between;gap:4px;">';
                if ($i > 0) {
                    $html .= '<a href="' . $base . '&moveBg=' . urlencode($f) . '&dir=up" class="btn btn-default btn-xs">↑</a>';
                } else {
                    $html .= '<span></span>';
                }
                $html .= '<a href="' . $base . '&deleteBg=' . urlencode($f) . '" class="btn btn-danger btn-xs" onclick="return confirm(\'Supprimer ce fond ?\')">✕</a>';
                if ($i < $total - 1) {
                    $html .= '<a href="' . $base . '&moveBg=' . urlencode($f) . '&dir=down" class="btn btn-default btn-xs">↓</a>';
                } else {
                    $html .= '<span></span>';
                }
                $html .= '</div></div>';
            }
            $html .= '</div>';
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

        $this->context->smarty->assign([
            'mpe_backgrounds' => $backgrounds,
            'mpe_bg_url' => $bgUrl,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/editor.tpl');
    }
}
