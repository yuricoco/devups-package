<?php 

        
use Genesis as g;

class ConfigurationForm extends FormManager{

    public $configuration;

    public static function init(\Configuration $configuration, $action = ""){
        $fb = new ConfigurationForm($configuration, $action);
        $fb->configuration = $configuration;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['_key'] = [
                "label" => t('configuration._key'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->configuration->_key, 
        ];

            $this->fields['_value'] = [
                "label" => t('configuration._value'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->configuration->_value, 
        ];

            $this->fields['_type'] = [
                "label" => t('configuration._type'), 
			FH_REQUIRE => false,
            "type" => FORMTYPE_RADIO,
            "options" => FormManager::key_as_value(["string", "bool", "integer", "float"]),
            "value" => $this->configuration->_type, 
        ];

        $this->fields['comment'] = [
            "label" => t('comment'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->configuration->getComment(),
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("configuration.formWidget", self::getFormData($id, $action));
    }
    
}
    