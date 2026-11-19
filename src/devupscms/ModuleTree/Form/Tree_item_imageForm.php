<?php

namespace devupscms\ModuleTree\Form;

use \Genesis as g;

class Tree_item_imageForm extends \FormManager
{

    public $tree_item_image;

    public static function init(\Tree_item_image $tree_item_image, $action = "")
    {
        $fb = new Tree_item_imageForm($tree_item_image, $action);
        $fb->tree_item_image = $tree_item_image;
        return $fb;
    }

    public function buildForm()
    {

        $this->fields['width_size'] = [
            "label" => t('tree_item_image.width_size'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree_item_image->getWidth_size(),
        ];

        $this->fields['tree_item'] = [
            "type" => FORMTYPE_SELECT,
            "value" => $this->tree_item_image->getTree_item()->getId(),
            "label" => t('entity.tree_item'),
            "options" => \FormManager::Options_Helper('name', \Tree_item::allrows()),
        ];

        $this->fields['image'] = [
            "label" => t('image'),
            "type" => FORMTYPE_FILE,
            "filetype" => FILETYPE_IMAGE,
            "value" => $this->tree_item_image->getImage(),
            "src" => $this->tree_item_image->showImage(),
        ];

        return $this;

    }

    public static function renderWidget($id = null, $action = "create")
    {
        \Genesis::renderView("tree_item_image.formWidget", self::getFormData($id, $action));
    }

}

// ETAPE 5.2 (v8.0) : alias de compatibilite - voir TODO.md "LOT 5.2".
class_alias(Tree_item_imageForm::class, 'Tree_item_imageForm');
