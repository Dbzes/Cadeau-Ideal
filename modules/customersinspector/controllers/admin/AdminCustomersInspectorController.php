<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'customersinspector/classes/GeoIpResolver.php';

class AdminCustomersInspectorController extends ModuleAdminController
{
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

        $rows = $this->fetchConnectionsRange($dateFrom, $dateTo);
        $resolver = new CustomersInspectorGeoIpResolver();

        $availableCountries = [];
        $uniqueGuestsByCountry = [];
        $uniqueGuestsAllSet = [];

        foreach ($rows as $row) {
            $iso = $resolver->resolve($row['ip_address']) ?: 'XX';
            $availableCountries[$iso] = true;
            if (!isset($uniqueGuestsByCountry[$iso])) {
                $uniqueGuestsByCountry[$iso] = [];
            }
            $uniqueGuestsByCountry[$iso][(int) $row['id_guest']] = true;
            $uniqueGuestsAllSet[(int) $row['id_guest']] = true;
        }

        if (!empty($selectedCountries)) {
            $totalUnique = 0;
            $merged = [];
            foreach ($selectedCountries as $iso) {
                if (isset($uniqueGuestsByCountry[$iso])) {
                    foreach ($uniqueGuestsByCountry[$iso] as $idGuest => $_) {
                        $merged[$idGuest] = true;
                    }
                }
            }
            $totalUnique = count($merged);
        } else {
            $totalUnique = count($uniqueGuestsAllSet);
        }

        $countryList = [];
        foreach (array_keys($availableCountries) as $iso) {
            $countryList[] = [
                'iso' => $iso,
                'label' => $this->isoToLabel($iso),
                'count' => count($uniqueGuestsByCountry[$iso] ?? []),
            ];
        }
        usort($countryList, fn($a, $b) => $b['count'] <=> $a['count']);

        $this->context->smarty->assign([
            'ci_date_from' => $dateFrom,
            'ci_date_to' => $dateTo,
            'ci_selected_countries' => $selectedCountries,
            'ci_country_list' => $countryList,
            'ci_total_unique' => $totalUnique,
            'ci_geoip_ready' => $resolver->isReady(),
            'ci_form_action' => self::$currentIndex . '&token=' . $this->token,
            'ci_total_connections' => count($rows),
        ]);

        $tplPath = _PS_MODULE_DIR_ . 'customersinspector/views/templates/admin/index.tpl';
        $this->content = $this->context->smarty->fetch($tplPath);
        $this->context->smarty->assign('content', $this->content);
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
            if (is_string($iso) && preg_match('/^[A-Z]{2}$/', $iso)) {
                $clean[] = $iso;
            } elseif ($iso === 'XX') {
                $clean[] = 'XX';
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
