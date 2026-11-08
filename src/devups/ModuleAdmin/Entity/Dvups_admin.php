<?php

/**
 * @Entity @Table(name="dvups_admin")
 * */
class Dvups_admin extends Model implements JsonSerializable
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;

    /**
     * @Column(name="firstconnexion", type="integer", nullable=true )
     * @var string
     * */
    protected $firstconnexion = 1;
    /**
     * @Column(name="lastlogin_at", type="datetime", nullable=true )
     * @var string
     * */
    protected $lastlogin_at;
    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     * */
    protected $name;
    /**
     * @Column(name="email", type="string" , length=255, nullable=true )
     * @var string
     * */
    protected $email;
    /**
     * @Column(name="lang", type="string" , length=2 , nullable=true)
     * @var string
     **/
    protected $lang = "en";

    /**
     * @Column(name="login", type="string" , length=255 )
     * @var string
     * */
    protected $login;

    /**
     * @Column(name="password", type="string" , length=255 )
     * @var string
     * */
    protected $password;

    /**
     * ETAPE K.3 : hash (sha256) du token "remember me" courant, jamais le
     * token en clair ni le mot de passe. Null quand "remember me" n'est pas
     * actif (deconnexion, changement de mot de passe, jamais active).
     * @Column(name="remember_token_hash", type="string" , length=255 , nullable=true )
     * @var string
     * */
    protected $remember_token_hash;

    /**
     * @Column(name="remember_token_expires_at", type="datetime" , nullable=true )
     * @var string
     * */
    protected $remember_token_expires_at;

    /**
     * @ManyToOne(targetEntity="\Dvups_role")
     * @var \Dvups_role
     */
    public $dvups_role;

    public function __construct($id = null)
    {
        $this->dvsoftdelete = true;

        if ($id) {
            $this->id = $id;
        }

        $this->dvups_role = new Dvups_role();

    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getFirstconnexion()
    {
        return $this->firstconnexion;
    }

    /**
     * @param string $firstconnexion
     */
    public function setFirstconnexion($firstconnexion)
    {
        $this->firstconnexion = $firstconnexion;
    }

    /**
     * @return string
     */
    public function getLastloginAt()
    {
        return $this->lastlogin_at;
    }

    /**
     * @param string $lastlogin_at
     */
    public function setLastloginAt($lastlogin_at)
    {
        $this->lastlogin_at = $lastlogin_at;
    }

    function getName()
    {
        return $this->name;
    }

    function setName($name)
    {
        if (!$name)
            return "name empty";

        $this->name = $name;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password ? dv_hash_password($password) : $password;
    }

    /**
     * ETAPE K.1 : verifie un mot de passe EN CLAIR contre le hash stocke.
     * Accepte encore l'ancien format sha1() et, dans ce cas uniquement,
     * remplace transparentement le hash stocke par password_hash() (puis
     * sauvegarde) - aucune reinitialisation forcee pour l'administrateur.
     */
    public function verifyPassword(string $password): bool
    {
        $upgraded = false;
        $ok = dv_verify_password($password, $this->password, $upgraded);
        if ($ok && $upgraded) {
            $this->password = dv_hash_password($password);
            $this->__update();
        }
        return $ok;
    }

    /**
     * Change le mot de passe apres verification de l'ancien. Retourne false
     * si l'ancien mot de passe est incorrect (rien n'est modifie), true
     * sinon. Invalide au passage le "remember me" courant - un cookie vole
     * avant le changement de mot de passe ne doit plus donner acces apres.
     *
     * NB : methode appelee par Dvups_adminFrontController::updateAction()
     * mais qui n'existait pas auparavant (bug preexistant, jamais exerce
     * par un test) - ajoutee ici dans le cadre de l'ETAPE K.1.
     */
    public function updatePassword(string $oldpwd, string $newpwd): bool
    {
        if (!$this->verifyPassword($oldpwd))
            return false;

        $this->setPassword($newpwd);
        $this->clearRememberToken();
        return true;
    }

    public function getRememberTokenHash()
    {
        return $this->remember_token_hash;
    }

    public function getRememberTokenExpiresAt()
    {
        return $this->remember_token_expires_at;
    }

    /**
     * ETAPE K.3 : genere un nouveau token "remember me" (jamais le meme que
     * l'ancien - rotation a chaque usage), en stocke uniquement le hash
     * (sha256) et une expiration, et renvoie le token EN CLAIR (destine au
     * cookie, jamais persiste tel quel).
     */
    public function issueRememberToken(int $ttlSeconds = 30 * 24 * 3600): string
    {
        $token = bin2hex(random_bytes(32));
        $this->remember_token_hash = hash('sha256', $token);
        $this->remember_token_expires_at = date('Y-m-d H:i:s', time() + $ttlSeconds);
        $this->__update();
        return $token;
    }

    /**
     * Verifie un token "remember me" EN CLAIR recu via cookie. Refuse (et ne
     * consomme rien) si le token ne correspond pas ou si l'expiration est
     * passee - l'appelant est responsable de retirer le cookie dans ce cas.
     */
    public function verifyRememberToken(string $token): bool
    {
        if (!$this->remember_token_hash || !$this->remember_token_expires_at)
            return false;
        if (strtotime($this->remember_token_expires_at) < time())
            return false;
        return hash_equals($this->remember_token_hash, hash('sha256', $token));
    }

    /** Invalide le token "remember me" courant (deconnexion, changement de mot de passe). */
    public function clearRememberToken(): void
    {
        $this->remember_token_hash = null;
        $this->remember_token_expires_at = null;
        // __update() sans argument (chemin DBAL::updateDbal()) ignore les
        // proprietes null lors de la construction du SET - passer les
        // colonnes explicitement est le seul moyen d'ecrire NULL en base.
        $this->__update(['remember_token_hash' => null, 'remember_token_expires_at' => null]);
    }

    function setDvups_role($dvups_role)
    {
        $this->dvups_role = $dvups_role;
    }

    /**
     *  manyToMany
     * @return \Dvups_role
     */
    function getDvups_role()
    {
        return $this->dvups_role;
    }

    function collectDvups_role()
    {
        $this->dvups_role = $this->__hasmany('dvups_role');
        return $this->dvups_role;
    }

    function addDvups_role(\Dvups_role $dvups_role)
    {
        $this->dvups_role[] = $dvups_role;
    }

    function dropDvups_roleCollection()
    {
        $this->dvups_role = EntityCollection::entity_collection('dvups_role');
    }

    function availableentityright($action)
    {
        if (isset($this->manageentity[$action])) {
            $entity = $this->manageentity[$action];
            return $entity->availableright();
        }
        return [];
    }

    function callbackbtnAction()
    {
        return "<a class='btn btn-default' href='".("/admin/devups/ModuleAdmin/dvups-admin/resetcredential?id=" . $this->getId() ). "'>".t('reset password')."</a>";
    }

    function resetCredential()
    {
        $password = $this->generatePassword();
        $this->setPassword($password);
//        $dvups_admin->setLogin();
        $this->generateLogin($this->getName());

        $this->__save();
        return $password;
//        return [
//            "login" => $this->login,
//            "password" => $this->password,
//        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'password' => $this->password,
            //'dvups_role' => $this->dvups_role,
        ];
    }

    public function __insert()
    {

        return parent::__insert();

    }

    protected function wd_remove_accents($str, $charset = 'utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
        return str_replace(' ', '_', $str); // supprime les autres caractères
    }

    /**
     * @param mixed $login
     */
    public function generateLogin()
    {//on envoi une liste de login
        $list = "1234567890";
        mt_srand((double)microtime() * 10000);
        $generate = "";
        while (strlen($generate) < 4) {
            $generate .= $list[mt_rand(0, strlen($list) - 1)];
        }

        if (strlen($this->name) > 6)
            $alias = substr($this->name, 0, -(strlen($this->name) - 6));
        else
            $alias = $this->name;

        $this->login = $this->wd_remove_accents($alias) . $generate;
        $login = strtolower($this->login);
        return $login;
    }

    /**
     * @param mixed
     */
    public function generatePassword()
    {
        $list = "0123456789abcdefghijklmnopqrstvwxyz";
        mt_srand((double)microtime() * 1000000);
        $password = "";
        while (strlen($password) < 8) {
            $password .= $list[mt_rand(0, strlen($list) - 1)];
        }
        return $password;
    }

}
