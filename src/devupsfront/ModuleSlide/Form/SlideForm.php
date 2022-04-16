<?php


use Genesis as g;

class SlideForm extends FormManager
{

    public $slide;

    public static function init(\Slide $slide, $action = "")
    {
        $fb = new SlideForm($slide, $action);
        $fb->slide = $slide;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['activated'] = [
            "label" => t('slide.activated'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_RADIO,
            "options" => ["unactive", "activate"],
            "value" => $this->slide->getActivated(),
        ];

        $this->fields['redirect'] = [
            "label" => t('slide.targeturl'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->slide->getRedirect(),
        ];

        $this->fields['image'] = [
            "type" => FORMTYPE_FILE,
            FH_REQUIRE => true,
            "label" => t('entity.dv_image'),
            "value" => "",
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("slide.formWidget", self::getFormData($id, $action));
    }

}
    