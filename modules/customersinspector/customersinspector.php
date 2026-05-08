<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomersInspector extends Module
{
    public function __construct()
    {
        $this->name = 'customersinspector';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.0';
        $this->author = 'CadeauIdeal';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customers Inspector');
        $this->description = $this->l('Analyse des visiteurs uniques par période et pays (géolocalisation IP).');
    }

    public function install()
    {
        return parent::install()
            && $this->installTab();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && parent::uninstall();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminCustomersInspector';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Customers Inspector';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminStats');
        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminCustomersInspector');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomersInspector'));
    }
}
