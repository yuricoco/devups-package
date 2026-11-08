<?php


use Genesis as g;

class CmscommentForm extends FormManager
{

    public $cmscomment;

    public static function init(\Cmscomment $cmscomment, $action = "")
    {
        $fb = new CmscommentForm($cmscomment, $action);
        $fb->cmscomment = $cmscomment;
        return $fb;
    }

    public function buildForm()
    {

        $this->fields['pseudo'] = [
            "label" => t('pseudo'),
            FH_REQUIRE => true,
            "type" => FORMTYPE_TEXT,
            "value" => $this->cmscomment->getPseudo(),
        ];

        $this->fields['email'] = [
            "label" => t('email'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->cmscomment->getEmail(),
        ];

        $this->fields['comment'] = [
            "label" => t('comment'),
            FH_REQUIRE => true,
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->cmscomment->getComment(),
        ];

        $this->fields['seen'] = [
            "label" => t('Statut'),
            "type" => FORMTYPE_RADIO,
            "options" => [0 => t('non'), 1 => t('oui')],
            "value" => $this->cmscomment->getSeen() ? 1 : 0,
        ];

        return $this;

    }

}
