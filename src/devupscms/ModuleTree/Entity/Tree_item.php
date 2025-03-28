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
     * @Column(name="chain", type="text"  , nullable=true)
     * @var text
     **/
    protected $chain;

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

    public static function position($ref)
    {
        $ti = self::mainmenu("this.position")->andwhere("this.slug", $ref)
            ->firstOrNull();
        // dv_dump($ti);
        if (!$ti) {
            //return new Tree_item();
            $tree = Tree::where("name", "position")->firstOrNull();
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
        $tree = Tree::where("name", $treename)->firstOrNull();
        if (!$tree) {
            $tree = new Tree();
            $tree->name = $treename;
            $tree->__insert();
        }
        foreach ($tree_items as $item) {
            $ti = new Tree_item();
            $ti->name = $item['name'];
            $ti->main = 1;
            $ti->slug = $item['slug'];
            $ti->tree = $tree;
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

    public function jsonMini()
    {
        return [
            'id' => $this->id,
            'src_image' => $this->srcImage(),
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }

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
                'content_id' => $this->getCmstext()->getId(),
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

    public static function childrenOf(string $slug, $id_lang = null)
    {
        return self::select()
            ->where("this.parent_id")
            ->in(" select id from tree_item where slug = '$slug' ")
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

    public function getParentsCatTree()
    {
        $categorytree = [];
        if ($this->chain)
            $categorytree = self::whereIn("id", explode(",", $this->chain))->get();

        return $categorytree;
    }

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
        return self::where("tree.name")->in($trees)
            ->andwhere("main", 1)
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
            ->andwhere("main", 1)
            ->orderby("position")
            ->setLang($id_lang)
            //->limit(5)
            ->get();
    }

    public function getCmstext()
    {
        return Cmstext::where($this)
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
