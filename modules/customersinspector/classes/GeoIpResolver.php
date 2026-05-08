<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomersInspectorGeoIpResolver
{
    /** @var \GeoIp2\Database\Reader|null */
    private $reader;

    /** @var array<string,string> Cache mémoire IP→ISO */
    private $cache = [];

    public function __construct()
    {
        $dbPath = _PS_MODULE_DIR_ . 'customersinspector/data/dbip-country-lite.mmdb';
        if (file_exists($dbPath) && class_exists('\\GeoIp2\\Database\\Reader')) {
            try {
                $this->reader = new \GeoIp2\Database\Reader($dbPath);
            } catch (\Throwable $e) {
                $this->reader = null;
            }
        }
    }

    public function isReady(): bool
    {
        return $this->reader !== null;
    }

    /**
     * Résout une IP (string ou bigint PrestaShop) en code ISO 2 lettres.
     * Retourne null si non trouvé ou IP privée/locale.
     */
    public function resolve($ip): ?string
    {
        if (!$this->reader) {
            return null;
        }
        $ipStr = is_numeric($ip) ? long2ip((int) $ip) : (string) $ip;
        if ($ipStr === '' || $ipStr === '0.0.0.0') {
            return null;
        }
        if (isset($this->cache[$ipStr])) {
            return $this->cache[$ipStr] ?: null;
        }
        try {
            $record = $this->reader->country($ipStr);
            $iso = $record->country->isoCode ?: '';
            $this->cache[$ipStr] = $iso;
            return $iso ?: null;
        } catch (\Throwable $e) {
            $this->cache[$ipStr] = '';
            return null;
        }
    }

    /**
     * Résout en lot. Retourne ['ip' => 'ISO'|null].
     */
    public function resolveMany(array $ips): array
    {
        $out = [];
        foreach ($ips as $ip) {
            $out[(string) $ip] = $this->resolve($ip);
        }
        return $out;
    }
}
