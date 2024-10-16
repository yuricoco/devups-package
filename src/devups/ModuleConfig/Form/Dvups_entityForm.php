<?php


use Genesis as g;

class Dvups_entityForm extends FormManager{

    public $dvups_entity;

    public static function init(\Dvups_entity $dvups_entity, $action = ""){
        $fb = new Dvups_entityForm($dvups_entity, $action);
        $fb->dvups_entity = $dvups_entity;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['name'] = [
            "label" => t('dvups_entity.name'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_entity->getName(),
        ];

        $this->fields['url'] = [
            "label" => t('URL'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_entity->url,
        ];

        $this->fields['label'] = [
            "label" => t('dvups_entity.label'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_entity->getLabel(),
            "lang" => true,

        ];

        $this->fields['dvups_module.id'] = [
            "type" => FORMTYPE_SELECT,
            "value" => $this->dvups_entity->getDvups_module()->getId(),
            "label" => t('dvups_module'),
            "options" => FormManager::Options_Helper('name', Dvups_module::allrows()),
        ];

        $this->fields['dvups_right::values'] = [
            "type" => FORMTYPE_CHECKBOX,
            "values" => $this->dvups_entity->inCollectionOf('Dvups_right'),
            "label" => t('dvups_right'),
            "options" => FormManager::Options_Helper('name', Dvups_right::allrows()),
        ];


        return  $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("dvups_entity.formWidget", self::getFormData($id, $action));
    }
    public static function renderExportWidget($id = null, $action = "create")
    {
        $classname = strtolower(Request::$uri_get_param["entity"]);
        $entity = (ucfirst($classname));
        $entity = new $entity;
        $fields = [];

        $attributes = Dvups_entity::describe($classname);
        foreach ($attributes as $attribute)
            $fields['this.'.$attribute[0]] = $attribute[0];
        foreach ($entity->dvtranslated_columns as $col)
            $fields[$classname.'_lang.'.$col] = $col;
//            $fields[] = Request::$uri_get_param["entity"]."_lang.".$col;

        $langs = Dvups_lang::allrows();
        return Genesis::getView("admin.dvups_entity.formExportWidget", Request::$uri_get_param + Request::$uri_post_param
            + ["langs" => $langs, 'fields' => $fields]); // +['entityCore'=>$entityCore]
    }

}
