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
    
        
            $this->fields['fingerprint'] = [
                "label" => t('dvups_log.fingerprint'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->fingerprint, 
        ];

            $this->fields['level'] = [
                "label" => t('dvups_log.level'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->level, 
        ];

            $this->fields['entity_class'] = [
                "label" => t('dvups_log.entity_class'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->entity_class, 
        ];

            $this->fields['file'] = [
                "label" => t('dvups_log.file'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->file, 
        ];

            $this->fields['line'] = [
                "label" => t('dvups_log.line'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->line, 
        ];

            $this->fields['message'] = [
                "label" => t('dvups_log.message'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->message, 
        ];

            $this->fields['occurrences'] = [
                "label" => t('dvups_log.occurrences'), 
			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->occurrences, 
        ];

            $this->fields['first_at'] = [
                "label" => t('dvups_log.first_at'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->first_at, 
        ];

            $this->fields['last_at'] = [
                "label" => t('dvups_log.last_at'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->last_at, 
        ];

            $this->fields['context'] = [
                "label" => t('dvups_log.context'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->context, 
        ];

            $this->fields['log_file'] = [
                "label" => t('dvups_log.log_file'), 
			FH_REQUIRE => false,
 			"type" => FORMTYPE_TEXT, 
                "value" => $this->dvups_log->log_file, 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("dvups_log.formWidget", self::getFormData($id, $action));
    }
    
}
    