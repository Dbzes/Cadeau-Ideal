<?php
/**
 * Checkout Ecologie
 * Option "carton de seconde main" dans le tunnel de commande, avec
 * réduction automatique appliquée comme cart rule.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Checkoutecologie extends Module
{
    const CONF_ENABLED = 'CECO_ENABLED';
    const CONF_LABEL = 'CECO_LABEL';
    const CONF_AMOUNT = 'CECO_AMOUNT';
    const CONF_ICON = 'CECO_ICON';
    const CONF_BO_LABEL = 'CECO_BO_LABEL';
    const CONF_CART_RULE_ID = 'CECO_CART_RULE_ID';

    const ICON_DIR = 'upload/';
    const ICON_MAX_SIZE = 524288; // 512 Ko
    const ICON_ALLOWED = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

    public function __construct()
    {
        $this->name = 'checkoutecologie';
        $this->tab = 'checkout';
        $this->version = '1.0.0';
        $this->author = 'Le Cadeau Idéal';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Checkout Ecologie');
        $this->description = $this->l('Option de carton de seconde main au tunnel de commande avec réduction automatique.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        Configuration::updateValue(self::CONF_ENABLED, 1);
        Configuration::updateValue(self::CONF_LABEL, 'Je veux que mon colis soit emballé dans un carton de seconde main, -0.50€');
        Configuration::updateValue(self::CONF_AMOUNT, '0.50');
        Configuration::updateValue(self::CONF_ICON, '');
        Configuration::updateValue(self::CONF_BO_LABEL, 'Carton de seconde main demandé');

        // Colonne dédiée sur ps_orders (utilisée pour la prep)
        $columnExists = (int) Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '" . _DB_PREFIX_ . "orders'
               AND COLUMN_NAME = 'eco_packaging'"
        );
        if (!$columnExists) {
            Db::getInstance()->execute('
                ALTER TABLE `' . _DB_PREFIX_ . 'orders`
                ADD COLUMN `eco_packaging` TINYINT(1) NOT NULL DEFAULT 0
            ');
        }

        $this->createCartRule((float) Configuration::get(self::CONF_AMOUNT));

        return parent::install()
            && $this->registerHook('displayBeforeCarrier')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayAdminOrderMain');
    }

    public function uninstall()
    {
        $id = (int) Configuration::get(self::CONF_CART_RULE_ID);
        if ($id) {
            $cr = new CartRule($id);
            if (Validate::isLoadedObject($cr)) {
                $cr->delete();
            }
        }

        Configuration::deleteByName(self::CONF_ENABLED);
        Configuration::deleteByName(self::CONF_LABEL);
        Configuration::deleteByName(self::CONF_AMOUNT);
        Configuration::deleteByName(self::CONF_ICON);
        Configuration::deleteByName(self::CONF_BO_LABEL);
        Configuration::deleteByName(self::CONF_CART_RULE_ID);

        // On garde la colonne eco_packaging pour ne pas perdre l'historique commandes

        return parent::uninstall();
    }

    protected function createCartRule($amount)
    {
        $cartRule = new CartRule();
        $cartRule->name = [
            (int) Configuration::get('PS_LANG_DEFAULT') => 'Carton de seconde main',
        ];
        $cartRule->description = 'Réduction automatique pour le choix d\'un carton de seconde main (module Checkout Ecologie).';
        $cartRule->code = '';
        $cartRule->active = 1;
        $cartRule->highlight = 0;
        $cartRule->reduction_amount = (float) $amount;
        $cartRule->reduction_tax = 1; // TTC
        $cartRule->reduction_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cartRule->date_from = date('Y-m-d 00:00:00');
        $cartRule->date_to = date('Y-m-d 00:00:00', strtotime('+10 years'));
        $cartRule->quantity = 0;
        $cartRule->quantity_per_user = 0;
        $cartRule->cart_rule_restriction = 0;
        $cartRule->minimum_amount = 0;
        $cartRule->add();
        Configuration::updateValue(self::CONF_CART_RULE_ID, (int) $cartRule->id);

        return (int) $cartRule->id;
    }

    protected function syncCartRuleAmount($amount)
    {
        $id = (int) Configuration::get(self::CONF_CART_RULE_ID);
        if (!$id) {
            return $this->createCartRule($amount);
        }
        $cr = new CartRule($id);
        if (!Validate::isLoadedObject($cr)) {
            return $this->createCartRule($amount);
        }
        $cr->reduction_amount = (float) $amount;
        $cr->reduction_tax = 1;
        $cr->update();

        return $id;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCheckoutEcoSettings')) {
            Configuration::updateValue(self::CONF_ENABLED, (int) Tools::getValue('CECO_ENABLED'));
            $label = (string) Tools::getValue('CECO_LABEL');
            Configuration::updateValue(self::CONF_LABEL, $label);
            $amount = (float) str_replace(',', '.', Tools::getValue('CECO_AMOUNT'));
            if ($amount <= 0) {
                $amount = 0.50;
            }
            Configuration::updateValue(self::CONF_AMOUNT, number_format($amount, 2, '.', ''));
            $boLabel = (string) Tools::getValue('CECO_BO_LABEL');
            Configuration::updateValue(self::CONF_BO_LABEL, $boLabel);

            // Synchroniser la cart rule sur le nouveau montant
            $this->syncCartRuleAmount($amount);

            // Upload icône
            if (!empty($_FILES['CECO_ICON_FILE']) && $_FILES['CECO_ICON_FILE']['error'] === UPLOAD_ERR_OK) {
                $output .= $this->handleIconUpload();
            }

            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        if (Tools::getValue('deleteIcon')) {
            $output .= $this->handleIconDelete();
        }

        return $output . $this->renderForm();
    }

    protected function handleIconUpload()
    {
        $f = $_FILES['CECO_ICON_FILE'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ICON_ALLOWED, true)) {
            return $this->displayWarning($this->l('Format d\'icône non autorisé (PNG, JPG, WEBP, SVG).'));
        }
        if ($f['size'] > self::ICON_MAX_SIZE) {
            return $this->displayWarning($this->l('Icône trop volumineuse (max 512 Ko).'));
        }

        $dir = _PS_MODULE_DIR_ . $this->name . '/' . self::ICON_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Supprimer ancienne icône
        $old = (string) Configuration::get(self::CONF_ICON);
        if ($old && file_exists($dir . $old)) {
            @unlink($dir . $old);
        }
        $newName = 'icon_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . $newName)) {
            return $this->displayWarning($this->l('Échec de l\'upload de l\'icône.'));
        }
        Configuration::updateValue(self::CONF_ICON, $newName);

        return $this->displayConfirmation($this->l('Icône mise à jour.'));
    }

    protected function handleIconDelete()
    {
        $old = (string) Configuration::get(self::CONF_ICON);
        if ($old) {
            $path = _PS_MODULE_DIR_ . $this->name . '/' . self::ICON_DIR . $old;
            if (file_exists($path)) {
                @unlink($path);
            }
            Configuration::updateValue(self::CONF_ICON, '');

            return $this->displayConfirmation($this->l('Icône supprimée.'));
        }

        return '';
    }

    protected function getIconUrl()
    {
        $icon = (string) Configuration::get(self::CONF_ICON);
        if (!$icon) {
            return '';
        }
        $path = _PS_MODULE_DIR_ . $this->name . '/' . self::ICON_DIR . $icon;
        if (!file_exists($path)) {
            return '';
        }

        return $this->_path . self::ICON_DIR . $icon . '?t=' . filemtime($path);
    }

    protected function renderForm()
    {
        $iconUrl = $this->getIconUrl();
        $deleteIconUrl = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&deleteIcon=1';

        $html = '<form method="post" enctype="multipart/form-data" class="defaultForm form-horizontal">';
        $html .= '<div class="panel"><h3><i class="icon-leaf"></i> ' . $this->l('Configuration Checkout Ecologie') . '</h3>';

        // Toggle activé
        $enabled = (int) Configuration::get(self::CONF_ENABLED);
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Afficher l\'option côté client') . '</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<span class="switch prestashop-switch fixed-width-lg">';
        $html .= '<input type="radio" name="CECO_ENABLED" id="CECO_ENABLED_on" value="1"' . ($enabled ? ' checked="checked"' : '') . '><label for="CECO_ENABLED_on">' . $this->l('Oui') . '</label>';
        $html .= '<input type="radio" name="CECO_ENABLED" id="CECO_ENABLED_off" value="0"' . (!$enabled ? ' checked="checked"' : '') . '><label for="CECO_ENABLED_off">' . $this->l('Non') . '</label>';
        $html .= '<a class="slide-button btn"></a></span>';
        $html .= '</div></div>';

        // Texte de la case
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Texte affiché à côté de la case') . '</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<input type="text" name="CECO_LABEL" value="' . htmlspecialchars((string) Configuration::get(self::CONF_LABEL), ENT_QUOTES) . '" class="form-control" />';
        $html .= '<p class="help-block">' . $this->l('Tu peux ajuster ce texte. Inclus le montant si tu veux qu\'il soit visible.') . '</p>';
        $html .= '</div></div>';

        // Montant
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Montant de la réduction (€ TTC)') . '</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<input type="text" name="CECO_AMOUNT" value="' . htmlspecialchars((string) Configuration::get(self::CONF_AMOUNT), ENT_QUOTES) . '" class="form-control" style="max-width:120px;" />';
        $html .= '<p class="help-block">' . $this->l('Réduction appliquée sur le sous-total produits, cumulable avec d\'autres promos.') . '</p>';
        $html .= '</div></div>';

        // Label BO commande
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Mention affichée en BO commande') . '</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<input type="text" name="CECO_BO_LABEL" value="' . htmlspecialchars((string) Configuration::get(self::CONF_BO_LABEL), ENT_QUOTES) . '" class="form-control" />';
        $html .= '<p class="help-block">' . $this->l('Visible par les préparateurs sur la fiche commande quand le client a coché l\'option.') . '</p>';
        $html .= '</div></div>';

        // Icône
        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Icône (facultatif)') . '</label>';
        $html .= '<div class="col-lg-9">';
        if ($iconUrl) {
            $html .= '<div style="margin-bottom:10px;display:flex;align-items:center;gap:12px;">';
            $html .= '<img src="' . $iconUrl . '" style="max-width:64px;max-height:64px;border:1px solid #ddd;padding:4px;background:#fafafa;" />';
            $html .= '<a href="' . $deleteIconUrl . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer l\\\'icône ?\')"><i class="icon-trash"></i> ' . $this->l('Supprimer') . '</a>';
            $html .= '</div>';
        }
        $html .= '<input type="file" name="CECO_ICON_FILE" accept="image/png,image/jpeg,image/webp,image/svg+xml" />';
        $html .= '<p class="help-block">' . $this->l('PNG, JPG, WEBP, SVG · max 512 Ko. Affichée à gauche du texte de la case.') . '</p>';
        $html .= '</div></div>';

        $html .= '</div>'; // panel

        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitCheckoutEcoSettings" class="btn btn-default pull-right"><i class="process-icon-save"></i> ' . $this->l('Enregistrer') . '</button>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'checkoutecologie',
            'modules/' . $this->name . '/views/css/checkoutecologie.css',
            ['priority' => 200]
        );
        $this->context->controller->registerJavascript(
            'checkoutecologie',
            'modules/' . $this->name . '/views/js/checkoutecologie.js',
            ['priority' => 200]
        );
    }

    public function hookDisplayBeforeCarrier($params)
    {
        if (!Configuration::get(self::CONF_ENABLED)) {
            return '';
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return '';
        }

        $cartRuleId = (int) Configuration::get(self::CONF_CART_RULE_ID);
        $isActive = false;
        foreach ($cart->getCartRules() as $cr) {
            if ((int) $cr['id_cart_rule'] === $cartRuleId) {
                $isActive = true;
                break;
            }
        }

        $this->context->smarty->assign([
            'ceco_label' => (string) Configuration::get(self::CONF_LABEL),
            'ceco_amount' => (float) Configuration::get(self::CONF_AMOUNT),
            'ceco_icon_url' => $this->getIconUrl(),
            'ceco_active' => $isActive,
            'ceco_toggle_url' => $this->context->link->getModuleLink($this->name, 'toggle', [], true),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/beforecarrier.tpl');
    }

    public function hookActionValidateOrder($params)
    {
        if (!isset($params['cart'], $params['order'])) {
            return;
        }
        /** @var Cart $cart */
        $cart = $params['cart'];
        /** @var Order $order */
        $order = $params['order'];

        $cartRuleId = (int) Configuration::get(self::CONF_CART_RULE_ID);
        if (!$cartRuleId) {
            return;
        }

        foreach ($cart->getCartRules() as $cr) {
            if ((int) $cr['id_cart_rule'] === $cartRuleId) {
                Db::getInstance()->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'orders` SET `eco_packaging` = 1 WHERE `id_order` = ' . (int) $order->id
                );

                return;
            }
        }
    }

    public function hookDisplayAdminOrderMain($params)
    {
        $idOrder = (int) ($params['id_order'] ?? 0);
        if (!$idOrder) {
            return '';
        }
        $eco = (int) Db::getInstance()->getValue(
            'SELECT `eco_packaging` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . $idOrder
        );
        if (!$eco) {
            return '';
        }

        $this->context->smarty->assign([
            'ceco_bo_label' => (string) Configuration::get(self::CONF_BO_LABEL),
            'ceco_icon_url' => $this->getIconUrl(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/orderdetail.tpl');
    }
}
