<?php
/**
 * Google Analytics — module CadeauIdeal
 *
 * Injecte la balise gtag.js dans <head> via le hook displayHeader.
 *
 * Champ vide = balise désactivée (rien d'injecté).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ga4 extends Module
{
    const CFG_SNIPPET = 'GA4_SNIPPET';

    public function __construct()
    {
        $this->name = 'ga4';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Google Analytics');
        $this->description = $this->l('Intègre la balise Google Analytics (gtag.js) dans le <head> du front-office.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayHeader')
        ) {
            return false;
        }

        Configuration::updateValue(self::CFG_SNIPPET, '', true);

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CFG_SNIPPET);

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitGa4Module')) {
            $snippet = Tools::getValue(self::CFG_SNIPPET, '');
            Configuration::updateValue(self::CFG_SNIPPET, $snippet, true);
            $output .= $this->displayConfirmation($this->l('Balise Google Analytics sauvegardée.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration Google Analytics'),
                    'icon' => 'icon-cog',
                ],
                'description' => $this->l('Coller ici la balise complète fournie par Google Analytics (gtag.js). Champ vide = balise désactivée.'),
                'input' => [
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Balise Google Analytics (gtag.js)'),
                        'name' => self::CFG_SNIPPET,
                        'cols' => 80,
                        'rows' => 12,
                        'desc' => $this->l('Coller la balise <script>...</script> complète fournie par Google. Sera injectée dans <head>.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Sauvegarder'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGa4Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function getConfigFormValues()
    {
        return [
            self::CFG_SNIPPET => Configuration::get(self::CFG_SNIPPET),
        ];
    }

    public function hookDisplayHeader($params)
    {
        $snippet = (string) Configuration::get(self::CFG_SNIPPET);
        return trim($snippet) === '' ? '' : $snippet;
    }
}
