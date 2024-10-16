<?php


namespace devupscms\ModuleTree;

use dclass\devups\Controller\Controller;
use dclass\devups\Controller\ModuleController;
use Dv_image;
use Genesis as g;
use Request as R;
use Dvups_module;
use Genesis;
use Request;
use Tree;
use Tree_item;
use Tree_item_imageController;
use Tree_itemController;
use Tree_itemTable;
use TreeController;

class ModuleTree extends ModuleController
{

    public function __construct($route)
    {

        parent::__construct($route);
    }

    public function layoutView()
    {
        Controller::$jsfiles[] = __admin.'plugins/vue.min.js';
        Controller::$cssfiles[] = Dv_image::classpath('Resource/css/image.css');
        Controller::$jsfiles[] = Tree::classpath('Resource/js/treeForm.js');
        Controller::$jsfiles[] = Tree::classpath('Resource/js/tree_item_imageForm.js');
        Controller::$jsfiles[] = Tree::classpath('Resource/js/tree_itemManager.js');


        Genesis::renderView("admin.overview");
    }

    public function listView($next = 1, $per_page = 10){

        $this->ctrl->datatable = Tree_itemTable::init(new Tree_item())->buildindextable();

        Controller::$jsfiles[] = Tree_item::classpath('Resource/js/tree_itemCtrl.js');

        $break = '';
        if ($parentid = Request::get("parent_id:eq")) {
            $this->ctrl->datatable->addFilterParam("parent_id", $parentid);
            $cat = Tree_item::find($parentid, 1);
            $breakcumth = [$cat];
            $cat->getParent($cat->parent_id, $breakcumth, 1);

            $break = "<a href='" . Tree_item::classpath("tree-item/list") . "' class=''>Liste</a>";
            foreach ($breakcumth as $i => $bc) {
                $break .= " > <a href='" . Tree_item::classpath("tree-item/list?dfilters=on&parent_id:eq=" . $bc->id) . "' class='btn btn-info'>{$bc->name}</a>";
            }
        }
        $this->ctrl->entitytarget = 'Tree_item';
        $this->ctrl->title = "Manage Tree_item > " . $break;

        $this->ctrl->renderListView();

    }

    public function webservices()
    {

        $treeitemCtrl = new \Tree_itemFrontController();
        $treeCtrl = new TreeController();

        (new Request('hello'));

        switch (R::get('path')) {

            case 'tree.create':
                g::json_encode($treeCtrl->createAction());
                break;
            case 'tree.update':
                g::json_encode($treeCtrl->updateAction(Request::get("id")));
                break;
            case 'tree-item.create':
                g::json_encode($treeitemCtrl->createAction());
                break;
            case 'tree-item.detail':
                g::json_encode($treeitemCtrl->detailAction(Request::get('id')));
                break;
            case 'tree.lazyloading':
                g::json_encode((new TreeFrontController())->ll());
                break;
            case 'tree-item.lazyloading':
                g::json_encode($treeitemCtrl->ll());
                break;
            case 'tree-item.update':
                g::json_encode($treeitemCtrl->updateAction(Request::get('id')));
                break;
            case 'tree-item.order':
                g::json_encode($treeitemCtrl->orderAction());
                break;
            case 'tree-item.addcontent':
                g::json_encode($treeitemCtrl->addcontentAction());
                break;
            case 'tree-item.delete':
                g::json_encode($treeitemCtrl->deleteAction(Request::get('id')));
                break;

            case 'tree-item.changestatus':
                g::json_encode($treeitemCtrl->changestatus(Request::get('id'), Request::get('status')));
                break;
            case 'tree-item.getdatafront':
                g::json_encode($treeitemCtrl->getdatafront());
                break;
            case 'tree-item-image.upload':
                g::json_encode((new Tree_item_imageController())->uploadAction(Request::get('tree_item_id')));
                break;
            case 'tree-item-image.delete':
                g::json_encode((new Tree_item_imageController())->deleteAction(Request::get('id')));
                break;
            case 'tree-item.images':
                g::json_encode((new Tree_item(Request::get('id')))->images());
                break;
            case 'tree-item.getdata':
            case 'tree-items.getdata':
            case 'tree-item.init':
                g::json_encode($treeitemCtrl->getdata());
                break;
            case 'tree-item.getchildren':
                g::json_encode($treeitemCtrl->getchildren(Request::get("id")));
                break;

        }
    }

}
