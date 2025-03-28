<?php 

        
use Genesis as g;

class Dvups_logForm extends FormManager{

    public $dvups_log;

    public static function init(\Dvups_log $dvups_log, $action = ""){
        $fb = new Dvups_logForm($dvups_log, $action);
        $fb->dvups_log = $dvups_log;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['object'] = [
                "label" => t('dvups_log.object'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->dvups_log->object, 
        ];

            $this->fields['log'] = [
                "label" => t('dvups_log.log'), 
			FH_REQUIRE => false,
 "type" => FORMTYPE_TEXT,
            "value" => $this->dvups_log->log, 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("dvups_log.formWidget", self::getFormData($id, $action));
    }
    
}
    