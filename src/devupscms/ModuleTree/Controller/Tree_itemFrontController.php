<?php


use dclass\devups\Datatable\Lazyloading;

class Tree_itemFrontController extends Tree_itemController
{

    public function ll($next = 1, $per_page = 10)
    {

        $ll = new Lazyloading();
        $ll->lazyloading(new Tree_item());

        return $ll;

    }

    public function createAction($tree_item_form = null)
    {
        $rawdata = \Request::raw();
        $tree_item = $this->hydrateWithJson(new Tree_item(), $rawdata["tree_item"]);

        $id = $tree_item->__insert();
        return array('success' => true,
            'tree_item' => $tree_item,
            'detail' => '');

    }

    public function updateAction($id, $tree_item_form = null)
    {
        $rawdata = \Request::raw();

        $tree_item = $this->hydrateWithJson(new Tree_item($id), $rawdata["tree_item"]);

        $tree_item->__update();
        return array('success' => true,
            'tree_item' => $tree_item,
            'detail' => '');

    }


    public function detailAction($id)
    {

        $tree_item = Tree_item::find($id);

        return array('success' => true,
            'tree_item' => $tree_item,
            'detail' => '');

    }

}
