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


        $this->fields['name'] = [
            "label" => t('Libelle'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree_item->name,
            "lang" => true,

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
        ];

        $this->fields['main'] = [
            "label" => t('Reference'),
            "type" => FORMTYPE_TEXT,
            "hidden" => true,
            "value" => 1,
        ];

        $this->fields['slug'] = [
            "type" => FORMTYPE_TEXT,
            "value" => Request::get("product_id"),
            "label" => t('Produit'),
            "hidden" => true,
        ];
        $this->fields['chain'] = [
            "type" => FORMTYPE_TEXT,
            "value" => Request::get("product_name"),
            "label" => t('Produit'),
            "hidden" => true,
        ];

        $this->fields['tree.id'] = [
            "type" => FORMTYPE_SELECT,
            "hidden" => true,
            "value" => Request::get("tree_id"),
            "label" => t('tree'),
            "options" => [Request::get("tree_id")=>Request::get("tree_id")],
        ];


        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("tree_item.formWidget", self::getFormData($id, $action));
    }

}
