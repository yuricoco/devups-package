<?php


use Genesis as g;

class StatusForm extends FormManager
{

    public $status;

    public static function init(\Status $status, $action = "")
    {
        $fb = new StatusForm($status, $action);
        $fb->status = $status;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['entityid'] = [
            "label" => t('Entity'),
            "type" => FORMTYPE_SELECT,
            "value" => $this->status->entity->getId(),
            "options" => status_entities ?
                FormManager::Options_Helper("name", Dvups_entity::whereIn("this.name", status_entities)
                ->orderBy("this.name asc")->get()) : [],
        ];

        $this->fields['color'] = [
            "label" => t('status.color'),
            "type" => FORMTYPE_TEXT,
            "directive" => ["class"=> "form-control color_picker", "autocomplete"=>"off"],
            "value" => $this->status->color,
        ];

        $this->fields['_key'] = [
            "label" => t('status.key'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->status->_key,
        ];

        $this->fields['label'] = [
            "label" => t('status.label'),
            "type" => FORMTYPE_TEXT,
            "lang" => true,
            "value" => $this->status->label,
        ];

        $this->fields['position'] = [
            "label" => t('position'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->status->position,
        ];

        $this->addcss(__admin.'plugins/colorpicker/css/evol-colorpicker.min');
        $this->addjs(__admin.'plugins/colorpicker/js/evol-colorpicker.min');
        $this->addjs(Status::classpath('Resource/js/statusForm'));

        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("status.formWidget", self::getFormData($id, $action));
    }

}
