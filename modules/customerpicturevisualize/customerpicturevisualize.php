<?php
/**
 * Customer Picture Visualize — vignettes des images en attente dans les paniers
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomerPictureVisualize extends Module
{
    public function __construct()
    {
        $this->name = 'customerpicturevisualize';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customer Picture Visualize');
        $this->description = $this->l('Visualise les images personnalisées en attente dans les paniers.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $items = $this->fetchPendingImages();
        $fsItems = $this->scanFilesystemImages();
        return $this->renderPanel($items) . $this->renderFsPanel($fsItems);
    }

    /**
     * Scan des fichiers physiques mousepadeditor non encore liés à un panier.
     * - Fonds clients : modules/mousepadeditor/uploads/customer/{key}/bg.*
     * - Aperçus HD générés : modules/mousepadeditor/uploads/previews/*.png
     */
    protected function scanFilesystemImages()
    {
        $base = _PS_MODULE_DIR_ . 'mousepadeditor/uploads/';
        $baseUrl = __PS_BASE_URI__ . 'modules/mousepadeditor/uploads/';
        $items = [];

        // Fonds clients
        $custDir = $base . 'customer/';
        if (is_dir($custDir)) {
            foreach (scandir($custDir) as $key) {
                if ($key === '.' || $key === '..') continue;
                $sub = $custDir . $key . '/';
                if (!is_dir($sub)) continue;
                foreach (glob($sub . 'bg.*') as $f) {
                    $items[] = [
                        'type' => $this->l('Fond client'),
                        'thumb_url' => $baseUrl . 'customer/' . $key . '/' . basename($f),
                        'full_url' => $baseUrl . 'customer/' . $key . '/' . basename($f),
                        'key' => $key,
                        'name' => basename($f),
                        'size' => filesize($f),
                        'mtime' => date('Y-m-d H:i', filemtime($f)),
                    ];
                }
            }
        }

        // Aperçus HD générés (composés mais pas forcément ajoutés au panier)
        $prevDir = $base . 'previews/';
        if (is_dir($prevDir)) {
            foreach (glob($prevDir . '*.png') as $f) {
                $items[] = [
                    'type' => $this->l('Aperçu HD'),
                    'thumb_url' => $baseUrl . 'previews/' . basename($f),
                    'full_url' => $baseUrl . 'previews/' . basename($f),
                    'key' => '',
                    'name' => basename($f),
                    'size' => filesize($f),
                    'mtime' => date('Y-m-d H:i', filemtime($f)),
                ];
            }
        }

        usort($items, function($a, $b){ return strcmp($b['mtime'], $a['mtime']); });
        return $items;
    }

    protected function renderFsPanel(array $items)
    {
        $count = count($items);
        $size = 0;
        foreach ($items as $i) $size += $i['size'];

        $html = '<div class="panel" style="margin-top:20px;border-left:4px solid #ee7a03;">';
        $html .= '<h3><i class="icon-folder-open"></i> ' . $this->l('Fichiers physiques (hors panier)') . '</h3>';
        $html .= '<p style="font-size:14px;color:#666;">' . $this->l('Fonds clients uploadés et aperçus HD générés, pas encore liés à une customization panier.') . '</p>';
        $html .= '<p style="font-size:16px;color:#ee7a03;"><strong>' . $count . '</strong> ' . $this->l('fichier(s)') . ' · <strong>' . $this->formatBytes($size) . '</strong></p>';
        $html .= '</div>';

        if ($count === 0) return $html;

        $html .= '<div class="panel">';
        $html .= '<div style="display:flex;flex-wrap:wrap;gap:14px;">';
        foreach ($items as $i) {
            $badge = $i['type'] === 'Fond client' ? '#3498db' : '#ee7a03';
            $html .= '<div style="border:1px solid #ddd;border-radius:6px;padding:10px;width:180px;background:#fafafa;">';
            $html .= '<a href="#" class="cpv-zoom" data-full="' . htmlspecialchars($i['full_url']) . '">';
            $html .= '<img src="' . htmlspecialchars($i['thumb_url']) . '" style="width:100%;height:120px;object-fit:cover;border-radius:4px;display:block;" />';
            $html .= '</a>';
            $html .= '<div style="font-size:10px;color:#fff;background:' . $badge . ';display:inline-block;padding:2px 6px;border-radius:3px;margin-top:6px;">' . htmlspecialchars($i['type']) . '</div>';
            if ($i['key']) {
                $html .= '<div style="font-size:10px;color:#666;margin-top:4px;word-break:break-all;">' . $this->l('Clé') . ' : ' . htmlspecialchars($i['key']) . '</div>';
            }
            $html .= '<div style="font-size:10px;color:#999;margin-top:4px;">' . $this->formatBytes($i['size']) . ' · ' . htmlspecialchars($i['mtime']) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Récupère toutes les customizations file (type=1) dont le panier n'a pas
     * encore été converti en commande.
     */
    protected function fetchPendingImages()
    {
        $sql = '
            SELECT cd.value AS hash,
                   c.id_customization,
                   c.id_cart,
                   c.id_product,
                   ca.id_customer,
                   ca.date_upd,
                   pl.name AS product_name,
                   CONCAT(cu.firstname, " ", cu.lastname) AS customer_name,
                   cu.email
            FROM ' . _DB_PREFIX_ . 'customized_data cd
            INNER JOIN ' . _DB_PREFIX_ . 'customization c
                ON c.id_customization = cd.id_customization
            INNER JOIN ' . _DB_PREFIX_ . 'cart ca
                ON ca.id_cart = c.id_cart
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o
                ON o.id_cart = ca.id_cart
            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl
                ON pl.id_product = c.id_product AND pl.id_lang = ' . (int) $this->context->language->id . '
            LEFT JOIN ' . _DB_PREFIX_ . 'customer cu
                ON cu.id_customer = ca.id_customer
            WHERE cd.type = 1
              AND o.id_order IS NULL
            ORDER BY ca.date_upd DESC
        ';
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) return [];

        $base = __PS_BASE_URI__ . 'upload/';
        $items = [];
        foreach ($rows as $r) {
            $hash = $r['hash'];
            $file = _PS_UPLOAD_DIR_ . $hash;
            if (!file_exists($file)) continue;
            $thumb = file_exists($file . '_small') ? $base . $hash . '_small' : $base . $hash;
            $items[] = [
                'hash' => $hash,
                'thumb_url' => $thumb,
                'full_url' => $base . $hash,
                'id_customization' => (int) $r['id_customization'],
                'id_cart' => (int) $r['id_cart'],
                'id_product' => (int) $r['id_product'],
                'product_name' => $r['product_name'],
                'customer_name' => trim($r['customer_name']) ?: 'Invité',
                'email' => $r['email'] ?: '—',
                'date' => $r['date_upd'],
                'size' => filesize($file),
            ];
        }
        return $items;
    }

    protected function renderPanel(array $items)
    {
        $count = count($items);
        $size = 0;
        foreach ($items as $i) $size += $i['size'];

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-picture"></i> ' . $this->l('Images en attente dans les paniers') . '</h3>';
        $html .= '<p style="font-size:16px;color:#004774;"><strong>' . $count . '</strong> ' . $this->l('image(s)') . ' · <strong>' . $this->formatBytes($size) . '</strong></p>';
        $html .= '<p style="color:#888;font-size:12px;">' . $this->l('Cliquez sur une vignette pour l\'agrandir.') . '</p>';
        $html .= '</div>';

        if ($count === 0) {
            $html .= '<div class="panel"><p>' . $this->l('Aucune image en attente.') . '</p></div>';
            return $html;
        }

        $html .= '<div class="panel">';
        $html .= '<div style="display:flex;flex-wrap:wrap;gap:14px;">';
        foreach ($items as $i) {
            $html .= '<div style="border:1px solid #ddd;border-radius:6px;padding:10px;width:180px;background:#fafafa;">';
            $html .= '<a href="#" class="cpv-zoom" data-full="' . htmlspecialchars($i['full_url']) . '">';
            $html .= '<img src="' . htmlspecialchars($i['thumb_url']) . '" style="width:100%;height:120px;object-fit:cover;border-radius:4px;display:block;" />';
            $html .= '</a>';
            $html .= '<div style="font-size:11px;margin-top:8px;color:#333;font-weight:600;word-break:break-all;">' . htmlspecialchars($i['product_name'] ?: 'Produit #' . $i['id_product']) . '</div>';
            $html .= '<div style="font-size:10px;color:#666;margin-top:4px;">' . htmlspecialchars($i['customer_name']) . '</div>';
            $html .= '<div style="font-size:10px;color:#888;">' . htmlspecialchars($i['email']) . '</div>';
            $html .= '<div style="font-size:10px;color:#999;margin-top:4px;">' . $this->l('Panier') . ' #' . $i['id_cart'] . ' · ' . $this->formatBytes($i['size']) . '</div>';
            $html .= '<div style="font-size:10px;color:#999;">' . htmlspecialchars($i['date']) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';

        // Lightbox + JS
        $html .= '
<div id="cpv-lightbox" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;">
  <img id="cpv-lightbox-img" src="" style="max-width:92vw;max-height:92vh;box-shadow:0 0 40px rgba(0,0,0,0.5);border-radius:4px;" />
</div>
<script>
(function(){
  var lb = document.getElementById("cpv-lightbox");
  var img = document.getElementById("cpv-lightbox-img");
  document.querySelectorAll(".cpv-zoom").forEach(function(a){
    a.addEventListener("click", function(e){
      e.preventDefault();
      img.src = this.getAttribute("data-full");
      lb.style.display = "flex";
    });
  });
  lb.addEventListener("click", function(){ lb.style.display = "none"; img.src = ""; });
  document.addEventListener("keydown", function(e){ if (e.key === "Escape") { lb.style.display = "none"; img.src = ""; } });
})();
</script>';

        return $html;
    }

    protected function formatBytes($bytes)
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' Mo';
        return round($bytes / 1073741824, 2) . ' Go';
    }
}
