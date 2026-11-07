<?php 

        
use Genesis as g;

class IntervalForm extends FormManager{

    public $interval;

    public static function init(\Interval $interval, $action = ""){
        $fb = new IntervalForm($interval, $action);
        $fb->interval = $interval;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['_min'] = [
                "label" => t('interval._min'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->interval->_min, 
        ];

            $this->fields['_max'] = [
                "label" => t('interval._max'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->interval->_max, 
        ];

            $this->fields['label'] = [
                "label" => t('interval.label'), 
			FH_REQUIRE => false,
 "type" => FORMTYPE_TEXT,
            "value" => $this->interval->label, 
        ];

            $this->fields['_value'] = [
                "label" => t('interval._value'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->interval->_value, 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("interval.formWidget", self::getFormData($id, $action));
    }
    
}
    