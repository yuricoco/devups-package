<?php
// user \dclass\devups\model\Model;

/**
 * Remplace Imagecms (ETAPE G.2/G.3) : au lieu d'une table de jointure
 * vers Dv_image, Cmsresource EST directement un fichier (etend
 * ImageCore, meme modele que Dv_image), associe a un Cmscontent -
 * plus de dependance a ModuleGallery pour les images de contenu CMS.
 *
 * @Entity @Table(name="cmsresource")
 * */
class Cmsresource extends ImageCore implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;

    /**
     * @ManyToOne(targetEntity="\Cmscontent")
     * @JoinColumn(onDelete="cascade")
     * @var \Cmscontent
     */
    public $cmscontent;

    public function __construct($id = null)
    {

        if ($id) {
            $this->id = $id;
        }
        $this->cmscontent = new Cmscontent();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Cmscontent
     */
    public function getCmscontent()
    {
        return $this->cmscontent;
    }

    /**
     * @param Cmscontent $cmscontent
     */
    public function setCmscontent(Cmscontent $cmscontent): void
    {
        $this->cmscontent = $cmscontent;
    }

    public function uploadImage($file = 'image')
    {
        $dfile = self::Dfile($file);
        if (!$dfile->errornofile) {

            $filedir = 'cms/';
            $url = $dfile
                ->sanitize($this->id . "_")
                ->addresize([150, 150], "150_", "", true)
                ->moveto($filedir);

            if (!$url['success']) {
                return array('success' => false, 'error' => $url);
            }

            $this->uploaddir = $filedir;
            $this->image = $url['file']['hashname'];
            $this->name = $url['file']['hashname'];
            $this->reference = $url['file']['hashname'];
            // ETAPE V : l'ordre etait inverse par rapport au meme code dans
            // Tree_item_image::uploadImage() - $url['file']['imagesize'] est
            // le tableau brut de getimagesize() (pas un nombre), passe en
            // priorite dans $this->size (colonne "size", type float) :
            // "Warning: Array to string conversion" au INSERT (DBAL.php).
            $this->size = $url['file']['size'] ?? ($url['file']['imagesize'] ?? null);
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'image' => $this->image,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function __delete($exec = true)
    {
        Dfile::deleteFile($this->image, $this->uploaddir);
        Dfile::deleteFile("150_" . $this->image, $this->uploaddir);

        return parent::__delete($exec);
    }

}
