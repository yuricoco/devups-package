<?php

namespace devupscms\ModuleNotification\Form;

use \Genesis as g;

class NotificationForm extends \FormManager
{

    public $notification;

    public static function init(\Notification $notification, $action = "")
    {
        $fb = new NotificationForm($notification, $action);
        $fb->notification = $notification;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['entity'] = [
            "label" => t('notification.entity'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->notification->getEntity(),
        ];

        $this->fields['entityid'] = [
            "label" => t('notification.entityid'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->notification->getEntityid(),
        ];

        $this->fields['content'] = [
            "label" => t('notification.content'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->notification->getContent(),
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        \Genesis::renderView("notification.formWidget", self::getFormData($id, $action));
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(NotificationForm::class, 'NotificationForm');
    