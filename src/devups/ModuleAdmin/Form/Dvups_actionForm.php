<?php 

        
use Genesis as g;

class Dvups_actionForm extends FormManager{

    public $dvups_action;

    public static function init(\Dvups_action $dvups_action, $action = ""){
        $fb = new Dvups_actionForm($dvups_action, $action);
        $fb->dvups_action = $dvups_action;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['entity'] = [
                "label" => t('dvups_action.entity'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_action->entity, 
        ];

            $this->fields['action'] = [
                "label" => t('dvups_action.action'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_action->action, 
        ];

            $this->fields['label'] = [
                "label" => t('dvups_action.label'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_action->label, 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("dvups_action.formWidget", self::getFormData($id, $action));
    }
    
}
    