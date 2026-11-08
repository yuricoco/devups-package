<?php
// user \dclass\devups\model\Model;

/**
 * @Entity @Table(name="tree_item")
 * */
class Tree_item extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="position", type="integer" , nullable=true )
     * @var string
     **/
    protected $position;
    /**
     * @Column(name="slug", type="string" , length=55 , nullable=true)
     * @var string
     **/
    protected $slug;
    /**
     * @Column(name="status", type="integer"  , nullable=true)
     * @var integer
     **/
    protected $status;
    /**
     * @Column(name="parent_id", type="integer"  , nullable=true)
     * @var integer
     **/
    protected $parent_id;
    /**
     * @Column(name="main", type="integer"  , nullable=true)
     * @var integer
     **/
    protected $main;
    /**
     * Decision du proprietaire (2026-11-03) : cette
     * colonne est POLYVALENTE PAR CONCEPTION, son format se definit PAR
     * USAGE (ex. setParents_id()/getParents_id() : ids separes par des
     * espaces). Il n'y a donc PAS de format unique a faire respecter.
     *
     * CONSEQUENCE OBLIGATOIRE : aucun accesseur generique ne doit en
     * presumer le format. Mesure en base le 2026-11-04 : sur les 95 items
     * reels, `chain` contient aussi bien des listes d'ids (aucune
     * actuellement) que du texte libre sans rapport avec un parcours
     * d'arbre (ex. item id 67, arbre "configuration" :
     * "Achat automatique apres les pages d'extrait" ; item id 91, arbre
     * "network" : "fb"). Un lecteur qui presumerait un format casse sur
     * ces lignes reelles (ex. une virgule ou un espace dans le texte libre
     * fait exploser un explode() + whereIn() generique - voir l'ancienne
     * getParentsCatTree(), retiree pour cette raison,.
     *
     * Avant d'ecrire un nouveau lecteur de `chain` : rattache-le
     * explicitement a L'USAGE qui ecrit ce format precis (comme
     * getParents_id() le fait pour le sien), jamais une methode partagee
     * qui s'appliquerait a tout item de tout arbre.
     *
     * @Column(name="chain", type="text"  , nullable=true)
     * @var text
     **/
    protected $chain;

    /**
     * @Column(name="icon", type="text" , nullable=true )
     * @var string
     **/
    protected $icon;
    /**
     * @Column(name="image", type="string" , length=255, nullable=true )
     * @var string
     **/
    protected $image;
    /**
     * @Column(name="uploaddir", type="string" , length=25, nullable=true )
     * @var string
     **/
    protected $uploaddir;

    /**
     * @ManyToOne(targetEntity="\Tree")
     * @var \Tree
     */
    public $tree;


    public function __construct($id = null)
    {
        $this->dvtranslate = true;
        $this->dvtranslated_columns = ["name", "content"];
        if ($id) {
            $this->id = $id;
        }

        $this->tree = new Tree();
    }

    /**
     * Nom de l'arbre de CET item, lu directement par
     * jointure SQL plutot que via $this->tree->name : selon le chemin
     * d'appel (find() simple, hydrateWithJson, ...), la relation n'est
     * pas toujours hydratee avec son nom. Fiable dans __insert/__update
     * (apres ecriture, id garanti) et __delete (avant suppression).
     */
    private function currentTreeName(): ?string
    {
        if (!$this->id) {
            return $this->tree && $this->tree->name ? $this->tree->name : null;
        }
        $stmt = \Model::pdo()->prepare(
            'SELECT t.name FROM tree_item ti JOIN tree t ON t.id = ti.tree_id WHERE ti.id = ?'
        );
        $stmt->execute([$this->id]);
        $name = $stmt->fetchColumn();
        return $name !== false ? $name : null;
    }

    public function __insert()
    {
        $id = parent::__insert();
        $treeName = $this->currentTreeName();
        if ($treeName) {
            Tree::markDirty($treeName);
        }
        return $id;
    }

    public function __update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        $result = parent::__update($arrayvalues, $seton, $case, $defauljoin);
        $treeName = $this->currentTreeName();
        if ($treeName) {
            Tree::markDirty($treeName);
        }
        return $result;
    }

    public function __delete($exec = true)
    {
        $treeName = $this->currentTreeName();
        $result = parent::__delete($exec);
        if ($treeName) {
            Tree::markDirty($treeName);
        }
        return $result;
    }

    /**
     * Deux defauts empiles, les deux mesures avant
     * correction :
     * - mainmenu() prend un NOM D'ARBRE en premier argument, pas une
     *   colonne : l'appel passait "this.position" (ne correspond a aucun
     *   arbre reel), donc $ti etait TOUJOURS null et la branche de repli
     *   ci-dessous TOUJOURS prise. Corrige en "position", le nom de
     *   l'arbre reel (id 6, verifie en base) ;
     * - Tree::where("name", ...) est ambigu (jointure de traduction de
     *   Tree, colonne "name" partagee avec dvups_lang) - qualifie en
     *   "this.name" (voie A, voir protocole.
     */
    public static function position($ref)
    {
        $ti = self::mainmenu("position")->andwhere("this.slug", $ref)
            ->firstOrNull();
        // dv_dump($ti);
        if (!$ti) {
            //return new Tree_item();
            $tree = Tree::where("this.name", "position")->firstOrNull();
            if (!$tree) {
                $tree = new Tree();
                $tree->setName(["en" => "position", "fr" => "position"]);
                $tree->__insert();
            }
            $ti = new Tree_item();
            $ti->name = ["en" => $ref, "fr" => $ref];
            $ti->setMain(1);
            $ti->setSlug($ref);
            $ti->tree = $tree;
            $ti->__insert();
        }
        return $ti;
    }

    public static function append($treename, ...$tree_items)
    {
        //if (!$ti) {
        //return new Tree_item();
        // Tree::where("name", ...) ambigu - qualifie
        // (voie A, voir protocole.
        $tree = Tree::where("this.name", $treename)->firstOrNull();
        if (!$tree) {
            $tree = new Tree();
            $tree->name = $treename;
            $tree->__insert();
        }
        foreach ($tree_items as $i => $item) {
            $ti = new Tree_item();
            foreach ($item as $key => $value) {
                $ti->{$key} = $value;
            }
            if (is_array($item['name']))
                $ti->name = $item['name'];
            else
                $ti->name = [
                    'fr' => $item['name'],
                    'en' => $item['name']
                ];

            $ti->main = 1;
            $ti->tree = $tree;
            $ti->position = $i;
            $ti->__insert();
        }
        //}
        return $ti;
    }

    public function getId()
    {
        return $this->id;
    }

    public function uploadImage($file = 'image')
    {
        $this->uploaddir = $this->tree->name;
        $dfile = self::Dfile($file);
        if (!$dfile->errornofile) {

            $url = $dfile
                ->hashname()
                ->addresize([50], "50_")
                ->moveto($this->uploaddir);

            if (!$url['success']) {
                return array('success' => false,
                    'error' => $url);
            }

            if ($this->image) {
                Dfile::deleteFile("50_" . $this->image, $this->uploaddir);
                Dfile::deleteFile($this->image, $this->uploaddir);
            }
            $this->image = $url['file']['hashname'];
        }
    }

    public function srcImage($prefix = "")
    {
        return $this->image ? Dfile::show($prefix . $this->image, $this->uploaddir) : null;
    }

    public function showImage($prefix = "")
    {
        if (is_array($prefix)) {
            if ($prefix)
                $prefix = $prefix[0];
            else
                $prefix = "";
        }
        $url = Dfile::show($prefix . $this->image, $this->uploaddir);
        return Dfile::fileadapter($url, $this->image);
    }

    /**
     * $level (1 = racine) et $branch (nom du
     * palier correspondant dans tree.branches, ou null si l'arbre n'en a
     * pas) sont calcules par Tree::buildCacheNode(), seul appelant de
     * cette methode dans tout le depot (mesure par grep avant d'etendre
     * jsonMini() elle-meme plutot que d'ajouter ces cles ailleurs).
     * Additif : aucune des cles existantes ne change.
     */
    public function jsonMini(int $level = 1, ?string $branch = null)
    {
        return [
            'id' => $this->id,
            'src_image' => $this->srcImage(),
            'name' => $this->name,
            'slug' => $this->slug,
            'position' => (int)$this->position,
            'content' => $this->content,
            'parent_id' => $this->parent_id,
            'main' => $this->main,
            'status' => $this->status,
            'chain' => $this->chain,
            'level' => $level,
            'branch' => $branch,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {

        $children = [];
        $countchildren = 0;
        if ($this->id)
            $countchildren = (int)self::where("parent_id", $this->id)->count();
        if (!$this->parent_id && $countchildren && $this->id) {
            $children = self::where("parent_id", $this->id)->get();
        }
        $parent = [];
        if ($this->parent_id) {
            $item = self::find($this->parent_id);
            $parent = [
                'parent_slug' => $item->slug
            ];
        }

        return [
            ... parent::jsonSerialize(),
            'count_children' => $countchildren,
            'children' => $children,
            'src_image' => $this->srcImage(),
        ];
        return [
                'id' => $this->id,
                'src_image' => $this->srcImage(),
                'name' => $this->name,
                'slug' => $this->slug,
                'position' => (int)$this->position,
                'content' => $this->content,
                'parent_id' => $this->parent_id,
                'main' => $this->main,
                'status' => $this->status,
                'chain' => $this->chain,
                'content_id' => $this->getCmscontent()->getId(),
                'count_children' => $countchildren,
                'children' => $children,
                'in_company' => $this->in_company,
                // 'children' => (int)self::where("parent_id", $this->id)->count(),
            ] + self::addAttributes($this);

    }

    public $in_company;

    public function getChildren($category = null)
    {
        if (!$category)
            $category = new Tree_item();

        return self::select()
            ->where("this.parent_id", $this->id)
            //->andwhere("this.id", "!=", $category->getId())
            ->orderby("this.position")
            ->get();
    }

    /**
     * L'ancienne version concatenait $slug directement
     * dans une sous-requete SQL (injection). Remplace par une requete
     * preparee qui resout les ids, puis ->in() sur un tableau (qb_sanitize
     * applique a chaque valeur) - aucune valeur n'est plus concatenee.
     */
    public static function childrenOf(string $slug, $id_lang = null)
    {
        $stmt = \Model::pdo()->prepare('SELECT id FROM tree_item WHERE slug = ?');
        $stmt->execute([$slug]);
        $parentIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [0];

        return self::select()
            ->where("this.parent_id")
            ->in($parentIds)
            ->setLang($id_lang)
            ->orderby("this.position")
            ->get();
    }

    /**
     * @param null $idp
     * @return $this
     */
    public function getParent($idp = null)
    {
        if (!$idp)
            $idp = $this->parent_id;

        $categoryparent = self::find($idp);
        if ($idp = $categoryparent->getParent_id())
            $this->getParent($idp);
        else
            return $categoryparent;
    }

    public function collectChildren($limit = 10)
    {
        $count = self::select()->where("parent_id", $this->id)->count();
        if ($count)
            return self::select()->where("parent_id", $this->id)
                ->limit($limit)
                ->get();

        return [];

    }

    public function setParents_id($parent_idsarray)
    {

        if ($parent_idsarray) {
            $this->chain = implode(" ", array_keys($parent_idsarray));
        }
    }

    public function getParents_id()
    {

        if (!$this->chain)
            return false;

        $returns = [];
        $arrays = explode(' ', $this->chain);
        foreach ($arrays as $val) {
            $returns[$val] = $val;
        }

        return $returns;

    }

    /**
     * getParentsCatTree() retiree : accesseur
     * generique qui presumait un format (explode(",") puis explode(" "),
     *) sur une colonne desormais actee comme polyvalente par
     * conception (voir la doc de $chain plus haut). Elle explosait avec une
     * erreur SQL quand `chain` contenait du texte libre (apostrophe non
     * echappee par whereIn()).
     */

    public function ofSameTree()
    {
        $categoryparent = self::find($this->parent_id);
        return $categoryparent->collectChildren(25);
    }

    /**
     * @param string $tree
     * @return QueryBuilder
     */
    public static function mainmenu($tree = "menu", $id_lang = null)
    {
        return self::select("*", Dvups_lang::defaultLang()->id)
            //->leftjoin(Tree::class)
            //->leftjoinrecto(Tree_lang::class, Tree::class)
            ->where("tree.name", $tree)
            ->where("this.main", 1)->setLang($id_lang);
    }

    /**
     * @param string $tree
     * @return QueryBuilder
     */
    public static function mainmenus($trees = ["menu"], $id_lang = null)
    {
        //join(Tree::class)->
        // "main" ambigu (colonne partagee avec
        // dvups_lang via la jointure de traduction) - qualifie (voie A).
        return self::where("tree.name")->in($trees)
            ->andwhere("this.main", 1)
            ->setLang($id_lang);
    }

    /**
     * @param string $tree
     * @return array
     */
    public static function getmainmenu($tree = "menu", $id_lang = null)
    {
        //join(Tree::class, null, $id_lang)->
        return self::where("tree.name", $tree)
            ->andwhere("this.main", 1)
            ->orderby("this.position")
            ->setLang($id_lang)
            //->limit(5)
            ->get();
    }

    public function getCmscontent()
    {
        return Cmscontent::where($this)
            ->first();
    }

    public static function children($id, $id_lang = null)
    {
        if (!$id)
            return [];

        return self::select()
            ->where("this.parent_id", $id)
            ->orderby("this.position")
            ->setLang($id_lang)
            ->get();
    }

    public function images()
    {
        $items = $this->__hasmany(Tree_item_image::class, true, null, true);
        $success = true;
        return compact("items", "success");
    }

    public function get_image()
    {
        return $this->__hasone(Tree_item_image::class);
    }

    /**
     * @return Tree_item_image|null
     */
    public function firstImage()
    {
        return $this->__hasmany(Tree_item_image::class, false)->first();
    }

}
