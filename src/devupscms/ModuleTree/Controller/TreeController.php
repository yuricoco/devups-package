<?php


use dclass\devups\Controller\Controller;

class TreeController extends Controller
{

    public function listView($next = 1, $per_page = 10)
    {

        $this->datatable = TreeTable::init(new Tree())->buildindextable();

        self::$jsfiles[] = Tree::classpath('Ressource/js/treeCtrl.js');

        $this->entitytarget = 'Tree';
        $this->title = "Manage Tree";

        $this->renderListView();

    }

    public function managerView()
    {

        Genesis::renderView("overview", [
            "basecontenturl" => Cmstext::classpath("cmstext/")
        ]);
    }

    public function datatable($next, $per_page)
    {

        return ['success' => true,
            'datatable' => TreeTable::init(new Tree())->buildindextable()->getTableRest(),
        ];

    }

    public function createAction($tree_form = null)
    {
        extract($_POST);

        $tree = $this->form_fillingentity(new Tree(), $tree_form);
        if ($this->error) {
            return array('success' => false,
                'tree' => $tree,
                'action' => 'create',
                'error' => $this->error);
        }


        $id = $tree->__insert();
        return array('success' => true,
            'tree' => $tree,
            'tablerow' => TreeTable::init()->buildindextable()->getSingleRowRest($tree),
            'detail' => '');

    }

    public function updateAction($id, $tree_form = null)
    {
        extract($_POST);

        $tree = $this->form_fillingentity(new Tree($id), $tree_form);

        if ($this->error) {
            return array('success' => false,
                'tree' => $tree,
                'action_form' => 'update&id=' . $id,
                'error' => $this->error);
        }

        $tree->__update();
        return array('success' => true,
            'tree' => $tree,
            'tablerow' => TreeTable::init()->buildindextable()->getSingleRowRest($tree),
            'detail' => '');

    }


    public function detailView($id)
    {

        $this->entitytarget = 'Tree';
        $this->title = "Detail Tree";

        $tree = Tree::find($id);

        $this->renderDetailView(
            TreeTable::init()
                ->builddetailtable()
                ->renderentitydata($tree)
        );

    }

    public function deleteAction($id)
    {

        Tree::find($id)->__delete();
        return array('success' => true,
            'detail' => '');
    }


    public function deletegroupAction($ids)
    {

        Tree::where("id")->in($ids)->delete();

        return array('success' => true,
            'detail' => '');

    }

    public function fillData()
    {
        Tree_item::append('days',
            [
                'name' => ['fr' => 'lundi', 'en' => 'lundi'],
                'slug' => '1'
            ],
            [
                'name' => ['fr' => 'Mardi', 'en' => 'Mardi'],
                'slug' => '2'
            ]
        );
        Tree_item::append('period',
            [
                'name' => ['fr' => '07h - 10h', 'en' => '07h - 10h'],
                'slug' => '1'
            ],
            [
                'name' => ['fr' => '10h - 14h', 'en' => '10h - 14h'],
                'slug' => '2'
            ]
        );
    }

}
