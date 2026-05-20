<?php
/**
 * Module CILegacyRedirect — Le Cadeau Idéal
 *
 * Redirige automatiquement (301) les anciennes URLs sans préfixe ID
 * (ex: /produits-personnalisable) vers leur URL canonique actuelle
 * (ex: /10-produits-personnalisable).
 *
 * Couvre : catégories, produits et pages CMS actives.
 * Le matching se fait par link_rewrite, sans modification du code PS.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CILegacyRedirect extends Module
{
    public function __construct()
    {
        $this->name = 'cilegacyredirect';
        $this->tab = 'seo';
        $this->version = '1.0.0';
        $this->author = 'Le Cadeau Idéal';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Legacy URL Redirect');
        $this->description = $this->l('Redirige en 301 les anciennes URLs sans préfixe ID vers leur URL canonique (catégories, produits, CMS).');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionDispatcherBefore');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Hook déclenché en début de dispatch (avant que PS ne route la requête).
     * On y intercepte les URLs en forme de slug nu pour les rediriger en 301
     * vers leur URL canonique si elles correspondent à une entité active.
     */
    public function hookActionDispatcherBefore($params)
    {
        // DEBUG TEMP
        @file_put_contents('/tmp/cilegacy.log', date('H:i:s') . ' HOOK called uri=' . ($_SERVER['REQUEST_URI'] ?? 'NULL') . "\n", FILE_APPEND);

        // Skip back-office, AJAX, webservice, admin
        if (defined('_PS_ADMIN_DIR_')) {
            return;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if ($uri === '') {
            return;
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $slug = trim((string) $path, '/');

        @file_put_contents('/tmp/cilegacy.log', date('H:i:s') . '   slug=' . $slug . "\n", FILE_APPEND);

        // Skip extensions et préfixe ID (déjà canoniques)
        if (preg_match('#^\d+-#', $slug)) {
            @file_put_contents('/tmp/cilegacy.log', "   skip: prefix id\n", FILE_APPEND);
            return;
        }
        if (preg_match('#\.[a-z0-9]{2,5}$#i', $slug)) {
            @file_put_contents('/tmp/cilegacy.log', "   skip: extension\n", FILE_APPEND);
            return;
        }

        try {
            $ctx = Context::getContext();
            $idLang = $ctx && $ctx->language ? (int) $ctx->language->id : 0;
            $idShop = $ctx && $ctx->shop ? (int) $ctx->shop->id : 0;
            @file_put_contents('/tmp/cilegacy.log', "   ctx idLang=$idLang idShop=$idShop\n", FILE_APPEND);

            if ($idLang === 0) {
                $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
                @file_put_contents('/tmp/cilegacy.log', "   fallback idLang=$idLang\n", FILE_APPEND);
            }
            if ($idShop === 0) {
                $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
                @file_put_contents('/tmp/cilegacy.log', "   fallback idShop=$idShop\n", FILE_APPEND);
            }
        } catch (\Throwable $e) {
            @file_put_contents('/tmp/cilegacy.log', "   ERR ctx: " . $e->getMessage() . "\n", FILE_APPEND);
            return;
        }

        // Skip racine, sous-chemins, requêtes avec query/params
        if ($slug === '' || strpos($slug, '/') !== false) {
            return;
        }

        try {
            $target = $this->findCategoryUrl($slug, $idLang, $idShop)
                ?: $this->findProductUrl($slug, $idLang, $idShop)
                ?: $this->findCmsUrl($slug, $idLang, $idShop);
        } catch (\Throwable $e) {
            @file_put_contents('/tmp/cilegacy.log', "   ERR find: " . $e->getMessage() . "\n", FILE_APPEND);
            return;
        }

        @file_put_contents('/tmp/cilegacy.log', date('H:i:s') . '   target=' . var_export($target, true) . "\n", FILE_APPEND);

        if ($target !== null) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $target);
            header('Cache-Control: max-age=86400, public');
            exit;
        }
    }

    private function findCategoryUrl($slug, $idLang, $idShop)
    {
        $sql = 'SELECT c.id_category, cl.link_rewrite
                FROM ' . _DB_PREFIX_ . 'category c
                INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl
                    ON c.id_category = cl.id_category
                   AND cl.id_lang = ' . (int) $idLang . '
                   AND cl.id_shop = ' . (int) $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs
                    ON c.id_category = cs.id_category
                   AND cs.id_shop = ' . (int) $idShop . '
                WHERE cl.link_rewrite = "' . pSQL($slug) . '"
                  AND c.active = 1
                LIMIT 1';

        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }

        return Context::getContext()->link->getCategoryLink(
            (int) $row['id_category'],
            $row['link_rewrite'],
            (int) $idLang
        );
    }

    private function findProductUrl($slug, $idLang, $idShop)
    {
        $sql = 'SELECT p.id_product, pl.link_rewrite
                FROM ' . _DB_PREFIX_ . 'product p
                INNER JOIN ' . _DB_PREFIX_ . 'product_lang pl
                    ON p.id_product = pl.id_product
                   AND pl.id_lang = ' . (int) $idLang . '
                   AND pl.id_shop = ' . (int) $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps
                    ON p.id_product = ps.id_product
                   AND ps.id_shop = ' . (int) $idShop . '
                WHERE pl.link_rewrite = "' . pSQL($slug) . '"
                  AND ps.active = 1
                  AND ps.visibility IN ("both", "catalog", "search")
                LIMIT 1';

        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }

        return Context::getContext()->link->getProductLink(
            (int) $row['id_product'],
            $row['link_rewrite'],
            null,
            null,
            (int) $idLang
        );
    }

    private function findCmsUrl($slug, $idLang, $idShop)
    {
        $sql = 'SELECT c.id_cms, cl.link_rewrite
                FROM ' . _DB_PREFIX_ . 'cms c
                INNER JOIN ' . _DB_PREFIX_ . 'cms_lang cl
                    ON c.id_cms = cl.id_cms
                   AND cl.id_lang = ' . (int) $idLang . '
                   AND cl.id_shop = ' . (int) $idShop . '
                INNER JOIN ' . _DB_PREFIX_ . 'cms_shop cs
                    ON c.id_cms = cs.id_cms
                   AND cs.id_shop = ' . (int) $idShop . '
                WHERE cl.link_rewrite = "' . pSQL($slug) . '"
                  AND c.active = 1
                LIMIT 1';

        $row = Db::getInstance()->getRow($sql);
        if (!$row) {
            return null;
        }

        return Context::getContext()->link->getCMSLink(
            (int) $row['id_cms'],
            $row['link_rewrite'],
            null,
            (int) $idLang
        );
    }
}
