<?php

class AdminTemplateGenerator extends DvAdmin
{

    private $traitement;

    function __construct()
    {
        $this->traitement = new Traitement();
    }

    // les deux methodes ci-dessous relisaient
    // chacune un tableau $actionclass en dur ('edit'=>'...btn-warning',
    // 'show'=>'...btn-info', 'delete'=>'...btn-danger') pour retraduire
    // $el["class"] - un mot-cle semantique ("edit"/"show"/"delete") pose
    // par Datatable.php. $el["class"] porte desormais directement la
    // vraie classe Bootstrap (voir Datatable::__construct(), ou elle
    // vient de $this->btnedit_class/btnview_class/btndelete_class, donc
    // d'une seule source) - plus besoin de retraduire quoi que ce soit ici,
    // $el["class"] s'utilise tel quel.
    static function dt_btn_action_builder($rowaction, $customrowactions)
    {
        $act = '';
        foreach ($customrowactions as $el) {
            $act .= '' . $el . '';
        }
        foreach ($rowaction as $el) {
            if (is_array($el)) {

                $act .= '<button type="button" class="' . $el["class"] . '" ' . $el["action"] . ' >' . $el["content"] . '</button>';

            } else {
                $act .= $el;
            }
        }

        return <<<EOF
$act
EOF;
    }

    static function dt_btn_action($rowaction, $customrowactions, $actionDropdown, $mainaction)
    {

        $act = "";

        if (!$actionDropdown) {

            foreach ($customrowactions as $el) {
                $act .= '' . $el . '';
            }
            foreach ($rowaction as $el) {
                if (is_array($el)) {

                    // mb-2 mr-2 preserves tels
                    // quels (c'etait deja l'espacement de l'ancien tableau
                    // $actionclass en dur) - seule la couleur change de
                    // source.
                    $act .= '<button type="button" class="' . $el["class"] . ' mb-2 mr-2" ' . $el["action"] . ' >' . $el["content"] . '</button>';

                } else {
                    $act .= $el;
                }
            }

            return <<<EOF
$act
EOF;
        }

        foreach ($customrowactions as $el) {
            $act .= '<li class="nav-item">' . $el . '</li>';
        }
        foreach ($rowaction as $el) {

            if (is_array($el)) {

                $act .= '<li class="nav-item"><button type="button" class="' . $el["class"] . ' mb-2 mr-2 btn-block" ' . $el["action"] . ' >' . $el["content"] . '</button></li>';

            } else {
                $act .= $el;
            }
        }

        if (is_array($mainaction)) {

            // mainaction["class"] (deja la
            // vraie classe Bootstrap, voir Datatable::__construct()) prend
            // le relais du "btn btn-warning" fige en dur - le bouton
            // principal de chaque ligne (le plus repete du back-office)
            // n'est plus orange par cablage.
            $mainaction = '<button type="button" class="' . $mainaction["class"] . '" ' . $mainaction["action"] . ' >' . $mainaction["content"] . '</button>';

            }
        // le menu ci-dessous portait
        // style="...transform: translate3d(-131px, 33px, 0px)" - retire.
        // Mesure avant de trancher, aux deux largeurs, sur une ligne du
        // haut ET du bas, par clic reel (pas suppose) : le style en dur
        // se faisait deja ecraser par le JS du dropdown Bootstrap des
        // l'ouverture reelle (computed transform mesure "none" juste
        // apres le clic, jamais la valeur figee) - et SA PRESENCE causait
        // un leger debordement a droite (+9px hors viewport a 1440px, sur
        // la premiere ligne) que son retrait fait disparaitre (teste en
        // supprimant l'attribut avant le clic : menu entierement dans le
        // viewport). A 390px, aucune difference mesuree avec ou sans.
        // Retrait verifie strictement benefique ou neutre a chaque mesure,
        // jamais nuisible - Popper.js n'est pas charge dans ce
        // back-office, le
        // positionnement automatique vient de la classe Bootstrap pure
        // .dropdown-menu-right, pas de Popper.
        return <<<EOF
<div class="btn-group d-inline-block dropdown">
    $mainaction
    <div class="btn-group" role="group">
    <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" class="btn-shadow dropdown-toggle btn btn-light">
        <i class="fa fa-angle-down"></i>
    </button>
    <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">
        <ul class="nav flex-column"> $act </ul>
    </div>
    </div>
</div>
EOF;

    }

    static function optionImport($entityname)
    {
        return '

<div class="btn-group d-inline-block dropdown" role="group">
    <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" class="btn-shadow dropdown-toggle btn btn-light">
        options 
    </button>
    <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-131px, 33px, 0px);">
      
        <button data-entity="' . $entityname . '" type="button" onclick="ddatatable._import(this, \'' . $entityname . '\')" class="  btn btn-default btn-block" >
            <i class="fa fa-arrow-up"></i> Import csv
        </button>
    </div>
  </div>
           ';
    }

    static function modulelinkGenerator($projet)
    {
        return "";
    }

    static function layoutGenerator($module, $_dir)
    {

        $layout = "@extends('layout.layout')
@section('title', 'Page Title')

<?php function style(){ ?>

<?php foreach (dclass\devups\Controller\Controller::$" . "cssfiles as $" . "cssfile){ ?>
<link href=\"<?= $" . "cssfile ?>\" rel=\"stylesheet\">
<?php } ?>

<?php } ?>

@section('content')
 
    @include(\"default.moduleheaderwidget\")
    <hr>

    @yield('layout_content')


        @endsection
        
<?php function script(){ ?>

<script src=\"<?= CLASSJS ?>devups.js\"></script>
<script src=\"<?= CLASSJS ?>model.js\"></script>
<script src=\"<?= CLASSJS ?>ddatatable.js\"></script>
<?php foreach (dclass\devups\Controller\Controller::$" . "jsfiles as $" . "jsfile){ ?>
<script src=\"<?= $" . "jsfile ?>\"></script>
<?php } ?>

<?php } ?>

	";

        $layoutMod = fopen($_dir . 'layout.blade.php', 'w');

        fputs($layoutMod, $layout);

        fclose($layoutMod);

        $overview = " @extends('admin.layout')
            @section('title', 'List')
            
            @section('layout_content')
                <div class=\"row\">
                    @foreach(\$moduledata->dvups_entity as \$entity)
                        @include(\"default.entitywidget\")
                    @endforeach
                </div>
            @endsection 
            ";

        $overviewMod = fopen($_dir . 'overview.blade.php', 'w');

        fputs($overviewMod, $overview);

        fclose($overviewMod);

    }

    static function viewsGenerator($listemodule, $entity, $_dir)
    {

        $name = strtolower($entity->name);

        $index = self::buildindexdatatable($listemodule, $entity);

        if (!file_exists($_dir))
            mkdir($_dir, 0777, true);

        //---------------------------------- $head.
        $layout = "
@extends('admin.layout')
@section('title', 'List')

@section('layout_content')

<div class=\"row\">
        <div class=\"col-lg-12 col-md-12  stretch-card\">
            <div class=\"card\">
                <div class=\"card-header-tab card-header\">
                    <div class=\"card-header-title\">
                        <i class=\"header-icon lnr-rocket icon-gradient bg-tempting-azure\"> </i>
                        {{ $" . "title }}
                    </div>
                    <div class=\"btn-actions-pane-right\">
                        <div class=\"nav\">

                        </div>
                    </div>
                </div>
                <div class=\"card-body\">
                    {!! $" . "datatablehtml; !!}
                </div>
            </div>
        </div>
    </div>
        
@endsection

";

        $view = fopen($_dir . 'index.blade.php', 'w');
        fputs($view, $layout);
        fclose($view);

        //---------------------------------- $head.
        $layout = "
@extends('admin.layout')
@section('title', 'List')

@section('layout_content')

<div class=\"row\">
        <div class=\"col-lg-12 col-md-12  stretch-card\">
            <div class=\"card\">
                <div class=\"card-header-tab card-header\">
                    <div class=\"card-header-title\">
                        <i class=\"header-icon lnr-rocket icon-gradient bg-tempting-azure\"> </i>
                        {{ $" . "title }}
                    </div>
                    <div class=\"btn-actions-pane-right\">
                        <div class=\"nav\">

                        </div>
                    </div>
                </div>
                <div class=\"card-body\">
                    {!! $" . "datatablehtml; !!}
                </div>
            </div>
        </div>
    </div>
        
@endsection

";

        $view = fopen($_dir . 'detail.blade.php', 'w');
        fputs($view, $layout);
        fclose($view);

        //----------------------------------

    }

    static function dashboardView(){
        $admin = getadmin();
        $modules = []; //$admin->dvups_role->collectDvups_module();

        return compact("admin", "modules");
    }

}
