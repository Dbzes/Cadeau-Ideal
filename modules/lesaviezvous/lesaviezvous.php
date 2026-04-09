<?php
/**
 * Le Saviez-Vous — module de blocs de texte rotatifs
 * Utilisable par d'autres modules via LeSaviezVous::getBlocs()
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class LeSaviezVous extends Module
{
    public function __construct()
    {
        $this->name = 'lesaviezvous';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Claude';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Le Saviez-Vous ?');
        $this->description = $this->l('Blocs de texte rotatifs utilisables dans les zones d\'attente du site.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $default = json_encode([
            ['text' => 'Nos tapis de souris sont imprimés en haute définition à 150 dpi.', 'active' => true],
            ['text' => 'Chaque création est unique et fabriquée à la demande.', 'active' => true],
            ['text' => 'Vous pouvez ajouter jusqu\'à 3 images sur votre tapis personnalisé.', 'active' => true],
        ]);
        return parent::install()
            && Configuration::updateValue('LSV_BLOCS', $default);
    }

    public function uninstall()
    {
        Configuration::deleteByName('LSV_BLOCS');
        return parent::uninstall();
    }

    /**
     * API publique — utilisable par n'importe quel module
     * @return array Liste des blocs actifs ['text' => '...']
     */
    public static function getBlocs()
    {
        $raw = Configuration::get('LSV_BLOCS');
        $blocs = json_decode($raw, true);
        if (!is_array($blocs)) return [];
        return array_values(array_filter($blocs, function ($b) {
            return !empty($b['active']);
        }));
    }

    public function getContent()
    {
        $output = '';

        // Ajout d'un bloc
        if (Tools::isSubmit('submitAddBloc')) {
            $text = trim(Tools::getValue('lsv_new_text'));
            if ($text) {
                $blocs = $this->loadBlocs();
                $blocs[] = ['text' => $text, 'active' => true];
                $this->saveBlocs($blocs);
                $output .= $this->displayConfirmation($this->l('Bloc ajouté.'));
            }
        }

        // Modification d'un bloc
        if (Tools::isSubmit('submitEditBloc')) {
            $idx = (int) Tools::getValue('lsv_edit_idx');
            $text = trim(Tools::getValue('lsv_edit_text'));
            $blocs = $this->loadBlocs();
            if (isset($blocs[$idx]) && $text) {
                $blocs[$idx]['text'] = $text;
                $this->saveBlocs($blocs);
                $output .= $this->displayConfirmation($this->l('Bloc modifié.'));
            }
        }

        // Suppression
        if (Tools::getValue('deleteBloc') !== false && Tools::getValue('deleteBloc') !== '') {
            $idx = (int) Tools::getValue('deleteBloc');
            $blocs = $this->loadBlocs();
            if (isset($blocs[$idx])) {
                array_splice($blocs, $idx, 1);
                $this->saveBlocs($blocs);
                $output .= $this->displayConfirmation($this->l('Bloc supprimé.'));
            }
        }

        // Toggle actif/inactif
        if (Tools::getValue('toggleBloc') !== false && Tools::getValue('toggleBloc') !== '') {
            $idx = (int) Tools::getValue('toggleBloc');
            $blocs = $this->loadBlocs();
            if (isset($blocs[$idx])) {
                $blocs[$idx]['active'] = !$blocs[$idx]['active'];
                $this->saveBlocs($blocs);
                $output .= $this->displayConfirmation($this->l('Statut mis à jour.'));
            }
        }

        return $output . $this->renderPanel();
    }

    protected function loadBlocs()
    {
        $raw = Configuration::get('LSV_BLOCS');
        $blocs = json_decode($raw, true);
        return is_array($blocs) ? $blocs : [];
    }

    protected function saveBlocs(array $blocs)
    {
        Configuration::updateValue('LSV_BLOCS', json_encode(array_values($blocs)));
    }

    protected function renderPanel()
    {
        $blocs = $this->loadBlocs();
        $base = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-lightbulb-o"></i> ' . $this->l('Blocs « Le Saviez-Vous ? »') . '</h3>';
        $html .= '<p style="color:#888;font-size:13px;">' . $this->l('Ces textes s\'affichent en rotation dans les zones d\'attente du site (loader personnalisation, etc.).') . '</p>';

        if (empty($blocs)) {
            $html .= '<div class="alert alert-info">' . $this->l('Aucun bloc. Ajoutez-en un ci-dessous.') . '</div>';
        } else {
            $html .= '<table class="table table-striped"><thead><tr><th>#</th><th>' . $this->l('Texte') . '</th><th>' . $this->l('Statut') . '</th><th>' . $this->l('Actions') . '</th></tr></thead><tbody>';
            foreach ($blocs as $i => $b) {
                $active = !empty($b['active']);
                $html .= '<tr>';
                $html .= '<td>' . ($i + 1) . '</td>';
                $html .= '<td>';
                $html .= '<form method="post" style="display:flex;gap:8px;align-items:center;">';
                $html .= '<input type="hidden" name="lsv_edit_idx" value="' . $i . '" />';
                $html .= '<input type="text" name="lsv_edit_text" value="' . htmlspecialchars($b['text']) . '" class="form-control" style="flex:1;" />';
                $html .= '<button type="submit" name="submitEditBloc" class="btn btn-default btn-sm"><i class="icon-pencil"></i></button>';
                $html .= '</form>';
                $html .= '</td>';
                $html .= '<td><a href="' . $base . '&toggleBloc=' . $i . '" class="btn btn-sm ' . ($active ? 'btn-success' : 'btn-danger') . '">' . ($active ? $this->l('Actif') : $this->l('Inactif')) . '</a></td>';
                $html .= '<td><a href="' . $base . '&deleteBloc=' . $i . '" class="btn btn-sm btn-danger" onclick="return confirm(\'' . $this->l('Supprimer ce bloc ?') . '\')"><i class="icon-trash"></i></a></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }

        // Formulaire ajout
        $html .= '<form method="post" style="margin-top:20px;display:flex;gap:10px;align-items:center;">';
        $html .= '<input type="text" name="lsv_new_text" class="form-control" placeholder="' . $this->l('Nouveau texte...') . '" style="flex:1;" />';
        $html .= '<button type="submit" name="submitAddBloc" class="btn btn-primary"><i class="icon-plus"></i> ' . $this->l('Ajouter') . '</button>';
        $html .= '</form>';

        $html .= '</div>';

        // Aperçu live
        $active = self::getBlocs();
        if (!empty($active)) {
            $html .= '<div class="panel">';
            $html .= '<h3><i class="icon-eye"></i> ' . $this->l('Aperçu (rotation 10s)') . '</h3>';
            $html .= '<div style="background:#222;color:#fff;padding:30px;border-radius:6px;text-align:center;">';
            $html .= '<div style="color:#ee7a03;font-weight:700;font-size:16px;margin-bottom:8px;">Le saviez-vous ?</div>';
            $html .= '<div id="lsv-preview" style="font-size:14px;min-height:20px;transition:opacity .5s;">' . htmlspecialchars($active[0]['text']) . '</div>';
            $html .= '</div></div>';
            $html .= '<script>
            (function(){
                var blocs = ' . json_encode(array_column($active, 'text')) . ';
                var el = document.getElementById("lsv-preview");
                if (!el || blocs.length < 2) return;
                var idx = 0;
                setInterval(function(){
                    el.style.opacity = "0";
                    setTimeout(function(){
                        idx = (idx + 1) % blocs.length;
                        el.textContent = blocs[idx];
                        el.style.opacity = "1";
                    }, 500);
                }, 10000);
            })();
            </script>';
        }

        return $html;
    }
}
