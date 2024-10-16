<?php


use dclass\devups\Controller\Controller;
use dclass\devups\Datatable\Lazyloading;

class Tree_itemController extends Controller{

    public function listView($next = 1, $per_page = 10){

        $this->datatable = Tree_itemTable::init(new Tree_item())->buildindextable();

        self::$jsfiles[] = Tree_item::classpath('Resource/js/tree_itemCtrl.js');

        $break = '';
        if ($parentid = Request::get("parent_id:eq")) {
            $this->datatable->addFilterParam("parent_id", $parentid);
            $cat = Tree_item::find($parentid, 1);
            $breakcumth = [$cat];
            $cat->getParent($cat->parent_id, $breakcumth, 1);

            $break = "<a href='" . Tree_item::classpath("tree-item/list") . "' class=''>Liste</a>";
            foreach ($breakcumth as $i => $bc) {
                $break .= " > <a href='" . Tree_item::classpath("tree-item/list?dfilters=on&parent_id:eq=" . $bc->id) . "' class='btn btn-info'>{$bc->name}</a>";
            }
        }
        $this->entitytarget = 'Tree_item';
        $this->title = "Manage Tree_item > " . $break;

        $this->renderListView();

    }

    public function datatable($next, $per_page) {

        return ['success' => true,
            'datatable' => Tree_itemTable::init(new Tree_item())->router()->getTableRest(),
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
                ->addJs(__admin.'plugins/tinymce/tinymce.bundle')
                ->addJs(Tree_item::classpath('Resource/js/tree_itemForm'))
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
                        'tablerow' => Tree_itemTable::init()->router()->getSingleRowRest($tree_item),
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
                        'tablerow' => Tree_itemTable::init()->router()->getSingleRowRest($tree_item),
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


    public function changestatus( $id, $status)
    {
        $category = Tree_item::where('this.id', $id)->update([
            'this.status' => $status
        ]);

        return $category;
    }

    public function getchildren($id)
    {

        $qb = Tree_item::where("parent_id", $id);
//        if ($count)
        $ll = new Lazyloading();
        $ll->lazyloading(new Tree_item(),  $qb, "this.name asc");
        return $ll;
        $category = Tree_item::find($id);
//        $categories = Category::where("parent_id", $id)->take(self::per_page)
//            ->orderBy('name', 'asc')->get();
//        foreach ($categories as $cat) {
//            $cat->children = DB::table("categories")->select()->where("parent_id", $cat->id)->count();
//        }

        $categorytree = [];
        if ($category->getParents_id())
            $categorytree = Tree_item::where("this.id")->in($category->getParents_id())->__getAll();

        return compact("category", "categorytree", "ll");

    }

    /**
     *
     */
    public function getdata()
    {
//        $content = file_get_contents(self::$path);
//        $info = json_decode($content, true);
        $categories = [];
        $info = Category::getmaincategory();
//        foreach ($info as $cat) {
//            $cat->children = DB::table("categories")->select()->where("parent_id", $cat->id)->count();
//        }
        return ["suceess" => true, "data" => $info];

    }

    public function order()
    {

        $rawdata = \Request::raw();

        $tree_itemorder = $rawdata["tree_items"];

        foreach ($tree_itemorder as $order){
            Tree_item::where("this.id", $order[0])->update(['position'=>$order[1]]);
        }

        return Response::$data;

    }

    public function addcontentAction()
    {
        $menu = Tree_item::find(Request::get("id"));
        $content = new Cmstext();
        $content->setSlug($menu->getSlug());
        $content->title = $menu->name;
        $content->setContent("comming soon");
        $content->setSommary("comming soon");
        $content->tree_item = $menu;
        $id = $content->__insert();

        Response::set("redirect", Cmstext::classpath("cmstext/edit?id=".$id));
        return Response::$data;

    }

}
