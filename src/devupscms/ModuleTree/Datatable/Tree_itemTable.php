<?php


use dclass\devups\Datatable\Datatable as Datatable;

class Tree_itemTable extends Datatable
{


    public function __construct($tree_item = null, $datatablemodel = [])
    {
        parent::__construct($tree_item, $datatablemodel);
    }

    public static function init(\Tree_item $tree_item = null)
    {

        $dt = new Tree_itemTable($tree_item);
        $dt->entity = $tree_item;

        return $dt;
    }

    public function buildteststable()
    {

        /*
        $idcomic = Request::get("comicbook_id", $idcomic);
                $this->qbcustom->where("tree.name", "gender")
                    ->addColumns(" ( select count(*) from comicbook_gender where comicbook_id = $idcomic AND gender_id = this.id ) AS has_gender ");
        */
        //if ($idexp = Request::get("exploitation_id", $idexp))
        /*$this->qbcustom->leftjoinrecto("comicbook_member", "category", "cm")
            ->where("cm.comicbook_id", $idcomic)
            ->where_str("ec.category_id = this.category_id", "and");*/

        $this->groupaction = false;
        $this->base_url = __env . "admin/";
        $this->responsive = " table-responsive";
        $this->enablefilter();
        $this->per_page = 25;

        $this->datatablemodel = [
            //'id' => ['header' => t('member.id', '#') ],
            'name' => ['header' => "Genre"],
        ];

        $this->enabletopaction = false;
        $this->actionDropdown = false;
        $this->disableDefaultaction();

        return $this;
    }

    public function builddashboardtable()
    {

//        $this->enabletopaction = false;
//        $this->actionDropdown = false;
        $this->groupaction = false;
        $this->base_url = __env . "admin/";
        $this->responsive = " table-responsive";
        //$this->disableDefaultaction();
        $this->datatablemodel = [
            'id' => ['header' => t('tree_item.id', '#'),],
            'name' => ['header' => t('tree_item.name', 'Name'),],
            'slug' => ['header' => t('Ref'),],
        ];

       /* $this->addcustomaction(function ($item) {
            $url = Product::classview('product/list?dfilters=on&category.id:eq='.$item->id);
            return "<a class='btn btn-outline-info' href='$url'>List des services</a";
        });*/

        return $this;
    }

    public function buildtree3table()
    {

        $this->datatablemodel = [
            ['header' => t('tree_item.id', '#'), 'value' => 'id'],
            ['header' => t('tree_item.name', 'Name'), 'value' => 'name'],
            ['header' => t('Content'), 'value' => 'content'],
        ];

        return $this;
    }

    public function buildindextable()
    {

        $this->base_url = __env . "admin/";
        if (!Request::get("parent_id:eq") && !is_null($this->qbcustom))
            $this->qbcustom->where("main", 1);

        $this->datatablemodel = [
            'id' => ['header' => t('tree_item.id', '#'),],
            'name' => ['header' => t('tree_item.name', 'Name'),],
            //['header' => t('Content'), 'value' => 'content'],
            'parent_id' => ['header' => t('tree_item.parent_id', 'Parent_id'),],
            'main' => ['header' => t('tree_item.main', 'Main'),],
            'position' => ['header' => t('Position'),],
            'chain' => ['header' => t('tree_item.hierarchy', 'Hierarchy'),]
        ];

        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('tree_item.name'), 'value' => 'name'],
            ['label' => t('tree_item.description'), 'value' => 'description'],
            ['label' => t('tree_item.parent_id'), 'value' => 'parent_id'],
            ['label' => t('tree_item.main'), 'value' => 'main'],
            ['label' => t('tree_item.hierarchy'), 'value' => 'hierarchy']
        ];
        // TODO: overwrite datatable attribute for this view
        return $this;
    }

    public function router()
    {
        $tablemodel = Request::get("tablemodel", null);
        if ($tablemodel && method_exists($this, "build" . $tablemodel . "table") && $result = call_user_func(array($this, "build" . $tablemodel . "table"))) {
            return $result;
        } else
            switch ($tablemodel) {
                // case "": return this->
                default:
                    return $this->buildindextable();
            }

    }

}
