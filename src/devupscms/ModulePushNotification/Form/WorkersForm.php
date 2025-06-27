<?php 

        
use Genesis as g;

class WorkersForm extends FormManager{

    public $workers;

    public static function init(\Workers $workers, $action = ""){
        $fb = new WorkersForm($workers, $action);
        $fb->workers = $workers;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['queue'] = [
                "label" => t('workers.queue'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->workers->queue, 
        ];

            $this->fields['payload'] = [
                "label" => t('workers.payload'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->workers->payload, 
        ];

            $this->fields['type'] = [
                "label" => t('workers.type'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->workers->type, 
        ];

            $this->fields['callback'] = [
                "label" => t('workers.callback'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->workers->callback, 
        ];

            $this->fields['log'] = [
                "label" => t('workers.log'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->workers->log, 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("workers.formWidget", self::getFormData($id, $action));
    }
    
}
    