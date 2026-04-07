<?php
/**
 * Purgator — purge des fichiers orphelins déclarés par d'autres modules
 *
 * Architecture extensible :
 * Les modules tiers implémentent hookActionPurgatorRegister($params)
 * et appellent Purgator::register([...]) pour déclarer leurs sources.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Purgator extends Module
{
    public static $registry = [];

    public function __construct()
    {
        $this->name = 'purgator';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Purgator');
        $this->description = $this->l('Purge les fichiers orphelins déclarés par les modules tiers.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('PURGATOR_DELAY_DAYS', 90)
            && $this->registerHook('actionPurgatorRegister');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PURGATOR_DELAY_DAYS');
        return parent::uninstall();
    }

    /**
     * API publique pour les modules tiers
     */
    public static function register(array $source)
    {
        if (empty($source['source_id']) || empty($source['source_name']) || !isset($source['files'])) {
            return;
        }
        if (!isset($source['files']) || !is_array($source['files'])) {
            $source['files'] = [];
        }
        self::$registry[$source['source_id']] = $source;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitPurgatorSettings')) {
            $delay = (int) Tools::getValue('PURGATOR_DELAY_DAYS');
            if ($delay < 1) $delay = 1;
            Configuration::updateValue('PURGATOR_DELAY_DAYS', $delay);
            $output .= $this->displayConfirmation($this->l('Délai mis à jour : ') . $delay . ' ' . $this->l('jours'));
        }

        if (Tools::getValue('purge')) {
            $output .= $this->handlePurge(Tools::getValue('purge'));
        }

        $delay = (int) Configuration::get('PURGATOR_DELAY_DAYS') ?: 90;

        // Collecte les sources auprès des modules
        self::$registry = [];
        Hook::exec('actionPurgatorRegister', ['delay' => $delay]);

        return $output . $this->renderSettingsForm($delay) . $this->renderSourcesPanels();
    }

    protected function renderSettingsForm($delay)
    {
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $html = '<div class="panel"><h3><i class="icon-cogs"></i> ' . $this->l('Paramètres') . '</h3>';
        $html .= '<form method="post" style="display:flex;align-items:center;gap:15px;">';
        $html .= '<label style="margin:0;font-weight:600;">' . $this->l('Délai avant purge (jours) :') . '</label>';
        $html .= '<input type="number" name="PURGATOR_DELAY_DAYS" value="' . (int) $delay . '" min="1" style="width:100px;padding:6px;border:1px solid #ccc;border-radius:4px;" />';
        $html .= '<button type="submit" name="submitPurgatorSettings" class="btn btn-primary">' . $this->l('Enregistrer') . '</button>';
        $html .= '</form>';
        $html .= '<p style="color:#888;font-size:12px;margin-top:10px;">' . $this->l('Les fichiers plus anciens que ce délai sont considérés comme orphelins et purgeables.') . '</p>';
        $html .= '</div>';
        return $html;
    }

    protected function renderSourcesPanels()
    {
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $html = '';

        if (empty(self::$registry)) {
            $html .= '<div class="panel"><p>' . $this->l('Aucun module n\'a déclaré de fichiers à monitorer. Vérifiez que les modules concernés sont installés et utilisent le hook actionPurgatorRegister.') . '</p></div>';
            return $html;
        }

        $totalAll = 0;
        $sizeAll = 0;
        foreach (self::$registry as $src) {
            $totalAll += count($src['files']);
            foreach ($src['files'] as $f) { $sizeAll += isset($f['size']) ? (int) $f['size'] : 0; }
        }

        // Récap global
        $html .= '<div class="panel" style="border-left:4px solid #ee7a03;">';
        $html .= '<h3><i class="icon-trash"></i> ' . $this->l('Récapitulatif global') . '</h3>';
        $html .= '<p style="font-size:16px;color:#004774;"><strong>' . $totalAll . '</strong> ' . $this->l('fichier(s) en attente de purge') . ' · <strong>' . $this->formatBytes($sizeAll) . '</strong> ' . $this->l('récupérables') . '</p>';
        $html .= '</div>';

        // Un panneau par source
        foreach (self::$registry as $src) {
            $count = count($src['files']);
            $size = 0;
            foreach ($src['files'] as $f) { $size += isset($f['size']) ? (int) $f['size'] : 0; }

            $html .= '<div class="panel">';
            $html .= '<h3><i class="icon-folder-open"></i> ' . htmlspecialchars($src['source_name']) . '</h3>';
            $html .= '<div style="display:flex;gap:30px;align-items:center;flex-wrap:wrap;">';
            $html .= '<div><div style="font-size:24px;font-weight:700;color:#004774;">' . $count . '</div><div style="font-size:12px;color:#888;text-transform:uppercase;">' . $this->l('Fichier(s)') . '</div></div>';
            $html .= '<div><div style="font-size:24px;font-weight:700;color:#ee7a03;">' . $this->formatBytes($size) . '</div><div style="font-size:12px;color:#888;text-transform:uppercase;">' . $this->l('Récupérable') . '</div></div>';
            $html .= '<div style="margin-left:auto;display:flex;gap:8px;">';
            if ($count > 0) {
                $html .= '<a href="#mpe-view-' . $src['source_id'] . '" class="btn btn-default" onclick="document.getElementById(\'mpe-view-' . $src['source_id'] . '\').style.display=\'block\';this.style.display=\'none\';return false;">' . $this->l('Visualiser') . '</a>';
                $html .= '<a href="' . $base . '&purge=' . urlencode($src['source_id']) . '" class="btn btn-danger" onclick="return confirm(\'Purger définitivement ' . $count . ' fichier(s) ? Cette action est irréversible.\')">' . $this->l('Purger maintenant') . '</a>';
            }
            $html .= '</div></div>';

            if ($count > 0) {
                $html .= '<div id="mpe-view-' . $src['source_id'] . '" style="display:none;margin-top:18px;">';
                $html .= '<div style="display:flex;flex-wrap:wrap;gap:12px;">';
                foreach ($src['files'] as $f) {
                    $thumb = isset($f['preview_url']) ? $f['preview_url'] : '';
                    $name = isset($f['name']) ? $f['name'] : basename($f['path']);
                    $age = isset($f['mtime']) ? $this->formatAge($f['mtime']) : '';
                    $sz = isset($f['size']) ? $this->formatBytes($f['size']) : '';
                    $html .= '<div style="border:1px solid #ddd;padding:8px;width:140px;text-align:center;background:#fafafa;border-radius:4px;">';
                    if ($thumb) {
                        $html .= '<img src="' . htmlspecialchars($thumb) . '" style="max-width:100%;height:90px;object-fit:cover;display:block;margin-bottom:6px;border-radius:2px;" onerror="this.style.display=\'none\'" />';
                    }
                    $html .= '<div style="font-size:11px;color:#666;word-break:break-all;">' . htmlspecialchars($name) . '</div>';
                    $html .= '<div style="font-size:10px;color:#999;margin-top:4px;">' . $sz . ' · ' . $age . '</div>';
                    $html .= '</div>';
                }
                $html .= '</div></div>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    protected function handlePurge($sourceId)
    {
        $delay = (int) Configuration::get('PURGATOR_DELAY_DAYS') ?: 90;
        self::$registry = [];
        Hook::exec('actionPurgatorRegister', ['delay' => $delay]);

        if (!isset(self::$registry[$sourceId])) {
            return $this->displayWarning($this->l('Source inconnue.'));
        }

        $src = self::$registry[$sourceId];
        $deleted = 0;
        $freed = 0;
        foreach ($src['files'] as $f) {
            if (!empty($f['path']) && file_exists($f['path'])) {
                $size = (int) filesize($f['path']);
                if (@unlink($f['path'])) {
                    $deleted++;
                    $freed += $size;
                    // Callback de cleanup DB optionnel
                    if (!empty($src['cleanup_callback']) && is_callable($src['cleanup_callback'])) {
                        call_user_func($src['cleanup_callback'], $f);
                    }
                }
            }
        }
        return $this->displayConfirmation(sprintf(
            $this->l('Purge terminée : %d fichier(s) supprimé(s), %s libéré(s).'),
            $deleted,
            $this->formatBytes($freed)
        ));
    }

    protected function formatBytes($bytes)
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' Mo';
        return round($bytes / 1073741824, 2) . ' Go';
    }

    protected function formatAge($mtime)
    {
        $diff = time() - $mtime;
        $days = floor($diff / 86400);
        if ($days < 1) return 'aujourd\'hui';
        if ($days == 1) return '1 jour';
        if ($days < 30) return $days . ' jours';
        $months = floor($days / 30);
        if ($months == 1) return '1 mois';
        return $months . ' mois';
    }
}
