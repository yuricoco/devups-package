<?php


use Genesis as g;

class CmsresourceForm extends FormManager
{

    public $cmsresource;

    public static function init(\Cmsresource $cmsresource, $action = "")
    {
        $fb = new CmsresourceForm($cmsresource, $action);
        $fb->cmsresource = $cmsresource;
        return $fb;
    }

    public function buildForm()
    {

        $this->fields['cmscontent'] = [
            "label" => t('entity.cmscontent'),
            "type" => FORMTYPE_SELECT,
            "value" => $this->cmsresource->cmscontent->getId(),
            "options" => FormManager::Options_Helper("title", Cmscontent::allrows()),
        ];

        $this->fields['image'] = [
            "label" => t('image'),
            "type" => FORMTYPE_FILE,
            "filetype" => FILETYPE_IMAGE,
            "value" => $this->cmsresource->getImage(),
            "src" => $this->cmsresource->showImage(),
        ];

        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("admin.cmsresource.formWidget", self::getFormData($id, $action));
    }

}
