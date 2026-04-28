<?php
/**
 * Override : injecte le contenu d'une page CMS dans la page 404.
 *
 * L'ID de la page CMS est lu depuis Configuration::get('PS_CMS_404_ID').
 * Si la CMS est absente ou désactivée, le rendu par défaut s'applique.
 */
class PageNotFoundController extends PageNotFoundControllerCore
{
    public function initContent()
    {
        parent::initContent();

        $idCms = (int) Configuration::get('PS_CMS_404_ID');
        if (!$idCms) {
            return;
        }

        $cms = new CMS($idCms, $this->context->language->id);
        if (!Validate::isLoadedObject($cms) || !$cms->active) {
            return;
        }

        $this->context->smarty->assign([
            'errorContent' => $cms->content,
            'cms_404_title' => $cms->meta_title,
            'cms_404' => [
                'id_cms' => (int) $cms->id,
                'meta_title' => $cms->meta_title,
                'content' => $cms->content,
            ],
        ]);
    }
}
