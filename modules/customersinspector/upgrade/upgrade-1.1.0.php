<?php

if (!defined('_PS_VERSION_') || !defined('_PS_MODULE_DIR_')) {
    exit;
}

function upgrade_module_1_1_0($module)
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

    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

    if (!$module->isRegisteredInHook('displayHeader')) {
        $module->registerHook('displayHeader');
    }

    return true;
}
