<?php
/**
 * Mouse Pad Editor - Personnalisation de tapis de souris
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
    public function __construct()
    {
        $this->name = 'mousepadeditor';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mouse Pad Editor');
        $this->description = $this->l('Affiche une zone de personnalisation sur les fiches produits sélectionnés.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayProductExtraContent')
            && Configuration::updateValue('MOUSEPAD_PRODUCT_IDS', '');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MOUSEPAD_PRODUCT_IDS');

        return parent::uninstall();
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

        return $output . $this->renderForm();
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

    public function hookDisplayProductExtraContent($params)
    {
        $productId = (int) $params['product']->getId();
        $allowedIds = array_map('intval', array_filter(explode(',', Configuration::get('MOUSEPAD_PRODUCT_IDS'))));

        if (empty($allowedIds) || !in_array($productId, $allowedIds)) {
            return [];
        }

        $extraContent = new PrestaShop\PrestaShop\Core\Product\ProductExtraContent();
        $extraContent->setTitle($this->l('Personnalisation'));
        $extraContent->setContent($this->display(__FILE__, 'views/templates/hook/editor.tpl'));

        return [$extraContent];
    }
}
