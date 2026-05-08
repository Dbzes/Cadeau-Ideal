<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'customersinspector/classes/GeoIpResolver.php';

class AdminCustomersInspectorController extends ModuleAdminController
{
    private const PAGE_TYPE_LABELS = [
        'index' => "Page d'accueil",
        'pagenotfound' => 'Page introuvable (404)',
        'authentication' => 'Connexion / Inscription',
        'contact' => 'Contact',
        'category' => 'Catégorie',
        'search' => 'Recherche',
        'product' => 'Produit',
        'pricesdrop' => 'Promotions',
        'cart' => 'Panier',
        'order' => 'Commande',
        'password' => 'Mot de passe oublié',
        'registration' => 'Inscription',
        'myaccount' => 'Mon compte',
        'gdpr' => 'RGPD',
        'sellerproduct' => 'Produit vendeur',
        'm4pdf' => 'PDF (m4pdf)',
        'productdesigner' => 'Personnalisation produit',
        'popup' => 'Popup',
        'pdf' => 'PDF',
        'orderdetail' => 'Détail commande',
        'sitemap' => 'Plan du site',
        'bestsales' => 'Meilleures ventes',
        'cms' => 'Page CMS',
        'newproducts' => 'Nouveautés',
        'manufacturer' => 'Marque',
        'stores' => 'Magasins',
    ];

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'Customers Inspector';
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        [$dateFrom, $dateTo] = $this->getRequestedRange();
        $selectedCountries = $this->getRequestedCountries();

        $resolver = new CustomersInspectorGeoIpResolver();
        $idLang = (int) $this->context->language->id;

        // 1. Connexions (page d'entrée + IP) sur la période
        $rows = $this->fetchConnectionsRange($dateFrom, $dateTo);

        // 2. Construction maps pays + filtre + KPIs
        $guestCountry = [];
        $countryGuestSets = [];
        foreach ($rows as $row) {
            $iso = $resolver->resolve($row['ip_address']) ?: 'XX';
            $guestId = (int) $row['id_guest'];
            $guestCountry[$guestId] = $iso;
            $countryGuestSets[$iso][$guestId] = true;
        }

        $allowedGuests = $this->buildAllowedGuests($guestCountry, $selectedCountries);
        $totalUnique = count(array_filter(array_keys($guestCountry), fn($g) => isset($allowedGuests[$g])));

        // 3. Pages d'entrée — agrégées avec filtre pays
        $entryPagesRaw = $this->aggregatePages(
            $this->fetchConnectionsPagesAggregable($dateFrom, $dateTo),
            $allowedGuests,
            $selectedCountries
        );
        $entryPages = $this->enrichPageLabels($entryPagesRaw, $idLang);

        // 4. Pages vues (ps_connections_page) — depuis activation tracking
        $viewedPagesRaw = $this->aggregatePages(
            $this->fetchViewedPagesAggregable($dateFrom, $dateTo),
            $allowedGuests,
            $selectedCountries
        );
        $viewedPages = $this->enrichPageLabels($viewedPagesRaw, $idLang);

        // 5. Devices (ps_customersinspector_visits) — depuis activation tracking
        $deviceStats = $this->aggregateDevices($dateFrom, $dateTo, $resolver, $selectedCountries);

        // 6. Liste pays disponibles (pour le filtre)
        $countryList = [];
        foreach ($countryGuestSets as $iso => $set) {
            $countryList[] = [
                'iso' => $iso,
                'label' => $this->isoToLabel($iso),
                'count' => count($set),
            ];
        }
        usort($countryList, fn($a, $b) => $b['count'] <=> $a['count']);

        $this->context->smarty->assign([
            'ci_date_from' => $dateFrom,
            'ci_date_to' => $dateTo,
            'ci_selected_countries' => $selectedCountries,
            'ci_country_list' => $countryList,
            'ci_total_unique' => $totalUnique,
            'ci_total_connections' => count($rows),
            'ci_geoip_ready' => $resolver->isReady(),
            'ci_form_action' => self::$currentIndex . '&token=' . $this->token,
            'ci_entry_pages' => $entryPages,
            'ci_viewed_pages' => $viewedPages,
            'ci_device_stats' => $deviceStats['rows'],
            'ci_device_total' => $deviceStats['total'],
            'ci_viewed_pages_total' => array_sum(array_column($viewedPages, 'count')),
        ]);

        $tplPath = _PS_MODULE_DIR_ . 'customersinspector/views/templates/admin/index.tpl';
        $this->content = $this->context->smarty->fetch($tplPath);
        $this->context->smarty->assign('content', $this->content);
    }

    private function buildAllowedGuests(array $guestCountry, array $selectedCountries): array
    {
        if (empty($selectedCountries)) {
            return array_flip(array_keys($guestCountry));
        }
        $set = array_flip($selectedCountries);
        $allowed = [];
        foreach ($guestCountry as $guestId => $iso) {
            if (isset($set[$iso])) {
                $allowed[$guestId] = true;
            }
        }
        return $allowed;
    }

    private function getRequestedRange(): array
    {
        $preset = Tools::getValue('preset');
        $today = date('Y-m-d');
        $presets = [
            'today' => [$today, $today],
            'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
            '7d' => [date('Y-m-d', strtotime('-6 days')), $today],
            '30d' => [date('Y-m-d', strtotime('-29 days')), $today],
            'month' => [date('Y-m-01'), $today],
            'year' => [date('Y-01-01'), $today],
        ];
        if ($preset && isset($presets[$preset])) {
            return $presets[$preset];
        }
        $from = Tools::getValue('date_from');
        $to = Tools::getValue('date_to');
        $valid = fn($d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        if ($valid($from) && $valid($to)) {
            return [$from, $to];
        }
        return $presets['30d'];
    }

    private function getRequestedCountries(): array
    {
        $countries = Tools::getValue('countries');
        if (!is_array($countries)) {
            return [];
        }
        $clean = [];
        foreach ($countries as $iso) {
            if (is_string($iso) && (preg_match('/^[A-Z]{2}$/', $iso) || $iso === 'XX')) {
                $clean[] = $iso;
            }
        }
        return $clean;
    }

    private function fetchConnectionsRange(string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT id_guest, ip_address
                FROM `' . _DB_PREFIX_ . 'connections`
                WHERE date_add BETWEEN \'' . pSQL($dateFrom) . ' 00:00:00\'
                                   AND \'' . pSQL($dateTo) . ' 23:59:59\'
                  AND ip_address IS NOT NULL';
        $result = Db::getInstance()->executeS($sql);
        return is_array($result) ? $result : [];
    }

    private function fetchConnectionsPagesAggregable(string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT c.id_guest, c.id_page, p.id_page_type, p.id_object, pt.name AS page_type
                FROM `' . _DB_PREFIX_ . 'connections` c
                JOIN `' . _DB_PREFIX_ . 'page` p ON p.id_page = c.id_page
                JOIN `' . _DB_PREFIX_ . 'page_type` pt ON pt.id_page_type = p.id_page_type
                WHERE c.date_add BETWEEN \'' . pSQL($dateFrom) . ' 00:00:00\'
                                     AND \'' . pSQL($dateTo) . ' 23:59:59\'';
        $result = Db::getInstance()->executeS($sql);
        return is_array($result) ? $result : [];
    }

    private function fetchViewedPagesAggregable(string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT c.id_guest, cp.id_page, p.id_page_type, p.id_object, pt.name AS page_type
                FROM `' . _DB_PREFIX_ . 'connections_page` cp
                JOIN `' . _DB_PREFIX_ . 'connections` c ON c.id_connections = cp.id_connections
                JOIN `' . _DB_PREFIX_ . 'page` p ON p.id_page = cp.id_page
                JOIN `' . _DB_PREFIX_ . 'page_type` pt ON pt.id_page_type = p.id_page_type
                WHERE cp.time_start BETWEEN \'' . pSQL($dateFrom) . ' 00:00:00\'
                                        AND \'' . pSQL($dateTo) . ' 23:59:59\'';
        $result = Db::getInstance()->executeS($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Agrège des lignes (id_guest, page_type, id_object) en groupant par (page_type, id_object).
     * Filtre les guests autorisés.
     */
    private function aggregatePages(array $rows, array $allowedGuests, array $selectedCountries): array
    {
        $useFilter = !empty($selectedCountries);
        $grouped = [];
        foreach ($rows as $row) {
            $guestId = (int) $row['id_guest'];
            if ($useFilter && !isset($allowedGuests[$guestId])) {
                continue;
            }
            $type = (string) $row['page_type'];
            $idObject = isset($row['id_object']) ? (int) $row['id_object'] : 0;
            $key = $type . '|' . $idObject;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'page_type' => $type,
                    'id_object' => $idObject,
                    'count' => 0,
                ];
            }
            $grouped[$key]['count']++;
        }
        usort($grouped, fn($a, $b) => $b['count'] <=> $a['count']);
        return array_values($grouped);
    }

    private function enrichPageLabels(array $pages, int $idLang): array
    {
        $needLookup = ['category' => [], 'product' => [], 'cms' => [], 'manufacturer' => []];
        foreach ($pages as $p) {
            $type = $p['page_type'];
            $idObj = (int) $p['id_object'];
            if ($idObj > 0 && isset($needLookup[$type])) {
                $needLookup[$type][$idObj] = true;
            }
        }
        $names = [
            'category' => $this->fetchNames('category_lang', 'id_category', 'name', array_keys($needLookup['category']), $idLang),
            'product' => $this->fetchNames('product_lang', 'id_product', 'name', array_keys($needLookup['product']), $idLang),
            'cms' => $this->fetchNames('cms_lang', 'id_cms', 'meta_title', array_keys($needLookup['cms']), $idLang),
            'manufacturer' => $this->fetchNames('manufacturer', 'id_manufacturer', 'name', array_keys($needLookup['manufacturer']), null),
        ];
        $out = [];
        foreach ($pages as $p) {
            $type = $p['page_type'];
            $idObj = (int) $p['id_object'];
            $typeLabel = self::PAGE_TYPE_LABELS[$type] ?? ucfirst($type);
            $objectName = '';
            if ($idObj > 0 && isset($names[$type][$idObj])) {
                $objectName = $names[$type][$idObj];
            }
            $out[] = [
                'page_type' => $type,
                'page_type_label' => $typeLabel,
                'id_object' => $idObj,
                'object_name' => $objectName,
                'count' => (int) $p['count'],
            ];
        }
        return $out;
    }

    private function fetchNames(string $table, string $idCol, string $nameCol, array $ids, ?int $idLang): array
    {
        if (empty($ids)) {
            return [];
        }
        $idsSafe = array_map('intval', $ids);
        $idsList = implode(',', $idsSafe);
        $where = $idLang !== null ? ' AND id_lang = ' . (int) $idLang : '';
        $sql = 'SELECT `' . pSQL($idCol) . '` AS id, `' . pSQL($nameCol) . '` AS name
                FROM `' . _DB_PREFIX_ . pSQL($table) . '`
                WHERE `' . pSQL($idCol) . '` IN (' . $idsList . ')' . $where;
        $rows = Db::getInstance()->executeS($sql);
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $out[(int) $r['id']] = (string) $r['name'];
            }
        }
        return $out;
    }

    private function aggregateDevices(string $dateFrom, string $dateTo, $resolver, array $selectedCountries): array
    {
        $sql = 'SELECT device_type, ip_address, country_iso
                FROM `' . _DB_PREFIX_ . 'customersinspector_visits`
                WHERE date_add BETWEEN \'' . pSQL($dateFrom) . ' 00:00:00\'
                                   AND \'' . pSQL($dateTo) . ' 23:59:59\'';
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            $rows = [];
        }
        $useFilter = !empty($selectedCountries);
        $set = array_flip($selectedCountries);
        $counts = ['mobile' => 0, 'tablet' => 0, 'desktop' => 0, 'bot' => 0, 'unknown' => 0];
        $total = 0;
        foreach ($rows as $r) {
            if ($useFilter) {
                $iso = !empty($r['country_iso']) ? $r['country_iso'] : ($resolver->resolve($r['ip_address']) ?: 'XX');
                if (!isset($set[$iso])) {
                    continue;
                }
            }
            $type = (string) $r['device_type'];
            if (!isset($counts[$type])) {
                $counts['unknown']++;
            } else {
                $counts[$type]++;
            }
            $total++;
        }
        $labels = [
            'mobile' => 'Mobile',
            'tablet' => 'Tablette',
            'desktop' => 'Ordinateur',
            'bot' => 'Bot / Crawler',
            'unknown' => 'Inconnu',
        ];
        $out = [];
        foreach ($counts as $k => $n) {
            if ($n === 0 && $k === 'unknown') {
                continue;
            }
            $out[] = [
                'type' => $k,
                'label' => $labels[$k],
                'count' => $n,
                'pct' => $total > 0 ? round($n * 100 / $total, 1) : 0,
            ];
        }
        usort($out, fn($a, $b) => $b['count'] <=> $a['count']);
        return ['rows' => $out, 'total' => $total];
    }

    private function isoToLabel(string $iso): string
    {
        if ($iso === 'XX') {
            return 'Inconnu';
        }
        $idCountry = (int) Country::getByIso($iso);
        if ($idCountry > 0) {
            $name = Country::getNameById($this->context->language->id, $idCountry);
            if ($name) {
                return $name . ' (' . $iso . ')';
            }
        }
        return $iso;
    }
}
