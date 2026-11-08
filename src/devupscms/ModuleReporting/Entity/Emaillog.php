<?php
// user \dclass\devups\model\Model;

/**
 * Journal des emails REELLEMENT envoyes (DMail::sendMail(),
 * Reportingmodel::sendMail()) - jamais des erreurs/evenements qui n'ont
 * rien a voir avec un envoi, voir Log:: pour cela (README.md#Logs).
 *
 * @Entity @Table(name="emaillog")
 * */
class Emaillog extends Model implements JsonSerializable
{
    const STATUT_ENVOYE = 'envoye';
    const STATUT_ECHEC = 'echec';

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="destinataire", type="string" , length=500 )
     * @var string
     **/
    protected $destinataire;
    /**
     * @Column(name="sujet", type="string" , length=500 )
     * @var string
     **/
    protected $sujet;
    /**
     * @Column(name="modele", type="string" , length=255, nullable=true )
     * @var string
     **/
    protected $modele;
    /**
     * @Column(name="statut", type="string" , length=20 )
     * @var string
     **/
    protected $statut;
    /**
     * @Column(name="erreur", type="text" , nullable=true)
     * @var text
     **/
    protected $erreur;
    /**
     * @Column(name="created_at", type="datetime" , nullable=true)
     * @var datetime
     **/
    protected $created_at;
    /**
     * @Column(name="updated_at", type="datetime" , nullable=true)
     * @var datetime
     **/
    protected $updated_at;
    /**
     * @Column(name="deleted_at", type="datetime" , nullable=true)
     * @var datetime
     **/
    protected $deleted_at;

    public function __construct($id = null)
    {
        if ($id) {
            $this->id = $id;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDestinataire()
    {
        return $this->destinataire;
    }

    public function setDestinataire($destinataire)
    {
        $this->destinataire = $destinataire;
    }

    public function getSujet()
    {
        return $this->sujet;
    }

    public function setSujet($sujet)
    {
        $this->sujet = $sujet;
    }

    public function getModele()
    {
        return $this->modele;
    }

    public function setModele($modele)
    {
        $this->modele = $modele;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($statut)
    {
        $this->statut = $statut;
    }

    public function getErreur()
    {
        return $this->erreur;
    }

    public function setErreur($erreur)
    {
        $this->erreur = $erreur;
    }

    /**
     * Enregistre un envoi reussi : $destinataire est soit une chaine, soit
     * un tableau email => nom (receivers/receiver_cc de DMail, ou
     * Reportingmodel::$emailreceiver) - serialise en chaine lisible.
     */
    public static function logSuccess(string $sujet, $destinataire, ?string $modele = null): void
    {
        self::create([
            'destinataire' => self::formatDestinataire($destinataire),
            'sujet' => $sujet,
            'modele' => $modele,
            'statut' => self::STATUT_ENVOYE,
            'erreur' => null,
        ]);
    }

    public static function logFailure(string $sujet, $destinataire, string $erreur, ?string $modele = null): void
    {
        self::create([
            'destinataire' => self::formatDestinataire($destinataire),
            'sujet' => $sujet,
            'modele' => $modele,
            'statut' => self::STATUT_ECHEC,
            'erreur' => $erreur,
        ]);
    }

    private static function formatDestinataire($destinataire): string
    {
        if (is_array($destinataire))
            return implode(', ', array_keys($destinataire));
        return (string)$destinataire;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'destinataire' => $this->destinataire,
            'sujet' => $this->sujet,
            'modele' => $this->modele,
            'statut' => $this->statut,
            'erreur' => $this->erreur,
            'created_at' => $this->created_at,
        ];
    }
}
