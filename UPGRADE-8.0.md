# Guide de migration vers Devups 8.0

Ce fichier documente, lot par lot, chaque rupture introduite par le
chantier v8.0 (voir TODO.md, section "Chantier v8.0") : ce qui change,
comment migrer un projet existant, et la commande de migration
automatique quand il en existe une.

## LOT 1 - Socle

### PHP 8.3 minimum

**Avant** : `composer.json` exigeait `"php": ">=8.2"`.

**Apres** : `"php": ">=8.3"`. La CI GitHub (workflow `tests`) tourne
desormais sur la matrice PHP 8.3/8.4/8.5 (8.2 retire).

**Comment migrer** : mettre a jour l'interpreteur PHP du projet vers
8.3 ou plus avant de passer a cette version de Devups. Aucun
changement de code applicatif requis pour ce point seul.

### Doctrine ORM 3 (DBAL 4)

**Avant** : `doctrine/orm` ^2.1, mapping par annotations docblock
(`@Entity`, `@Column`, ...), `Doctrine\ORM\Tools\Setup::
createAnnotationMetadataConfiguration()`, `EntityManager::create()`
(methode statique).

**Apres** : `doctrine/orm` ^3.7 (`doctrine/dbal` suit en ^4, transitif).
Mapping par attributs PHP 8 (`#[ORM\Entity]`, `#[ORM\Column]`, ...),
configuration via `ORMSetup::createAttributeMetadataConfig()`,
construction explicite `new EntityManager(DriverManager::
getConnection($conn, $config), $config)`.

Consequences pour un projet qui etendait directement l'API Doctrine du
framework (rare - l'ORM d'execution de Devups reste Tchutte, Doctrine
n'est utilise que pour les metadonnees/schema) :

- `doctrine/cache` et `doctrine/annotations` ne sont plus des
  dependances (retires de `composer.json`) - Doctrine ORM 3 n'en a
  plus besoin pour ses propres caches de metadonnees (PSR-6 natif).
  `symfony/cache` est ajoute a la place (requis par
  `ORMSetup::createAttributeMetadataConfig()` pour son cache de
  developpement).
- `bin/doctrine` (et `.php`/`.bat`) : doctrine/orm 3 ne fournit plus de
  binaire console pre-construit (l'ancien `vendor/doctrine/orm/bin/
  doctrine` a disparu). Ces fichiers, versionnes dans le depot comme
  repli de `dv_doctrine_command()` (voir `dclass/generator/
  builder.php`), ont ete reecrits : ils construisent eux-memes
  l'application console via `ConsoleRunner::run()` +
  `SingleManagerProvider`. `cli-config.php` retourne desormais ce
  provider (et non plus un `HelperSet`, API supprimee).
- Toute commande CLI custom basee sur `ConsoleRunner::
  createHelperSet()` doit migrer vers `EntityManagerProvider` (voir
  `cli-config.php`).

**Comment migrer** : executer `composer update` (le `composer.json`
fourni epingle les bonnes versions). Aucune action manuelle requise
pour un projet qui ne touche pas directement l'API Doctrine - toute la
lecture de metadonnees interne au framework (DBAL, Model, Controller,
generateur) continue de fonctionner a l'identique (les objets
`FieldMapping`/`AssociationMapping` de l'ORM 3 restent accessibles par
cle de tableau, compatibilite ArrayAccess conservee par Doctrine).

### Entites : annotations -> attributs

**Avant** : les entites Doctrine (`src/*/Entity/*.php`, classes de base
`dclass/extends/*Core*.php`/`Dvups_migration.php`) declaraient leur
mapping via des annotations dans un docblock :

```php
/**
 * @Entity @Table(name="status", uniqueConstraints={
 *     @UniqueConstraint(name="uq_status_entity_key", columns={"entity", "_key"})
 * })
 * */
class Status extends Model
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     * */
    protected $id;
}
```

**Apres** : le meme mapping s'exprime en attributs PHP 8 (le docblock,
lui, ne garde que le texte libre/`@var` restant) :

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "status", uniqueConstraints: [new ORM\UniqueConstraint(name: "uq_status_entity_key", columns: ["entity", "_key"])])]
class Status extends Model
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected $id;
}
```

Toutes les entites du framework (41 fichiers : `src/**/Entity/*.php`,
`dclass/extends/{Dv_langCore,Dvups_config_item,Dvups_migration,
ImageCore,UserCore}.php`, trait `dclass/devups/model/DateTimeTrait.php`)
ont ete converties. **Aucun changement de schema** : un test
d'integration dedie (`tests/Integration/
TreeStatusUniqueConstraintAttributesTest::
testSchemaUpdateReportsNoPendingDifference`) verifie que
`php devups schema:update --dump-sql` reste vide sur une base
installee en 7.1.

Piege rencontre et corrige pendant la conversion : une entite qui
redeclarait elle-meme une propriete deja fournie par sa classe parente
(`MappedSuperclass` - ex. `$id` sur `Dvups_entity`/`User`/
`Tree_item_image`/`Cmsresource`, ou `created_at`/`updated_at`/
`deleted_at` herites via `DateTimeTrait` sur les 3 `MappedSuperclass`
qui l'utilisent) etait tolere par l'ancien `AnnotationDriver` mais leve
desormais une `MappingException: Duplicate definition of column` sous
`AttributeDriver` (Doctrine ORM 3). Corrige en supprimant la
redeclaration redondante dans les entites filles, et en redeclarant
explicitement (jamais via un simple `use` du meme trait, insuffisant -
verifie) `created_at`/`updated_at`/`deleted_at` sur les 3
`MappedSuperclass` concernees (`Dvups_config_item`, `ImageCore`,
`UserCore`) pour que Doctrine retrouve une classe declarante coherente
entre l'heritage de mapped-superclass et le scan direct de l'entite
fille.

**Comment migrer un projet utilisateur** : la commande

```bash
php devups upgrade:entities [--dry-run] [chemin]
```

convertit automatiquement tous les fichiers PHP d'un repertoire
(`src/` par defaut) en appliquant le MEME convertisseur
(`dclass/generator/EntityAttributeConverter.php`) que celui utilise
pour migrer le framework lui-meme. `--dry-run` liste les fichiers qui
seraient modifies sans rien ecrire. La commande est idempotente (un
second passage sur des fichiers deja en attributs ne modifie rien) et
preserve le reste du docblock (texte libre, `@var`, `@param`) ainsi
que tout le code autour.

Formes reconnues par le convertisseur (voir TODO.md ETAPE 1.0 pour
l'inventaire complet) : `@Entity @Table(name=...[, uniqueConstraints=
{...}])`, `@MappedSuperclass`, `@Id @GeneratedValue
@Column(type="integer")`, `@Column(...)` (name/type/length/nullable/
options), `@Id @ManyToOne(...)`, `@ManyToOne(...)`, `@JoinColumn(...)`.
Une annotation Doctrine non reconnue (forme non rencontree dans
Devups - `@OneToMany`, `@ManyToMany`, `@Embeddable`, ...) est laissee
telle quelle dans le docblock, jamais supprimee silencieusement -
a convertir a la main si rencontree, puis verifier avec
`php devups schema:update --dump-sql` (doit rester vide).

### Generateur (entites generees)

`dclass/generator/BackendGenerator.php` (cree une entite/une entite de
traduction a partir d'un datacore) emet desormais directement des
attributs PHP 8 pour toute NOUVELLE entite generee - aucune action
requise, mais un composant genere avant ce chantier et jamais
regenere depuis reste en annotations tant qu'il n'est pas repasse par
`upgrade:entities` ou regenere.

## LOT 2 - Vues

### Moteur de vues : philo/laravel-blade -> illuminate/view directement

**Avant** : `philo/laravel-blade` (`3.*`) assemblait un moteur Blade
autonome (hors application Laravel) en epinglant `illuminate/view` a
`~5` - version abandonnee depuis 2017, source de l'exception
`composer audit` `PKSA-h74q-2n9z-j6wq` (CVE-2021-43808, XSS sur
`@parent`), impossible a lever tant que `philo/laravel-blade` (lui
aussi abandonne) restait dans l'arbre des dependances.

**Apres** : `philo/laravel-blade` est retire. Le meme assemblage
(Container/Filesystem/Dispatcher/EngineResolver/FileViewFinder/Factory,
moteurs `php` et `blade`, directive personnalisee `@tt(...)`) est
desormais construit directement sur `illuminate/view` ^11 (+
`illuminate/events` ^11) par une nouvelle classe
`dclass/devups/View.php` (`\Devups\View`), point d'entree UNIQUE du
moteur de vues dans le framework. `BladeD` (`dclass/devups/Genesis.php`,
utilisee par `Genesis`, `Controller` et `Http\Request`) devient un
simple alias `class BladeD extends \Devups\View {}` : meme nom, meme
API publique (`->view()->make($view, $data)->render()`), aucun
changement d'appel necessaire cote framework ni cote projet utilisateur
qui passerait par `Genesis::render()`/`renderView()`/`getView()`/
`renderBladeView()`.

Consequence directe : l'exception `composer audit`
`PKSA-h74q-2n9z-j6wq` est retiree de `composer.json` - `illuminate/view`
^11 est une version maintenue, le correctif du CVE y est inclus depuis
longtemps.

**Comment migrer** : executer `composer update`. Aucune action requise
pour un projet qui rendait ses vues via `Genesis::render()`/
`renderView()`/`getView()`/`renderBladeView()` ou via `Controller`/
`Http\Request` (seul point d'entree possible dans ce framework - aucune
instanciation directe de `Philo\Blade\Blade` n'existe ailleurs dans le
depot). Un projet qui instancierait directement
`Philo\Blade\Blade`/`BladeD` avec des options avancees non couvertes
par `\Devups\View` (rare) devra adapter son code a l'API de
`\Devups\View` (constructeur identique : `$viewPaths, $cachePath,
?Dispatcher $events = null`).

Verifie par : la suite de tests complete (toutes les vues Blade du
depot sont exercees via `AdminScreensTest` - chaque entite declaree
dans `config/dvups_configurations.php`, page liste + formulaire - ainsi
que `BladeViewCacheAutoCreateTest`, `CountryCreateViaFormTest`,
`CmscontentListWithTranslatedTitleTest`, `CountryListWithDataTest`,
`LegacyServicesFormEndpointsTest`), toutes vertes a l'identique avant/
apres ce changement.

## LOT 3 - Routes

### Annotations de routage : docblocks + bibliotheque vendoree -> attributs PHP 8 natifs

**Avant** : le routage par annotations (`@Api`, `@GET`, `@POST`, `@PUT`,
`@DELETE`, `@Auth`, `@MethodView`, lues par `Router`/`Request` via
`hasAnnotation()`/`getAnnotation()`) s'appuyait sur deux choses
distinctes :
- `dclass/lib/addendum/` : une copie en arbre (pas Composer) de la
  bibliotheque "Addendum PHP Reflection Annotations" (Jan Suchal,
  2006), qui parse les docblocks a l'execution pour simuler des
  annotations avant l'existence des attributs natifs PHP.
- `maslosoft/addendum` (`composer.json`, `require`) : un paquet
  Composer homonyme mais totalement independant et non utilise (verifie
  par recherche exhaustive dans le depot) - seule la bibliotheque
  vendoree ci-dessus faisait le travail reel.

Les deux etaient devenus inutiles face aux attributs natifs disponibles
depuis PHP 8.0, et `dclass/lib/addendum/` en particulier n'est plus
maintenue depuis des annees.

**Apres** : `dclass/lib/addendum/` est retiree, `maslosoft/addendum`
est retire de `composer.json`. Les 7 classes d'annotation
(`dclass/lib/annotation/Api.php`, `GET.php`, `POST.php`, `PUT.php`,
`DELETE.php`, `MethodView.php`, `Auth.php`, plus `ApiLazyloading.php`
et `DDoc.php` sans usage reel) deviennent des attributs PHP 8 natifs
(`#[\Attribute(...)]`), avec un constructeur explicite assignant
chaque propriete (au lieu de l'ancienne hydratation par tableau de
`Annotation::__construct()`). `hasAnnotation()`/`getAnnotation()` sont
preserves a l'identique via une nouvelle classe
`dclass/lib/ReflectionAnnotated.php` (`ReflectionAnnotatedClass`/
`ReflectionAnnotatedMethod`, sous-classes de `ReflectionClass`/
`ReflectionMethod` s'appuyant sur `getAttributes()->newInstance()`) -
meme pattern "garder l'API d'appel, remplacer l'implementation" que
`BladeD`/`\Devups\View` au LOT 2.

Exemple (`route/Admin.php`) :

```php
// avant
/**
 * @MethodView(GET="hello", Auth=1)
 */
public function helloService() { ... }

// apres
#[\MethodView(GET: "hello", Auth: 1)]
public function helloService() { ... }
```

Point d'attention PHP (pas specifique a Devups) : un attribut utilise
dans une classe qui declare un `namespace` doit etre reference par son
nom pleinement qualifie (`#[\MethodView(...)]`) si la classe
d'attribut elle-meme est dans l'espace de noms global - sans quoi PHP
cherche `route\MethodView` et non `\MethodView`. C'est le cas de
`route/Admin.php` et `route/AdminService.php` (`namespace route;`) ;
les 3 FrontControllers convertis sont, eux, dans l'espace de noms
global (namespace commente dans leur fichier), donc `#[GET]`/`#[POST]`/
etc. s'y resolvent sans prefixe.

`dclass/generator/BackendGenerator.php` emet desormais directement des
attributs PHP 8 pour toute NOUVELLE entite/controleur genere (meme
remarque qu'au LOT 2 pour une entite generee avant ce chantier et
jamais regeneree : elle reste en docblocks tant qu'elle n'est pas
repassee par le generateur - ces docblocks ne sont alors plus lus,
`dclass/lib/addendum/` etant retiree. Cette situation n'existe dans
aucun composant genere present dans ce depot, verifie par recherche
exhaustive des 4 motifs `@Api(`/`@GET`/`@POST(`/`@PUT(`/`@DELETE(`/
`@Auth(`/`@MethodView(` sur `src/`, `dclass/` et `route/`).

**Comment migrer** : meme transformation mecanique pour un projet
utilisateur ayant ses propres FrontControllers annotes : chaque
`@Nom(arg='valeur')` devient `#[Nom(arg: 'valeur')]` (guillemets simples
ou doubles, pas de virgule finale). Aucune commande de migration
dediee n'est fournie : la surface reelle dans ce depot est entierement
enumeree (3 FrontControllers + `route/Admin.php` +
`route/AdminService.php`), une conversion manuelle a la main suffit
pour un projet de taille comparable ; un projet avec un grand nombre de
controleurs annotes peut s'appuyer sur une regle de recherche/
remplacement simple (`@(\w+)\(([^)]*)\)` -> `#[$1($2)]`, puis remplacer
les `=` par `:` dans les arguments) suivie d'un `php -l` systematique.

Verifie par : la suite de tests complete (412 tests, aucun echec reel -
voir note ci-dessous - 2 incomplets pre-existants sans rapport), qui
exerce le routage annote de bout en bout via les E2E (connexion,
notifications, push, administration) ; `composer phpstan` sans erreur.
Note locale : deux executions paralleles de `composer test` sur la
meme base de test dediee produisent de faux echecs par collision
(cache des langues actives, cle de configuration dupliquee, hash de
mot de passe) - sans rapport avec ce lot, resolu en executant la suite
en serie.

## LOT 5 - PSR-4, conteneur, Log

**Aucune rupture dans ce lot** - a la difference des LOTs 1-3
ci-dessus, chaque point de LOT 5 a ete delibere pour etre **sans
impact** sur le code d'un projet existant (voir TODO.md, sections "LOT
5.0" a "LOT 5.5", pour le detail des decisions et de ce qui a ete
audite avant chaque etape). Rien ci-dessous n'exige de modification
dans un projet qui ne choisit pas explicitement d'adopter les nouvelles
possibilites decrites.

### Namespacage des entites/controleurs/datatables/formulaires + des classes de base partagees

**Avant** : toutes les entites (`src/*/Entity/*.php`), leurs
controleurs/datatables/formulaires (`src/*/Controller|Datatable|
Form/*.php`), et les classes de base partagees (`dclass/extends/
{Dvups_config_item,Dvups_migration,ImageCore,UserCore,Dv_langCore}.php`)
vivaient dans l'espace de noms global - `new Dvups_lang()`,
`UserController::class`, `extends ImageCore`, etc.

**Apres** : chacune de ces classes porte desormais un vrai namespace
PHP (`<composant>\<Module>\<Entity|Controller|Datatable|Form>` pour
`src/`, `dclass\devups\Core` pour les classes de base partagees). Le
nom COURT (`Dvups_lang`, `UserController`, `ImageCore`...) reste
utilisable a l'identique partout - `class_alias(FQCN, 'NomCourt')` est
ajoute juste apres chaque classe namespacee, qui fait pointer l'ancien
nom global vers la nouvelle classe. Aucune difference de comportement
observable a l'execution : `new Dvups_lang()`, `Dvups_lang::xxx()`,
`$x instanceof Dvups_lang`, les annotations Doctrine
(`targetEntity: "\Dvups_lang"`) continuent tous de fonctionner tel
quel (verifie par audit exhaustif : aucun site du depot ne fait de
comparaison `get_class($x) === 'NomCourt'`, seul cas que
`class_alias()` ne survivrait pas - voir TODO.md "LOT 5.2").

**Comment migrer** : rien a faire pour continuer a utiliser les noms
courts. Un projet qui souhaite adopter les nouveaux FQCN (ex. pour un
`use` explicite plutot qu'une reference globale `\Dvups_lang`) peut le
faire au cas par cas - les deux formes coexistent indefiniment, aucune
depreciation n'est prevue.

### Conteneur PSR-11 (`Devups\Container`)

**Nouveau** (pas une migration) : `Devups\Container` (`dclass/devups/
Container.php`) implemente `Psr\Container\ContainerInterface`
(`get()`/`has()`), avec `set()`/`bind()`/`singleton()` pour y
enregistrer des services. Disponible pour du code qui voudrait s'y
brancher ; rien dans le framework ne l'utilise encore en interne (voir
TODO.md "LOT 5.4" pour la justification de cette portee volontairement
limitee).

### Adaptateur PSR-3 (`Devups\Logger`)

**Nouveau** (pas une migration) : `Devups\Logger` (`dclass/devups/
Logger.php`) implemente `Psr\Log\LoggerInterface` au-dessus de la
facade statique existante `\Log` (`Log::error()`/`warning()`/
`info()`, inchangee et toujours l'implementation reelle). Utile pour
brancher une bibliotheque tierce qui attend specifiquement un
`Psr\Log\LoggerInterface` ; les 8 niveaux PSR-3
(emergency/alert/critical/error/warning/notice/info/debug) sont
ramenes aux 3 niveaux de `\Log` (voir TODO.md "LOT 5.5" pour le detail
du mapping et de la convention suivie pour les exceptions passees en
contexte).

Verifie par : la suite de tests complete (445 tests, 0 echec - 8
deprecations et 2 incomplets preexistants et sans rapport) ; `composer
phpstan` sans erreur. CI GitHub verte sur `release/v8.0` (matrice PHP
8.3/8.4/8.5) a chaque commit de ce lot.

## LOT 6 - HTTP (Request/Response, middlewares)

**Aucune rupture dans ce lot**, meme logique que le LOT 5 : `Request`/
`Response`/`Router`/`Route` (`dclass/devups/Http/`) restent inchanges,
espace de noms global, comportement de dispatch identique.

**Nouveau** (pas une migration) : un pipeline de middleware PSR-15
minimal, `Devups\Http\MiddlewareStack` (`Psr\Http\Server\
RequestHandlerInterface`), plus `Devups\Http\CallableRequestHandler`
(adapte un callable en handler PSR-15) et `Devups\Http\ResponseEmitter`
(envoie un `Psr\Http\Message\ResponseInterface` comme reponse HTTP
reelle). Les objets de message PSR-7 eux-memes ne sont pas
reimplementes par Devups - `GuzzleHttp\Psr7\ServerRequest::
fromGlobals()`/`GuzzleHttp\Psr7\Response` (deja disponibles via
`guzzlehttp/guzzle`) suffisent pour construire une requete/reponse
PSR-7 a passer a `MiddlewareStack`.

Rien n'est branche dans le dispatch existant : ces classes sont
disponibles pour un projet qui voudrait composer un pipeline de
middleware PSR-15 independamment du routage par attributs existant
(`#[GET]`/`#[POST]`/...), sans obligation d'y toucher.

Verifie par : la suite de tests complete (454 tests, 0 echec - 8
deprecations et 2 incomplets preexistants et sans rapport) ; `composer
phpstan` sans erreur.

## LOT 7 - Qualite (PHPStan)

**Aucune rupture d'API ou de comportement runtime dans ce lot** -
uniquement des corrections de documentation (docblocks `@param`/
`@return`/`@var` errones, zero impact a l'execution) et deux
changements d'outillage interne au depot :

- `phpstan/phpstan` remonte de `^1.10` a `^2.3` (uniquement une
  dependance `require-dev`, sans effet sur une application qui
  consomme ce paquet - pertinent seulement pour qui execute `composer
  phpstan` sur ce depot lui-meme).
- `phpstan.neon.dist` passe du niveau 1 au niveau 5 (verifications
  plus strictes a chaque execution de `composer phpstan`, rien a
  migrer cote consommateur).

Deux corrections ont un effet observable, mineur et strictement
ameliorant :

- `Util::clearcookie()` n'emet plus l'avertissement de depreciation
  PHP 8.1+ "Passing null to parameter of type string is deprecated"
  (comportement d'effacement du cookie identique).
- `curl_setopt($ch, CURLOPT_POST, ...)` (notifications push FCM/Expo)
  passe desormais `true` plutot que `1` - comportement HTTP identique.

Verifie par : la suite de tests complete (454 tests, 0 echec - 8
deprecations et 2 incomplets preexistants et sans rapport) ; `composer
phpstan` sans erreur au niveau 5.
