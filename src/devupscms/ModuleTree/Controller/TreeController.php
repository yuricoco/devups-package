<?php


use dclass\devups\Controller\Controller;

class TreeController extends Controller
{

    public function listView($next = 1, $per_page = 10)
    {

        $this->datatable = TreeTable::init(new Tree())->buildindextable();

        self::$jsfiles[] = Tree::classpath('Resource/js/treeCtrl.js');

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

    public function formView($id = null)
    {
        $tree = new Tree();
        $action = __env . ("admin/api/tree/create");
        if ($id) {
            $action = __env . ("admin/api/tree/update?id=" . $id);
            $tree = Tree::find($id);
        }

        return ['success' => true,
            'form' => TreeForm::init($tree, $action)
                ->buildForm()
                ->addDformjs()
                ->renderForm(),
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

        /*Tree_lang::truncate();
        Tree::truncate();
        Tree_item_lang::truncate();
        Tree_item::truncate();*/

        Tree_item::append('training_item',
            [
                'name' => ['fr' => 'Atelier live virtuel', 'en' => 'Atelier live virtuel'],
                'slug' => 'live'
            ],
            [
                'name' => ['fr' => 'Test de Vérification', 'en' => 'Test de Vérification'],
                'slug' => 'quiz'
            ],
            [
                'name' => ['fr' => "Test d'Evaluation", 'en' => "Test d'Evaluation"],
                'slug' => 'quiz'
            ],
            [
                'name' => ['fr' => "Sondage", 'en' => "Sondage"],
                'slug' => 'quiz'
            ],
            [
                'name' => ['fr' => "Informations", 'en' => "Informations"],
                'slug' => 'info'
            ],
            [
                'name' => ['fr' => "Forums", 'en' => "Forums"],
                'slug' => 'forums'
            ],
            [
                'name' => ['fr' => "Devoir", 'en' => "Devoir"],
                'slug' => 'work'
            ],
            [
                'name' => ['fr' => "Projet", 'en' => "Projet"],
                'slug' => 'project'
            ],
            [
                'name' => ['fr' => "Examen", 'en' => "Examen"],
                'slug' => 'exam'
            ],
            [
                'name' => ['fr' => "Lecon async", 'en' => "v"],
                'slug' => 'async'
            ]
        );
        Tree_item::append('activity_resource',
            [
                'name' => ['fr' => 'Ressource', 'en' => 'Ressource'],
                'slug' => 'ressource'
            ],
            [
                'name' => ['fr' => 'Quiz Maker', 'en' => 'Quiz Maker'],
                'slug' => 'quiz'
            ],
            [
                'name' => ['fr' => 'Rich Text Box', 'en' => 'Rich Text Box'],
                'slug' => 'rtb'
            ],
            [
                'name' => ['fr' => 'Image', 'en' => 'Image'],
                'slug' => 'illustration'
            ],
            [
                'name' => ['fr' => 'report', 'en' => 'report'],
                'slug' => 'report'
            ],
            [
                'name' => ['fr' => 'Classe Virtuelle', 'en' => 'Classe Virtuelle'],
                'slug' => 'virtual_class'
            ],
            [
                'name' => ['fr' => 'Video Player', 'en' => 'Video Player'],
                'slug' => 'video'
            ],
            [
                'name' => ['fr' => 'Audio Player', 'en' => 'Audio Player'],
                'slug' => 'audio'
            ],
            [
                'name' => ['fr' => 'Response Poster', 'en' => 'Response Poster'],
                'slug' => 'rp'
            ],
            [
                'name' => ['fr' => 'Image Slider', 'en' => 'Image Slider'],
                'slug' => 'image',
                'chain' => '.jpg,.png,.jpeg',
            ]
        );
        Tree_item::append('quizz_category',
            [
                'name' => ['fr' => "Question à Réponse Multiple", 'en' => "Question à Réponse Multiple"],
                'slug' => 'mrq'
            ],
            [
                'name' => ['fr' => "Question à Réponse Courte", 'en' => "Short Answer Question"],
                'slug' => 'saq'
            ],
            [
                'name' => ['fr' => "Question Vrai ou Faux", 'en' => "True or False question"],
                'slug' => 'tfq'
            ],
            [
                'name' => ['fr' => " Question a réponse longue", 'en' => "Long answer question"],
                'slug' => 'essay'
            ],
            [
                'name' => ['fr' => "Question à Choix Multiple", 'en' => "Multiple Choice Question"],
                'slug' => 'mcq'
            ]
        );
        Tree_item::append('note_type',
            [
                'name' => ['fr' => 'Pourcentage', 'en' => 'Pourcentage'],
                'slug' => 'percent'
            ],
            [
                'name' => ['fr' => 'Over 100', 'en' => 'Over 100'],
                'slug' => '100'
            ],
            [
                'name' => ['fr' => 'Over 20', 'en' => 'Over 20'],
                'slug' => '20'
            ],
            [
                'name' => ['fr' => 'Over 5', 'en' => 'Over 5'],
                'slug' => '5'
            ]
        );
        Tree_item::append('formats',
            [
                'name' => ['fr' => 'En ligne', 'en' => 'On Line'],
                'slug' => 'online'
            ],
            [
                'name' => ['fr' => 'Présentiel', 'en' => 'On place'],
                'slug' => 'onplace'
            ],
            [
                'name' => ['fr' => 'Hybride', 'en' => 'Hybrid'],
                'slug' => 'hybrid'
            ]
        );
        Tree_item::append('tools',
            [
                'name' => ['fr' => 'Zoom', 'en' => 'Zoom'],
                'slug' => 'zoom'
            ],
            [
                'name' => ['fr' => 'Google Meet', 'en' => 'Google Meet'],
                'slug' => 'gm'
            ],
            [
                'name' => ['fr' => 'BigBlueButton', 'en' => 'BigBlueButton'],
                'slug' => 'bbb'
            ],
            [
                'name' => ['fr' => 'Live Storm', 'en' => 'Live Storm'],
                'slug' => 'ls'
            ]
        );
        Tree_item::append('roles',
            [
                'name' => ['fr' => 'Modérateur(trice)', 'en' => 'Moderator'],
                'slug' => 'moderator'
            ],
            [
                'name' => ['fr' => 'Orateur(trice)', 'en' => 'Orator'],
                'slug' => 'orator'
            ],
            [
                'name' => ['fr' => 'Evaluator', 'en' => 'Evaluator'],
                'slug' => 'evaluator'
            ],
            [
                'name' => ['fr' => 'Facilitateur(trice)', 'en' => 'Facilitator'],
                'slug' => 'facilitator'
            ]
        );
        Tree_item::append('civilities',
            [
                'name' => ['fr' => 'M', 'en' => 'Sir'],
                'slug' => 'M'
            ],
            [
                'name' => ['fr' => 'Mme', 'en' => 'Mss'],
                'slug' => 'Mme'
            ],
            [
                'name' => ['fr' => 'Mlle', 'en' => 'Ldy'],
                'slug' => 'Mlle'
            ],
            [
                'name' => ['fr' => 'Dr (Mrs)', 'en' => 'Mrs (Dr)'],
                'slug' => 'Mrs'
            ],
            [
                'name' => ['fr' => 'Prof (Mrs)', 'en' => 'Prof (Mrs)'],
                'slug' => 'Prof'
            ],
            [
                'name' => ['fr' => 'Hon', 'en' => 'Hon'],
                'slug' => 'Hon'
            ],
            [
                'name' => ['fr' => 'Son Excellence', 'en' => 'Son Excellence'],
                'slug' => 'S.E'
            ],
            [
                'name' => ['fr' => 'Chief (Mrs)', 'en' => 'Chief (Mrs)'],
                'slug' => 'Chief'
            ]
        );
        Tree_item::append('ticket_types',
            [
                'name' => ['fr' => 'Stand', 'en' => 'Stand'],
                'slug' => 'Stand'
            ],
            [
                'name' => ['fr' => 'B2B', 'en' => 'B2B'],
                'slug' => 'B2B'
            ],
            [
                'name' => ['fr' => 'Participant', 'en' => 'Participant'],
                'slug' => 'Participant'
            ],
            [
                'name' => ['fr' => 'Projet', 'en' => 'Project'],
                'slug' => 'Project'
            ]
        );
        Tree_item::append('skills',
            [
                'name' => ['fr' => 'Languages & Humanities', 'en' => 'Languages & Humanities'],
                'slug' => 'lh'
            ],
            [
                'name' => ['fr' => 'Agriculture & Agroindustry', 'en' => 'Agriculture & Agroindustry'],
                'slug' => 'aa'
            ],
            [
                'name' => ['fr' => 'Business & Entrepreneurship', 'en' => 'Business & Entrepreneurship'],
                'slug' => 'be'
            ],
            [
                'name' => ['fr' => 'Low Carbon Transition & Circularity', 'en' => 'Low Carbon Transition & Circularity'],
                'slug' => 'lctc'
            ]
        );

    }

    /**
     * Outil de REPARATION manuelle -
     * la reconstruction normale se fait desormais a l'ecriture
     * (Tree::markDirty(), appele par Tree_item::__insert/__update/__delete
     * et Tree::__delete). Delegue a Tree::rebuildCacheFiles() pour ne pas
     * dupliquer la logique de construction (toutes les langues actives).
     */
    public function cache($id)
    {
        $tree = Tree::find($id);
        Tree::rebuildCacheFiles($tree->name);
        return $tree;
    }

}
