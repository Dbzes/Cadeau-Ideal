<?php

class CMS extends CMSCore
{
    public function __construct($id_cms = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_cms, $id_lang, $id_shop);

        // Toute nouvelle page CMS doit etre indexable par defaut (SEO)
        if (!$id_cms) {
            $this->indexation = true;
        }
    }
}
