<?php


use compagnons\ModuleMember\Entity\Blacklist;
use Genesis as g;

class BlacklistForm extends FormManager{

    public $blacklist;

    public static function init(\compagnons\ModuleMember\Entity\Blacklist $blacklist, $action = ""){
        $fb = new \compagnons\ModuleMember\Form\BlacklistForm($blacklist, $action);
        $fb->blacklist = $blacklist;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['comment'] = [
                "label" => t('blacklist.comment'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->blacklist->comment, 
        ];

            $this->fields['subject'] = [
                "label" => t('blacklist.subject'), 
			"type" => FORMTYPE_SELECT, 
                "value" => $this->blacklist->subject, 
                "options" => Blacklist::$subjects, 
                
        ];

        $this->fields['user.id'] = [
            "type" => FORMTYPE_SELECT, 
            "value" => $this->blacklist->user->id,
            "label" => t('user'),
            "options" => FormManager::Options_Helper('firstname', User::allrows()),
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("blacklist.formWidget", self::getFormData($id, $action));
    }
    
}
    