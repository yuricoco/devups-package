<?php


use Genesis as g;

class ReportingmodelForm extends FormManager
{

    public $emailmodel;

    public static function init(\Reportingmodel $emailmodel, $action = "")
    {
        $fb = new ReportingmodelForm($emailmodel, $action);
        $fb->emailmodel = $emailmodel;
        return $fb;
    }

    public function buildForm()
    {
        $dir = ROOT . "src/devupscms/ModuleReporting/Resource/views/models/";
        $files = is_dir($dir) ? array_values(array_diff(scandir($dir), ['.', '..'])) : [];
        if (!$this->emailmodel->type && $files)
            $this->emailmodel->type = explode(".", $files[0])[0];

        $this->fields['type'] = [
            "label" => t('Type of reporting '),
            "type" => FORMTYPE_RADIO,
            "options" => FormManager::key_as_value(array_values($files)),
            "value" => $this->emailmodel->getType().".blade.php",
        ];

        $this->fields['name'] = [
            "label" => t('Email model name'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->emailmodel->getName(),
        ];

        $this->fields['title'] = [
            "label" => t('Title of the mail'),
            "type" => FORMTYPE_TEXT,
            "lang" => true,
            "value" => $this->emailmodel->getTitle(),
        ];

        $this->fields['object'] = [
            "label" => t('Object of the mail'),
            "type" => FORMTYPE_TEXT,
            "lang" => true,
            "value" => $this->emailmodel->getObject(),
        ];
        $this->fields['description'] = [
            "label" => t('Description of the mail'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->emailmodel->description,
        ];
        $this->fields['contenttext'] = [
            "label" => t('Contenu text'),
            "type" => FORMTYPE_TEXTAREA,
            "lang" => true,
            "value" => $this->emailmodel->contenttext,
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("emailmodel.formWidget", self::getFormData($id, $action));
    }

}
    