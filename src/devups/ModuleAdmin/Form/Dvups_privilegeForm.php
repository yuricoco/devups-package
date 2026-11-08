<?php 

        
use Genesis as g;

class Dvups_privilegeForm extends FormManager{

    public $dvups_privilege;

    public static function init(\Dvups_privilege $dvups_privilege, $action = ""){
        $fb = new Dvups_privilegeForm($dvups_privilege, $action);
        $fb->dvups_privilege = $dvups_privilege;
        return $fb;
    }

    public function buildForm()
    {
    
        
        $this->fields['dvups_role.id'] = [
            "type" => FORMTYPE_SELECT, 
            "value" => $this->dvups_privilege->dvups_role->id,
            "label" => t('dvups_role'),
            "options" => FormManager::Options_Helper('name', Dvups_role::allrows()),
        ];

        $this->fields['dvups_action.id'] = [
            "type" => FORMTYPE_SELECT, 
            "value" => $this->dvups_privilege->dvups_action->id,
            "label" => t('dvups_action'),
            "options" => FormManager::Options_Helper('entity', Dvups_action::allrows()),
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("dvups_privilege.formWidget", self::getFormData($id, $action));
    }
    
}
    