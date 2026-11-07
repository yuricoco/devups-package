<?php


use devupsfront\ModuleUser\Entity\Oauth_client;

class Oauth_clientForm extends FormManager
{

    public $oauth_client;

    public static function init(Oauth_client $oauth_client, $action = "")
    {
        $fb = new Oauth_clientForm($oauth_client, $action);
        $fb->oauth_client = $oauth_client;
        return $fb;
    }

    public function buildForm()
    {
        $this->fields['client'] = [
            "label" => t('oauth_client.client', 'Fournisseur (google, apple...)'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->oauth_client->client,
        ];

        $this->fields['name'] = [
            "label" => t('oauth_client.name', 'Nom'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->oauth_client->name,
        ];

        $this->fields['user_ext_id'] = [
            "label" => t('oauth_client.user_ext_id', 'Identifiant chez le fournisseur'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->oauth_client->user_ext_id,
        ];

        $this->fields['user_id'] = [
            "label" => t('oauth_client.user_id', 'Utilisateur'),
            "type" => FORMTYPE_NUMBER,
            "value" => $this->oauth_client->user_id,
        ];

        return $this;
    }

}
