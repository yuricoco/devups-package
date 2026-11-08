<?php

namespace admin\generator;

trait TableTemplateRender
{

    protected $btnsearch_class = "btn btn-primary";
    // table-bordered retire (striped+hover suffisent, les
    // verticales n'ajoutaient que du bruit) ; dataTable/no-footer retires -
    // mesure : 0 occurrence des deux dans main.css/dv_style.css/dv_theme.css,
    // ce sont des classes de la bibliotheque DataTables, qui n'est pas
    // chargee dans ce back-office - elles n'habillaient rien.
    protected $table_class = "table table-striped table-hover";

    // edit/show passent en neutre (btn-outline-secondary,
    // fourni par le theme) - seule la suppression garde une couleur saturee
    // (btn-danger) : une action destructive doit etre la seule chose rouge
    // d'une ligne.
    protected $btnedit_class = "btn btn-outline-secondary btn-sm";
    protected $btnview_class = "btn btn-outline-secondary btn-sm";
    protected $btndelete_class = "btn btn-danger btn-sm";
    protected $defaultgroupaction = "";
    // table-responsive pose ici, au bon endroit (a
    // l'interieur de la carte, dans templateTable() ci-dessous) : sans ce
    // wrapper, une table a colonnes larges elargit la carte puis la page
    // entiere sur mobile.
    protected $responsive = "table-responsive";

    // les labels et fa-close->fa-times sont corriges
    // dans Datatable::__construct() (dclass/devups/Datatable/Datatable.php),
    // pas ici : un default de propriete PHP doit etre une expression
    // constante, t() n'y est pas permis (fatal "Constant expression
    // contains invalid operations") - le createaction du constructeur de
    // Datatable.php passait deja par t('create') pour cette meme raison,
    // suivi ici pour defaultaction aussi.
    protected $defaultaction = [
        "edit" => [
            //'type' => 'btn',
            'content' => '<i class="fa fa-edit" ></i> edit',
            'class' => 'edit',
            'action' => '',
            'habit' => 'stateless',
            'modal' => 'data-toggle="modal" ',
        ],
        "show" => [
            //'type' => 'btn',
            'content' => '<i class="fa fa-eye" ></i> show',
            'class' => 'show',
            'action' => '',
            'habit' => 'stateless',
            'modal' => 'data-toggle="modal" ',
        ],
        "delete" => [
            //'type' => 'btn',
            'content' => '<i class="fa fa-close" ></i> delete',
            'class' => 'delete',
            'action' => '',
            'habit' => 'stateless',
            'modal' => 'data-toggle="modal" ',
        ],
    ];
    protected $createaction = [
        //'type' => 'btn',
        'content' => '<i class="fa fa-plus" ></i> create',
        'class' => 'btn btn-success',
        'action' => 'onclick="model._new(this)"',
        'habit' => 'stateless',
        'modal' => 'data-toggle="modal" ',
    ];

    public function renderDefaultGroupAction(){

        $this->defaultgroupaction = '<button id="deletegroup" onclick="ddatatable.groupdelete(this, \'' . $this->class . '\')" class="btn btn-danger btn-block">' . t('delete') . '</button>'
            . '<button data-entity="' . $this->class . '"  onclick="ddatatable._export(this, \'' . $this->class . '\')" type="button" class="btn btn-default btn-block" >
            <i class="fa fa-arrow-down"></i> ' . t('Export csv') . '
        </button>';

    }

    public function renderTopOptionAction($groupaction, $headaction){
        // col-lg-8/col-lg-4 retires : ce conteneur est
        // un flex (d-sm-flex), pas un .row - une colonne Bootstrap hors
        // .row porte un padding-left non compensable par la marge negative
        // habituelle du .row, ce qui decale son contenu. Mesure avant
        // correction : le bouton "Action group" etait a 20px du bord
        // gauche de la carte contre 15px pour la table juste en dessous
        // (5px d'ecart visible) - le flex deja present (justify-content-
        // between) suffit a repartir les deux blocs sans grille imbriquee.
        return <<<EOF
<div class="d-sm-flex justify-content-between align-items-start">


<div>
                                    $groupaction
                                 </div>
<div class="text-right">
                                    $headaction
                               </div>

                        </div>
EOF;
    }

    public function templateTable($lang, $per_page, $filterParam, $base_url, $class, $theader, $tbody, $newrows){
        return '<div class="' . $this->responsive . '">
        <table id="dv_table" ' . $lang . ' data-perpage="' . $per_page . '" data-filterparam="' . $filterParam . '" data-route="' . $base_url . '" data-entity="' . $class . '"  class="dv_datatable ' . $this->table_class . '" >'
            . '<thead>' . $theader['th'] . $theader['thf'] . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '<tfoot>' . $newrows . '</tfoot>'
            . '</table></div>';
    }

    private function perpagebuilder()
    {

        if (!$this->per_pageEnabled)
            return "";

        $html = '
            <div data-notice="' . $this->pagination . '" class="col-lg-3 col-md-12 ">

        <label class=" " >' . t("Line to show") . '</label><br>';

        $html .= '<select id="dt_nbrow" class="form-control" style="width:100px; display: inline-block" onchange="ddatatable.setperpage(this.options[this.selectedIndex].value)" >';
        //$html .= '<option value="&next=' . $current_page . '&per_page=10" >10</option>';

        // l'option correspondant a per_page n'est plus
        // ajoutee en plus (elle doublait une option deja produite par la
        // boucle quand per_page valait un multiple de 10, ex: 20
        // apparaissait deux fois) - elle est marquee "selected" si elle
        // existe deja dans la boucle, sinon ajoutee une seule fois apres.
        $matched = false;
        for ($i = 1; $i <= 10; $i++) {
            $value = $i * 10;
            if ($value == $this->per_page) {
                $matched = true;
                $html .= '<option selected value="' . $value . '" >' . $value . '</option>';
            } else {
                $html .= '<option value="' . $value . '" >' . $value . '</option>';
            }
        }
        if (!$matched) {
            $html .= '<option selected value="' . $this->per_page . '" >' . $this->per_page . '</option>';
        }
        $html .= '<option value="all" >' . t('All') . '</option>';
        $html .= " </select>
    </div>";

        return $html;
    }

    /**
     * ETAPE P.2 : $label est deja la chaine extraite (voir l'unique appel,
     * Datatable::getTableEntityBody() -> self::templateTableRowDetail(
     * $valuetd["label"], $td)) - PHP 7 masquait silencieusement le bug
     * ($valuetd["label"]["label"] degradait en "premier caractere d'une
     * chaine"), PHP 8 le leve en TypeError ("Cannot access offset of type
     * string on string"), ce qui cassait l'affichage detail (_show) de
     * TOUTE entite passant par ce chemin (jamais exerce par un test avant
     * LegacyServicesFormEndpointsTest::testDvupsRoleShowDoesNotFatal()).
     */
    public static function templateTableRowDetail($label, $td){
        return '<tr ><td> ' . $label . ' </td><td>' . $td . '</td></tr>';
    }

}
