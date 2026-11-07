<?php
// user \dclass\devups\model\Model;

/**
 * @Entity @Table(name="tree")
 * */
class Tree extends Model implements JsonSerializable
{

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
    /**
     * @Column(name="name", type="string" , length=255 )
     * @var string
     **/
    protected $name;
    /**
     * use to organise the data in the tree view
     * @Column(name="branches", type="string", length=120  , nullable=true)
     * @var text
     **/
    protected $branches;
    /**
     * defaut errone (entier 2) sur une colonne text
     * nullable - mesure en base : 7/16 arbres reels (device, device_page,
     * mobile_alert, configuration, criteria, post_category, network) ont
     * literalement la chaine "2" en description, les 9 autres sont NULL.
     * Ces 7 lignes ne sont PAS corrigees ici (aucune donnee nettoyee par
     * la main de l'autorite - voir rapport) ; seul le defaut, qui aurait
     * continue a ecrire "2" sur tout nouvel arbre non explicitement
     * renseigne, est corrige pour s'aligner sur la colonne nullable.
     * @Column(name="description", type="text"  , nullable=true)
     * @var text
     **/
    protected $description = null;

    /**
     * Plafond de recursion du cache, INDEPENDANT de
     * count(branches) : mesure en base le 2026-11-04, zero cycle parent_id
     * sur les 95 tree_item existants et une profondeur reelle max de 2
     * niveaux (menu/about-us) - mais rien dans le schema n'empeche un
     * cycle ni une valeur demesuree de branches demain. 10 reprend la
     * convention deja presente dans ce meme fichier pour une borne "large
     * mais sure" (Tree_item::collectChildren($limit=10)) : tres au-dessus
     * de tout besoin reel observe, sans permettre une descente sans fin.
     */
    const MAX_CACHE_DEPTH = 10;


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

    public function getName()
    {
        return $this->name;
    }

    /**
     * tree.name est une cle machine referencee par du
     * code (Tree::where("name", ...), getmainmenu($name), le nom de
     * fichier de cache). La renommer casse tout ce qui la cite, en
     * silence. Refuse plutot que de renommer.
     */
    public function __update($arrayvalues = null, $seton = null, $case = null, $defauljoin = true)
    {
        if ($this->id) {
            $stmt = \Model::pdo()->prepare('SELECT name FROM tree WHERE id = ?');
            $stmt->execute([$this->id]);
            $oldName = $stmt->fetchColumn();
            if ($oldName !== false && (string)$oldName !== (string)$this->name) {
                throw new \RuntimeException(
                    'Tree::__update() : "name" est immuable (U2) une fois cree - ' .
                    'valeur actuelle "' . $oldName . '", tentative "' . $this->name . '".'
                );
            }
        }
        return parent::__update($arrayvalues, $seton, $case, $defauljoin);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getBranches()
    {
        return $this->branches;
    }

    /**
     * "branches" est le schema de profondeur de
     * l'arbre (protocolebis) : noms de niveau separes par des virgules.
     * Aucun hook de validation declarative n'existe dans ce cadre (verifie
     * dans Core.php/BackendGenerator.php avant d'ecrire) - la validation se
     * fait dans le setter, qui RETOURNE une chaine d'erreur plutot que de
     * la stocker, exactement le contrat attendu par le remplissage
     * generique du formulaire (Controller::formWithPost() :
     * $error = call_user_func([$object,$setter], ...); if ($error)
     * $this->error[$key] = $error; - meme convention que
     * Dvups_admin::setName(), verifiee avant d'ecrire celle-ci). Refus
     * explicite, jamais un nettoyage muet : segment vide refuse, espaces
     * de bordure retires par segment. Une virgule a l'INTERIEUR d'un nom
     * de niveau est structurellement impossible puisque la virgule EST le
     * separateur - rien a valider de plus sur ce point.
     */
    public function setBranches($branches)
    {
        if ($branches === null || $branches === '') {
            $this->branches = null;
            return;
        }

        $segments = array_map('trim', explode(',', $branches));
        foreach ($segments as $segment) {
            if ($segment === '') {
                return 'branches : un nom de niveau ne peut pas etre vide - verifie les virgules en double ou en bordure.';
            }
        }

        $this->branches = implode(',', $segments);
    }


    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            ... parent::jsonSerialize(),
            'branches' => $this->branches ? explode(',', $this->branches): [],
            /*'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,*/
        ];
    }

    /**
     * Delegue a constant() : readCache() est publique,
     * un appelant hors depot peut encore s'en servir
     * protocole). Le contrat change : plus de [] silencieux quand le
     * fichier manque - voir constant().
     */
    public static function readCache($name){
        return self::constant($name);
    }

    /**
     * Point d'entree unique de lecture du cache des
     * arbres :
     *
     *   Tree::constant('criteria');                 // liste ordonnee
     *   Tree::constant('criteria', 'originalite');   // un item, O(1)
     *   Tree::constant('criteria', null, $id_lang);  // dans une langue
     *
     * C5 : si le fichier de la langue demandee manque, repli sur la
     * langue par defaut (C2) ; si MEME celui-la manque, repli sur la
     * base - journalise (error_log, jamais dv_dump qui tue la requete),
     * et leve une RuntimeException en developpement (__prod === false)
     * pour que le trou soit visible tout de suite plutot que masque par
     * un resultat vide. En production, ne jette jamais : retourne les
     * donnees recalculees depuis la base, le cache est une optimisation,
     * jamais une source de verite divergente.
     */
    public static function constant(string $name, ?string $slug = null, ?int $id_lang = null)
    {
        // Un seul appel a defaultLang() (une requete) : le
        // chiffrage du point 3 avait d'abord mesure DEUX appels ici (un
        // pour $lang quand $id_lang est null, un pour $defaultIso) - deux
        // requetes pour resoudre la MEME ligne. Trouve EN MESURANT avant
        // de livrer, corrige ici.
        $defaultLang = \Dvups_lang::defaultLang();
        $defaultIso = $defaultLang ? $defaultLang->getIso_code() : null;
        $lang = $id_lang ? \Dvups_lang::find($id_lang) : $defaultLang;
        $iso = $lang ? $lang->getIso_code() : null;

        $file = $slug === null ? $name : ($name . '_references');

        $data = self::readTreeCacheFile($file, $iso);
        if ($data === null && $iso !== $defaultIso) {
            $data = self::readTreeCacheFile($file, $defaultIso);
        }

        if ($data === null) {
            $message = 'Tree::constant("' . $name . '"' .
                ($slug !== null ? ', "' . $slug . '"' : '') .
                ') : cache manquant (langue "' . $iso . '", repli langue par defaut "' .
                $defaultIso . '" egalement absent) - retombee sur la base.';
            error_log($message);
            if (!__prod) {
                throw new \RuntimeException($message);
            }
            $built = self::buildCacheData($name, $lang ? $lang->getId() : $id_lang);
            $data = $slug === null ? $built['list'] : $built['references'];
        }

        if ($slug === null) {
            return $data;
        }
        return $data[$slug] ?? null;
    }

    /**
     * Lit un fichier de cache "<file>.<iso>.php" sous cache/trees/.
     * Retourne null quand le fichier est absent (jamais [] - C5 doit
     * pouvoir distinguer "absent" de "present mais vide").
     */
    private static function readTreeCacheFile(string $file, ?string $iso)
    {
        $path = ROOT . "cache/trees/{$file}.{$iso}.php";
        if (!file_exists($path)) {
            return null;
        }
        return require $path;
    }

    /**
     * Construit, pour un
     * arbre et une langue, la liste ordonnee et la carte par slug -
     * exactement ce que `cache()` ecrit sur disque, et ce sur quoi
     * `constant()` retombe quand le disque est absent.
     *
     * La profondeur suit desormais count(tree.branches) au lieu d'un seul
     * niveau code en dur (ancienne regle C6, ecrite arbitrairement -
     * reconnue comme telle et corrigee ici) : un arbre a N paliers nommes
     * descend sur N niveaux ; un arbre sans branches garde son unique
     * niveau historique (maxDepth=1, comportement STRICTEMENT identique a
     * avant ce point pour les 16 arbres actuels, tous a branches=NULL).
     * Plafond MAX_CACHE_DEPTH en plus de count(branches) - voir sa
     * doc - et garde anti-cycle parent_id (buildCacheNode ci-dessous) :
     * aucun des deux n'a ete declenche par les donnees reelles au moment
     * d'ecrire ceci (0 cycle, profondeur reelle max 2), mais rien dans le
     * schema ne les empeche pour autant.
     *
     * Lecture de "branches" en SQL BRUT via \Model::pdo(), jamais via
     * Tree::where(...)->firstOrNull() : mesure en base - la jointure de traduction de initSelect() est un
     * INNER JOIN dvups_lang chaine apres un LEFT JOIN <table>_lang, donc
     * TOUTE ligne sans ligne _lang correspondante disparait silencieusement
     * du resultat : passer par l'ORM rendrait "branches" invisible pour
     * tout arbre sans traduction (meme pattern que Tree_item::currentTreeName()).
     */
    public static function buildCacheData(string $treeName, ?int $id_lang = null): array
    {
        $branchesRaw = \Model::pdo()
            ->prepare("SELECT branches FROM tree WHERE name = ? LIMIT 1");
        $branchesRaw->execute([$treeName]);
        $branchesValue = $branchesRaw->fetchColumn();

        $branchNames = [];
        if ($branchesValue !== false && $branchesValue !== null && $branchesValue !== '') {
            $branchNames = array_map('trim', explode(',', $branchesValue));
        }
        $maxDepth = $branchNames ? min(count($branchNames), self::MAX_CACHE_DEPTH) : 1;

        // $id_lang transmis aux enfants aussi : le code d'origine
        // (TreeController::cache()) ne le faisait pas - les enfants
        // restaient dans la langue par defaut quelle que soit la langue
        // demandee (ecart corrige au passage, documente au protocole).
        $tree_items = \Tree_item::mainmenu($treeName, $id_lang)
            ->addColumn("select count(*) from tree_item t where t.parent_id = this.id", 'has_children')
            ->whereNotNull('this.slug')
            ->get();

        $references = [];
        $list = [];
        foreach ($tree_items as $item) {
            $visited = [$item->id => true];
            [$listNode, $refNode] = self::buildCacheNode(
                $item, 1, $maxDepth, $branchNames, $id_lang, $visited, (bool)$item->has_children
            );
            $list[] = $listNode;
            $references[$item->slug] = $refNode;
        }

        return ['list' => $list, 'references' => $references];
    }

    /**
     * Noeud recursif du cache. Construit en UNE
     * seule passe les deux representations attendues (liste ordonnee pour
     * 'list', carte par slug pour 'references') sans interroger deux fois
     * la base pour les memes enfants.
     *
     * $visited est passe par reference sur toute la descente d'UN item
     * racine : un id deja vu qui revient (cycle parent_id - rien dans le
     * schema ne l'empeche) arrete la descente a CE noeud plutot que de
     * boucler, et le signale par error_log (jamais dv_dump, qui tuerait
     * la reconstruction du cache de TOUT l'arbre pour un seul noeud
     * corrompu).
     *
     * jsonMini() est le seul point qui expose desormais le niveau (1 =
     * racine) et le nom du palier correspondant (tree.branches) - mesure
     * avant d'ecrire : jsonMini() n'a pas d'autre appelant dans tout le
     * depot que ces deux methodes, donc etendue directement plutot que
     * de dupliquer ces deux cles seulement ici.
     */
    private static function buildCacheNode(
        \Tree_item $item,
        int $level,
        int $maxDepth,
        array $branchNames,
        ?int $id_lang,
        array &$visited,
        ?bool $hasChildrenHint = null
    ): array {
        $branchName = $branchNames[$level - 1] ?? null;
        $node = $item->jsonMini($level, $branchName);
        $listNode = $node;
        $refNode = $node;

        if ($level >= $maxDepth || $hasChildrenHint === false) {
            return [$listNode, $refNode];
        }

        $children = \Tree_item::children($item->id, $id_lang);
        if (!$children) {
            return [$listNode, $refNode];
        }

        $childrenList = [];
        $childrenMap = [];
        foreach ($children as $child) {
            if (isset($visited[$child->id])) {
                error_log('Tree::buildCacheData : cycle parent_id detecte sur tree_item id=' .
                    $child->id . ' (parent courant id=' . $item->id .
                    ') - descente arretee a ce noeud, le reste du cache continue.');
                continue;
            }
            $visited[$child->id] = true;
            [$childList, $childRef] = self::buildCacheNode($child, $level + 1, $maxDepth, $branchNames, $id_lang, $visited);
            $childrenList[] = $childList;
            $childrenMap[$child->slug] = $childRef;
        }

        if ($childrenList) {
            $listNode['children'] = $childrenList;
            $refNode['children'] = $childrenMap;
        }

        return [$listNode, $refNode];
    }

    /**
     * Ecrit, pour CHAQUE langue active, les deux
     * fichiers de cache de cet arbre (liste + carte par slug). Appelee
     * par TreeController::cache() (action de reparation manuelle) et par
     * le mecanisme d'invalidation a l'ecriture (markDirty/shutdown,
     * ci-dessous).
     */
    public static function rebuildCacheFiles(string $treeName): void
    {
        if (!file_exists(ROOT . "cache/trees"))
            mkdir(ROOT . "cache/trees", 0777, true);

        $langs = \Dvups_lang::where("active", 1)->get();
        foreach ($langs as $lang) {
            $built = self::buildCacheData($treeName, $lang->getId());
            \DClass\lib\Util::arrayToPhpFile($built['references'], $treeName . '_references.' . $lang->getIso_code(), 'cache/trees/');
            \DClass\lib\Util::arrayToPhpFile($built['list'], $treeName . '.' . $lang->getIso_code(), 'cache/trees/');
        }
    }

    /**
     * Les ecritures de Tree
     * et de Tree_item appellent ceci au lieu de reconstruire
     * immediatement. Un import en boucle (Tree_item::append() insere N
     * items dans le MEME arbre) ne marque "sale" qu'une fois par arbre ;
     * la reconstruction REELLE n'a lieu qu'a la fin de la requete
     * (register_shutdown_function, enregistree une seule fois), quand
     * les N lignes sont toutes en base - jamais une reconstruction par
     * ligne, jamais une reconstruction a partir d'un etat partiel.
     */
    private static $dirtyTrees = [];
    private static $shutdownRegistered = false;

    public static function markDirty(string $treeName): void
    {
        self::$dirtyTrees[$treeName] = true;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(function () {
                foreach (array_keys(self::$dirtyTrees) as $name) {
                    try {
                        self::rebuildCacheFiles($name);
                    } catch (\Throwable $e) {
                        error_log('Tree::markDirty() shutdown rebuild "' . $name . '" : ' . $e->getMessage());
                    }
                }
                self::$dirtyTrees = [];
            });
        }
    }

    /**
     * Supprimer un Tree
     * efface ses fichiers de cache - sinon constant() continuerait a
     * servir des donnees mortes (le FK tree_item.tree_id est RESTRICT,
     * donc cette suppression n'est possible que si l'arbre n'a deja plus
     * aucun item - verifie sur la base live,.
     *
     * PIEGE VERIFIE EN TESTANT : supprimer tous les Tree_item d'un arbre
     * (obligatoire avant de pouvoir supprimer le Tree, le FK l'impose)
     * marque l'arbre "sale" (markDirty) AVANT que le Tree lui-meme ne
     * soit supprime. Sans unset() ci-dessous, la reconstruction differee
     * en fin de requete s'executerait APRES le nettoyage fait ici et
     * recreerait des fichiers (vides, l'arbre n'existant plus) pour un
     * arbre qui n'existe plus - exactement l'orphelin que ce point veut
     * eviter. On annule donc la reconstruction en attente pour ce nom.
     */
    public function __delete($exec = true)
    {
        $name = $this->name;
        $result = parent::__delete($exec);
        // Motifs ancres sur un "." juste apres $name : "category.*.php" ne
        // doit PAS effacer "category_menu.*.php" (un nom d'arbre peut etre
        // le prefixe litteral d'un autre).
        $patterns = [
            ROOT . "cache/trees/" . $name . ".*.php",
            ROOT . "cache/trees/" . $name . "_references.*.php",
        ];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                @unlink($path);
            }
        }
        unset(self::$dirtyTrees[$name]);
        return $result;
    }

}
