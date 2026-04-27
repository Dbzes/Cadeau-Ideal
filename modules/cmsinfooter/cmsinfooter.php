<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cmsinfooter extends Module
{
    const FOOTER_CMS_ID = 8;

    public function __construct()
    {
        $this->name = 'cmsinfooter';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Le Cadeau Idéal';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('CMS Footer Links');
        $this->description = $this->l('Affiche le contenu de la page CMS "Liens footer" dans le footer.');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayFooterAfter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayFooterAfter($params)
    {
        $cms = new CMS(self::FOOTER_CMS_ID, $this->context->language->id);

        if (!Validate::isLoadedObject($cms) || !$cms->active) {
            return '';
        }

        $this->context->smarty->assign('cms_footer_content', $cms->content);

        return $this->display(__FILE__, 'views/templates/hook/footer_cms.tpl');
    }
}
