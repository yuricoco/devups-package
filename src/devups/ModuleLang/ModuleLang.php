<?php


namespace devups\ModuleLang;


use Dvups_module;
use Genesis as g;
use Request;

/**
 * Les traductions vivent desormais dans des fichiers (lang/<iso2>.php,
 * voir lang.php et README.md#Langues) : l'ancien editeur en base
 * (Local_content/Local_content_key/Local_content_lang/Page_mapped) a ete
 * retire (Phase 3, Langues - C.4/C.5/C.7). Il ne reste que la vue
 * d'ensemble (liste des langues actives, rappel des commandes CLI) et la
 * gestion CRUD generique de Dvups_lang (routee par Request::service(),
 * comme toute autre entite - voir CrudTrait).
 */
class ModuleLang
{

    public $moduledata;

    public function __construct()
    {

    }

    public function web()
    {

        $this->moduledata = Dvups_module::init('ModuleLang');

        (new Request('layout'));

        switch (Request::get('path')) {

            case 'layout':
                g::renderView("overview", ['langs' => \Dvups_lang::all()]);
                break;

            default:
                g::renderView('404', ['page' => Request::get('path')]);
                break;
        }
    }

    public static function services($path)
    {
        header("Access-Control-Allow-Origin: *");

        Request::service($path);
    }

}
