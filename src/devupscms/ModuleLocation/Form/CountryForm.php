<?php

namespace devupscms\ModuleLocation\Form;

use \Genesis as g;

class CountryForm extends \FormManager
{

    public $country;

    public static function init(\Country $country, $action = "")
    {
        $fb = new CountryForm($country, $action);
        $fb->country = $country;
        return $fb;
    }

    public function buildForm()
    {



        $this->fields['name'] = [
            "label" => t('country.name'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->country->getName(),
        ];
        $this->fields['nicename'] = [
            "label" => t('Nom commun'),
            "type" => FORMTYPE_TEXT,
            "lang" => true,
            "value" => $this->country->nicename,
        ];

        $this->fields['iso'] = [
            "label" => t('country.iso'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->country->getIso(),
        ];

        $this->fields['phonecode'] = [
            "label" => t('country.phonecode'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->country->getPhonecode(),
        ];

        $this->fields['status'] = [
            "label" => t('country.status'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->country->getStatus(),
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        \Genesis::renderView("country.formWidget", self::getFormData($id, $action));
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(CountryForm::class, 'CountryForm');
    