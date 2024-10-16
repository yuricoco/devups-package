<?php


use Genesis as g;

class UserForm extends FormManager
{

    public $user;

    public static function init(\User $user, $action = "")
    {
        $fb = new UserForm($user, $action);
        $fb->user = $user;
        return $fb;
    }

    public function buildForm()
    {


        $this->fields['dvups_role\\role.id'] = [
            "label" => t('Role'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_SELECT,
            "options" => FormManager::Options_Helper("name", Dvups_role::all("name")),
            "value" => $this->user->role->id,
        ];

        $this->fields['firstname'] = [
            "label" => t('user.firstname'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->getFirstname(),
        ];

        $this->fields['lastname'] = [
            "label" => t('user.lastname'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->getLastname(),
        ];

        $this->fields['email'] = [
            "label" => t('user.email'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->email,
        ];


        $this->fields['phonenumber'] = [
            "label" => t('user.phonenumber'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->phonenumber,
        ];

        $this->fields['resetpassword'] = [
            "label" => t('user.password'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => "",
        ];

        $this->fields['is_activated'] = [
            "label" => t('user.is_activated'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_RADIO,
            "options" => ["Desactivate", "Activate"],
            "value" => $this->user->is_activated,
        ];
       /* $this->fields['lang'] = [
            "label" => t('user.lang'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->getLang(),
        ];*/

        $this->fields['username'] = [
            "label" => t('user.username'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->user->getUsername(),
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("user.formWidget", self::getFormData($id, $action));
    }

}
    