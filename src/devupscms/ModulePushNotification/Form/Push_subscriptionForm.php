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
            "label" => t('push_subscription.subscription_type', 'Type (fcm, expo, webpush)'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->subscription_type,
        ];

        $this->fields['user_id'] = [
            "label" => t('push_subscription.user_id', 'Utilisateur'),
            "type" => FORMTYPE_NUMBER,
            "value" => $this->push_subscription->user_id,
        ];

        $this->fields['status'] = [
            "label" => t('push_subscription.status', 'Statut'),
            "type" => FORMTYPE_RADIO,
            "options" => ['Desactive', 'Active'],
            "value" => $this->push_subscription->status,
        ];

        $this->fields['auth_token'] = [
            "label" => t('push_subscription.auth_token', 'Jeton'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->auth_token,
        ];

        $this->fields['public_key'] = [
            "label" => t('push_subscription.public_key', 'Cle publique'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->public_key,
        ];

        $this->fields['content_type'] = [
            "label" => t('push_subscription.content_type', 'Content-Type'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_subscription->content_type,
        ];

        return $this;
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("push_subscription.formWidget", self::getFormData($id, $action));
    }
    
}
    