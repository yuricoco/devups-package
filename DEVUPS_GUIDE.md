# Guide Devups — mémo interne (à relire au besoin)

Ce fichier condense la doc technique + la série de vidéos "prise en main de
Devups" (transcript fourni le 2026-07-04). Il sert de référence rapide pour
ne pas re-découvrir ces conventions à chaque session. Ne pas confondre avec
`README.md` (install rapide) — ce fichier est le "comment ça marche vraiment".

## 1. Architecture à 2 niveaux

- **Component** = un métier (ex: `devupscms` = contenu, `devupsfront` =
  utilisateurs, `toolrad` = notre métier applicatif). Le composant principal
  d'un projet porte généralement le même nom que le projet ("main component").
- **Module** = à l'intérieur d'un component (ex: `ModuleProject`,
  `ModuleTask`). Contient Entity/Controller/Form/Datatable/Core/Resource.
- Ne jamais éparpiller un module dans un component auquel il n'appartient pas
  logiquement (même si ça marcherait techniquement) — casse l'harmonie du
  regroupement (analogie : ranger des .jpg dans le dossier des .png).

## 2. Les Datacore (fichiers JSON qui pilotent la génération de code)

| Niveau | Fichier | Emplacement |
|---|---|---|
| Component | `<component>Core.json` (ex: `todoCore.json`) | racine du component |
| Module | `<module>Core.json` (ex: `moduletaskCore.json`) | racine du module |
| Entity | `<Entity>Core.json` (ex: `EntityCore.json`, `FeatureCore.json`) | dans le sous-dossier `Core/` du module |

Schéma d'un ComponentCore.json :
```json
{
  "name": "todo",
  "template": "adminv2",
  "listmodule": [
    {
      "name": "ModuleTask",
      "project": "todo",
      "listentity": [ { "name": "task", "attribut": {...}, "relation": [...] } ]
    }
  ]
}
```

Schéma `attribut` (objet indexé "1","2",... pas un tableau) :
```json
"1": {
  "name": "name",          // minuscule, alphanumerique + underscore uniquement
  "datatype": "string",    // integer | string | text | json | ...
  "size": "255",           // pertinent surtout pour string
  "nullable": "not",       // "not" (NOT NULL) | "default" (nullable)
  "formtype": "text"       // text | textarea | image | ...
}
```
`id` est implicite (auto-increment), ne jamais le déclarer dans `attribut`.

Schéma `relation` (tableau) :
```json
{ "entity": "project", "cardinality": "manyToOne", "nullable": "default", "ondelete": "cascade" }
```

## 3. Commandes CLI utiles

```
php devups install                          # 1ere install : DB + admin + cache
php devups serve                             # lance le serveur PHP dev

php devups core:g:component <compName>       # génère TOUT un component depuis son Core.json
php devups core:g:entity <Comp\Module\entity>       # régénère juste la classe Entity
php devups core:g:table <Comp\Module\entity>        # régénère la Datatable
php devups core:g:form <Comp\Module\entity>         # régénère le Form
php devups core:g:crud <Comp\Module\entity>         # entity + form + table + controller
php devups core:g:frontcontroller <Comp\Module\entity>  # génère le FrontController REST

php devups schema:update --dump-sql          # preview du SQL a executer
php devups schema:update --force             # applique la migration + sync entities
```

### 3.0.1 `install` peut créer le schéma sans créer le master admin —
piège vécu sur ce projet, aucun message d'erreur visible

`install` fait trois choses en séquence : crée la base, lance
`bin\doctrine orm:schema:create` (crée les tables **vides**, y compris
`dvups_admin`/`dvups_role`/`dvups_lang`), puis exécute
`dclass/generator/config_data.sql` en une seule requête PDO
(`prepare()->execute()`) pour semer le master admin. **Ce fichier commence
par ses propres `CREATE TABLE dvups_admin`/`dvups_lang`/`dvups_role`** — un
dump mysqldump complet, pas un simple script d'`INSERT`. Sur une base où
`schema:create` vient tout juste de créer ces mêmes tables, la première
instruction du fichier échoue (`SQLSTATE[42S01]: Base table or view
already exists`), l'exception est absorbée par un `try/catch` qui appelle
`dv_dump()` — donc `die(1)` hors production (voir CLAUDE.md section 5) — et
**aucune des lignes `INSERT` du fichier ne s'exécute**. Résultat : le
schéma existe, mais `dvups_admin`/`dvups_role`/`dvups_lang` restent vides
et la connexion à `/admin/` échoue faute de compte — sans qu'`install`
ait affiché la moindre erreur visible dans son propre message de sortie
("Set the master admin... login: dv_admin password: admin"), qui s'affiche
de toute façon puisqu'il est câblé après le bloc `try/catch`, qu'il ait
réussi ou non.

`php devups dvups_:admin` (qui rejoue le même fichier via `DBAL`) reproduit
l'échec à l'identique — même cause, même fichier. **Le rattrapage constaté
ici** : extraire à la main les trois `INSERT` du fichier (lignes 45-47,
70-71, 74-75 de `config_data.sql` — `dvups_lang` x2, `dvups_role` x1,
`dvups_admin` x1 avec `login=dv_admin`, `password` déjà haché en sha1 de
`admin`) et les rejouer directement en SQL, en sautant les `CREATE
TABLE`/`ALTER TABLE` déjà satisfaits par Doctrine. Après ça, la connexion à
`/admin/login.php` avec `dv_admin`/`admin` fonctionne (redirige vers
`complete-registration`, `firstconnexion=1`).

Le bloc `TRUNCATE configuration; INSERT ...` concaténé par `install` après
la lecture du fichier a le même problème structurel en plus grave : les
colonnes qu'il insère (`_key`, `_value`, `_type`) ne couvrent pas les
colonnes `NOT NULL` sans défaut de l'entité `Configuration` du socle
(`label`, `group_name`, `scope`, `sort_order`) — il aurait de toute façon
échoué même sans le conflit de table. Non rejoué : ces quatre clés
techniques (`PROJECT_NAME`, `LANG`, `__lang`, `JSON_ENCODE_DEPTH`) ne sont
pas nécessaires au fonctionnement observé de l'admin, et la table
`configuration` de ce projet est de toute façon destinée aux paramètres
`scope='business'` du registre applicatif (`AppSetting`, voir
`docs/MODELE_DONNEES.md` section 1), pas à ces clés techniques.

### 3.0.2 `vendor/composer/autoload_classmap.php` peut pointer vers un
autre projet — piège vécu en générant les premières entités `bgfiqueue`

`vendor/` est un dossier hérité tel quel du projet précédent (non
versionné, jamais reconstruit sur ce poste). Son classmap Composer
(`vendor/composer/autoload_classmap.php`) l'est aussi : il contenait 284
entrées `src/atccomplex/...`, un chemin qui n'existe pas dans ce dépôt.
Composer résout une classe par son **nom**, pas par le composant qui la
déclare : la première entité `bgfiqueue` dont le nom existait déjà dans
l'ancien projet (`App_role`, mais `Permission`/`Role_permission`/
`Employee`/`Audit_log`/`Print_job` auraient fait pareil) a été interceptée
par cette entrée périmée avant même d'atteindre le mécanisme de chargement
propre à Devups — `Class 'App_role' does not exist`, malgré un
`schema:update --force` qui venait de réussir et une classe bien présente
sur le disque au bon endroit.

**Symptôme qui trompe** : le message ne pointe jamais vers le fichier
`Entity/App_role.php` réel, mais vers un `include()` échoué sur un chemin
d'un autre projet — se fier au message d'erreur fait chercher au mauvais
endroit.

**Correctif** : `composer dump-autoload` (depuis `backend/`) régénère le
classmap à partir du `composer.json` réel de ce dépôt. À relancer si une
nouvelle entité porte un nom déjà utilisé dans un projet Devups précédent
sur ce poste et que `schema:update --force` réussit sans que la classe ne
soit trouvable ensuite.

### 3.0.3 `core:g:crud` régénérant une entité existante duplique les
`require` de son fichier `<component>.module<module>.php`

Éditer le datacore d'une entité **déjà générée** (ex. ajouter une relation
`manyToOne` à `employee`) puis relancer `core:g:crud` régénère bien
`Entity/<Entity>.php`, mais **ajoute un second bloc de `require`** à la fin
de `<component>.module<module>.php` au lieu de laisser le premier en place
— constaté sur `employee` et `print_job` (P1-01) : le fichier contenait
deux fois `require 'Entity/Employee.php';` (et Form/Datatable/Controller).
`require` (pas `require_once`) sur le même fichier PHP deux fois est un
`Fatal error: Cannot redeclare class Employee` **potentiel**, pas
systématique (dépend de l'ordre de chargement) — mais jamais correct.
**Toujours relire le fichier de module après un `core:g:crud` sur une
entité déjà existante** et supprimer le bloc dupliqué à la main ; les
`require` d'un `FrontController` ajoutés manuellement (voir P1-30) ne sont
pas touchés par cette régénération (`core:g:crud` ne génère jamais de
FrontController, seulement `core:g:frontcontroller`), donc pas à
reproduire dans le bloc conservé.

### 3.0.4 `schema:update --force` peut ne pas appliquer un renommage
d'index sur une contrainte `FOREIGN KEY` ajoutée à une table existante

En ajoutant une relation `manyToOne` à une entité déjà en base (P1-01,
`employee.agency`/`print_job.agency`) : `--dump-sql` proposait `ALTER
TABLE employee DROP FOREIGN KEY FK_...; DROP INDEX fk_...; CREATE INDEX
IDX_...; ALTER TABLE employee ADD CONSTRAINT FK_...`. Lancer `--force`
répond `[OK] Database schema updated successfully!` mais **ne l'applique
pas** : `--dump-sql` relancé propose exactement la même chose, à
l'identique, même après plusieurs `--force` d'affilée. `SHOW CREATE TABLE`
confirme : l'index reste nommé `FK_...` (nom auto-attribué par MySQL à la
création de la contrainte) au lieu du `IDX_...` que Doctrine attend.
**Contournement** : exécuter le SQL proposé par `--dump-sql` directement
en base (`mysql ... -e "..."`), pas via `--force`. Une fois fait,
`--dump-sql` redevient stable. **Mise à jour P1-36 (2026-08-16)** :
l'ancien « bruit permanent connu » des contraintes uniques posées hors
pipeline (`reinstall_constraints.php`) n'existe plus sur ce projet —
voir §3.0.6 ci-dessous. `schema:update --dump-sql` doit désormais
répondre `Nothing to update` sur un dépôt propre ; tout diff qui
apparaît est un vrai diff en attente, plus un bruit à trier.

**Revécu à l'identique en P2-05** (`ticket.preregistration`, nouvelle
relation nullable ajoutée à une table existante) : `--force` répond
« success » mais laisse l'index nommé `fk_...` au lieu de `IDX_...`
attendu par Doctrine, `--dump-sql` propose la même chose en boucle.
Contournement identique : exécuter le SQL proposé directement (ici via
PDO — `mysql` n'est pas dans le `PATH` de cet environnement), pas via
`--force`. Confirme que le piège n'est pas propre à `employee`/
`print_job` : il touche **tout ajout de relation à une table déjà en
base**, quelle que soit l'entité.

**Après avoir généré un nouveau component/module**, deux choses à vérifier
sinon `Fatal error: Class XController not found` :
1. Ajouter `require 'component/ModuleXxx/moduleXxx.php';` dans le
   `requires.php` racine de `src/`.
2. À l'intérieur du module, décommenter la ligne
   `require 'Controller/XxxFrontController.php';` dans son fichier
   `<component>.module<module>.php` une fois le FrontController généré (elle
   existe mais est commentée par défaut).

Après **toute** modification de datacore touchant la structure de la table
(nouvel attribut, nouvelle relation), il faut relancer
`php devups schema:update --force` pour synchroniser la vraie base de
données (Doctrine ORM) — la génération de code seule ne modifie pas la DB.

### 3.0.5 `update()` généré n'écrit rien sur un vrai changement de champ —
utiliser `find($id)`, jamais `new X($id)`, dans `hydrateWithJson()`

Découvert en vérifiant à l'exécution (pas au typecheck) l'écran
`AgencyServicesView.vue` (P1-33) : modifier un champ puis enregistrer
répond `200 OK` avec `{"success":false,"detail":"Empty parameters"}`,
rien n'est écrit en base. **Reproduit à l'identique sur n'importe quelle
entité** — `Agency::update()`, aucun rapport avec l'écran d'origine.

**Cause, établie à l'exécution, pas supposée** (comparer `dvold` dans les
deux cas, voir P1-37) : `Model::hydrate()` (`dclass/devups/model/
Model.php`) pose `$this->dvold = $this` — une **référence au même objet**,
pas un clone. `entityKeyUpdate()` (`dclass/devups/model/ModelTrait.php`,
utilisée par `updateDbal()` pour construire le `SET`) décide quels champs
écrire en comparant `$val != $this->dvold->{$key}` : comme `dvold` EST
`$this`, modifier un champ modifie aussi sa propre valeur "avant" au même
moment (même propriété, même mémoire) — la comparaison ne peut donc
jamais détecter de différence, quel que soit le champ. Vérifiable
directement : `print_r()` sur une entité fraîchement hydratée affiche
`[dvold] => NomEntite Object *RECURSION*` — PHP détecte lui-même que
`dvold` boucle sur son propre conteneur.

**`Entity::find($id)` n'a pas ce problème** : il passe par
`DBAL::__findOneRow()`, qui pose `$flowBD->dvold = clone $flowBD` — un
vrai clone, objet distinct (`spl_object_id()` différent, vérifié). Comme
`find()` fixe déjà `dvfetched = true`, l'appel interne `$object->hydrate()`
que fait `hydrateWithJson()` sur un objet déjà hydraté ressort
immédiatement (`if (!$this->id || $this->dvfetched) return $this;`) sans
jamais toucher à ce `dvold` correct.

**Correctif retenu (arbitrage de l'auteur du framework, pas une décision
de ce projet)** : dans chaque `update($id)` généré, remplacer

```php
$x = $this->hydrateWithJson(new Agency_service($id), $rawdata["agency_service"]);
```

par

```php
$x = $this->hydrateWithJson(Agency_service::find($id), $rawdata["agency_service"]);
```

et refaire un `find($id)` juste avant de construire la réponse, pour
retourner l'objet réellement enregistré plutôt que l'instance en mémoire.
**Ne pas corriger `Model::hydrate()`** (`$this->dvold = clone $this`
aurait aussi marché, testé) : ça touche le cœur du framework, avec un
risque de régression sur tout projet basé sur ce socle. Le correctif dans
le gabarit généré est local à chaque `FrontController`.

**Ce n'est pas corrigé une fois pour toutes.** C'est un défaut du gabarit
de génération de `core:g:crud`, pas du code applicatif : toute
régénération future d'une entité (y compris sur un autre projet Devups)
réintroduit `new X($id)`. Tant que le gabarit lui-même n'est pas corrigé
côté framework, réappliquer ce correctif après chaque `core:g:crud` sur
une entité qui expose un `update()`.

**Coût accepté** : deux requêtes de plus par mise à jour (le `find` avant
hydratation, le `find` avant la réponse) — négligeable au volume d'un
écran référentiel, à reconsidérer seulement si un écran de masse à très
haute fréquence apparaît un jour.

### 3.0.6 Exprimer une contrainte d'unicité dans la cartographie Doctrine
plutôt que hors pipeline — `@UniqueConstraint`/`unique=true`, zéro bruit
`--dump-sql`

Le format datacore JSON n'a **aucune** notion de contrainte unique
(vérifié en lisant tout `dclass/generator/BackendGenerator.php` : le seul
« unique » du fichier est une phrase de commentaire sans rapport,
`@Column(...)` généré ne porte que `name`/`type`/`length`/`nullable`).
Poser une contrainte unique hors pipeline (un script du type
`reinstall_constraints.php`, réappliqué après chaque `--force`) marche,
mais produit un `DROP INDEX` **permanent** dans `--dump-sql` — Doctrine ne
connaît pas cet index, il propose de le supprimer à chaque appel, pour
toujours. Le critère de recette « aucun diff en attente » finit par se
lire « aucun diff au-delà de N lignes connues », ce qui rend un vrai diff
plus facile à manquer.

**La contrainte peut pourtant être exprimée directement dans l'annotation
Doctrine de l'entité, à la main** — deux formes, selon que l'identité de
la table doit changer ou non :

- **Table qui n'est ni exposée en API ni référencée par une clé
  étrangère** : identité composite, `@Id @ManyToOne` sur les colonnes
  concernées, plus de colonne `id` de substitution du tout (P1-32/33/34,
  voir `docs/DECISIONS.md` D-014/D-015).
- **Table exposée (FrontController, écran) où `id` doit rester scalaire**
  (P1-33/36, `agency_service`, `agency`, `service`, `app_role`,
  `permission`, `employee`) :
  ```php
  /**
   * @Entity @Table(name="agency", uniqueConstraints={@UniqueConstraint(name="uniq_agency_code", columns={"code"})})
   */
  ```
  **Nommer la contrainte à l'identique de l'index physique déjà présent
  en base** (celui posé par l'ancien script hors pipeline) : Doctrine
  reconnaît alors l'index existant comme sien, **aucune migration n'est
  nécessaire** — `--dump-sql` cesse simplement de proposer le `DROP
  INDEX` dès l'annotation posée, vérifié à chaque fois (`agency_service`,
  puis les 5 colonnes de code de P1-36). À l'inverse, `@Column(...,
  unique=true)` **sans nom explicite** fait générer par Doctrine son
  propre nom d'index (`UNIQ_...`), différent de l'existant : `--dump-sql`
  propose alors un vrai `DROP INDEX ancien; CREATE UNIQUE INDEX nouveau`
  — pas faux, mais une migration évitable si le nom peut être repris à
  l'identique via `uniqueConstraints`.

**Vérifier que l'`AnnotationDriver` lit bien `uniqueConstraints` avant de
s'engager** (standard Doctrine, mais à re-confirmer sur ce projet la
première fois) :
```php
$em->getClassMetadata('Agency')->table['uniqueConstraints'];
```

**Même piège de docblock que pour l'identité composite (§3.0.4bis /
D-014)** : ne jamais remplacer le docblock `@Entity @Table(...)` par un
commentaire de prose — `AnnotationDriver` ne lit que le docblock
immédiatement adjacent à `class Xxx`. L'explication va dans un
commentaire ordinaire `/* */` séparé, juste avant.

**Coût, pas un détail** : toute entité éditée à la main de cette façon ne
doit plus jamais être régénérée par `core:g:crud`, qui écrase
l'annotation et revient à la forme générée d'origine (vérifié sur
`Role_permission`, P1-32). Sur ce projet, 14 entités sont maintenant dans
ce régime pour cause d'`@UniqueConstraint` : 5 en identité composite
(`role_permission`, `counter_service`, `counter_session_service`,
`ticket_counter`, `document_counter` — P3-01, une relation + deux
colonnes scalaires `doc_type`/`year`, même motif que `ticket_counter`) +
9 sur `id` scalaire (`agency_service`, `agency`, `service`, `app_role`,
`permission`, `employee`, `customer` — P2-01, `fund_origin`/`motive` —
P2-04). S'y ajoutent les entités en édition manuelle pour cause de
`@JoinColumn(nullable=false, ...)` (§3.0.8, motif distinct) —
`third_party` (P2-03), `operation_form` (P2-02, **trois** relations
patchées : `agency`/`service`/`customer` — `identity_document` en a été
retirée en P2-12, D-029 : rendue nullable, obligatoire seulement si
`type=RETRAIT`, ce n'est plus une relation `nullable=false`) et
`preregistration` (P2-05, ses trois relations), plus `identity_document`
elle-même (P2-13 : `expires_at` rendue nullable à la main — aucun
bordereau n'imprime de date d'expiration, pour personne — motif encore
différent, une colonne scalaire ordinaire, pas une relation) au moment
d'écrire ces lignes. Compter ce nombre avant de s'engager sur une
nouvelle table.

**Cas particulier de `customer` (P2-01)** : contrairement aux 6 entités
précédentes (retrofit d'un index physique déjà posé hors pipeline), il
n'y avait ici aucun index préexistant à faire reconnaître — `customer`
est une entité neuve. `--dump-sql` propose donc une vraie
`CREATE UNIQUE INDEX`, appliquée normalement par `--force`. Le bénéfice
du nommage explicite reste réel malgré tout : poser l'annotation dès la
génération évite le détour par un script hors pipeline que les 6 autres
ont dû défaire après coup.

**Résultat sur ce projet (P1-33 + P1-36, 2026-08-16)** : `reinstall_
constraints.php` n'a plus aucune contrainte à porter — supprimé.
`schema:update --dump-sql` répond `Nothing to update - your database is
already in sync`, zéro ligne de bruit. « Aucun diff en attente » est
redevenu un vrai critère de recette.

### 3.0.7 Poser une identité composite — marche à suivre complète

Validé sur ce projet (D-014/D-015, P1-32 à P1-34 ; D-025, P3-01) :
`role_permission`, `counter_service`, `counter_session_service` (deux
relations), `ticket_counter` (deux relations + une colonne scalaire) et
`document_counter` (une relation + deux colonnes scalaires `doc_type`/
`year` — portée annuelle plutôt que quotidienne, sinon même motif).
Réutilisable tel quel sur toute future table de liaison où l'`id` de
substitution ne
sert à rien.

**Quand l'utiliser plutôt que `@UniqueConstraint`** (§3.0.6) : la table
n'est **ni exposée par une `FrontController` avec un écran qui en
dépend, ni référencée par une clé étrangère depuis une autre table**. Si
l'un des deux est vrai, `@UniqueConstraint` est le bon choix — une
identité composite y romprait tout code qui suppose un `id` scalaire
(`CrudManager.vue` en particulier, voir D-014). Vérifier les DEUX
conditions avant de choisir, pas seulement l'exposition API — c'est ce
qui a débloqué `agency_service` en P1-33 après un premier essai raté.

**1. Annotations de l'entité** (`Entity/<Nom>.php`, édité à la main,
jamais généré) :

```php
<?php
    /*
     * Commentaire explicatif ORDINAIRE (delimiteurs slash-etoile ...
     * etoile-slash), JAMAIS un docblock (double etoile) - AnnotationDriver
     * ne lit que le docblock immediatement adjacent a `class Xxx`. Un
     * docblock de prose intercale fait echouer TOUTE lecture de
     * metadonnees ("Class ... is not a valid entity or mapped super
     * class"), vecu en P1-32.
     */
    /**
     * @Entity @Table(name="ma_table")
     */
    class Ma_table extends Model implements JsonSerializable {

        /**
         * @Id @ManyToOne(targetEntity="\Relation1")
         * @JoinColumn(onDelete="cascade")
         * @var \Relation1
         */
        public $relation1;

        /**
         * @Id @ManyToOne(targetEntity="\Relation2")
         * @JoinColumn(onDelete="cascade")
         * @var \Relation2
         */
        public $relation2;

        // Colonne scalaire dans l'identité (ticket_counter) : possible,
        // vérifié en P1-34 - mélanger relations et scalaire fonctionne.
        // /**
        //  * @Id @Column(name="ma_date", type="date")
        //  * @var date
        //  */
        // protected $ma_date;

        // PAS de colonne `id` du tout - ni @Id @GeneratedValue, ni
        // propriété $id.

        public function __construct() {
            $this->relation1 = new Relation1();
            $this->relation2 = new Relation2();
        }

        // getId() DOIT être surchargée - Model::getId() de base fait
        // `return $this->id`, qui n'a plus de sens ici.
        public function getId() {
            $r1 = $this->relation1 instanceof Relation1 ? $this->relation1->getId() : null;
            $r2 = $this->relation2 instanceof Relation2 ? $this->relation2->getId() : null;
            if (!$r1 || !$r2) {
                return null; // tant que l'identité n'est pas resolue - c'est ce null qui distingue insertion/mise a jour
            }
            return ['relation1' => $r1, 'relation2' => $r2];
        }

        public function jsonSerialize() {
            return [...$this->dvserialised];
        }
    }
```

**2. Migration** :
1. `php devups schema:update --dump-sql` — vérifier la séquence proposée
   AVANT `--force`. Si la table existe déjà avec un `id` AUTO_INCREMENT
   qui est la clé primaire, la séquence attendue (produite correctement
   par Doctrine à chaque essai fait sur ce projet, aucun réordonnancement
   manuel n'a jamais été nécessaire) est : `MODIFY id INT NOT NULL`
   (retire `AUTO_INCREMENT`) → `DROP PRIMARY KEY` → `DROP id, CHANGE
   ... NOT NULL` (colonnes de la future identité passées `NOT NULL`) →
   `ADD PRIMARY KEY (col1, col2, ...)`.
2. Si la séquence proposée est différente ou dans un autre ordre, ne pas
   supposer qu'elle est fausse — mais la vérifier sur une base de test
   avant de l'appliquer en production.
3. `php devups schema:update --force`.
4. `SHOW CREATE TABLE ma_table` — confirmer l'absence de colonne `id`, la
   `PRIMARY KEY` composite, les données existantes intactes (`SELECT
   COUNT(*)` avant/après identique).

**3. Datacore** : le fichier `<entity>Core.json` n'a pas besoin de
changer (le format n'a aucune notion d'identité composite, l'annotation
seule porte l'information) — mais **ne jamais relancer `core:g:crud`**
sur cette entité ensuite : le générateur écrase la forme composite et
revient à un `id` `AUTO_INCREMENT` classique. `php devups dvups_:update`
reste nécessaire si l'entité est nouvelle (jamais si elle existait déjà).

**4. Si une `FrontController` existe** : retirer `detail($id)`/
`update($id)`/`delete($id)` (sans objet — aucun `id` scalaire à
chercher), garder `index()`/`create()` (ne dépendent d'aucun `id`).

**5. Vérifications avant de considérer la tâche terminée** :
- `$em->getClassMetadata('Ma_table')->isIdentifierComposite` vaut `true`.
- Un doublon est rejeté par la base (`INSERT` direct, `SQLSTATE[23000]
  ... Duplicate entry ... for key 'PRIMARY'`).
- `<Entite>::initQb()->lazyloading()` retourne `success:true` avec les
  bonnes données — c'est le seul consommateur générique qui compte
  (`Datatable.php`, le back-office `.blade`, n'a aucune incidence sur le
  fonctionnement de l'application, hors périmètre de validation).
- `schema:update --dump-sql` ne propose plus de supprimer la nouvelle
  clé composite.

### 3.0.8 Le `"nullable":"not"` d'une **relation** dans le datacore est
silencieusement ignoré — la colonne physique reste NULLable — trouvé en
construisant `third_party` (P2-03)

Vérifié dans `dclass/generator/BackendGenerator.php` (les deux points où
`@JoinColumn` est généré, lignes ~193 et ~218) : le gabarit interpole
`$relation->ondelete`, **jamais `$relation->nullable`**. Résultat : que le
datacore déclare `"nullable":"not"` ou `"nullable":"default"` sur une
relation `manyToOne`, l'annotation générée est toujours
`@JoinColumn(onDelete="cascade")` sans `nullable=...` — et Doctrine
applique alors son propre défaut, `nullable=true`. La colonne FK est donc
**physiquement NULLable dans tous les cas**, quoi que le datacore
prétende.

**Portée** : ce n'est pas spécifique à `third_party` ni à ce module — tout
relation `manyToOne` générée sur ce projet avec `"nullable":"not"` porte le
même écart entre l'intention du datacore et la contrainte réellement posée
en base (`ticket.agency`/`ticket.service`, `ticket_counter.agency`/
`.service`, `agency_service.agency`/`.service`, etc. — vérifié sur
`Ticket.php`, même absence de `nullable=false`). L'intégrité n'a donc
jamais reposé que sur la validation applicative pour ces relations, pas
sur une contrainte de base — jusqu'ici non détecté parce qu'aucun test de
ce projet n'insère directement en base sans passer par le contrôleur qui
valide déjà la présence de la relation.

**Correctif pour une relation réellement obligatoire** : ajouter
`nullable=false` à la main dans l'annotation `@JoinColumn` après
génération :
```php
/**
 * @ManyToOne(targetEntity="\Identity_document")
 * @JoinColumn(nullable=false, onDelete="cascade")
 * @var \Identity_document
 */
public $identity_document;
```
Comme pour `@UniqueConstraint` (§3.0.6), ceci **écrase au prochain
`core:g:crud`** : l'entité passe en édition manuelle permanente. Appliqué
sur `third_party` (P2-03) ; à appliquer de la même façon sur
`operation_form`/`preregistration` (P2-02/P2-05) pour leurs relations
non-nullables. **Décision de portée pour ce projet** : le correctif n'a
été posé que sur les entités neuves de P2-A, pas rétroactivement sur les
relations pré-existantes de P0/P1 (`ticket.agency`, etc.) — remédiation
plus large signalée mais non entreprise ici, hors périmètre de P2-A.

### 3.1 Créer un NOUVEAU MODULE à la main (sans passer par le générateur) —
piège vécu en créant `ModuleTask`

`php devups schema:update` (voir `dclass/generator/builder.php`) shell
littéralement `bin\doctrine orm:schema:update`. Doctrine ne scanne PAS
bêtement tout `src/` à la recherche de `@Entity` — la liste des dossiers à
scanner est reconstruite dynamiquement à chaque appel par
`DBAL::getEntityManager()` (`dclass/devups/Tchutte/DBAL.php`), qui appelle
`Core::buildOriginCore()` (`dclass/devups/Core.php`). Cette fonction :
1. Parcourt chaque dossier de `src/` et exige `src/<component>/<component>Core.json`
   pour même considérer le component (sinon `findprojectcore()` retourne `[]`).
2. **Reconstruit `listmodule` en scannant les SOUS-DOSSIERS** du component et
   en exigeant, pour CHACUN, un fichier `src/<component>/<Module>/<module minuscule>Core.json`
   (`findmodulecore()`) — **le contenu de `listmodule` déjà présent dans le
   fichier `<component>Core.json` est ignoré/écrasé**, éditer ce tableau à la
   main ne sert donc à RIEN.
3. Pour chaque module ainsi trouvé, ajoute son dossier `Entity/` en entier
   aux chemins scannés par Doctrine — **peu importe le contenu de
   `Core/<Entity>Core.json`** (ces fichiers individuels ne servent qu'à une
   collection annexe `$enittycollection`, pas au scan Doctrine lui-même).

**Conséquence pratique** : pour qu'un nouveau module (ex: `ModuleTask`) et
ses entités (`Task.php`, `Report.php`) soient repérés par
`schema:update`, il faut ET IL SUFFIT de créer
**`src/toolrad/ModuleTask/moduletaskCore.json`** avec au minimum
`{"name":"ModuleTask", ...}` — sans ce fichier, `schema:update --dump-sql`
ne montre AUCUN diff pour les nouvelles tables, silencieusement (pas
d'erreur, juste rien dans le SQL généré). Une fois ce fichier présent, TOUT
`.php` annoté `@Entity` dans `Entity/` du module est automatiquement pris
en compte, qu'il ait un `Core/<Entity>Core.json` individuel ou non.

Séparément, pour que le NOM DE ROUTE (`/task`, `/report`) soit reconnu par
`route/WebService.php` (dispatch REST via `$public_access`, voir section
4.2), il faut aussi enregistrer chaque entité dans `$public_access` — ça,
c'est indépendant du mécanisme Doctrine ci-dessus (voir section 4.2).

`config/dvups_configurations.php` et `config/module_configurations.php`
(les tableaux PHP générés avec les clés `path/module/name/namespace/
component`) sont utilisés par l'ADMIN PANEL (vues Blade, formulaires
auto-générés) — PAS par `schema:update`. Les éditer n'est nécessaire que
si on veut que l'entité apparaisse correctement dans l'admin, pas pour que
l'API REST fonctionne.

## 4. Deux styles d'accès API — NE PAS LES CONFONDRE

### 4.1 Style générique legacy — `action/entité`

Piloté par `route/WebService.php` (méthode `systemServe()`) + les méthodes
`createCore/updateCore/deleteCore/detailCore/ll()` de
`dclass/devups/Controller/FrontController.php`. L'entité doit être listée
dans `$public_access` de `WebService.php` (`'task' => \Task::class`) sinon :
`Fatal error: entity task has no public access`.

| Action | Route | Verbe observé dans nos tests ToolRad |
|---|---|---|
| Create | `/create/<entity>` | POST |
| Detail | `/detail/<entity>?id=X` | GET |
| List (lazyloading) | `/lazyloading/<entity>?dfilters=on&project.id:eq=X` | GET |
| Update | `/update/<entity>?id=X` | **POST fonctionnait sur ToolRad** (la doc officielle montre PUT, mais le Router de ToolRad semblait tolérant sur ce point pour ce style de route — vérifié empiriquement sur ce projet-là ; à revérifier avant de s'y fier ici) |
| Delete | `/delete/<entity>?id=X` | **GET fonctionnait sur ToolRad** (la doc officielle montre DELETE — même remarque que ci-dessus) |

C'est le style utilisé par ToolRad pour **Project, Component, Module,
Entity** (voir `EditorView.vue` : `apiClient.post('/update/entity?id=X', ...)`,
`apiClient.get('/delete/entity?id=X')` — ça fonctionne, ne pas y toucher sans
raison).

**PIÈGE CRITIQUE #1 : `?project_id=X` sur `/lazyloading/<entity>` NE FILTRE
PAS réellement par projet.** Vérifié empiriquement : `GET
/lazyloading/entity?project_id=1` renvoyait `nb_element: 1021` (le total de
TOUTE la table entity, tous projets confondus, dans cette base de dev
partagée), alors que le projet 1 n'a en réalité que 21 entités. Le bon
filtre relationnel est **`?dfilters=on&project.id:eq=X`** (confirmé : donne
`nb_element: 21`, identique au nombre d'entités réellement imbriquées dans
`GET /project/1`). Ne jamais utiliser `?project_id=X` pour filtrer une liste
par projet sur ce style de route — ça semble marcher (200, success:true,
des résultats reviennent) mais silencieusement renvoie tout le monde.

**PIÈGE CRITIQUE #2 : `/lazyloading/<entity>` est paginé côté serveur,
silencieusement**, même une fois le bon filtre (`dfilters`) en place. Par
défaut `per_page = 30` (`dclass/devups/Datatable/Lazyloading.php`). La
réponse contient `nb_element` (total réel du filtre appliqué), `per_page`,
`current_page`, `next`, `remain` (1 tant qu'il reste des pages, 0 sur la
dernière). **Les paramètres de query pour changer de page sont
`?next=<page>&per_page=<taille>`** (PAS `?page=`, `?current_page=`, `?p=`,
etc. — testés et non fonctionnels). Tout code qui appelle
`/lazyloading/<entity>` sans boucler sur `remain`/`next` jusqu'à épuisement
affiche silencieusement une liste incomplète dès que le résultat filtré
dépasse 30 lignes. Pattern de boucle complet (combine les 2 pièges) :
```js
let all = [], page = 1, remain = true
while (remain) {
  const res = await apiClient.get(
    `/lazyloading/xxx?dfilters=on&project.id:eq=${projectId}&next=${page}&per_page=100`
  )
  all = all.concat(res.data.listEntity || [])
  remain = !!res.data.remain
  page = res.data.next
}
```
Ces 2 pièges ont été découverts en vérifiant la feature d'import d'entités
et le dashboard (v8/v9, 2026-07-04) : une conclusion erronée a d'abord été
tirée ("l'API `/entity/importData` ne persiste rien") en cherchant les
entités de test dans le résultat non filtré de `?project_id=1` puis en le
croyant tronqué à tort — en réalité les entités de test étaient bien créées
ET correctement associées au projet 1 ; c'est `?project_id=X` lui-même qui
ne filtrait rien. Voir `frontend2/aiMemory/memory_2026-07-04_v9.txt` et
`v10.txt` pour le détail complet de cette double correction.

Payload générique : `{"<entity>": { "champ": "valeur", "relation.id": 3 }}`
(remarquez le point pour référencer une relation ManyToOne : `project.id`).

**Upload de fichier** (attribut `formtype: "image"` ou autre binaire) : il
faut passer en `multipart/form-data` avec des clés `<entity>_form[champ]`
au lieu du JSON brut `{"<entity>": {...}}`. Exemple concret vu dans la doc :
`project_form[name]`, `project_form[description]`, `project_form[logo]`
(fichier).

### 4.2 Style REST dédié — FrontController par entité

Généré via `php devups core:g:frontcontroller`. Classe annotée
`@Api(name='/xxx')`, méthodes annotées `@GET` / `@POST` / `@GET(path='/:id')`
/ `@PUT(path='/:id')` / `@DELETE(path='/:id')`. **CE STYLE EST STRICT SUR LE
VERBE HTTP** (une méthode GET annotée renvoie 405 si on appelle en POST) —
confirmé dans la doc ET par la correction de l'utilisateur sur Feature/
Workflow, sur ToolRad.

Convention REST standard générée par le CLI :

| Méthode PHP | Annotation | Route | Usage |
|---|---|---|---|
| `index()` | `@GET` | `GET /xxx` | liste (via `Entity::initQb()->lazyloading()`) |
| `create()` | `@POST` | `POST /xxx` | création, body `{"xxx": {...}}` |
| `detail($id)` | `@GET(path='/:id')` | `GET /xxx/:id` | détail (id dans le **path**, pas en query param) |
| `update($id)` | `@PUT(path='/:id')` | `PUT /xxx/:id` | mise à jour, body `{"xxx": {...}}` |
| `delete($id)` | `@DELETE(path='/:id')` | `DELETE /xxx/:id` | suppression |

C'est le style utilisé par ToolRad pour **Feature et Workflow**
(`FeatureFrontController.php`, `WorkflowFrontController.php` — voir
`backend/src/toolrad/ModuleProject/Controller/`). Côté frontend
(`EditorView.vue`), on utilise bien `apiClient.post('/feature', ...)`,
`apiClient.put('/feature/:id', ...)`, `apiClient.delete('/feature/:id')`.

**Filtrage de la liste** pour ce style : `GET /xxx?dfilters=on&champ:op=valeur`
Exemple confirmé par l'utilisateur : `GET /feature?dfilters=on&project.id:eq=1`
(`dfilters=on` active le système de filtres dynamiques ; `project.id:eq=1`
filtre sur la relation `project`, opérateur `eq`). Ne pas utiliser le
`?project_id=X` du style legacy ici, ça ne s'applique qu'à
`/lazyloading/<entity>`.

**Liste complète des opérateurs `op` supportés** (voir
`filterswicher()` dans `dclass/devups/Datatable/Lazyloading.php`) :
`eq` (=), `neq` (!=), `oreq` (OR =), `gt` (>), `lt` (<), `get` (>=),
`let` (<=), `lk`/`lkr`/`lkl` (LIKE, variantes préfixe/suffixe), `in`,
`notin`, `empty`, `isNull`, `notNull`, et **`btw`** pour un intervalle
(`champ:btw=DEBUT_FIN`, les deux bornes jointes par un `_`, ex:
`created_at:btw=2026-07-01_2026-07-31` → `BETWEEN '2026-07-01' AND
'2026-07-31'`). **`gte`/`lte` N'EXISTENT PAS** — un opérateur non
reconnu tombe silencieusement dans le `default` du switch (`LIKE`), ce
qui ne fait planter rien mais renvoie un résultat vide ou faux sans
avertissement (vérifié : `created_at:gte=X` génère
`created_at LIKE '%X%'`, pas une vraie comparaison). Toujours utiliser
`get`/`let`/`btw` pour des comparaisons numériques/dates.

**Actions personnalisées au-delà du CRUD sur un FrontController — NE
FONCTIONNE PAS comme la doc officielle/le tutoriel vidéo le suggère.**
Un `@GET(path='/hello')` sur une méthode `hello()` de `TaskFrontController`
n'est **jamais lu** par le dispatch réel (`Request::FrontController()` dans
`dclass/devups/Http/Router.php`) : celui-ci route uniquement sur le
**nombre de segments d'URL** après le nom d'entité, pas sur le texte de
l'annotation :
  - 1 segment (`/xxx`) → `index()` (GET) ou `create()` (POST)
  - 2 segments (`/xxx/42`) → toujours `detail()`/`update()`/`delete()`
    selon le verbe HTTP, **jamais** une autre méthode même si son nom
    correspond au 2e segment (vérifié empiriquement : `/invitation/bytoken`
    avec une vraie méthode `byToken()` définie échoue quand même)
  - 3+ segments (`/xxx/42/accept`) → est aussi possible
    `"Request method X not allowed for this route"` (404), point final.
**Pattern qui fonctionne réellement, vérifié sur la feature invitations**
(voir `backend/aiMemory/memory_2026-07-05_v3.txt` section 2) : exposer
l'action comme une méthode `xxxServe()` directement sur `route/WebService.php`,
exactement comme `helloServe()`/`cacheServe()` déjà présents (URL à 1 seul
segment ne matchant aucune clé de `$public_access`, ex:
`POST /api/accept-invitation?id=5` → `acceptInvitationServe()`). À
l'intérieur, instancier le FrontController manuellement et appeler sa
méthode métier directement (bypass total du système d'annotations) ; le
JWT est déjà décodé et `Auth::$user_id` déjà peuplé à ce stade (fait une
fois pour toutes en tête de `runServe()`), donc il suffit de vérifier
`if (!Auth::$user_id) { ...401... }` à la main pour répliquer
`@Auth(authorized=1)`. Les identifiants/paramètres (id, token, etc.)
passent en query string, pas en segment de chemin.

## 5. Charger les relations imbriquées dans une liste

Par défaut, une relation ManyToOne ne renvoie que l'`id` de l'entité liée.
Deux façons de charger plus de champs :

1. Dans le FrontController, chaîner `.with('relationName', ['champ1','champ2'])`
   avant `.lazyloading()` :
   ```php
   return Task::initQb()->with('project', ['name','description','logo'])->lazyloading();
   ```
2. Sans toucher au code : header HTTP `columns` avec un JSON
   `{"project":["name","description"]}` **et** le paramètre `?dfilters=on`
   dans l'URL. Utile pour du debug/exploration rapide sans redéployer.

## 6. Champs automatiques

Chaque entité mappée reçoit automatiquement `created_at`, `updated_at`,
`deleted_at` (soft delete probable vu `deleted_at`). Les migrations SQL
générées par `schema:update` sont archivées dans `database/migration/`
(utile pour rejouer en prod).

## 7. Ressources CMS réutilisables (`devupscms`)

Le component `devupscms` fournit des modules prêts à l'emploi plutôt que de
réinventer des systèmes similaires à la main :
- **ModuleData** → gestion de **Status** génériques (couleur, position, clé,
  label, rattachés à une entité cible) — pour des statuts type "en attente /
  en cours / terminé / annulé" sans coder de table dédiée.
- **ModuleTree** → catégories hiérarchiques ("tree item").
- **ModuleGallery**, **ModuleLocation**, **ModuleNotification**,
  **ModulePushNotification**, **ModuleReporting** → idem, à vérifier avant
  de recoder l'équivalent pour BGFIBank Queue si un besoin similaire se
  présente (ex: notifications, reporting/dashboard).

## 8. Repères spécifiques à CE projet (BGFIBank Queue)

- Backend tourne sur `http://127.0.0.1:3322/api` (voir
  `backend/config/constante.php`, `__server`/`__env_port`).
- CORS : `__csrf_origin` dans `backend/config/constante.php` whitelist
  `localhost:8081`, `localhost:5173`, `localhost:3000`, `null`,
  `https://localhost` — **et `localhost:5174`**, ajouté en session (P0-10)
  parce que le port 5173 par défaut de Vite était déjà occupé par un autre
  projet sans rapport sur ce poste de développement. Si `yarn dev` choisit
  un autre port que celui déjà listé, l'ajouter ici : sinon l'appel de
  connexion échoue par `CORS policy: No 'Access-Control-Allow-Origin'`,
  sans message clair côté frontend au-delà de « Failed to fetch ».
- `user` (socle `devupsfront`) est la seule entité exposée dans
  `$public_access` à l'issue de P0 (voir CLAUDE.md §3.2 pour la distinction
  style REST dédié / style générique). `app_role`, `permission`,
  `role_permission`, `employee`, `audit_log`, `print_job` (`ModuleCore`,
  P0-07) existent en base mais ne sont **pas encore** exposées — c'est
  attendu, pas un oubli : leur exposition viendra avec les tâches P1 qui en
  ont réellement besoin (référentiel, personnel), en style REST dédié
  (§4.2), jamais générique pour ces entités-là.
- `configuration` (socle `devups/ModuleConfig`, pas une entité `bgfiqueue`)
  porte le registre de paramètres. Son `ConfigurationFrontController` existe
  déjà (généré avec le socle), mais **n'est pas non plus exposée** dans
  `$public_access` à l'issue de P0 — vérifié empiriquement (`GET
  /api/configuration` répond la même erreur générique qu'une entité
  inconnue). Colonne `scope` : `business` pour tout paramètre destiné à
  `administration/`, `technical` sinon. Voir `docs/DECISIONS.md` D-007.
