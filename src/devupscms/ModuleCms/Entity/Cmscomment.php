<?php
// user \dclass\devups\model\Model;

/**
 * Genere proprement (ETAPE G.1) a partir de CommentCore.json (dont le
 * `module.name` pointait vers un `ModuleComment` inexistant - corrige
 * vers ModuleCms, ou l'entite est deja enregistree dans
 * config/dvups_configurations.php / module_configurations.php) : la
 * table legacy `comment` n'a jamais existe (aucune entite generee),
 * donc aucune donnee a migrer.
 *
 * @Entity @Table(name="cmscomment")
 * */
class Cmscomment extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="pseudo", type="string", length=255)
     * @var string
     **/
    protected $pseudo;
    /**
     * @Column(name="email", type="string", length=255, nullable=true)
     * @var string
     **/
    protected $email;
    /**
     * @Column(name="comment", type="text")
     * @var text
     **/
    protected $comment;
    /**
     * @Column(name="seen", type="boolean")
     * @var bool
     **/
    protected $seen = false;

    /**
     * @ManyToOne(targetEntity="\Cmscontent")
     * @JoinColumn(onDelete="cascade")
     * @var \Cmscontent
     */
    public $cmscontent;

    /**
     * @ManyToOne(targetEntity="\User")
     * @JoinColumn(onDelete="cascade")
     * @var \User
     */
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
