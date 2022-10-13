<?php


use Genesis as g;

class Push_emailForm extends FormManager
{

    public $push_email;

    public static function init(\Push_email $push_email, $action = "")
    {
        $fb = new Push_emailForm($push_email, $action);
        $fb->push_email = $push_email;
        return $fb;
    }

    public function buildForm()
    {

        $this->fields['notificationtype.id'] = [
            "type" => FORMTYPE_SELECT,
            "value" => $this->push_email->notificationtype->id,
            "label" => t('notificationtype'),
            "options" => FormManager::Options_Helper('_key', Notificationtype::where("dvups_entity.name", "user")
                ->where("emailmodel", "!=", "")->get()),
        ];
        $this->fields['status'] = [
            "type" => FORMTYPE_RADIO,
            "value" => $this->push_email->status,
            "label" => t('Status'),
            "options" => ['Desactive', 'Active'],
        ];

        /*$this->fields['reportingmodel.id'] = [
            "type" => FORMTYPE_SELECT,
            "value" => $this->push_email->constraint->getId(),
            "label" => t('Contraint'),
            "options" => FormManager::Options_Helper('name', Repo::getmainmenu("constraint", 1)),
        ];*/

        $this->fields['date_start'] = [
            "label" => t('Date de debut'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_DATE,
            "value" => $this->push_email->date_start,
        ];
        $this->fields['date_end'] = [
            "label" => t('push_email.date_end'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_DATE,
            "value" => $this->push_email->getDate_end(),
        ];
        $this->fields['last_call'] = [
            "label" => t('Last call'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_DATE,
            "value" => $this->push_email->last_call,
        ];
        $this->fields['next_call'] = [
            "label" => t('next_call'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_DATE,
            "value" => $this->push_email->next_call,
        ];

        $this->fields['reference'] = [
            "label" => "reference",
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_email->reference,
        ];
        $this->fields['description'] = [
            "label" => "Description",
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->push_email->description,
        ];
        $this->fields['constraint'] = [
            "label" => "constraint SQL",
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->push_email->constraint,
        ];
        $this->fields['interval'] = [
            "label" => t('interval (J)'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->push_email->getInterval(),
        ];


        return $this;

    }


}
