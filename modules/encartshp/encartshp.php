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
    public function __construct()
    {
        $this->name = 'encartshp';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Encarts HP');
        $this->description = $this->l('Gestion des encarts de la page d\'accueil.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('ENCARTSHP_ACTIVE', 0);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('ENCARTSHP_ACTIVE');
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitEncartshpModule')) {
            Configuration::updateValue('ENCARTSHP_ACTIVE', (int) Tools::getValue('ENCARTSHP_ACTIVE'));
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
                        'type' => 'switch',
                        'label' => $this->l('Activer les encarts'),
                        'name' => 'ENCARTSHP_ACTIVE',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Oui'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Non'),
                            ],
                        ],
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
        $helper->submit_action = 'submitEncartshpModule';
        $helper->fields_value['ENCARTSHP_ACTIVE'] = Configuration::get('ENCARTSHP_ACTIVE');

        return $helper->generateForm([$fields_form]);
    }
}
