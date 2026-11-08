<?php


class CmscontentFrontController extends CmscontentController
{

    public static function documentView()
    {
        $collection = [];
        $list = Tree_item::getmainmenu("documentation");
        foreach ($list as $el) {
            $collection[] = [
                "item" => $el,
                "children" => $el->getChildren(),
            ];
        }
        $cms = null;
        if ($ref = Request::get("ref"))
            $cms = Cmscontent::where("slug", $ref)->__getOne();
        Genesis::render('documentation', compact("collection", "cms"));
    }


    public function detailAction($id)
    {

        $cmscontent = Cmscontent::find($id);

        return array('success' => true,
            'cmscontent' => $cmscontent,
            'detail' => '');

    }

    public function faqView()
    {
        $collection = [];
        $list = Tree_item::getmainmenu("faq");
        foreach ($list as $el){
            /*$children = $el->getChildren();
            $colchildren = [];
            foreach ($children as $child){
                $content = $child->getContent()->getContent();
                $colchildren = [
                    "item"=>$child,
                    "content"=>$child,
                ];
            }*/
            $collection[] = [
                "item" => $el,
                "children" => $el->getChildren(),
            ];
        }
        return compact("collection");

    }


}
