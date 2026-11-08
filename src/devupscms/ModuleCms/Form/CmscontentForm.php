<?php


use Genesis as g;

class CmscontentForm extends FormManager
{

    public $cmscontent;

    public static function init(\Cmscontent $cmscontent, $action = "")
    {
        $fb = new CmscontentForm($cmscontent, $action);
        $fb->cmscontent = $cmscontent;
        return $fb;
    }

    public function buildContestForm()
    {
        
        $this->fields['reference'] = [
            "label" => t('cmscontent.reference'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->cmscontent->reference,
        ];

        return $this;
    }

    public function buildForm()
    {


        $this->fields['title'] = [
            "label" => t('Titre'),
            FH_REQUIRE => false,
            "lang" => true,
            "type" => FORMTYPE_TEXT,
            "value" => $this->cmscontent->title,
        ];

        $this->fields['reference'] = [
            "label" => t('cmscontent.reference'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->cmscontent->getReference(),
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("cmscontent.formWidget", self::getFormData($id, $action));
    }

}
    