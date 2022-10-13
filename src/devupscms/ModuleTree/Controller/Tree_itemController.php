<?php


use dclass\devups\Controller\Controller;

class Tree_itemController extends Controller{

    public function listView($next = 1, $per_page = 10){

        $this->datatable = Tree_itemTable::init(new Tree_item())->buildindextable();

        self::$jsfiles[] = Tree_item::classpath('Ressource/js/tree_itemCtrl.js');

        $this->entitytarget = 'Tree_item';
        $this->title = "Manage Tree_item";

        $this->renderListView();

    }

    public function datatable($next, $per_page) {

        return ['success' => true,
            'datatable' => Tree_itemTable::init(new Tree_item())->buildindextable()->getTableRest(),
        ];

    }

    public function formView($id = null)
    {
        $tree_item = new Tree_item();
        $action = Tree_item::classpath("services.php?path=tree_item.create&tablemodel=".Request::get("tablemodel", ''));
        if ($id) {
            $action = Tree_item::classpath("services.php?path=tree_item.update&id=" . $id."&tablemodel=".Request::get("tablemodel", ''));
            $tree_item = Tree_item::find($id);
        }else{

            $tree = Request::get("parent_id");
            $ti = Tree_item::find($tree);
        }

        return ['success' => true,
            'form' => Tree_itemForm::init($tree_item, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
        ];
    }

    public function createAction($tree_item_form = null ){
        extract($_POST);

        $tree_item = $this->form_fillingentity(new Tree_item(), $tree_item_form);
        if ( $this->error ) {
            return 	array(	'success' => false,
                            'tree_item' => $tree_item,
                            'action' => 'create',
                            'error' => $this->error);
        }


        $id = $tree_item->__insert();
        return 	array(	'success' => true,
                        'tree_item' => $tree_item,
                        'tablerow' => Tree_itemTable::init()->buildindextable()->getSingleRowRest($tree_item),
                        'detail' => '');

    }

    public function updateAction($id, $tree_item_form = null){
        extract($_POST);

        $tree_item = $this->form_fillingentity(new Tree_item($id), $tree_item_form);

        if ( $this->error ) {
            return 	array(	'success' => false,
                            'tree_item' => $tree_item,
                            'action_form' => 'update&id='.$id,
                            'error' => $this->error);
        }

        $tree_item->__update();
        return 	array(	'success' => true,
                        'tree_item' => $tree_item,
                        'tablerow' => Tree_itemTable::init()->buildindextable()->getSingleRowRest($tree_item),
                        'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Tree_item';
        $this->title = "Detail Tree_item";

        $tree_item = Tree_item::find($id);

        $this->renderDetailView(
            Tree_itemTable::init()
                ->builddetailtable()
                ->renderentitydata($tree_item)
        );

    }

    public function deleteAction($id){

        return 	array(	'success' => true,
                        'detail' => Tree_item::delete($id));

    }


    public function deletegroupAction($ids)
    {

        Tree_item::where("id")->in($ids)->delete();

        return array('success' => true,
                'detail' => '');

    }

}
