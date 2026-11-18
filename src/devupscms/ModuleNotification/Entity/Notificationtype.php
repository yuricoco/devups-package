<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

#[ORM\Entity]
#[ORM\Table(name: "notificationtype")]
class Notificationtype extends Model implements JsonSerializable
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
    #[ORM\Column(name: "_key", type: "string", length: 55)]
    protected $_key;
    /**
     * @var string
     */
    #[ORM\Column(name: "session", type: "string", length: 55)]
    protected $session = 'user';
    /**
     * @var string
     */
    #[ORM\Column(name: "redirect", type: "string", length: 255, nullable: true)]
    protected $redirect;
    /**
     * @var integer
     */
    #[ORM\Column(name: "emailmodel", type: "string", length: 255, nullable: true)]
    protected $emailmodel;

    /**
     * @var integer
     */
    #[ORM\Column(name: "entity", type: "string", length: 55)]
    public $entity;

    public function __construct($id = null)
    {

        $this->dvtranslate = true;
        $this->dvtranslated_columns = ["content"];
        if ($id) {
            $this->id = $id;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param string $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return int
     */
    public function getEmailmodel()
    {
        return $this->emailmodel;
    }

    /**
     * @param int $emailmodel
     */
    public function setEmailmodel($emailmodel)
    {
        $this->emailmodel = $emailmodel;
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }
    /**
     * @return string
     */
    public function getRedirection()
    {
        return $this->redirect;
    }

    /**
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    public function get_key()
    {
        return $this->_key;
    }

    public function set_key($_key)
    {
        $this->_key = $_key;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    // public function jsonSerialize()
    // {
    //     return [
    //         'id' => $this->id,
    //         '_key' => $this->_key,
    //         'content' => $this->content,
    //         'redirect' => $this->redirect,
    //         'entity' => $this->entity,
    //     ];
    // }

    public function getTest()
    {
        return '
        <input id="notification-' . $this->id . '" class="form-control" name="phonenumber" />
        <button type="button" onclick="model.sendsms(this, ' . $this->id . ')" class="btn btn-info"> Test sms </button>';
    }
}
