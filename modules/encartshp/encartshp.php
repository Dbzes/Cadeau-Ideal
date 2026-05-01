<?php
/**
 * Encarts HP - Module de gestion des encarts de la page d'accueil
 *
 * @author    Claude
 * @copyright 2026
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Encartshp extends Module
{
    private $grands = [1, 2];
    private $grands2 = [6, 7];
    private $petits = [3, 4, 5];
    private $orderDefaults = [1, 2, 6, 7];

    public function __construct()
    {
        $this->name = 'encartshp';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Encarts HP');
        $this->description = $this->l('Affiche 2 grands encarts + 3 petits encarts sur la page d\'accueil.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    private function getAllPositions()
    {
        return array_merge($this->grands, $this->grands2, $this->petits);
    }

    private function getAllowedOrderValues()
    {
        return array_merge([0], $this->grands, $this->grands2);
    }

    private function getOrder()
    {
        $order = [];
        for ($pos = 1; $pos <= 4; $pos++) {
            $val = Configuration::get('ENCARTSHP_ORDER_' . $pos);
            if ($val === false) {
                $order[$pos] = $this->orderDefaults[$pos - 1];
            } else {
                $order[$pos] = (int) $val;
            }
        }
        return $order;
    }

    private function getGrandLabel($i)
    {
        $labels = [
            1 => $this->l('Grand encart — Gauche'),
            2 => $this->l('Grand encart — Droite'),
            6 => $this->l('Grand encart 2 — Gauche'),
            7 => $this->l('Grand encart 2 — Droite'),
        ];
        return isset($labels[$i]) ? $labels[$i] : '';
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayHome')
            || !$this->registerHook('actionFrontControllerSetMedia')
        ) {
            return false;
        }

        Configuration::updateValue('ENCARTSHP_ACTIVE', 1);

        foreach ($this->getAllPositions() as $i) {
            Configuration::updateValue('ENCARTSHP_LINK_' . $i, '');
            Configuration::updateValue('ENCARTSHP_ALT_' . $i, '');
            Configuration::updateValue('ENCARTSHP_TITLE_' . $i, '');
            Configuration::updateValue('ENCARTSHP_NEWTAB_' . $i, 0);
        }

        foreach ($this->grands2 as $i) {
            Configuration::updateValue('ENCARTSHP_DISABLED_' . $i, 1);
        }

        for ($pos = 1; $pos <= 4; $pos++) {
            Configuration::updateValue('ENCARTSHP_ORDER_' . $pos, $this->orderDefaults[$pos - 1]);
        }

        $imgDir = _PS_MODULE_DIR_ . $this->name . '/img/';
        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('ENCARTSHP_ACTIVE');

        for ($pos = 1; $pos <= 4; $pos++) {
            Configuration::deleteByName('ENCARTSHP_ORDER_' . $pos);
        }

        foreach ($this->getAllPositions() as $i) {
            Configuration::deleteByName('ENCARTSHP_LINK_' . $i);
            Configuration::deleteByName('ENCARTSHP_ALT_' . $i);
            Configuration::deleteByName('ENCARTSHP_TITLE_' . $i);
            Configuration::deleteByName('ENCARTSHP_NEWTAB_' . $i);
            Configuration::deleteByName('ENCARTSHP_DISABLED_' . $i);

            $imgPath = _PS_MODULE_DIR_ . $this->name . '/img/encart_' . $i . '.jpg';
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitEncartshpModule')) {
            $output .= $this->processForm();
        }

        return $output . $this->renderForm();
    }

    protected function processForm()
    {
        $output = '';

        Configuration::updateValue('ENCARTSHP_ACTIVE', (int) Tools::getValue('ENCARTSHP_ACTIVE'));

        $allowed = $this->getAllowedOrderValues();
        for ($pos = 1; $pos <= 4; $pos++) {
            $submitted = Tools::getValue('ENCARTSHP_ORDER_' . $pos);
            if ($submitted === false || $submitted === null || $submitted === '') {
                continue;
            }
            $value = (int) $submitted;
            if (!in_array($value, $allowed, true)) {
                $value = $this->orderDefaults[$pos - 1];
            }
            Configuration::updateValue('ENCARTSHP_ORDER_' . $pos, $value);
        }

        foreach ($this->grands2 as $i) {
            Configuration::updateValue('ENCARTSHP_DISABLED_' . $i, (int) Tools::getValue('ENCARTSHP_DISABLED_' . $i));
        }

        foreach ($this->getAllPositions() as $i) {
            Configuration::updateValue('ENCARTSHP_LINK_' . $i, Tools::getValue('ENCARTSHP_LINK_' . $i));
            Configuration::updateValue('ENCARTSHP_ALT_' . $i, Tools::getValue('ENCARTSHP_ALT_' . $i));
            Configuration::updateValue('ENCARTSHP_TITLE_' . $i, Tools::getValue('ENCARTSHP_TITLE_' . $i));
            Configuration::updateValue('ENCARTSHP_NEWTAB_' . $i, (int) Tools::getValue('ENCARTSHP_NEWTAB_' . $i));

            if (isset($_FILES['ENCARTSHP_IMG_' . $i]) && $_FILES['ENCARTSHP_IMG_' . $i]['size'] > 0) {
                $imgDir = _PS_MODULE_DIR_ . $this->name . '/img/';
                $fileName = 'encart_' . $i . '.jpg';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                if (!in_array($_FILES['ENCARTSHP_IMG_' . $i]['type'], $allowedTypes)) {
                    $output .= $this->displayError($this->l('Encart') . ' ' . $i . ' : ' . $this->l('format non supporté (JPG, PNG, WebP, GIF uniquement).'));
                    continue;
                }

                if (!move_uploaded_file($_FILES['ENCARTSHP_IMG_' . $i]['tmp_name'], $imgDir . $fileName)) {
                    $output .= $this->displayError($this->l('Encart') . ' ' . $i . ' : ' . $this->l('erreur lors de l\'upload.'));
                    continue;
                }
            }

            if (Tools::getValue('ENCARTSHP_DELETE_IMG_' . $i)) {
                $imgPath = _PS_MODULE_DIR_ . $this->name . '/img/encart_' . $i . '.jpg';
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }
        }

        if (empty($output)) {
            $output = $this->displayConfirmation($this->l('Paramètres mis à jour.'));
        }

        return $output;
    }

    protected function renderForm()
    {
        $inputs = [
            [
                'type' => 'switch',
                'label' => $this->l('Activer les encarts'),
                'name' => 'ENCARTSHP_ACTIVE',
                'is_bool' => true,
                'values' => [
                    ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Oui')],
                    ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Non')],
                ],
            ],
        ];

        $forms = [];

        $forms[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Paramètres généraux'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $forms[] = $this->buildOrderForm();

        $petitLabels = [
            3 => $this->l('Petit encart — Gauche'),
            4 => $this->l('Petit encart — Centre'),
            5 => $this->l('Petit encart — Droite'),
        ];

        foreach ($this->grands as $i) {
            $forms[] = $this->buildEncartForm($i, $this->getGrandLabel($i), '545x340');
        }

        foreach ($this->grands2 as $i) {
            $forms[] = $this->buildEncartForm($i, $this->getGrandLabel($i), '545x340', true);
        }

        foreach ($this->petits as $i) {
            $forms[] = $this->buildEncartForm($i, $petitLabels[$i], '300x183');
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->submit_action = 'submitEncartshpModule';

        $helper->fields_value['ENCARTSHP_ACTIVE'] = Configuration::get('ENCARTSHP_ACTIVE');

        $order = $this->getOrder();
        for ($pos = 1; $pos <= 4; $pos++) {
            $helper->fields_value['ENCARTSHP_ORDER_' . $pos] = $order[$pos];
        }

        foreach ($this->getAllPositions() as $i) {
            $helper->fields_value['ENCARTSHP_LINK_' . $i] = Configuration::get('ENCARTSHP_LINK_' . $i);
            $helper->fields_value['ENCARTSHP_ALT_' . $i] = Configuration::get('ENCARTSHP_ALT_' . $i);
            $helper->fields_value['ENCARTSHP_TITLE_' . $i] = Configuration::get('ENCARTSHP_TITLE_' . $i);
            $helper->fields_value['ENCARTSHP_NEWTAB_' . $i] = Configuration::get('ENCARTSHP_NEWTAB_' . $i);
            $helper->fields_value['ENCARTSHP_DISABLED_' . $i] = Configuration::get('ENCARTSHP_DISABLED_' . $i);
            $helper->fields_value['ENCARTSHP_DELETE_IMG_' . $i] = 0;
        }

        return $helper->generateForm($forms);
    }

    protected function buildOrderForm()
    {
        $options = [
            ['id_option' => 0, 'name' => $this->l('— Masqué —')],
        ];
        foreach (array_merge($this->grands, $this->grands2) as $i) {
            $options[] = ['id_option' => $i, 'name' => $this->getGrandLabel($i)];
        }

        $positionLabels = [
            1 => $this->l('1re position'),
            2 => $this->l('2e position'),
            3 => $this->l('3e position'),
            4 => $this->l('4e position'),
        ];

        $inputs = [];
        for ($pos = 1; $pos <= 4; $pos++) {
            $inputs[] = [
                'type' => 'select',
                'label' => $positionLabels[$pos],
                'name' => 'ENCARTSHP_ORDER_' . $pos,
                'options' => [
                    'query' => $options,
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'desc' => $pos === 1
                    ? $this->l('Choisissez l\'encart à afficher dans chaque position. « Masqué » laisse le slot vide. Les positions 1-2 forment la 1re ligne, 3-4 la 2e ligne. Les encarts désactivés dans leur bloc ne s\'affichent pas même s\'ils sont sélectionnés ici.')
                    : '',
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ordre d\'affichage des grands encarts'),
                    'icon' => 'icon-sort',
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];
    }

    protected function buildEncartForm($i, $label, $size, $hasDisable = false)
    {
        $encartInputs = [];

        if ($hasDisable) {
            $encartInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Désactiver cet encart'),
                'name' => 'ENCARTSHP_DISABLED_' . $i,
                'is_bool' => true,
                'values' => [
                    ['id' => 'disabled_' . $i . '_on', 'value' => 1, 'label' => $this->l('Oui')],
                    ['id' => 'disabled_' . $i . '_off', 'value' => 0, 'label' => $this->l('Non')],
                ],
            ];
        }

        $encartInputs = array_merge($encartInputs, [
            [
                'type' => 'file',
                'label' => $this->l('Image') . ' (' . $size . ')',
                'name' => 'ENCARTSHP_IMG_' . $i,
                'desc' => $this->l('Formats : JPG, PNG, WebP, GIF. Taille recommandée :') . ' ' . $size . ' px.',
            ],
            [
                'type' => 'text',
                'label' => $this->l('URL du lien'),
                'name' => 'ENCARTSHP_LINK_' . $i,
                'desc' => $this->l('Adresse de destination au clic sur l\'encart.'),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Texte alternatif (alt)'),
                'name' => 'ENCARTSHP_ALT_' . $i,
                'desc' => $this->l('Attribut alt de l\'image pour le référencement et l\'accessibilité.'),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Titre du lien (title)'),
                'name' => 'ENCARTSHP_TITLE_' . $i,
                'desc' => $this->l('Attribut title du lien pour le référencement.'),
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Ouvrir dans un nouvel onglet'),
                'name' => 'ENCARTSHP_NEWTAB_' . $i,
                'is_bool' => true,
                'values' => [
                    ['id' => 'newtab_' . $i . '_on', 'value' => 1, 'label' => $this->l('Oui')],
                    ['id' => 'newtab_' . $i . '_off', 'value' => 0, 'label' => $this->l('Non')],
                ],
            ],
        ]);

        $imgPath = _PS_MODULE_DIR_ . $this->name . '/img/encart_' . $i . '.jpg';
        if (file_exists($imgPath)) {
            $imgUrl = $this->_path . 'img/encart_' . $i . '.jpg?' . filemtime($imgPath);
            array_unshift($encartInputs, [
                'type' => 'html',
                'name' => 'ENCARTSHP_PREVIEW_' . $i,
                'html_content' => '<div class="form-group"><label class="control-label col-lg-3">' . $this->l('Aperçu') . '</label><div class="col-lg-9"><img src="' . $imgUrl . '" style="max-width:272px;max-height:170px;border:1px solid #ccc;margin-bottom:10px;" /><br/></div></div>',
            ]);
            $encartInputs[] = [
                'type' => 'switch',
                'label' => $this->l('Supprimer l\'image'),
                'name' => 'ENCARTSHP_DELETE_IMG_' . $i,
                'is_bool' => true,
                'values' => [
                    ['id' => 'delete_' . $i . '_on', 'value' => 1, 'label' => $this->l('Oui')],
                    ['id' => 'delete_' . $i . '_off', 'value' => 0, 'label' => $this->l('Non')],
                ],
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $label,
                    'icon' => 'icon-picture-o',
                ],
                'input' => $encartInputs,
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];
    }

    public function hookActionFrontControllerSetMedia()
    {
        if ((int) Configuration::get('ENCARTSHP_ACTIVE')) {
            $this->context->controller->registerStylesheet(
                'module-encartshp-style',
                'modules/' . $this->name . '/views/css/encartshp.css',
                ['media' => 'all', 'priority' => 150]
            );
        }
    }

    public function hookDisplayHome()
    {
        if (!(int) Configuration::get('ENCARTSHP_ACTIVE')) {
            return '';
        }

        $allowed = $this->getAllowedOrderValues();
        $order = $this->getOrder();

        $grandsFlat = [];
        foreach ($order as $i) {
            if ($i === 0 || !in_array($i, $allowed, true)) {
                continue;
            }
            if (in_array($i, $this->grands2, true) && (int) Configuration::get('ENCARTSHP_DISABLED_' . $i)) {
                continue;
            }
            $imgPath = _PS_MODULE_DIR_ . $this->name . '/img/encart_' . $i . '.jpg';
            $hasImage = file_exists($imgPath);
            $grandsFlat[] = [
                'position' => $i,
                'has_image' => $hasImage,
                'image_url' => $hasImage ? $this->_path . 'img/encart_' . $i . '.jpg?' . filemtime($imgPath) : '',
                'link' => Configuration::get('ENCARTSHP_LINK_' . $i),
                'alt' => Configuration::get('ENCARTSHP_ALT_' . $i),
                'title' => Configuration::get('ENCARTSHP_TITLE_' . $i),
                'new_tab' => (int) Configuration::get('ENCARTSHP_NEWTAB_' . $i),
            ];
        }

        $grandsRows = array_chunk($grandsFlat, 2);

        $petits = [];
        foreach ($this->petits as $i) {
            $imgPath = _PS_MODULE_DIR_ . $this->name . '/img/encart_' . $i . '.jpg';
            $hasImage = file_exists($imgPath);
            $petits[] = [
                'position' => $i,
                'has_image' => $hasImage,
                'image_url' => $hasImage ? $this->_path . 'img/encart_' . $i . '.jpg?' . filemtime($imgPath) : '',
                'link' => Configuration::get('ENCARTSHP_LINK_' . $i),
                'alt' => Configuration::get('ENCARTSHP_ALT_' . $i),
                'title' => Configuration::get('ENCARTSHP_TITLE_' . $i),
                'new_tab' => (int) Configuration::get('ENCARTSHP_NEWTAB_' . $i),
            ];
        }

        $this->context->smarty->assign([
            'grands_rows' => $grandsRows,
            'petits' => $petits,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayHome.tpl');
    }
}
