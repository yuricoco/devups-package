<?php
// user \dclass\devups\model\Model;

/**
 * @Entity @Table(name="status", uniqueConstraints={
 *     @UniqueConstraint(name="uq_status_entity_key", columns={"entity", "_key"})
 * })
 * */
class Status extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="color", type="string" , length=25 )
     * @var string
     **/
    protected $color;
    /**
     * @Column(name="_key", type="string", length=55  )
     * @var integer
     **/
    protected $_key;
    /**
     * @Column(name="position", type="integer", nullable=true )
     * @var string
     **/
    protected $position = 1;
    /**
     * @Column(name="description", type="text" , nullable=true )
     * @var string
     **/
    protected $description;

    /**
     * @Column(name="entity", type="string", length=55  )
     * @var integer
     **/
    public $entity;

    /**
     * L'entier que `_key` designe pour l'entite qui porte
     * cette ligne, quand mesure et confirme (voir la migration
     * migration_20261104_status_value_column.sql et
     * - NULL sinon, jamais devine.
     * Propriete ajoutee ici (pas au commit de la colonne elle-meme) :
     * necessaire a partir du moment ou le cache (point 3) lit cette
     * valeur via l'ORM (`$row->value`) - sans elle, `__get()` la traite
     * comme non mappee (notice "Propriete non-definie").
     *
     * @Column(name="value", type="integer", nullable=true)
     * @var int|null
     **/
    protected $value;

    public function __construct($id = null)
    {

        $this->dvtranslate = true;
        $this->dvtranslated_columns = ["label"];
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
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int|null $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Libelle de la position du statut.
     *
     * @return string
     */
    public function getPositionLabel()
    {
        return "<span class='position'>" . $this->position . "</span>";
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getColor()
    {
        return $this->color;
    }

    /**
     * Couleur de texte calculee depuis la luminance du fond
     * (aucune bibliotheque), pas #333333 en dur peu importe le fond -
     * mesure sur les 7 couleurs reelles de la table (seuil 150, formule
     * ITU-R BT.601 ponderee, cf. textColorFor()) :
     *   #8db3e2 (process)   -> 172.996 -> sombre (#333333)
     *   #92cddc (end)       -> 189.07  -> sombre
     *   #953734 (suspended) ->  82.76  -> clair (#ffffff)
     *   #00b050 (published) -> 112.43  -> clair
     *   #d8d8d8 (draft)     -> 216.0   -> sombre
     *   #0070c0 (in_pending)->  87.63  -> clair
     *   #00b050 (succeed)   -> 112.43  -> clair
     *
     * Rendu HTML colore, destine aux colonnes de datatable du back-office
     * (ex. `$entity->status->getColortab()`).
     */
    public function getColortab()
    {
        return "<span class='dv-color' style='background: " . $this->color . "; color: " . self::textColorFor($this->color) . " '>" . $this->label . "</span>";
    }

    /**
     * Luminance percue (formule ITU-R BT.601, pas de bibliotheque) :
     * repli sur #333333 si `$hexColor` n'est pas un hex valide (couleur
     * absente/mal formee), jamais une erreur.
     */
    private static function textColorFor(?string $hexColor): string
    {
        $hex = ltrim((string)$hexColor, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#333333';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
        return $brightness > 150 ? '#333333' : '#ffffff';
    }

    /**
     * Libelle brut, sans HTML (utiliser getColortab() pour le rendu colore).
     */
    public function __toString()
    {
        return (string)$this->label;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getKey()
    {
        return $this->_key;
    }

    public function get_key()
    {
        return $this->_key;
    }

    public function setKey($_key)
    {
        $this->_key = $_key;
    }

    public function set_key($_key)
    {
        $this->_key = $_key;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function is($key)
    {
        if ($this->_key == $key)
            return true;

        return false;
    }

    public $label;
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $return = [
            'id' => $this->id,
            'color' => $this->color,
            '_key' => $this->_key,
            'label' => $this->label,
            ///'colortab' => $this->getColortab(),
        ];

        return $return;
    }

    public static function get($key)
    {
        return self::getbyattribut("this._key", $key);
    }

    /**
     * `entity` est une colonne VARCHAR simple sur `status` (pas une
     * relation) : la comparaison se fait sur "this.entity", pas
     * "entity.name" (qui suppose a tort une jointure vers un alias
     * "entity" inexistant - mesure le 2026-11-04, SQLSTATE[42S22]
     * "Unknown column 'entity.name'" a chaque appel scope avant ce
     * correctif, pour les trois accesseurs ci-dessous. Detail dans
     * point 2.
     *
     * Sans $entityname/$entity, ces trois accesseurs peuvent retourner
     * plusieurs lignes si un `_key`/`position` est un jour partage par
     * plusieurs entites : ils retournent alors la PREMIERE ligne trouvee
     * (ordre naturel de la requete), jamais une erreur. Preferer les
     * variantes qui precisent l'entite.
     *
     * @param $key
     * @param null $entityname
     * @return Status|type|null
     */
    public static function getStatus($key, $entityname = null)
    {
        if ($entityname)
            return self::where("this._key", "=", $key)->where("this.entity", "=", $entityname)->first();

        return self::getbyattribut("this._key", $key);
    }

    public static function getStatusPosition($position, $entity = null)
    {
        if ($entity)
            return self::where("this.position", "=", $position)->where("this.entity", "=", $entity)->first();
        return self::getbyattribut("this.position", $position);
    }


    public static function getStatusOf($entity)
    {
        return Status::where("this.entity", "=", $entity)->get();
    }

    /**
     * Point d'entree unique de lecture du cache des
     * statuts, par entite, point 3 -
     * verdict C1-C7 detaille la-bas, pas duplique ici) :
     *
     *   Status::constant('article');                 // liste ordonnee
     *   Status::constant('article', 'published');    // un item, O(1)
     *   Status::constant('article', null, $id_lang);  // dans une langue
     *
     * Modelee sur Tree::constant() (meme regles, PROTOCOLE_moduletree.md
     * : C5 est reprise a l'identique, y compris son comportement
     * en apparence severe - si AUCUNE ligne `status` ne porte encore
     * $entity (mesure le 2026-11-04 : aucune des 7 lignes reelles n'a
     * d'entity non vide, voir point 0a/2 du protocole), le fichier de
     * cache n'a jamais ete construit et cet appel LEVE en developpement
     * (!__prod) - c'est le signal voulu (le trou de donnees, pas un bug
     * du cache) plutot qu'un resultat vide qui le masquerait.
     */
    public static function constant(string $entity, ?string $key = null, ?int $id_lang = null)
    {
        $defaultLang = \Dvups_lang::defaultLang();
        $defaultIso = $defaultLang ? $defaultLang->getIso_code() : null;
        $lang = $id_lang ? \Dvups_lang::find($id_lang) : $defaultLang;
        $iso = $lang ? $lang->getIso_code() : null;

        $file = $key === null ? $entity : ($entity . '_references');

        $data = self::readStatusCacheFile($file, $iso);
        if ($data === null && $iso !== $defaultIso) {
            $data = self::readStatusCacheFile($file, $defaultIso);
        }

        if ($data === null) {
            $message = 'Status::constant("' . $entity . '"' .
                ($key !== null ? ', "' . $key . '"' : '') .
                ') : cache manquant (langue "' . $iso . '", repli langue par defaut "' .
                $defaultIso . '" egalement absent) - retombee sur la base.';
            error_log($message);
            if (!__prod) {
                throw new \RuntimeException($message);
            }
            $built = self::buildCacheDataFor($entity, $lang ? $lang->getId() : $id_lang);
            $data = $key === null ? $built['list'] : $built['references'];
        }

        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? null;
    }

    /**
     * Lit un fichier de cache "<entity>.<iso>.php" (ou
     * "<entity>_references.<iso>.php") sous cache/status/. Retourne null
     * quand le fichier est absent (jamais [] - C5 doit pouvoir distinguer
     * "absent" de "present mais vide", exactement comme Tree::constant()).
     */
    private static function readStatusCacheFile(string $file, ?string $iso)
    {
        $path = ROOT . self::CACHE_DIR . "{$file}.{$iso}.php";
        if (!file_exists($path)) {
            return null;
        }
        return require $path;
    }

    const CACHE_DIR = 'cache/status/';

    /**
     * Construit, pour une entite et une langue, la liste ordonnee (par
     * `position`) et la carte par `_key` - exactement ce que
     * rebuildCacheFiles() ecrit sur disque, et ce sur quoi constant()
     * retombe quand le disque est absent.
     *
     * ->setLang($id_lang) est appele EN CHAINE avant ->get() (jamais
     * $id_lang passe en argument de get()) : mesure le 2026-11-04,
     * get($column, $id_lang) appelle initSelect() avant de poser
     * $this->id_lang quand celui-ci n'etait pas deja fixe - la jointure
     * de traduction est donc construite sans langue et renvoie un
     * `label` concatenant TOUTES les langues (`[fr]...[en]...`), jamais
     * une seule. Contourne comme dans Tree_item::getmainmenu() : setLang()
     * explicite avant get() sans argument.
     */
    public static function buildCacheDataFor(string $entity, ?int $id_lang = null): array
    {
        $rows = self::where("this.entity", "=", $entity)
            ->orderBy("this.position")
            ->setLang($id_lang)
            ->get();

        $list = [];
        $references = [];
        foreach ($rows as $row) {
            $node = [
                'id' => $row->getId(),
                '_key' => $row->getKey(),
                'value' => $row->getValue(),
                'position' => $row->getPosition(),
                'color' => $row->getColor(),
                'label' => $row->label,
                'entity' => $row->entity,
            ];
            $list[] = $node;
            $references[$row->getKey()] = $node;
        }

        return ['list' => $list, 'references' => $references];
    }

    /**
     * Ecrit, pour CHAQUE langue active, les deux
     * fichiers de cache de cette entite (liste + carte par _key). Appelee
     * par le mecanisme d'invalidation a l'ecriture (markDirty/shutdown,
     * ci-dessous).
     */
    public static function rebuildCacheFiles(string $entity): void
    {
        if (!file_exists(ROOT . self::CACHE_DIR)) {
            mkdir(ROOT . self::CACHE_DIR, 0777, true);
        }

        $langs = \Dvups_lang::where("active", 1)->get();
        foreach ($langs as $lang) {
            $built = self::buildCacheDataFor($entity, $lang->getId());
            \DClass\lib\Util::arrayToPhpFile($built['references'], $entity . '_references.' . $lang->getIso_code(), self::CACHE_DIR);
            \DClass\lib\Util::arrayToPhpFile($built['list'], $entity . '.' . $lang->getIso_code(), self::CACHE_DIR);
        }
    }

    /**
     * Meme mecanisme que Tree::markDirty() : une seule
     * reconstruction par entite et par requete, differee en fin de
     * requete (register_shutdown_function), jamais une reconstruction
     * par ligne ni a partir d'un etat partiel.
     */
    private static $dirtyEntities = [];
    private static $shutdownRegisteredStatus = false;

    public static function markDirty(?string $entity): void
    {
        if ($entity === null || $entity === '') {
            // Une entite vide n'a pas de nom de fichier
            // valide (Util::arrayToPhpFile() refuse un segment vide -
            // meme regex que C1 pour Tree) et n'est de toute facon jamais
            // un argument valide de constant() (toujours une entite
            // reelle attendue). Les lignes sans entite restent lisibles
            // par Status::get()/getStatus() sans scope (hors cache) -
            // documente comme limite du perimetre de ce cache, pas une
            // perte silencieuse.
            return;
        }
        self::$dirtyEntities[$entity] = true;
        if (!self::$shutdownRegisteredStatus) {
            self::$shutdownRegisteredStatus = true;
            register_shutdown_function(function () {
                foreach (array_keys(self::$dirtyEntities) as $name) {
                    try {
                        self::rebuildCacheFiles($name);
                    } catch (\Throwable $e) {
                        error_log('Status::markDirty() shutdown rebuild "' . $name . '" : ' . $e->getMessage());
                    }
                }
                self::$dirtyEntities = [];
            });
        }
    }

    public function __insert()
    {
        $id = parent::__insert();
        self::markDirty($this->entity);
        return $id;
    }

    /**
     * Capture l'`entity` EN BASE avant l'ecriture (requete preparee
     * directe, pas this->entity qui porte deja la valeur demandee par
     * l'appelant) : si `entity` change, les DEUX caches (ancien et
     * nouveau) doivent etre invalides, jamais seulement le nouveau -
     * sinon le fichier de l'ancienne entite continuerait a lister une
     * ligne qui ne lui appartient plus (orphelin, meme piege que
     * Tree::__delete().
     */
    public function __update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        $oldEntity = null;
        if ($this->id) {
            $stmt = \Model::pdo()->prepare('SELECT entity FROM status WHERE id = ?');
            $stmt->execute([$this->id]);
            $fetched = $stmt->fetchColumn();
            $oldEntity = $fetched !== false ? $fetched : null;
        }

        $result = parent::__update($arrayvalues, $seton, $case, $defauljoin);

        self::markDirty($this->entity);
        if ($oldEntity !== null && $oldEntity !== $this->entity) {
            self::markDirty($oldEntity);
        }
        return $result;
    }

    public function __delete($exec = true)
    {
        $entity = $this->entity;
        $result = parent::__delete($exec);
        self::markDirty($entity);
        return $result;
    }

}
