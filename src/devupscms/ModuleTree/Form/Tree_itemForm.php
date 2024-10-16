<?php


use Genesis as g;

class Tree_itemForm extends FormManager
{

    public $tree_item;

    public static function init(\Tree_item $tree_item, $action = "")
    {
        $fb = new Tree_itemForm($tree_item, $action);
        $fb->tree_item = $tree_item;
        return $fb;
    }

    public function buildForm()
    {

        if ($parentid = Request::get("parent_id")) {
            $prod = Tree_item::find($parentid, 1);
            $this->fields['parent_id'] = [
                "label" => t('Parent id'),
                "type" => FORMTYPE_TEXT,
                // "placeholder" => "--- Selectionnez une categorie parent ---" ,
                "value" => $parentid,
            ];
            $this->fields['main'] = [
                "label" => t('Reference'),
                "type" => FORMTYPE_TEXT,
                "hidden" => true,
                "value" => 0,
            ];

            $this->fields['tree.id'] = [
                "type" => FORMTYPE_SELECT,
                "hidden" => true,
                "value" => $prod->tree->id,
                "label" => t('tree'),
                "options" => [$prod->tree->id =>$prod->tree->id],
            ];
        }else {
            $this->fields['parent_id'] = [
                "label" => t('Parent id'),
                "type" => FORMTYPE_TEXT,
                // "placeholder" => "--- Selectionnez une categorie parent ---" ,
                "value" => $this->tree_item->parent_id,
            ];

            $this->fields['main'] = [
                "label" => t('Reference'),
                "type" => FORMTYPE_TEXT,
                "hidden" => true,
                "value" => 1,
            ];

            $this->fields['tree.id'] = [
                "type" => FORMTYPE_SELECT,
                "hidden" => true,
                "value" => Request::get("tree_id:eq"),
                "label" => t('tree'),
                "options" => [Request::get("tree_id")=>Request::get("tree_id:eq")],
            ];
        }
        $this->fields['name'] = [
            "label" => t('Libelle'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree_item->name,
            "lang" => true,

        ];

        $this->fields['image'] = [
            "label" => t('Image'),
            "type" => FORMTYPE_FILE,
            "filetype" => FILETYPE_IMAGE,
            "value" => $this->tree_item->image,
            "src" => $this->tree_item->showImage(),
        ];

        $this->fields['position'] = [
            "type" => FORMTYPE_NUMBER,
            "value" => $this->tree_item->position,
            "label" => t('Numero'),
        ];

        $this->fields['content'] = [
            "label" => t('Description'),
            "type" => FORMTYPE_TEXTAREA,
            "value" => $this->tree_item->content,
            "lang" => true,
        ];

        $this->fields['slug'] = [
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree_item->slug,
            "label" => t('slug'),
        ];
        $this->fields['chain'] = [
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree_item->chain,
            "label" => t('chain'),
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("tree_item.formWidget", self::getFormData($id, $action));
    }

}
