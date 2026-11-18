<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

/**
 * Genere proprement (ETAPE G.1) a partir de CommentCore.json (dont le
 * `module.name` pointait vers un `ModuleComment` inexistant - corrige
 * vers ModuleCms, ou l'entite est deja enregistree dans
 * config/dvups_configurations.php / module_configurations.php) : la
 * table legacy `comment` n'a jamais existe (aucune entite generee),
 * donc aucune donnee a migrer.
 */
#[ORM\Entity]
#[ORM\Table(name: "cmscomment")]
class Cmscomment extends Model implements JsonSerializable
{

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
    /**
     * @var string
     */
    #[ORM\Column(name: "pseudo", type: "string", length: 255)]
    protected $pseudo;
    /**
     * @var string
     */
    #[ORM\Column(name: "email", type: "string", length: 255, nullable: true)]
    protected $email;
    /**
     * @var text
     */
    #[ORM\Column(name: "comment", type: "text")]
    protected $comment;
    /**
     * @var bool
     */
    #[ORM\Column(name: "seen", type: "boolean")]
    protected $seen = false;

    /**
     * @var \Cmscontent
     */
    #[ORM\ManyToOne(targetEntity: "\Cmscontent")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $cmscontent;

    /**
     * @var \User
     */
    #[ORM\ManyToOne(targetEntity: "\User")]
    #[ORM\JoinColumn(onDelete: "cascade")]
    public $user;

    public function __construct($id = null)
    {
        if ($id) {
            $this->id = $id;
        }
        $this->cmscontent = new Cmscontent();
        $this->user = new User();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPseudo()
    {
        return $this->pseudo;
    }

    public function setPseudo($pseudo)
    {
        $this->pseudo = $pseudo;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getSeen()
    {
        return $this->seen;
    }

    public function setSeen($seen)
    {
        $this->seen = $seen;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'pseudo' => $this->pseudo,
            'email' => $this->email,
            'comment' => $this->comment,
            'seen' => $this->seen,
        ];
    }

}
