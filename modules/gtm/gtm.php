<?php
/**
 * Google Tag Manager — module CadeauIdeal
 *
 * Injecte 2 balises GTM sur le front-office :
 *   - Snippet <head> (script gtm.js) → hook displayHeader, dans <head>.
 *   - Snippet <body> (noscript iframe) → hook displayAfterBodyOpeningTag, juste après <body>.
 *
 * Champ vide = balise désactivée (rien d'injecté).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Gtm extends Module
{
    const CFG_HEAD = 'GTM_HEAD_SNIPPET';
    const CFG_BODY = 'GTM_BODY_SNIPPET';

    public function __construct()
    {
        $this->name = 'gtm';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Google Tag Manager');
        $this->description = $this->l('Intègre les balises Google Tag Manager (head + body noscript) sur le front-office.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayAfterBodyOpeningTag')
        ) {
            return false;
        }

        Configuration::updateValue(self::CFG_HEAD, '', true);
        Configuration::updateValue(self::CFG_BODY, '', true);

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CFG_HEAD);
        Configuration::deleteByName(self::CFG_BODY);

        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitGtmModule')) {
            $head = Tools::getValue(self::CFG_HEAD, '');
            $body = Tools::getValue(self::CFG_BODY, '');

            Configuration::updateValue(self::CFG_HEAD, $head, true);
            Configuration::updateValue(self::CFG_BODY, $body, true);

            $output .= $this->displayConfirmation($this->l('Balises Google Tag Manager sauvegardées.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration Google Tag Manager'),
                    'icon' => 'icon-cog',
                ],
                'description' => $this->l('Coller ici les 2 balises fournies par Google Tag Manager. Champ vide = balise désactivée.'),
                'input' => [
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Balise <head> (script GTM)'),
                        'name' => self::CFG_HEAD,
                        'cols' => 80,
                        'rows' => 12,
                        'desc' => $this->l('Coller la balise <script>...</script> fournie par GTM. Sera injectée dans <head>.'),
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Balise <body> (noscript GTM)'),
                        'name' => self::CFG_BODY,
                        'cols' => 80,
                        'rows' => 5,
                        'desc' => $this->l('Coller la balise <noscript>...</noscript> fournie par GTM. Sera injectée juste après <body>.'),
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
        $helper->submit_action = 'submitGtmModule';
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
            self::CFG_HEAD => Configuration::get(self::CFG_HEAD),
            self::CFG_BODY => Configuration::get(self::CFG_BODY),
        ];
    }

    public function hookDisplayHeader($params)
    {
        $snippet = (string) Configuration::get(self::CFG_HEAD);
        return trim($snippet) === '' ? '' : $snippet;
    }

    public function hookDisplayAfterBodyOpeningTag($params)
    {
        $snippet = (string) Configuration::get(self::CFG_BODY);
        if (trim($snippet) === '') {
            return '';
        }

        // Robustesse : si l'utilisateur a oublié/perdu le <noscript> autour de l'<iframe>,
        // on enrobe automatiquement pour éviter que l'iframe s'affiche visiblement
        // (sinon ligne blanche en haut du site car JS est activé chez tous les visiteurs).
        if (stripos($snippet, '<noscript') === false) {
            $snippet = '<noscript>' . $snippet . '</noscript>';
        }

        return $snippet;
    }
}
