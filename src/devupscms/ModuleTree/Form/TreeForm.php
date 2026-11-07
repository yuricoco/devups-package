<?php


use Genesis as g;

class TreeForm extends FormManager
{

    public $tree;

    public static function init(\Tree $tree, $action = "")
    {
        $fb = new TreeForm($tree, $action);
        $fb->tree = $tree;
        return $fb;
    }

    public function buildForm()
    {
        $this->fields['name'] = [
            "label" => t('tree.name'),
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree->getName(),
        ];

        $this->fields['label'] = [
            "label" => t('tree.label', 'Libelle'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "lang" => true,
            "value" => $this->tree->label,
        ];

        $this->fields['description'] = [
            "label" => t('tree.description'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree->getDescription(),
        ];

        // schema de profondeur : noms de niveaux separes par des virgules,
        // valide par Tree::setBranches()
        $this->fields['branches'] = [
            "label" => t('tree.branches', 'Niveaux de l\'arbre (noms separes par des virgules, du plus haut au plus bas - laisser vide si l\'arbre n\'a qu\'un seul niveau)'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $this->tree->getBranches(),
        ];

        return $this;
    }

    public static function formBuilder($dataform, $button = false)
    {
        $tree = new \Tree();
        extract($dataform);
        $entitycore = new Core($tree);

        $entitycore->formaction = $action;
        $entitycore->formbutton = $button;

        //$entitycore->addcss('csspath');


        $entitycore->field['name'] = [
            "label" => t('tree.name'),
            "type" => FORMTYPE_TEXT,
            "value" => $tree->getName(),
        ];

        $entitycore->field['description'] = [
            "label" => t('tree.description'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $tree->getDescription(),
        ];

        // branches = le schema de profondeur de
        // l'arbre (protocolebis), jusqu'ici absent de ce formulaire -
        // seul le gestionnaire (tree_itemManager.js) savait l'ecrire.
        // Aucun FORMTYPE_* de ce cadre n'est un repeteur (verifie dans
        // FormManager.php) : champ texte, convention documentee dans le
        // libelle. Validation reelle dans Tree::setBranches().
        $entitycore->field['branches'] = [
            "label" => t('tree.branches', 'Niveaux de l\'arbre (noms separes par des virgules, du plus haut au plus bas - laisser vide si l\'arbre n\'a qu\'un seul niveau)'),
            FH_REQUIRE => false,
            "type" => FORMTYPE_TEXT,
            "value" => $tree->getBranches(),
        ];


        $entitycore->addDformjs($button);
        $entitycore->addjs(Tree::classpath('Resource/js/treeForm'));

        return $entitycore;
    }

    public static function __renderForm($dataform, $button = false)
    {
        return FormFactory::__renderForm(TreeForm::formBuilder($dataform, $button));
    }

    public static function getFormData($id = null, $action = "create")
    {
        if (!$id):
            $tree = new Tree();

            return [
                'success' => true,
                'tree' => $tree,
                'action' => "create",
            ];
        endif;

        $tree = Tree::find($id);
        return [
            'success' => true,
            'tree' => $tree,
            'action' => "update&id=" . $id,
        ];

    }

    public static function render($id = null, $action = "create")
    {
        g::json_encode(['success' => true,
            'form' => self::__renderForm(self::getFormData($id, $action), true),
        ]);
    }

    public static function renderWidget($id = null, $action = "create")
    {
        Genesis::renderView("tree.formWidget", self::getFormData($id, $action));
    }

}
    