<?php
use Doctrine\ORM\Mapping as ORM;

// user \dclass\devups\model\Model;

/**
 * Journal des emails REELLEMENT envoyes (DMail::sendMail(),
 * Reportingmodel::sendMail()) - jamais des erreurs/evenements qui n'ont
 * rien a voir avec un envoi, voir Log:: pour cela (README.md#Logs).
 */
#[ORM\Entity]
#[ORM\Table(name: "emaillog")]
class Emaillog extends Model implements JsonSerializable
{
    const STATUT_ENVOYE = 'envoye';
    const STATUT_ECHEC = 'echec';

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
    #[ORM\Column(name: "destinataire", type: "string", length: 500)]
    protected $destinataire;
    /**
     * @var string
     */
    #[ORM\Column(name: "sujet", type: "string", length: 500)]
    protected $sujet;
    /**
     * @var string
     */
    #[ORM\Column(name: "modele", type: "string", length: 255, nullable: true)]
    protected $modele;
    /**
     * @var string
     */
    #[ORM\Column(name: "statut", type: "string", length: 20)]
    protected $statut;
    /**
     * @var text
     */
    #[ORM\Column(name: "erreur", type: "text", nullable: true)]
    protected $erreur;
    /**
     * @var datetime
     */
    #[ORM\Column(name: "created_at", type: "datetime", nullable: true)]
    protected $created_at;
    /**
     * @var datetime
     */
    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    protected $updated_at;
    /**
     * @var datetime
     */
    #[ORM\Column(name: "deleted_at", type: "datetime", nullable: true)]
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
