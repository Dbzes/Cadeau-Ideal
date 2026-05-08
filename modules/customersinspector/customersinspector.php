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
        $this->version = '1.1.0';
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
            && $this->installDb()
            && $this->registerHook('displayHeader')
            && $this->installTab();
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallDb()
            && parent::uninstall();
    }

    public function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'customersinspector_visits` (
            `id_visit` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_guest` INT UNSIGNED NOT NULL,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
            `ip_address` BIGINT NULL,
            `country_iso` CHAR(2) NULL,
            `device_type` VARCHAR(10) NOT NULL,
            `user_agent` VARCHAR(500) NULL,
            `url` VARCHAR(500) NULL,
            `date_add` DATETIME NOT NULL,
            INDEX `idx_guest` (`id_guest`),
            INDEX `idx_date` (`date_add`),
            INDEX `idx_device` (`device_type`),
            INDEX `idx_country` (`country_iso`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';
        return Db::getInstance()->execute($sql);
    }

    public function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'customersinspector_visits`;');
    }

    private function installTab()
    {
        if (Tab::getIdFromClassName('AdminCustomersInspector')) {
            return true;
        }
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

    /**
     * Hook front : capture chaque page vue avec User-Agent → device_type.
     * Léger : 1 INSERT par page front. Pas d'INSERT en BO ni dans les modules ajax.
     */
    public function hookDisplayHeader($params)
    {
        try {
            if (defined('_PS_ADMIN_DIR_')) {
                return;
            }
            $controller = isset($this->context->controller) ? $this->context->controller : null;
            if ($controller && property_exists($controller, 'ajax') && $controller->ajax) {
                return;
            }
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
            $device = self::detectDevice($ua);

            $idGuest = 0;
            if (isset($this->context->cookie) && $this->context->cookie->id_guest) {
                $idGuest = (int) $this->context->cookie->id_guest;
            } elseif (isset($this->context->customer) && $this->context->customer->id) {
                $idGuest = (int) Guest::getFromCustomer((int) $this->context->customer->id);
            }

            $ip = Tools::getRemoteAddr();
            $ipLong = $ip ? sprintf('%u', ip2long($ip)) : null;

            $url = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            if (mb_strlen($url) > 500) {
                $url = mb_substr($url, 0, 500);
            }
            if (mb_strlen($ua) > 500) {
                $ua = mb_substr($ua, 0, 500);
            }

            Db::getInstance()->insert('customersinspector_visits', [
                'id_guest' => $idGuest,
                'id_shop' => (int) $this->context->shop->id,
                'ip_address' => $ipLong,
                'country_iso' => null,
                'device_type' => pSQL($device),
                'user_agent' => pSQL($ua),
                'url' => pSQL($url),
                'date_add' => date('Y-m-d H:i:s'),
            ], false, true, Db::INSERT_IGNORE);
        } catch (\Throwable $e) {
            // Ne jamais casser le front pour une erreur de tracking.
        }
    }

    public static function detectDevice(string $ua): string
    {
        if ($ua === '') {
            return 'unknown';
        }
        if (preg_match('/(bot|crawl|spider|slurp|baidu|googlebot|bingbot|yandex|duckduckbot|facebookexternalhit|telegrambot|whatsapp|applebot|semrush|ahrefs)/i', $ua)) {
            return 'bot';
        }
        if (preg_match('/(iPad|Tablet|PlayBook|Silk|Kindle)/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/(Mobile|Android|iPhone|iPod|BlackBerry|Opera Mini|IEMobile|webOS|Windows Phone)/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }
}
