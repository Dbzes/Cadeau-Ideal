<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cmsinfooter extends Module
{
    public function __construct()
    {
        $this->name = 'cmsinfooter';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Le Cadeau Idéal';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('CMS Footer Links');
        $this->description = $this->l('Affiche les pages CMS Informations en ligne dans le footer.');
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
        $cms_pages = CMS::getCMSPages(
            (int) $this->context->language->id,
            2,
            true
        );

        if (empty($cms_pages)) {
            return '';
        }

        $links = [];
        foreach ($cms_pages as $page) {
            $links[] = [
                'title' => $page['meta_title'],
                'url'   => $this->context->link->getCMSLink(
                    $page['id_cms'],
                    $page['link_rewrite']
                ),
            ];
        }

        $this->context->smarty->assign('cms_footer_links', $links);

        return $this->display(__FILE__, 'views/templates/hook/footer_cms.tpl');
    }
}
