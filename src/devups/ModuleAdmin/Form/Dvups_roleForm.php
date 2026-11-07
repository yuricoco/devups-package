<?php


use Genesis as g;

class Dvups_roleForm extends FormManager
{

    public $dvups_role;

    public static function init(\Dvups_role $dvups_role, $action = "")
    {
        $fb = new Dvups_roleForm($dvups_role, $action);
        $fb->dvups_role = $dvups_role;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['name'] = [
            "label" => t('dvups_role.name'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_role->name,
        ];

        $this->fields['alias'] = [
            "label" => t('dvups_role.alias'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_role->alias,
        ];


        return $this;

    }

    public static function renderWidget($dvups_role, $action = "create")
    {
        $data = compact("dvups_role", "action");
        $data["rights"] = Dvups_right::all();

        $data["configs"] = Core::buildOriginCore();
        $data["components"] = $data["dvups_role"]->getAttribute("components");
        $data["modules"] = $data["dvups_role"]->getAttribute("modules");
        $data["entities"] = $data["dvups_role"]->getAttribute("entities");

        return g::getView("admin.dvups_role.formWidgetGroup", $data);
        /*return [
            "success" => true,
            "form" => $form,
        ];*/
//        Genesis::renderView("dvups_role.formWidget", self::getFormData($id, $action));
    }

}
    