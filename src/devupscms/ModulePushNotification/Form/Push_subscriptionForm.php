<?php 

        
use Genesis as g;

class Push_subscriptionForm extends FormManager{

    public $push_subscription;

    public static function init(\Push_subscription $push_subscription, $action = ""){
        $fb = new Push_subscriptionForm($push_subscription, $action);
        $fb->push_subscription = $push_subscription;
        return $fb;
    }

    public function buildForm()
    {
    
        
            $this->fields['subscription_type'] = [
                "label" => t('push_subscription.subscription_type'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getSubscription_type(), 
        ];

            $this->fields['subscription_id'] = [
                "label" => t('push_subscription.subscription_id'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getSubscription_id(), 
        ];

            $this->fields['endpoint'] = [
                "label" => t('push_subscription.endpoint'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getEndpoint(), 
        ];

            $this->fields['public_key'] = [
                "label" => t('push_subscription.public_key'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getPublic_key(), 
        ];

            $this->fields['auth_token'] = [
                "label" => t('push_subscription.auth_token'), 
"type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getAuth_token(), 
        ];

            $this->fields['content_type'] = [
                "label" => t('push_subscription.content_type'), 
			FH_REQUIRE => false,
 "type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->getContent_type(), 
        ];

           
        return  $this;
    
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("push_subscription.formWidget", self::getFormData($id, $action));
    }
    
}
    