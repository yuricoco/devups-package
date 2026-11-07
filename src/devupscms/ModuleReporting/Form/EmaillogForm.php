<?php

use Genesis as g;

/**
 * Non exposee par EmaillogController (ecran lecture seule) : conservee
 * pour la convention du generateur (une Form par entite), au cas ou un
 * widget de detail personnalise en aurait besoin plus tard.
 */
class EmaillogForm extends FormManager{

    public $emaillog;

    public static function init(\Emaillog $emaillog, $action = ""){
        $fb = new EmaillogForm($emaillog, $action);
        $fb->emaillog = $emaillog;
        return $fb;
    }

    public function buildForm()
    {
        $this->fields['destinataire'] = [
            "label" => t('Destinataire'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->emaillog->getDestinataire(),
        ];

        $this->fields['sujet'] = [
            "label" => t('Sujet'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->emaillog->getSujet(),
        ];

        $this->fields['modele'] = [
            "label" => t('Modele'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->emaillog->getModele(),
        ];

        $this->fields['statut'] = [
            "label" => t('Statut'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->emaillog->getStatut(),
        ];

        $this->fields['erreur'] = [
            "label" => t('Erreur'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->emaillog->getErreur(),
        ];

        return $this;
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("emaillog.formWidget", self::getFormData($id, $action));
    }

}
