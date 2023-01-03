<?php

namespace admin\generator;

trait TableTemplateRender
{

    protected $btnsearch_class = "btn btn-primary";
    protected $table_class = "table table-bordered table-striped table-hover dataTable no-footer";

    protected $btnedit_class = "btn btn-warning btn-sm";
    protected $btnview_class = "btn btn-info btn-sm";
    protected $btndelete_class = "btn btn-danger btn-sm";
    protected $defaultgroupaction = "";
    protected $responsive = "";

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

        $this->defaultgroupaction = '<button id="deletegroup" onclick="ddatatable.groupdelete(this, \'' . $this->class . '\')" class="btn btn-danger btn-block">delete</button>'
            . '<button data-entity="' . $this->class . '"  onclick="ddatatable._export(this, \'' . $this->class . '\')" type="button" class="btn btn-default btn-block" >
            <i class="fa fa-arrow-down"></i> Export csv
        </button>';

    }

    public function renderTopOptionAction($groupaction, $headaction){
        return <<<EOF
<div class="d-sm-flex justify-content-between align-items-start">
                                
                                
<div class="col-lg-8 col-md-12">
                                    $groupaction
                                 </div>
<div class="col-lg-4 col-md-12 text-right">
                                    $headaction
                               </div>
                             
                        </div> 
EOF;
    }

    public function templateTable($lang, $per_page, $filterParam, $base_url, $class, $theader, $tbody, $newrows){
        return '<div class="  ' . $this->responsive . '">
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

        for ($i = 1; $i <= 10; $i++) {
            $html .= '<option value="' . $i * 10 . '" >' . $i * 10 . '</option>';
        }
        $html .= '<option selected value="' . $this->per_page . '" >' . $this->per_page . '</option>';
        $html .= '<option value="all" >All</option>';
        $html .= " </select>
    </div>";

        return $html;
    }

    public static function templateTableRowDetail($valuetd, $td){
        return '<tr ><td> ' . $valuetd["label"] . ' </td><td>' . $td . '</td></tr>';
    }

}
