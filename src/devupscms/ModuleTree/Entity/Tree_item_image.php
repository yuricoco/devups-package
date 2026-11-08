<?php
// user \dclass\devups\model\Model;

/**
 * ETAPE G.4 : etend directement ImageCore (meme modele que Cmsresource,
 * Phase 4/ETAPE G.2/G.3) au lieu de pointer vers Dv_image/ModuleGallery
 * via une relation - plus de dependance a ModuleGallery pour les
 * images d'item d'arbre.
 *
 * @Entity @Table(name="tree_item_image")
 * */
class Tree_item_image extends ImageCore implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="width_size", type="string" , length=255 , nullable=true)
     * @var string
     **/
    protected $width_size;

    /**
     * @ManyToOne(targetEntity="\Tree_item")
     * @JoinColumn(onDelete="cascade")
     * @var \Tree_item
     */
    public $tree_item;

    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }

        $this->tree_item = new Tree_item();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getWidth_size()
    {
        return $this->width_size;
    }

    public function setWidth_size($width_size)
    {
        $this->width_size = $width_size;
    }

    /**
     *  manyToOne
     * @return \Tree_item
     */
    function getTree_item()
    {
        $this->tree_item = $this->tree_item->__show();
        return $this->tree_item;
    }

    function setTree_item(\Tree_item $tree_item)
    {
        $this->tree_item = $tree_item;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'width_size' => $this->width_size,
            'image' => $this->image,
            'srcImage_100' => $this->srcImage("100_"),
            'srcImage' => $this->srcImage(),
        ];
    }

    public function uploadImage($file = 'image')
    {
        $dfile = self::Dfile($file);
        if (!$dfile->errornofile) {

            $filedir = "treeitem/";
            $url = $dfile
                ->sanitize()
                ->addresize([100, 100], "100_", "", false)
                ->addresize([150, 150], "150_", "", false)
                ->addresize([50, 50], "50_", "", false)
                ->moveto($filedir);

            if (!$url['success']) {
                return array('success' => false, 'error' => $url);
            }

            $this->uploaddir = $filedir;
            $this->image = $url['file']['hashname'];
            $this->extension = $url['file']['extension'] ?? null;
            $this->size = $url['file']['size'] ?? ($url['file']['imagesize'] ?? null);
        }
    }

    public function __delete($exec = true)
    {
        Dfile::deleteFile("150_" . $this->image, $this->uploaddir);
        Dfile::deleteFile("50_" . $this->image, $this->uploaddir);
        Dfile::deleteFile("100_" . $this->image, $this->uploaddir);

        return parent::__delete($exec);
    }

}
