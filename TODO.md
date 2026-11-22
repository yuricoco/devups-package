# TODO

Suivi des points ouverts, decisions prises, et blocages rencontres
pendant le chantier (droits / logs / langues / cms). Chaque entree :
phase, ce qui reste, pourquoi, fichiers concernes.

## Chantier v8.0 (rupture, modernisation)

Branche `release/v8.0`, creee depuis `origin/master` (v7.1.0 fusionnee).
Plan d'ensemble en 8 lots :

1. Socle (PHP 8.3, dependances, Doctrine ORM 3 + attributs)
2. Vues (illuminate/view)
3. Routes (attributs PHP 8, fin d'addendum)
4. Generateur
5. PSR-4 + conteneur PSR-11 + Log PSR-3
6. HTTP (Request/Response, middlewares)
7. Qualite (PHPStan 5)
8. Livraison (UPGRADE-8.0.md, doc, demo)

Decisions actees pour tout le chantier :

- PHP >= 8.3 (CI sur 8.3/8.4/8.5).
- L'ORM maison Tchutte est conserve : Doctrine reste limite aux
  metadonnees et au schema (DBAL/SchemaTool/mapping), jamais au query
  runtime applicatif qui reste 100% Tchutte.
- Namespace racine `Devups\` **pour le coeur du framework** (`dclass/`
  uniquement - Controller, DBAL, Genesis/View, generator, etc., a
  harmoniser sous ce prefixe au lot 5, choix laisse a l'implementation).
  Precision actee (session suivante, directive explicite) : pour les
  classes de **`src/`** (les composants/modules livres avec le
  framework - `devups`, `devupscms`, `devupsfront`), la racine du
  namespace est le **nom du composant lui-meme**, PAS `src` et PAS
  `Devups\` - ex. `devupsfront\ModuleUser\Entity\User` (et non
  `Devups\devupsfront\...` ni `src\devupsfront\...`). Cette convention
  est deja PARTIELLEMENT en place pour les entites : `Core.php`
  (generateur, methode qui ecrit `config/dvups_configurations.php`)
  pre-declare pour chaque entite une cle `"$component\$module\Entity\
  $entite"` en plus de la cle "nom court" historique (meme metadonnees
  `component`/`module`/`namespace` dans les deux cas) - mais les
  fichiers d'entite eux-memes restent en namespace global (pas de
  `namespace ...;` reel dans le fichier), seule la config anticipe la
  future FQCN. Le lot 5 doit donc : (a) ajouter le `namespace
  $component\$module\Entity;` reel aux fichiers sous `src/**/Entity/*.php`
  pour qu'il corresponde a ce que `dvups_configurations.php` annonce
  deja ; (b) etendre la meme convention aux AUTRES sous-dossiers de
  `src/<component>/<Module>/` non couverts par cette config (`Controller/`,
  `Datatable/`, `Form/` - voir arborescence `src/` complete, pas
  seulement `Entity/`) ; (c) s'assurer que tout code qui instancie ces
  classes par leur nom court actuel (`new Dvups_action()`, lookups par
  cle "nom court" dans `dvups_configurations.php`, etc.) continue de
  fonctionner (alias de compatibilite ou double-cle, a trancher au
  lot 5).
- Framework neutre, aucun secret versionne, base de test dediee,
  sql_mode strict dans les tests.
- Un commit par sous-etape, `composer test` et `composer phpstan`
  verts a chaque commit.
- Toute rupture : un test + une entree dans `UPGRADE-8.0.md`
  (avant / apres / comment migrer) + si possible une commande de
  migration automatique.
- A la fin de chaque lot : push sur `release/v8.0`, puis CI GitHub
  verte avant le lot suivant.
- Blocage ou choix d'architecture incertain : options ecrites ici,
  choix le plus prudent, on continue.
- Aucun tag ni release cree pendant ce chantier.

### LOT 1.0 - Plan (inventaire, sans code)

**Risque bloquant a noter avant tout** : cette machine de dev n'a que
PHP 8.1.34 / 8.2.12 / 7.4.33 installes (`xampp`), aucun 8.3+. Options :
(a) ecrire `composer.json` avec `"php": ">=8.3"` quand meme (c'est la
cible demandee) et verifier localement avec l'interpreteur 8.2 reel via
`composer config platform.php 8.2.99` (ou `--ignore-platform-reqs`
ponctuel) - les tests tournent alors sous un moteur 8.2, donc ne
couvrent pas une syntaxe strictement 8.3+, mais la CI GitHub (matrice
8.3/8.4/8.5, ETAPE 1.1) reste la verification reelle de la cible ; (b)
ne pas changer la contrainte php tout de suite. Choix retenu (le plus
prudent qui respecte la demande explicite) : (a) - `composer.json`
passe a `>=8.3`, platform override local pour pouvoir executer
`composer test`/`phpstan` ici, CI GitHub = seul juge final du
"compose test vert sur 8.3/8.4/8.5". A noter dans chaque commit de ce
chantier ou cette limite s'applique.

**Inventaire dependances directes** (`composer outdated --direct`,
versions installees 7.1.0 -> dernieres connues) :

| Paquet | Installee | Derniere | PHP 8.3+ | Action lot 1 |
|---|---|---|---|---|
| doctrine/orm | 2.9.3 | 3.7.6 | OK (ORM3 exige php >=8.1) | **MAJ vers ^3.7** - rupture mapping, voir plus bas |
| doctrine/cache | 1.11.3 | 2.2.0 | abandonne (`composer outdated` le signale) | A retirer si possible (ORM3 n'en a plus besoin pour le cache de metadonnees ; verifier usages directs hors ORM avant suppression) |
| philo/laravel-blade | 3.1 | - (abandonne) | epingle illuminate/view a ~5 | **lot 2**, pas lot 1 |
| phpunit/phpunit | 10.5.66 | 11.5.57 | OK | MAJ vers ^11 si aucune API retiree utilisee par la suite existante (a verifier en 1.1) |
| phpstan/phpstan | 1.12.34 | 2.3.5 | OK | MAJ vers ^2 - **lot 7** prevoit la bascule complete (regles changees) ; en lot 1 on reste sur ^1 tant que le reste du socle n'est pas stabilise, sauf si ^2 passe sans friction |
| firebase/php-jwt | 6.11.1 | 7.2.1 | OK les deux | reste sur ^6 - deja documente dans `composer.json` (audit ignore PKSA-y2cr-5h3j-g3ys) : google/apiclient->google/auth exige encore ^6, inchange en v8.0 |
| google/apiclient | 2.19.4 | 2.20.1 | OK | MAJ mineure sans risque |
| guzzlehttp/guzzle | 7.16.1 | 8.3.1 | OK les deux | reste sur ^7 en lot 1 (MAJ majeure non demandee, pas de rupture connue a corriger) ; a revisiter si une dependance du lot 6 (HTTP) l'exige |
| minishlink/web-push | 10.0.3 | 11.0.0 | OK les deux | reste sur ^10, pas de rupture a traiter dans ce chantier |
| phpmailer/phpmailer | 6.12.0 | 7.2.0 | OK les deux | reste sur ^6, pas touche par ce chantier |
| symfony/var-dumper | 5.4.54 | 7.4.22 | OK les deux (dev uniquement) | reste sur ^5.4 (dev-only, sans impact) |
| zircote/swagger-php | 4.11.1 | 7.5.0 | OK les deux | reste sur ^4, utilise par `documentation/api.php`/`guide.php` hors perimetre Doctrine |
| maslosoft/addendum | 7.0.6 (deja derniere) | - | a verifier en lot 3 | utilise pour le routing par annotations - **lot 3** ("fin d'addendum"), pas touche en lot 1 |
| mpdf/mpdf, ifsnop/mysqldump-php, barcode-bakery/barcode-1d, google/apiclient, minishlink/web-push | a jour ou proches | - | OK PHP 8.3+ | pas d'action lot 1 |

**Ce que casse Doctrine ORM 3 dans Devups** (`doctrine/orm` 2.9 -> 3.7,
`doctrine/dbal` suit en 4.x) :

- `Setup::createAnnotationMetadataConfiguration()` et la classe
  `Setup` : supprimes. Remplacement : `ORMSetup::
  createAttributeMetadataConfiguration($paths, $isDevMode)`.
- `EntityManager::create($conn, $config)` (methode statique) :
  supprimee. Remplacement : `new EntityManager(DriverManager::
  getConnection($conn, $config), $config)` (ou `EntityManager::
  create()` d'une factory differente selon la version finale - a
  confirmer a l'implementation, l'API exacte a bouge entre 3.x RC et
  stable).
- Driver d'annotations (`AnnotationDriver`, `AnnotationReader` de
  `doctrine/annotations`) : le paquet `doctrine/annotations` n'est plus
  une dependance de `doctrine/orm` 3 ; le mapping par docblocks
  n'est plus supporte nativement -> remplace par `AttributeDriver` +
  attributs PHP 8 (`#[ORM\Entity]`, etc.) sur toutes les entites.
- `doctrine/cache` (1.x, abandonne) : plus utilise par l'ORM 3
  (cache PSR-6/PSR-16 natif) - a retirer de `composer.json` si aucun
  usage direct hors ORM n'est trouve en 1.1.
- `cli-config.php` : l'API de retour (`ConsoleRunner::createHelperSet`
  avec un `EntityManagerHelper` construit a la main) change de forme
  avec `ORM\Tools\Console\ConsoleRunner` en 3.x (signature des helpers
  mise a jour) - a adapter en 1.2/1.4.
- `SchemaTool` : namespace et constructeur inchanges en substance,
  mais depend de la Configuration/EntityManager reconstruits ci-dessus
  - a revalider par le test d'integration `schema:update --dump-sql`
  (1.2).
- Types de colonnes (`Types::STRING` etc.) : l'API `Doctrine\DBAL\
  Types\Type` est globalement stable entre DBAL 2->4 pour les types
  utilises ici (`string`, `integer`, `boolean`, `datetime`, `text`) -
  risque faible, a confirmer par les tests existants (1.4).
- Lecture des metadonnees dans le reste du framework : 8 fichiers
  appellent directement l'API Doctrine (`getClassMetadata()`,
  `getMetadataFactory()`, `EntityManager::create`, `Setup::`) et
  doivent etre revus avec le nouveau driver/API (1.4) :
  `dclass/devups/Tchutte/DBAL.php` (le point d'entree principal,
  `getEntityManager()`), `dclass/devups/model/Model.php`,
  `dclass/devups/model/ModelTrait.php`,
  `dclass/devups/Controller/Controller.php`,
  `dclass/generator/BackendGenerator.php`, `dclass/generator/
  builder.php`, `tests/bootstrap.php`,
  `tests/Integration/TreeStatusUniqueConstraintAnnotationsTest.php`
  (renommage probable -> `...AttributesTest.php`, le nom actuel
  reference les annotations). `documentation/api.php` et
  `documentation/guide.php` (zircote/swagger-php) ne touchent pas
  l'API Doctrine - hors perimetre lot 1.

**Entites a convertir (annotations -> attributs), 41 fichiers** :
`dclass/devups/model/DateTimeTrait.php` (trait partage, `@Column` sur
proprietes reutilisees - a traiter en premier car tout le reste en
depend), `dclass/extends/{Dv_langCore,Dvups_config_item,
Dvups_migration,ImageCore,UserCore}.php` (MappedSuperclass/traits de
base), puis les 36 entites sous `src/**/Entity/*.php` (modules Admin,
Config, Lang, Log, Cms, Data, Location, Notification,
PushNotification, Reporting, Tree, User/Oauth). `dclass/generator/
BackendGenerator.php` genere lui-meme des docblocks Doctrine pour les
nouvelles entites -> doit emettre des attributs apres 1.2 (sinon le
generateur regenere du code incompatible) - couple a 1.3/1.4, pas
seulement a l'audit.

**Ordre d'execution retenu (lot 1)** :

1. 1.1 `composer.json` (php>=8.3, deps) + `composer audit` propre.
2. 1.2a `DateTimeTrait` + les 5 classes `dclass/extends/*Core*` /
   `*_migration` (base commune) -> attributs, car toutes les entites
   en heritent ou les utilisent en trait.
3. 1.2b Les 36 entites `src/**/Entity/*.php` -> attributs.
4. 1.2c `DBAL::getEntityManager()` -> `ORMSetup::
   createAttributeMetadataConfiguration` + nouvelle construction de
   l'EntityManager ; `cli-config.php` adapte.
5. 1.2d Test d'integration "`schema:update --dump-sql` vide sur un
   schema 7.1 installe" (garde-fou noyau du lot).
6. 1.3 Commande `upgrade:entities` (reutilise le meme mapping de
   conversion que 1.2, applique a un projet utilisateur) + fixtures.
7. 1.4 Les 8 autres consommateurs de metadonnees (Model, ModelTrait,
   Controller, generator, tests/bootstrap) + `BackendGenerator` emet
   des attributs pour les nouvelles entites generees.
8. 1.5 `UPGRADE-8.0.md`.
9. 1.6 Push + CI.

Ce fichier remplace entierement la version precedente : les points
Phase 2 (B.2/B.5/B.6/B.7/B.8) et Phase 3 (C.1/C.4/C.5/C.6/C.7) qu'elle
documentait comme ouverts ont ete traites dans cette session (voir
"Resume phase par phase" plus bas). Seul le point Phase 4 (CMS) reste
ouvert, voir ci-dessous.

### LOT 3.0 - Plan (inventaire, sans code) - Routes

**Ce que fait reellement le routage par annotations aujourd'hui** (a ne
pas confondre avec `maslosoft/addendum`, la dependance Composer du meme
nom - voir plus bas) :

- Le moteur d'annotations reellement utilise est une copie VENDOREE en
  arbre, PAS la dependance Composer : `dclass/lib/addendum/annotations.php`
  (+ `annotations/annotation_parser.php`) - "Addendum PHP Reflection
  Annotations" (Jan Suchal, 2006-2009, LGPL), chargee par un `require`
  manuel dans `dclass/lib/lib_dependances.php` (ligne 12), independamment
  de l'autoload Composer. Definit la classe `Annotation` (base commune)
  et `ReflectionAnnotatedClass`/`ReflectionAnnotatedMethod`/
  `ReflectionAnnotatedProperty` (sous-classes de `ReflectionClass`/
  `ReflectionMethod`/`ReflectionProperty` qui parsent le docblock pour
  exposer `hasAnnotation($nom)`/`getAnnotation($nom)`).
- **`maslosoft/addendum` (le paquet Composer, `composer.json`) n'est
  utilise NULLE PART dans le code** (`grep -r "Maslosoft\\Addendum"` :
  aucun resultat) - c'est une dependance morte, sans rapport avec le
  moteur reel ci-dessus malgre le nom partage (meme lignage historique,
  fork different). A retirer en 3.1, independamment du reste.
- 7 classes d'annotation metier (`dclass/lib/annotation/*.php`,
  `extends Annotation`) portent la logique de routage : `Api` (classe,
  `@Api(name='/prefix')` - prefixe d'URL d'un *FrontController*),
  `Auth` (methode, `@Auth(authorized=1)` - verifie `Auth::$user_id`
  avant d'executer la methode), `GET`/`POST`/`PUT`/`DELETE` (`extends
  Api`, methode - verbe HTTP + `path` optionnel, resolvent les
  parametres de route via `Route::parseRoute()`), `MethodView` (meme
  forme qu'`Api` mais utilisee directement sur les methodes de
  `route/Admin.php`/`route/AdminService.php`, hors *FrontController*).
  `ApiLazyloading` et `DDoc` existent mais n'ont **aucun usage reel**
  dans tout le depot (greps confirmes) - `DDoc` n'est meme pas
  `require`-e dans `lib_dependances.php`.
- Consommateurs de `ReflectionAnnotatedClass`/`Method` (API reellement
  utilisee - exhaustif, verifie par grep) : `dclass/devups/Http/Router.php`
  et `dclass/devups/Http/Request.php` (`new ReflectionAnnotatedClass($ctrl)`,
  puis `->getMethod($m)`, `->getMethods(ReflectionMethod::IS_PUBLIC)`,
  `->getAnnotation('Api')`, et sur la methode : `->hasAnnotation($nom)`,
  `->getAnnotation($nom)`, `->getParameters()` - herite de
  `ReflectionMethod`, inchange). Rien d'autre (pas de
  `ReflectionAnnotatedProperty`, pas de `getAllAnnotations()`) hors de
  la lib elle-meme et ses propres tests bundles.
- **Fichiers avec des annotations de routage REELLES a convertir**
  (3 *FrontController* + 2 fichiers `route/`, verifie exhaustivement par
  grep - tout le reste de `src/*/Controller/*.php` n'utilise aucune de
  ces annotations) :
  - `src/devupsfront/ModuleUser/Controller/UserFrontController.php` :
    1x `@Api(name=...)` (classe) + ~20x `@GET`/`@POST`/`@PUT`/`@DELETE`
    (bareword ou avec `path=`) + ~8x `@Auth(authorized=1)`.
  - `src/devupscms/ModuleNotification/Controller/NotificationFrontController.php` :
    1x `@Api` + 4x verbe HTTP + 3x `@Auth`.
  - `src/devupscms/ModulePushNotification/Controller/Push_subscriptionFrontController.php` :
    1x `@Api` + 2x verbe HTTP + 2x `@Auth`.
  - `route/Admin.php` et `route/AdminService.php` : 2x `@MethodView`
    chacun (`@MethodView(GET="hello", Auth=1)` et
    `@MethodView(path="...")`).
  - `dclass/generator/BackendGenerator.php` (lignes ~639-787) : emet
    ces memes annotations dans le template d'un *FrontController*
    genere - a mettre a jour en meme temps que le reste (meme logique
    que l'ETAPE 1.4 pour les entites).
  - (`dclass/generator/android/BackendGeneratorJava.php` utilise aussi
    `@GET`/`@POST` mais ce sont des annotations JAVA/Retrofit pour un
    client mobile genere - HORS PERIMETRE, aucun rapport avec le
    routage PHP de ce framework.)

**Design retenu** (meme pattern qu'au lot 2 - `BladeD`/`Devups\View` -
garder l'API d'appel strictement identique, remplacer uniquement
l'implementation sous-jacente) :

1. Les 7 classes d'annotation (`Api`, `Auth`, `GET`, `POST`, `PUT`,
   `DELETE`, `MethodView`, + `ApiLazyloading`/`DDoc` par coherence bien
   que mortes) deviennent des attributs PHP 8 natifs : `#[\Attribute
   (\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]`, un
   constructeur explicite a parametres nommes (remplace l'hydratation
   par tableau de l'ancienne classe `Annotation`, qui disparait), MEME
   proprietes publiques, MEME corps de methode (`execute()`,
   `authorize()`, etc. - logique metier non affectee par le mecanisme
   d'annotation). Toutes globales (pas de namespace) - coherent avec
   les chaines passees a `hasAnnotation('Auth')`/`getAnnotation('GET')`
   dans Router.php/Request.php, aucun changement de resolution de nom
   necessaire la non plus.
2. `ReflectionAnnotatedClass`/`ReflectionAnnotatedMethod` (nouvelles
   classes globales du framework, PAS la lib vendoree) deviennent de
   fines sous-classes de `\ReflectionClass`/`\ReflectionMethod` :
   `hasAnnotation($nom)`/`getAnnotation($nom)` lisent
   `getAttributes($nom)` + `newInstance()` au lieu de parser le
   docblock ; `getMethod($nom)`/`getMethods($filter)` renvoient des
   instances de `ReflectionAnnotatedMethod` (pas de simples
   `ReflectionMethod`). Aucun autre changement d'API - Router.php et
   Request.php n'ont besoin d'AUCUNE modification de leurs appels.
3. `dclass/lib/addendum/` (toute la lib vendoree, Annotation +
   parser + ses tests bundles) et la ligne `require` correspondante
   dans `lib_dependances.php` sont retires. `maslosoft/addendum` est
   retire de `composer.json` (dependance morte, sans lien avec ce qui
   precede).
4. Les 5 fichiers avec annotations reelles (3 controllers + 2
   `route/*.php`) sont convertis vers la syntaxe attribut. Le template
   de `BackendGenerator.php` emet desormais des attributs pour tout
   nouveau *FrontController* genere.

**Ordre d'execution** :

1. 3.1 Classes d'annotation -> attributs PHP 8 + retrait de
   `maslosoft/addendum` de `composer.json`.
2. 3.2 Nouveau shim `ReflectionAnnotatedClass`/`ReflectionAnnotatedMethod`
   (natif, base sur `getAttributes()`) + retrait de
   `dclass/lib/addendum/` et de son `require`.
3. 3.3 Conversion des 5 fichiers (3 *FrontController* + `route/Admin.php`/
   `AdminService.php`) + mise a jour du template `BackendGenerator.php`.
4. 3.4 Tests : la suite E2E existante (`AdminScreensTest`,
   `CountryCreateViaFormTest`, `LegacyServicesFormEndpointsTest`, etc.)
   exerce deja ces routes de bout en bout - garde-fou de non-regression
   suffisant, complete par un test unitaire dedie sur le nouveau shim
   (annotation presente/absente, heritage GET/POST/PUT/DELETE -> Api),
   meme rigueur que `EntityAttributeConverterTest` au lot 1.
5. 3.5 `UPGRADE-8.0.md` - section "LOT 3 - Routes".
6. 3.6 Push + CI.

### LOT 4.0 - Plan (inventaire, sans code) - Generateur

**Constat** : les deux seuls artefacts generes qui emettaient encore de
l'annotation-style (docblocks) ont deja ete mis a jour PENDANT les lots
precedents, chacun "en meme temps que le reste" comme prevu : le
template d'entite (`entityGenerator()`) a l'ETAPE 1.4 (attributs
Doctrine), le template de *FrontController*
(`controllerGenerator()`/`frontCtrlContent()`) a l'ETAPE 3.3 (attributs
de routage). Il ne reste donc, pour ce lot, qu'a VERIFIER qu'aucun
autre artefact genere ou aucun autre fichier de `dclass/generator/`
(le generateur lui-meme, pas son output) n'a besoin d'etre touche -
audit exhaustif, pas de changement attendu a priori.

**Audit effectue** (tous les fichiers PHP de `dclass/generator/` et
`admin/generator/`, hors le generateur Android/Java qui produit du
code client mobile, HORS PERIMETRE PHP - voir LOT 3.0) :

1. Recherche des 7 motifs d'annotation de routage
   (`@Api(|@GET\b|@POST(|@PUT(|@DELETE(|@Auth(|@MethodView(`) et des
   motifs Doctrine (`@Entity|@Column|@Id\b|@GeneratedValue|@ManyToOne|
   @OneToMany|@JoinColumn`) sur tout `dclass/generator/` : AUCUNE
   occurrence reelle (les 2 seules trouvailles sont des commentaires de
   code, pas du code genere).
2. Autres templates du generateur non couverts par les lots 1/3
   (`tableGenerator()`, `formGenerator()`, `coreGenerator()`,
   `entityLangGenerator()`, `detailWidgetGenerator()`,
   `formWidgetGenerator()`, `postmandocGenerator()`,
   `dclass/generator/template/{DvAdmin,Templatedependences}.php`,
   `OpenApiGenerator.php`, `RequestGenerator.php`,
   `DocGuideGenerator.php`) : aucun n'emet de code annote (le
   controleur ADMIN genere par `defaultCtrlContent()`, distinct du
   *FrontController* public, n'a jamais utilise d'annotations de
   routage - dispatch par convention de nom de methode, pas par
   attribut/annotation) ; `OpenApiGenerator.php`/`RequestGenerator.php`
   ne dependent ni de `hasAnnotation()`/`getAnnotation()` ni de
   `ReflectionAnnotated*` (verifie par recherche) - ils lisent les
   datacores JSON directement, aucun risque de rupture lie au LOT 3.
3. Recherche de constructions PHP devenues obsoletes/suppimees
   (`each(`, `create_function`, `utf8_decode`/`utf8_encode`,
   `strftime(`, `money_format`, syntaxe d'acces caractere par accolade
   `$var{0}`) sur `dclass/generator/` et `admin/generator/` : aucune
   occurrence.
4. `php -l` sur chaque fichier PHP du generateur (hors `android/`) :
   tous valides. `composer phpstan` (niveau 1, baseline, chemin
   `dclass` inclus) : aucune nouvelle erreur sur ce lot.

**Conclusion** : LOT 4 ne necessite aucun changement de code - le
travail reel du lot (templates generes modernises) a deja ete livre
aux lots 1 et 3, "en meme temps que le reste" comme anticipe par leurs
propres plans. Ce lot se limite donc a cet audit de cloture (ecrit
ici) + mise a jour de `TODO.md`/`UPGRADE-8.0.md` constatant l'absence
de rupture, sans commit de code attendu.

### LOT 5.0 - Plan (inventaire, sans code) - PSR-4 + conteneur PSR-11 + Log PSR-3

**EN COURS** - inventaire fait cette session, implementation PAS
commencee (lot trop risque pour etre code sans cet audit prealable -
voir la decision "choix le plus prudent, on continue" des regles du
chantier).

**Etat reel de l'autoload aujourd'hui** (corrige par rapport a une
hypothese fausse notee plus tot dans cette session, avant audit
exhaustif) :

- `composer.json` : `classmap: ["dclass/", "src/"]` - scanne les
  fichiers pour toute declaration `class`/`interface`/`trait`, QUEL QUE
  SOIT son namespace (un namespace n'est PAS un pre-requis du
  `classmap`, contrairement au PSR-4 reel). Donc le mix actuel
  (certains fichiers namespaces, d'autres non) fonctionne deja sans
  aucun probleme d'autoload.
- Quinze fichiers "infrastructure" de `dclass/devups/` portent un vrai
  `namespace dclass\devups\...;` actif (liste complete : `Api.php`,
  `Controller/{Controller,ControllerCore,CrudTrait,FrontController,
  ModuleController}.php`, `Datatable/{Datatable,Dbutton,Lazyloading,
  ListeView,TableData,TableRow}.php`, `Migration/MigrationRunner.php`,
  `model/{DateTimeTrait,ModelSerializer}.php`,
  `Tchutte/{Column,DBALException,DB_dumper}.php`) et sont references
  PARTOUT ailleurs par leur FQCN explicite - y compris dans des
  templates Blade admin non-PHP-namespaces
  (`admin/views/layout/*.blade.php`, `admin/views/dashboard.blade.php`)
  et dans le generateur (`admin/generator/AdminTemplateGenerator.php`,
  template de vue genere). Ce mix est deja en production, stable,
  verifie par `php -l` + la suite de tests complete (aucune de ces
  classes n'est jamais referencee par son nom court).
- `dclass/devups/model/Model.php` (classe de base de TOUTES les
  entites, sans exception) a un `//namespace dclass\devups;` **commente**
  a la ligne 13 - etat herite du commit squash initial du depot
  (`95c538e`), aucune trace du contexte d'origine. `Model` reste donc
  en namespace global aujourd'hui, et c'est la classe la PLUS
  referencee par nom court de tout le framework (des centaines
  d'entites `extends Model`).
- `dclass/devups/View.php` (ETAPE 2, cree par ce chantier) utilise
  `namespace Devups;` (racine courte, sans segment `dclass`) - DIFFERENT
  du schema `dclass\devups\...` ci-dessus. Cout de reconciliation tres
  faible si decide un jour (seuls `Genesis.php` - alias `BladeD` -,
  `Controller.php` et `Http/Request.php` y font reference, tous par
  FQCN `\Devups\View`/`\BladeD`) - PAS traite ce lot, pas bloquant.

**Le risque reel, confirme par audit** : toute la logique de
derivation table/colonne/cle de filtre/cle de cache de l'ORM maison
Tchutte repose sur `get_class($entity)`/`get_called_class()`
**en assumant que le resultat est deja le nom court** (c'est vrai
aujourd'hui puisque toute entite est en namespace global). Fichiers
concernes (19, audit exhaustif par recherche de
`get_class(|get_called_class(` sur tout le depot hors vendor/tests/
cache) : `dclass/devups/Controller/{Controller,FrontController,
ModuleController}.php`, `Core.php`, `Datatable/{Datatable,
Lazyloading}.php`, `Dfile.php`, `Form/{Form,FormManager}.php`,
`Http/{Request,Router}.php`, `Log.php`, `model/{EntityCore,Model,
ModelSerializer,ModelTrait}.php`, `Tchutte/{Bugmanager,DBAL,
QueryBuilder}.php`, et `src/devupscms/ModuleNotification/Entity/
Notification.php`. Exemples representatifs confirmes NON-SURS (a
corriger AVANT tout renommage de classe feuille) :
`Datatable.php:452` (`$this->filterParam[strtolower(get_class($param))
. ".id:eq"]`), `DBAL.php:281/311/345/384/404/423/442/511` (table/colonne
derivees), `QueryBuilder.php:546-547/709` (parametre/colonne SQL),
`ModelTrait.php:116` (colonne `_id` de table de traduction),
`Core.php:20/21/46` (chemin JSON de configuration d'entite),
`Form.php:25/47/62` (nom de formulaire). A l'inverse, SURS - ne pas
toucher - toute utilisation qui veut la VRAIE classe pour
reflection/instanciation/comparaison exacte : `new
ReflectionClass(get_called_class())` (tres majoritaire dans
`ModelTrait.php`), `get_class($value) == QueryBuilder::class` /
`== \dclass\devups\Tchutte\Column::class` (deja qualifies), `get_class(
...) == "DateTime"` (classe native, toujours globale).

**Avertissement sur la completude de cet audit** : les fichiers/lignes
listes ci-dessus sont une recherche par grep + lecture representative,
PAS encore une revue ligne-par-ligne exhaustive - deja constate en
relisant `Model.php` seul apres l'avoir "classe" : une methode
supplementaire (`Model::post()`, ligne ~222,
`strtolower(get_called_class())` -> cle `$_POST[...]`) etait absente
de la premiere liste. A traiter en debut de 5.1 : repasser CHAQUE
fichier des 19 un par un (pas un echantillon) avant d'ecrire le moindre
correctif, plutot que de corriger au fur et a mesure et decouvrir des
oublis plus tard.

**Ordre d'execution propose** (rien de code ci-dessous n'est fait) :

1. 5.1 Revue ligne-par-ligne exhaustive des 19 fichiers (pas
   d'echantillonnage - voir avertissement ci-dessus), puis introduire
   un point unique de derivation du nom court (helper base sur
   `ReflectionClass::getShortName()`) et convertir chaque site "non
   sur" pour l'utiliser au lieu de `get_class()`/`get_called_class()`
   brut. Comportement strictement IDENTIQUE aujourd'hui (tout est
   encore global, nom court == FQCN actuel) - donc ce sous-pas est
   testable et commitable SANS AUCUN risque, avant meme de toucher un
   seul `namespace`. C'est la fondation qui rend tout le reste du lot
   sur - et le sous-pas le plus consommateur de temps/attention de ce
   lot, a traiter dans une passe dediee plutot qu'en fin de session.
2. 5.2 Namespacer les 41 entites du coeur + le reste de `src/`
   (`Controller/`/`Datatable/`/`Form/` par module, pas seulement
   `Entity/`) suivant la convention actee (`<component>\<Module>\...`
   pour `src/`). Strategie de compatibilite nom court a trancher et
   verifier specifiquement contre toute comparaison `get_class($x) ===
   'NomCourt'` (ne survit PAS a un `class_alias()` - necessite soit de
   corriger ces comparaisons vers `(new ReflectionClass($x))
   ->getShortName() === 'NomCourt'`, soit de les recenser et accepter
   le changement de comportement au cas par cas).
3. 5.3 Completer la harmonisation `dclass/` -> `Devups\...` pour ce qui
   reste en namespace global (hors les ~15 fichiers deja migres,
   coexistence saine, pas de priorite).
4. 5.4 Conteneur PSR-11 (nouveau, pas de conversion a risque - *ajout*
   pur).
5. 5.5 Log PSR-3 (`dclass/devups/Log.php` existe deja - verifier son
   API contre l'interface PSR-3 `Psr\Log\LoggerInterface`, adapter si
   besoin).
6. 5.6 Tests + `UPGRADE-8.0.md` - section "LOT 5 - PSR-4, conteneur,
   Log" (sera la plus longue du document, plusieurs sous-sections).
7. 5.7 Push + CI, probablement en plusieurs commits/plusieurs passes
   de verification CI intermediaires compte tenu du risque (pas
   necessairement une seule CI verte en fin de lot comme pour les lots
   precedents - a juger a l'avancement).

### LOT 5.2 - EN COURS (namespacer les entites, strategie class_alias validee sur pilote)

**Strategie de compatibilite tranchee** (point (c) laisse ouvert en LOT
5.0) : `class_alias(FQCN, 'NomCourt')` juste apres chaque classe
d'entite nouvellement namespacee, PLUTOT que de reecrire les centaines
de sites qui l'instancient/l'appellent par son nom court
(`new Dvups_lang()`, `Dvups_lang::xxx()`, `targetEntity: "\Dvups_lang"`,
type hints...). Justifie par un audit exhaustif : `grep` de
`get_class\([^)]*\)\s*==` sur tout le depot hors vendor ne trouve QUE 2
occurrences, toutes deux en commentaire mort (`ModelTrait.php:1264`,
`DBAL.php:1546`) - donc aucun site vivant ne fait la comparaison de
chaine que `class_alias()` ne survit pas (voir note Lot 5.0). Valide
de bout en bout sur un pilote complet (`Dvups_lang`, entite de base
utilisee par toutes les entites traduites via `Dv_langCore`) :
`composer test` (423 tests, 0 echec) et `composer phpstan` (0 erreur,
apres ajout du mecanisme de stub ci-dessous) inchanges.

**Recette mecanique par entite** (a repeter module par module, un
commit par module ou petit groupe de modules) :

1. Ajouter `namespace $component\$Module\Entity;` juste apres `<?php`
   (convention deja actee en LOT 5.0 - `$component` = nom du
   sous-dossier racine de `src/` : `devups`/`devupscms`/`devupsfront`,
   PAS `Devups\` ni `src\`).
2. Prefixer d'un `\` chaque reference a une classe du namespace global
   utilisee SANS qualification DANS ce fichier (`extends Model` ->
   `extends \Model`, `implements JsonSerializable` ->
   `implements \JsonSerializable`, `catch (Exception $e)` ->
   `catch (\Exception $e)`, `Request::get(...)` -> `\Request::get(...)`,
   etc.) - les FONCTIONS globales (`dv_valid_lang()`...) et les
   CONSTANTES (`ROOT`, `__lang`...) n'ont pas besoin de ce prefixe (PHP
   retombe automatiquement sur le global pour elles, PAS pour les
   classes). Une reference non qualifiee a une AUTRE classe DEJA dans
   le MEME nouveau namespace (rare, cas d'auto-reference type
   `Dvups_lang::xxx()` dans `Dvups_lang` lui-meme) n'a besoin d'aucun
   prefixe - elle se resout directement.
3. Juste apres l'accolade fermante de la classe :
   `class_alias(<FQCN>::class, '<NomCourt>');` (commentaire-type a
   reprendre : voir `Dvups_lang.php`).
4. PHPStan ne voit pas les alias crees par `class_alias()` a
   l'execution - sans action, chaque site qui reference encore le nom
   court devient "Call to method on an unknown class"/"Instantiated
   class not found" (~50 erreurs sur `Dvups_lang` seul). Corrige une
   fois pour toutes par un mecanisme de stub : `phpstan-stubs/
   class_alias_stubs.php` (jamais autoload a l'execution - hors
   `classmap` de `composer.json`, lu uniquement par phpstan via
   `scanFiles` dans `phpstan.neon.dist`) declare `class <NomCourt>
   extends <FQCN> {}` pour chaque entite migree - une ligne a AJOUTER
   dans ce fichier a chaque nouvelle entite namespacee.
5. Si l'entite a deja une entree `phpstan-baseline.neon` referencant
   son ancien nom court (`Dvups_lang::xxx()`), la basculer vers le FQCN
   complet (`devups\ModuleLang\Entity\Dvups_lang::xxx()`) - sinon le
   baseline ne matche plus et `composer phpstan` echoue (meme message,
   signale "non matched" + rapporte comme nouvelle erreur).
6. `composer test` + `composer phpstan` verts avant de passer a
   l'entite/module suivant.

**ModuleAdmin (devups) - FAIT** : `Dvups_action`, `Dvups_admin`,
`Dvups_privilege`, `Dvups_role` namespaces sous `devups\ModuleAdmin\
Entity`, alias + stubs phpstan ajoutes. Piege confirme pendant cette
passe : la recherche initiale de `extends Model implements
JsonSerializable` ne suffit PAS a trouver tous les sites a prefixer -
`Dvups_role::buildRights()` appelait `Model::pdo()` plus loin dans le
fichier (erreur `Class "devups\ModuleAdmin\Entity\Model" not found`),
de meme `Dvups_admin::dropDvups_roleCollection()` (`EntityCollection::`)
et `Dvups_role::updateConfigs()` (`Core::buildOriginCore()`) - tous
caches dans des methodes, pas vus par une recherche limitee a la
declaration de classe. **Recherche fiable retenue** (etape 2 de la
recette ci-dessus, a refaire pour CHAQUE entite plutot que scanner
seulement l'entete) : `grep -oE '\b[A-Z][A-Za-z0-9_]*::|new
[A-Z][A-Za-z0-9_]*|catch \([A-Z][A-Za-z0-9_]*|instanceof
[A-Z][A-Za-z0-9_]*' <fichier>` puis verifier un par un si chaque hit est
du code mort (commentaire) ou vivant - trouve par `composer test`
(seule execution reelle du code revele l'erreur), confirme que
`composer phpstan` seul ne l'aurait pas signale non plus (la classe
`Model` existe bien globalement, phpstan ne sait juste pas qu'elle
n'est plus visible depuis le nouveau namespace au niveau du typage
statique de ce depot - niveau 1).

**ModuleConfig + ModuleLog (devups) - FAITS** : `Configuration`,
`Dvups_entity`, `Dvups_entity_lang`, `Dvups_log` namespaces. Composant
`devups` entierement migre (8 entites : ModuleAdmin + ModuleConfig +
ModuleLog). Rien de notable - la recherche large (voir recette
ci-dessus) a directement trouve tous les sites (`Notification::of()`,
`DBAL`/`new DBAL()` dans `Dvups_entity::describe()`), aucun miss cette
fois.

**ModuleCms (devupscms) - FAIT** : `Cmscomment`, `Cmscontent`,
`Cmscontent_lang`, `Cmsresource` namespaces sous `devupscms\ModuleCms\
Entity`. Confirme une nuance importante de la recette (etape 2) :
une reference non qualifiee a une AUTRE entite reste SANS prefixe
uniquement si cette entite vit dans le MEME module (donc le meme
namespace `$component\$Module\Entity` - ex. `new Cmscontent()` dans
`Cmscomment.php`, toutes deux dans `ModuleCms`) ; une reference a une
entite d'un AUTRE module (ex. `new User()`, `Tree_item::...` dans
`Cmscomment.php`/`Cmscontent.php`, modules `ModuleUser`/`ModuleTree`)
a BESOIN du prefixe `\`, qu'elle soit deja migree ou pas (l'alias
rend le nom court valide globalement, mais ne change rien a la regle
de resolution PHP : une classe non qualifiee se resout TOUJOURS dans
le namespace courant du fichier, jamais par repli automatique vers le
global, contrairement aux fonctions/constantes - voir etape 2 de la
recette, precisee ici).

**ModuleData (devupscms) - FAIT** : `Interval`, `Status`, `Status_lang`
namespaces sous `devupscms\ModuleData\Entity`. `Interval_group` etait
deja namespace (meme namespace) **avant ce chantier** - trouvaille
confirmant l'hypothese du LOT 5.1 (le bug `self::shortClassName()` dans
`ModelTrait` trouve par phpstan le mentionnait deja) : une tentative de
migration partielle anterieure, non documentee, a laisse cette seule
entite `extends \stdClass { use \ModelTrait; }` namespacee - sans
alias, mais sans consequence puisque son seul appelant
(`IntervalController.php`) l'importe deja par sa FQCN. Alias ajoute ici
par coherence avec le reste du module (voir commentaire dans le
fichier).

**ModuleLocation (devupscms) - FAIT** : `Country`, `Country_lang`
namespaces sous `devupscms\ModuleLocation\Entity`. Rien de notable.

**ModuleNotification (devupscms) - FAIT** : `Notification`,
`Notificationtype`, `Notificationtype_lang` namespaces sous
`devupscms\ModuleNotification\Entity`. Fichier `Notification.php` le
plus charge rencontre jusqu'ici (~15 classes/sites a prefixer :
`Model`, `Dvups_lang`, `Configuration`, `Request`, `Exception`,
`Reportingmodel`, `Genesis`, `Push_subscription`...) - confirme que
`Notification.php` importait DEJA `use devupscms\ModulePushNotification
\Entity\Workers;` (FQCN complete) AVANT ce chantier, meme trouvaille
que `Interval_group`/`Workers` lui-meme (ModulePushNotification) : la
migration partielle anterieure touchait plusieurs modules, pas
seulement ModuleData. Rien de casse par cette dependance croisee
prematuree - l'import `use` pointait deja vers la FQCN correcte.

**ModulePushNotification (devupscms) - FAIT** : `Push_subscription`
namespacee sous `devupscms\ModulePushNotification\Entity`. `Workers`
etait deja namespacee avant ce chantier (3e survivante de la migration
partielle anterieure, apres `Interval_group` et le `use` FQCN dans
`Notification.php` - tous ses appelants l'importent deja par sa FQCN,
alias ajoute par coherence sans necessite stricte).

**ModuleReporting (devupscms) - FAIT** : `Emaillog`, `Push_email`,
`Reportingmodel`, `Reportingmodel_lang` namespaces sous
`devupscms\ModuleReporting\Entity`. Rien de notable.

**ModuleTree (devupscms) - FAIT** : `Tree`, `Tree_item`,
`Tree_item_image`, `Tree_item_lang`, `Tree_lang` namespaces sous
`devupscms\ModuleTree\Entity`. `Tree_item.php` le plus charge de ce
module (recette de scan complet du fichier appliquee, pas seulement la
ligne de declaration de classe) : references croisees corrigees vers
`\Dfile::` (3 occurrences) et `\Cmscontent::where($this)` (module
ModuleCms) ; `Tree::`/`Tree_item::`/`new Tree()`/`new Tree_item()`
laisses sans prefixe (meme module, meme namespace) ; les constantes
`Tree_item_image::class` passees a `$this->__hasmany(...)`/
`$this->__hasone(...)` laissees telles quelles (meme module -
`::class` resout correctement une fois le fichier cible lui-meme
namespace, aucune dependance a `class_alias()` necessaire ici).
`Tree_item_image.php` : `extends ImageCore implements JsonSerializable`
-> prefixes, 3x `Dfile::deleteFile(...)` prefixees ; `self::Dfile($file)`
laisse (liaison statique tardive, jamais besoin de namespace). 3
entrees `phpstan-baseline.neon` mises a jour vers la FQCN
(`Tree_item::getParent()`, constructeurs `Tree_item_lang`/`Tree_lang`
avec parametre `$id` inutilise).

**ModuleUser (devupsfront) - FAIT** : `Oauth_client`, `User`
namespaces sous `devupsfront\ModuleUser\Entity`. `Oauth_client.php`
etait DEJA namespace avant ce chantier (4e survivante de la migration
partielle anterieure, apres `Interval_group`, `Workers` et le `use`
FQCN dans `Notification.php`) - seul l'alias manquait. `User.php` :
fichier le plus charge en volume (300+ lignes) mais peu de sites a
corriger - `extends UserCore implements JsonSerializable,
NotificationSerialize` prefixes, `new Country()` (module
ModuleLocation) prefixe, 6x `Dfile::` prefixees, `Push_subscription::
where(...)` (module ModulePushNotification) prefixe ; `new
\RateLimiter(...)` deja prefixe, `self::Dfile($file)` (liaison statique
tardive) et `User::classpath(...)` (auto-reference meme module)
laisses tels quels. Aucune entree `phpstan-baseline.neon` a mettre a
jour pour ce module.

**LOT 5.2 : les 35 entites de `src/**/Entity/*.php` sont desormais
toutes namespacees** (`devups` : ModuleAdmin/ModuleConfig/ModuleLog ;
`devupscms` : ModuleCms/ModuleData/ModuleLocation/ModuleNotification/
ModulePushNotification/ModuleReporting/ModuleTree ; `devupsfront` :
ModuleUser), chacune avec son alias `class_alias()` de compatibilite
et son stub `phpstan-stubs/class_alias_stubs.php` correspondant.
`composer test` (423 tests, 0 echec) et `composer phpstan` (0 erreur)
verifies verts apres chaque module tout au long du lot.

**Point (b) du LOT 5.0 - EN COURS (namespacer Controller/Datatable/
Form de chaque module src/<component>/<Module>/)**

**Decouverte d'architecture importante avant de commencer** : une
revue de `dclass/` montre que `dclass/devups/Controller/*.php`
(`Controller`, `ControllerCore`, `CrudTrait`, `FrontController`,
`ModuleController`) et `dclass/devups/Datatable/*.php` (`Datatable`,
`Dbutton`, `Lazyloading`, `ListeView`, `TableData`, `TableRow`) sont
DEJA namespaces sous `dclass\devups\Controller\`/`dclass\devups\
Datatable\` (travail anterieur a ce chantier, hors LOT 5.2). En
revanche `dclass/devups/Form/{Form,FormFactory,FormManager}.php`, et
surtout la quasi-totalite des classes "coeur" referencees partout avec
un `\` (`Model`, `Dfile`, `Genesis`, `Request`, `Response`, `Route`,
`Router`, `RateLimiter`, `Log`, `Core`, etc. - 63 fichiers sur 84 sous
`dclass/`) restent en namespace global. Namespacer ces classes coeur
serait un LOT a part, bien plus large et risque que le point (b)
(elles sont le "\" cible de TOUTES les references cross-module vues
dans tout LOT 5.2) - **decision (la plus prudente) : NE PAS les
toucher ici**, le point (b) se limite aux fichiers Controller/
Datatable/Form PROPRES A CHAQUE MODULE (ex. `Dvups_langController.php`,
pas `dclass/devups/Controller/Controller.php`). A documenter comme un
LOT separe si besoin plus tard.

**Nuance de recette decouverte sur le pilote (ModuleLang)** : contrairement
aux entites (ou deux entites du MEME module partagent le MEME namespace
`...\Entity`), Controller/Datatable/Form/Entity d'un meme module ont
chacun leur PROPRE namespace (`...\Controller`, `...\Datatable`,
`...\Form`, `...\Entity`). Il n'y a donc PAS d'exemption "meme module" :
un Controller qui reference son Entity/Datatable/Form soeur (meme
module) doit TOUJOURS prefixer par `\` (contrairement a Entite->Entite
du meme module, qui restait sans prefixe). Seules les auto-references
(le Controller vers lui-meme) restent sans prefixe.

**Pilote ModuleLang (devups) - FAIT** : `Dvups_langController`,
`Dvups_langTable`, `Dvups_langForm` namespaces sous `devups\
ModuleLang\{Controller,Datatable,Form}`. `Dvups_langController.php` :
toutes les refs vers `Dvups_lang`/`Dvups_langTable` (code commente
laisse tel quel, harmless) prefixees. `Dvups_langTable.php` : `Request::`
prefixe (classe coeur). `Dvups_langForm.php` : `extends FormManager`
et `Genesis::renderView(...)` prefixes (classes coeur) ; `use Genesis
as g;` laisse (un `use` est deja absolu, prefixe ou non). 2 entrees
`phpstan-baseline.neon` mises a jour vers la FQCN (`Dvups_langController
::$datatable`, `Dvups_langForm::getFormData()`) - **pattern recurrent
attendu pour quasi CHAQUE Controller/Form migre** (baseline a deja une
entree "undefined property ...Controller::$datatable" et "undefined
static method ...Form::getFormData()" par module, consequence de
`ControllerCore`/`FormManager` non entierement types - voir baseline
pour la liste complete des entrees a mettre a jour au fur et a mesure).

**ModuleConfig + ModuleLog (devups) - FAIT** : `ConfigurationController/
Table/Form` et `Dvups_logController/Table/Form` namespaces. Rien de
notable, meme recette que le pilote.

**ModuleAdmin (devups) - FAIT (13 fichiers)** : tous les Controller/
Datatable/Form de `Dvups_action`, `Dvups_admin` (+ `Dvups_adminFrontController`),
`Dvups_privilege`, `Dvups_role` namespaces. Deux trouvailles notables :
- `Dvups_adminFrontController.php` etait DEJA namespace avant ce
  chantier (5e survivante de la migration partielle anterieure, apres
  Interval_group/Workers/l'import dans Notification.php/Oauth_client) -
  seul `new RateLimiter(...)` (2x) n'etait pas prefixe. **Ce n'etait PAS
  juste une incoherence de style : phpstan confirme qu'avant notre fix
  c'etait un bug latent reel** - `RateLimiter` aurait ete recherche sous
  `devups\ModuleAdmin\Controller\RateLimiter` (n'existe pas) a l'execution,
  une erreur fatale sur `connexionAction()`/toute tentative de login admin
  via ce front controller. L'entree baseline correspondante ("Instantiated
  class ...RateLimiter not found") existait deja (symptome deja detecte
  par phpstan avant ce chantier mais jamais corrige) et a ete retiree
  (plus aucune erreur a ignorer, le bug est reellement corrige).
- `Dvups_roleForm::renderWidget()` est appelee par `src/devups/
  ModuleAdmin/services.php` (fichier de config, namespace global) sans
  argument alors que la signature en requiert - bug preexistant deja
  connu et baseline (`arguments.count`), inchange par cette migration,
  juste mis a jour vers la FQCN.
- Entites reference deja-meme-module traitees comme cross-namespace
  (ex. `Dvups_role`/`Dvups_privilege` entre eux) - confirme la nuance
  Controller/Datatable/Form decrite plus haut.

**ModuleCms (devupscms) - FAIT (11 fichiers)** : tous les Controller/
Datatable/Form de `Cmscomment`, `Cmscontent` (+ `CmscontentFrontController`),
`Cmsresource` (+ `CmsresourceFrontController`) namespaces. Trouvaille :
`CmsresourceFrontController::ll()` fait `new Lazyloading()` sans prefixe -
`Lazyloading` (dclass/devups/Datatable/Lazyloading.php) est DEJA namespace
sous `dclass\devups\Datatable\` (travail anterieur, comme Controller/
Datatable "coeur" - voir plus haut), donc meme AVANT ce chantier cet
appel etait deja casse (classe introuvable en global) - confirme par
une entree `phpstan-baseline.neon` deja presente ("Instantiated class
Lazyloading not found") AVANT notre edition. **Decision (la plus
prudente) : ne pas corriger ce bug pre-existant ici** (hors scope du
point (b), qui est une migration de namespace, pas une revue
fonctionnelle) - prefixe par `\` par coherence avec la recette (le
comportement/l'erreur phpstan reste identique, aucune regression
introduite, le bug reste documente dans le meme message baseline
inchange). A traiter separement si quelqu'un relance ce chantier.

**ModuleData (devupscms) - FAIT (6 fichiers)** : `IntervalController`,
`StatusController`, `IntervalTable`, `StatusTable`, `IntervalForm`,
`StatusForm` namespaces (`devupscms\ModuleData\{Controller,Datatable,
Form}`). `IntervalController.php` conservait un `use
devupscms\ModuleData\Entity\Interval_group;` preexistant - garde tel
quel (resolution par `use`, aucun changement necessaire). Aucune
trouvaille notable (pas de bug latent comme ModuleCms/ModuleAdmin) -
migration mecanique standard.

**ModuleLocation (devupscms) - FAIT (4 fichiers)** : `CountryController`
(+ `CountryFrontController`), `CountryTable`, `CountryForm` namespaces.
Meme trouvaille que ModuleCms : `CountryFrontController::ll()` fait
`new Lazyloading()` sans prefixe - meme bug pre-existant (classe
introuvable en global, confirme par l'entree baseline deja presente
AVANT notre edition) - **non corrige ici**, meme decision que pour
`CmsresourceFrontController` (hors scope du point (b)).

**ModuleNotification (devupscms) - FAIT (7 fichiers)** :
`NotificationController`, `NotificationtypeController` (+
`NotificationFrontController`), `NotificationTable`,
`NotificationtypeTable`, `NotificationForm`, `NotificationtypeForm`
namespaces. Trouvaille : `NotificationFrontController.php` avait deja
une declaration `namespace ...;` + plusieurs `use ...;` mais entierement
commentee (`/* ... */`), laissant le fichier reellement dans le
namespace global - remplacee par la vraie declaration `namespace
devupscms\ModuleNotification\Controller;` (les `use` n'etaient plus
necessaires, les references etant deja qualifiees ou desormais
prefixees par `\`). Les attributs PHP (`#[Api(...)]`, `#[GET]`,
`#[Auth(...)]`, `#[PUT(...)]`) referencent aussi des classes globales -
prefixes par `\` comme toute autre reference de classe (meme regle de
resolution de nom).

**ModulePushNotification (devupscms) - FAIT (7 fichiers)** :
`WorkersController`, `Push_subscriptionController` (+
`Push_subscriptionFrontController`), `WorkersTable`,
`Push_subscriptionTable`, `WorkersForm`, `Push_subscriptionForm`
namespaces. `WorkersController.php` conservait un `use
devupscms\ModulePushNotification\Entity\Workers;` preexistant - garde
tel quel (resolution par `use`, aucun `\` ajoute devant `Workers`
contrairement a `Push_subscription`, qui n'avait pas cet import et a
donc ete prefixe). Meme trouvaille que `NotificationFrontController` :
`Push_subscriptionFrontController.php` avait une declaration
`namespace ...;` + `use ...;` entierement commentee - remplacee par la
vraie declaration ; attributs PHP (`#[Api]`, `#[Auth]`, `#[GET]`,
`#[POST]`) prefixes par coherence. L'import `use
Minishlink\WebPush\VAPID;` (bibliotheque externe) laisse tel quel -
resolution par `use` independante du namespace du fichier, `VAPID`
reste donc non-prefixe dans le code (meme regle que pour les entites
importees par `use`).

**ModuleReporting (devupscms) - FAIT (9 fichiers)** : `EmaillogController`,
`Push_emailController`, `ReportingmodelController`, `EmaillogTable`,
`Push_emailTable`, `ReportingmodelTable`, `EmaillogForm`,
`Push_emailForm`, `ReportingmodelForm` namespaces. Module le plus dense
en logique de ce point (b) (envoi d'emails/SMS, cron, gabarits Blade) -
aucune trouvaille de bug latent, uniquement une migration mecanique
(nombreux commentaires de code mort avec des references de classe non
prefixees - laisses tels quels, hors du scope de la migration).

**ModuleTree (devupscms) - FAIT (10 fichiers)** : `TreeController`,
`Tree_itemController` (+ `Tree_itemFrontController`),
`Tree_item_imageController`, `TreeTable`, `Tree_itemTable`,
`Tree_item_imageTable`, `TreeForm`, `Tree_itemForm`,
`Tree_item_imageForm` namespaces. `Tree_itemController.php`/
`Tree_itemFrontController.php` conservaient un `use
dclass\devups\Datatable\Lazyloading;` preexistant - garde tel quel
(resolution par `use`, `Lazyloading` reste non-prefixe). Trouvaille
(meme pattern que `Lazyloading`/`CmsresourceFrontController`) :
`Tree_itemController::getdata()` fait `Category::getmaincategory()`
alors qu'aucune classe `Category` n'existe dans tout le depot - bug
latent pre-existant (confirme par l'entree baseline deja presente
"unknown class Category" AVANT notre edition) - **non corrige ici**,
meme decision que pour les deux autres cas similaires (hors scope du
point (b)).

**Point (b) : `devupscms` entierement termine (54 fichiers).**

**ModuleUser (devupsfront) - FAIT (7 fichiers)** : `UserController`
(+ `UserFrontController`), `Oauth_clientController`, `UserTable`,
`Oauth_clientTable`, `UserForm`, `Oauth_clientForm` namespaces.
`Oauth_clientController.php`/`Oauth_clientTable.php`/`Oauth_clientForm.php`
conservaient un `use devupsfront\ModuleUser\Entity\Oauth_client;`
preexistant - garde tel quel (resolution par `use`, `Oauth_client`
reste non-prefixe). **Fichier le plus volumineux de tout le point (b)**
(`UserFrontController.php`, ~1050 lignes, OAuth Google/Apple,
JWT, RateLimiter, reset password) - deux corrections necessaires
AU-DELA de la simple recette de prefixage, toutes deux liees a des
particularites de resolution de nom PHP sous namespace qui n'avaient
pas encore ete rencontrees dans ce chantier :
- `new Google\Client()` : nom QUALIFIE (contient un `\`) different
  d'un nom simple - un `use Google\Client;` n'affecte PAS la
  resolution d'un nom qualifie comme `Google\Client` (seuls les noms
  SIMPLES profitent d'un alias `use`). Sous namespace, ce
  `Google\Client` bare se serait resolu vers
  `devupsfront\ModuleUser\Controller\Google\Client` (inexistant) au
  lieu du `\Google\Client` de la librairie Composer - corrige en
  `new \Google\Client()`.
- `catch (Throwable $e)` (une seule occurrence sur les 3 de ce fichier)
  etait deja bare SANS backslash avant ce chantier (contrairement aux
  2 autres `catch (\Throwable $e)` du meme fichier, deja corrects) -
  sous namespace global (avant notre edit) cela resolvait
  correctement vers l'interface SPL globale par accident ; sous
  namespace (apres `namespace devupsfront\ModuleUser\Controller;`)
  cela aurait cherche une classe `devupsfront\ModuleUser\Controller\Throwable`
  inexistante. **Bug qui aurait ete introduit par cette migration si
  non corrige** - repere en relisant le fichier entierement apres le
  passage de `prefix.py` (qui ne traite que les noms simples, jamais
  les `catch()`/noms qualifies) - corrige en `catch (\Throwable $e)`.

**Point (b) : TERMINE - les 83 fichiers Controller/Datatable/Form des
17 modules (devups + devupscms + devupsfront) sont desormais tous
namespaces.** Bugs latents pre-existants trouves et **corriges** dans
le cadre de cette migration (classe deja namespacee avec une reference
non prefixee oubliee) : `RateLimiter` dans
`Dvups_adminFrontController.php` (ModuleAdmin). Bugs latents
pre-existants trouves et **deliberement non corriges** (hors scope -
pure migration de namespace, pas une revue fonctionnelle) : classe
`Lazyloading` introuvable dans `CmsresourceFrontController.php`
(ModuleCms) et `CountryFrontController.php` (ModuleLocation), classe
`Category` introuvable dans `Tree_itemController.php::getdata()`
(ModuleTree) - chacun confirme par une entree `phpstan-baseline.neon`
deja presente avant toute edition de ce chantier.

**Point (c) du LOT 5.0 - TERMINE (namespacer les classes de base
partagees `dclass/extends/*`).** Deliberement separe du point (b)
(decision prise a la fin de celui-ci : ces classes appartiennent au
framework lui-meme, pas a un module applicatif - risque et perimetre
differents). Les 5 fichiers de `dclass/extends/` namespaces sous
`dclass\devups\Core` (alias de compatibilite `class_alias()` comme
pour toutes les entites/controleurs deja migres) : `Dvups_config_item`,
`Dvups_migration`, `ImageCore`, `UserCore`, `Dv_langCore`.

Analyse prealable (grep de chaque nom de classe dans tout le depot,
avant toute edition) : toutes les entites qui etendent ces 5 classes
(`User`, `Tree_lang`, `Tree_item_lang`, `Tree_item_image`,
`Reportingmodel_lang`, `Notificationtype_lang`, `Country_lang`,
`Status_lang`, `Cmsresource`, `Cmscontent_lang`, `Dvups_entity`,
`Dvups_entity_lang`) reference deja leur classe de base avec un
`\` en tete (`extends \UserCore`, `extends \Dv_langCore`, etc.) -
consequence directe du point (a) (namespacage des entites) qui avait
deja du prefixer ces references cross-namespace a l'epoque. Resultat :
**aucun fichier d'entite n'a eu besoin d'etre modifie** - l'alias
`class_alias()` au nom court global suffit a satisfaire ces references
existantes, exactement comme prevu par la strategie.

Seuls les 5 fichiers de `dclass/extends/` eux-memes ont ete modifies :
ajout du `namespace dclass\devups\Core;`, puis prefixage des
references bare vers des classes du framework qui restent, elles,
dans le namespace global (`Model`, `JsonSerializable`, `Dfile`,
`DBAL`, `ModelTrait`, `User`, `Country`) - meme recette que pour le
point (b). `self::Dfile(...)` dans `ImageCore.php` n'est PAS une
reference de classe (c'est un appel a la methode statique
`Model::Dfile()`, heritee) - laisse tel quel.

`dclass/extends/extends_dependances.php` (simple suite de `require`
par chemin de fichier) et le generateur legacy
`dclass/generator/BackendGenerator.php` (qui emet du code PHP sans
aucune declaration de namespace, donc toujours valide tel quel) n'ont
necessite aucune modification.

**Effet de bord phpstan (pas une regression fonctionnelle) :** apres
namespacage de `UserCore.php`, l'erreur deja connue "Constant USER not
found" (constante jamais definie, bug latent pre-existant - deux
usages bare `$_SESSION[USER]` dans `userapp()`, deja presents avant ce
chantier) est desormais rapportee **2 fois au lieu d'1** par la meme
analyse - phpstan deduplique apparemment ce message par (regle, nom de
symbole, portee) quand la classe est dans le namespace global, mais
pas une fois namespacee (verifie par isolation : meme fichier, avec et
sans la ligne `namespace`, analyse hors de l'arborescence du projet).
Aucun changement de comportement a l'execution (la constante restait
indefinie dans les deux cas) : `phpstan-baseline.neon` mis a jour
(`count: 1` -> `count: 2`) plutot que le bug corrige (hors scope - pure
migration de namespace).

`composer test` (423 tests, 0 echec, 2 incomplete - inchange) et
`composer phpstan` (0 erreur) verts apres ce point (c).

**LOT 5.3 - REPORTE (decision prise avec l'utilisateur).** Perimetre
reel verifie avant toute edition : 58 fichiers de `dclass/` sont
encore en namespace global (ni `dclass\devups\...` ni `Devups\...`),
dont le **coeur runtime du framework** - `model/Model.php`,
`model/ModelTrait.php` (magie `__get`/`__set` tres utilisee),
`Tchutte/QueryBuilder.php`, `Tchutte/DBAL.php`, `Http/Router.php`,
`Http/Request.php`, `Genesis.php`, `RateLimiter.php`, les classes
`Form*`, et les classes-attributs `Api`/`Auth`/`GET`/`POST`/`PUT`/
`DELETE` utilisees comme attributs PHP dans tout le depot. Risque et
rayon d'impact largement superieurs aux points (a)/(b)/(c) (qui ne
touchaient que des classes feuilles, jamais etendues/appelees par le
reste du framework) - et le LOT 5.0 qualifiait deja explicitement ce
point de "pas de priorite". Decision : **reporte**, on passe a 5.4 (qui
est un ajout pur, sans conversion a risque). A reprendre plus tard,
eventuellement par sous-lot (ex. `Http/`/`annotation/` seuls, en
laissant `Model`/`ModelTrait`/`QueryBuilder`/`Router` pour une passe
dediee separee) plutot qu'en un seul bloc de 58 fichiers.

### LOT 5.4 - FAIT (conteneur PSR-11, ajout pur)

**Ajout pur, aucune conversion a risque** - conforme a la description
initiale du lot ("nouveau, pas de conversion a risque"). Aucun site
existant n'a ete modifie pour s'y brancher : le conteneur est
disponible pour du code futur qui choisirait de l'utiliser, sans
changer le comportement actuel de l'application (toute la resolution
de services existante - globals, `new X()` directs, statics -
continue de fonctionner exactement comme avant).

`psr/container` (`^2.0`, deja resolu a 2.0.2 de maniere transitive via
`illuminate/view` -> `illuminate/container`) ajoute comme dependance
EXPLICITE dans `composer.json` (`composer require psr/container:^2.0
--no-update` puis `composer update psr/container
--ignore-platform-req=php` - necessaire car le PHP CLI local est en
8.2.12, alors que `composer.json` exige `>=8.3` ; la CI GitHub, sur
8.3/8.4/8.5, n'a pas ce probleme. Aucun changement de version dans
`composer.lock`, seule la nouvelle dependance directe y apparait).

Deux nouvelles classes, namespace `Devups` (meme convention que
`Devups\View`, seul precedent existant pour du code neuf de ce
chantier - voir note LOT 5.3 ci-dessus sur la cible de namespace) :

- `dclass/devups/Container.php` - `Devups\Container implements
  Psr\Container\ContainerInterface`. API minimale : `set($id, $value)`
  (valeur deja construite), `bind($id, callable $factory)` (fabrique
  appelee a CHAQUE resolution), `singleton($id, callable $factory)`
  (fabrique resolue une seule fois, resultat mis en cache), plus
  `get()`/`has()` du contrat PSR-11.
- `dclass/devups/ContainerNotFoundException.php` - `Devups\
  ContainerNotFoundException extends RuntimeException implements
  Psr\Container\NotFoundExceptionInterface`, levee par `get()` pour un
  identifiant inconnu.

`tests/Unit/ContainerTest.php` (8 tests, namespace `Devups\Tests\Unit`
comme le reste de la suite) : contrat PSR-11 (`instanceof
ContainerInterface`), `set`/`get`, `has` apres `set`/`bind`, `bind`
rappelle la fabrique a chaque resolution (valeurs distinctes),
`singleton` ne resout qu'une fois (meme instance, fabrique appelee 1
seule fois), la fabrique recoit bien le conteneur en argument,
`get()` sur un identifiant inconnu leve `ContainerNotFoundException`
(et `NotFoundExceptionInterface`), `set()` ecrase une fabrique
precedemment `bind()`ee pour le meme identifiant.

`composer dump-autoload --ignore-platform-req=php` requis apres
l'ajout des 2 nouveaux fichiers (classmap, pas de regeneration
automatique). `composer test` (431 tests = 423 + 8 nouveaux, 0 echec,
2 incomplete inchanges) et `composer phpstan` (0 erreur, aucun stub
necessaire - `psr/container` est un paquet reellement installe, pas un
alias `class_alias()`) verts.

**Portee explicitement non traitee ici** (ajout pur, pas une
conversion) : aucun site existant (View.php/Container Illuminate
interne, globals `$enittycollection`/`$user`, instanciations directes
`new Model()`-like...) n'a ete bascule vers ce conteneur. Le brancher
reellement dans le framework (ex. resolution de services dans
`Router`/`Controller`) serait une conversion a risque, hors perimetre
de ce lot - a decider au cas par cas plus tard si besoin.

### LOT 5.5 - FAIT (adaptateur PSR-3 au-dessus de `\Log`)

**Audit prealable** (cf. description initiale du lot : "`Log.php`
existe deja - verifier son API contre `Psr\Log\LoggerInterface`,
adapter si besoin") : `\Log` (dclass/devups/Log.php) est une facade
**statique** (`Log::error()`/`warning()`/`info()`, 3 niveaux, agregation
par empreinte en base + debordement fichier + gardes globales) alors
que `Psr\Log\LoggerInterface` (psr/log ^3, deja resolu a 3.0.2 de
maniere transitive) est un contrat **d'instance** a 8 niveaux
(emergency/alert/critical/error/warning/notice/info/debug) plus
`log($level, $message, $context)`. Une classe statique ne peut pas
implementer une interface PHP (les methodes d'interface sont
necessairement d'instance) - **reecrire `\Log` en instance casserait
tous les appelants existants** (11 fichiers de production + plusieurs
tests, `Log::error(...)` partout). Decision (la plus prudente, comme
pour le LOT 5.4) : **adaptateur**, pas de reecriture - `\Log` reste
l'implementation reelle unique, inchangee.

`psr/log` (`^3.0`) ajoute comme dependance EXPLICITE dans
`composer.json` (meme procedure que `psr/container` en LOT 5.4 :
`composer require psr/log:^3.0 --no-update` puis `composer update
psr/log --ignore-platform-req=php` - aucun changement de version dans
`composer.lock`).

`dclass/devups/Logger.php` - `Devups\Logger implements
Psr\Log\LoggerInterface`. Mapping delibere (le plus simple possible,
documente dans le fichier) : les 4 niveaux PSR-3 les plus graves
(`emergency`/`alert`/`critical`/`error`) vers `Log::error()`,
`warning` vers `Log::warning()`, les 3 plus benins
(`notice`/`info`/`debug`) vers `Log::info()` - `\Log` n'a jamais eu
que 3 niveaux et aucun appelant existant n'a besoin de plus de
finesse. `log($level, ...)` inconnu leve `Psr\Log\
InvalidArgumentException` (conforme au contrat PSR-3). Convention
PSR-3 pour les exceptions : si `$context['exception']` est un
`\Throwable`, c'est LUI (pas le message texte) qui est transmis a
`\Log::`, pour garder la capture reelle classe/fichier/ligne/trace de
`\Log::write()` - la cle `exception` est retiree du contexte transmis
(un `\Throwable` brut ferait echouer le `json_encode()` de
`\Log::persist()`).

**Ajout pur** : les 11 fichiers appelant deja `Log::error()`/
`warning()`/`info()` directement n'ont pas ete touches - `Devups\
Logger` est disponible pour du code qui attend specifiquement un
`Psr\Log\LoggerInterface` (ex. une bibliotheque tierce), sans rien
changer au comportement actuel.

`tests/Integration/LoggerTest.php` (14 tests, `DatabaseTestCase` comme
`LogTest.php` existant - ne re-teste PAS l'agregation/le debordement
fichier/la purge, deja couverts par `LogTest.php`, seulement la
traduction de niveaux et le contexte) : implements `LoggerInterface`,
chacun des 8 niveaux + `log()` generique route vers le bon niveau
`\Log::`, niveau inconnu leve `InvalidArgumentException`, `exception`
en contexte devient le sujet du log avec sa vraie classe/fichier,
contexte arbitraire transmis, cle `exception` jamais persistee.
Attributs `#[DataProvider(...)]` (PHPUnit 11, pas l'annotation
`@dataProvider` deja deprecated ailleurs dans la suite - pas de
nouvelle deprecation ajoutee).

`composer dump-autoload --ignore-platform-req=php` requis apres le
nouveau fichier. `composer test` (445 tests = 431 + 14 nouveaux, 0
echec, 8 deprecations preexistantes inchangees, 2 incomplete
inchanges) et `composer phpstan` (0 erreur) verts.

### LOT 5.6 - FAIT (tests + UPGRADE-8.0.md)

**Couverture** : chaque point (a/b/c/5.4/5.5) a deja sa propre suite
de tests dediee (class_alias valide par pilote + regression complete
a chaque commit pour a/b/c ; 8 tests `ContainerTest.php` pour 5.4 ; 14
tests `LoggerTest.php` pour 5.5) - aucun test supplementaire n'etait
necessaire a ce stade, juste la verification que l'ensemble tient
toujours ensemble : `composer test` (445 tests, 0 echec) et `composer
phpstan` (0 erreur) confirmes une derniere fois pour tout le LOT 5.

**`UPGRADE-8.0.md`** : section "LOT 5 - PSR-4, conteneur, Log"
ajoutee. Plus courte que prevu dans le plan initial du LOT 5.0 ("sera
la plus longue du document, plusieurs sous-sections") - **consequence
directe du choix delibere, a chaque etape, de la strategie la plus
prudente** (`class_alias()` pour le namespacage, adaptateurs/ajouts
purs pour le conteneur et le logger) : aucun point de LOT 5 n'introduit
de rupture reelle, donc rien a documenter en "comment migrer" au sens
propre - seulement ce qui est NOUVEAU et desormais disponible
(FQCN des classes namespacees, `Devups\Container`, `Devups\Logger`),
sans obligation d'y toucher.

**LOT 5 (points a, b, c, 5.4, 5.5, 5.6) : TERMINE.** LOT 5.3 reste
**reporte** (decision prise avec l'utilisateur, voir note dediee
ci-dessus) - a reprendre plus tard, hors de ce chantier ou dans un
sous-lot dedie.

## LOT 6 - HTTP (Request/Response, middlewares)

**Audit prealable** (meme methode que pour le LOT 5 : pas de code avant
d'avoir mesure le risque reel) : `dclass/devups/Http/Request.php` (792
lignes) et `Router.php` (797 lignes) sont le coeur de dispatch de
CHAQUE requete HTTP (routage, cache, garde d'authentification) -
classes-facade statiques, espace de noms global. `Response.php` et
`Route.php`, plus petites, sont aussi reellement utilisees (verifie par
grep - pas du code mort). **Aucun concept de middleware n'existe
aujourd'hui** dans le depot.

**Decision prise avec l'utilisateur** (meme logique de prudence que le
LOT 5.3/5.4/5.5) : **ajout pur** - rien dans `Router`/`Request`/
`Response`/`Route` (espace de noms global, comportement de dispatch
reel) n'est modifie ni appele par ce qui suit. Alternative "brancher
reellement un point d'accroche dans `Router::runServe()`/
`manageServe()`" explicitement ecartee (rayon d'impact trop grand pour
un besoin encore hypothetique - aucun appelant reel n'attend de
middleware aujourd'hui).

**PSR-7** (`Psr\Http\Message\...`) : deja disponible de maniere
transitive via `guzzlehttp/psr7` (2.13.4, lui-meme amene par
`guzzlehttp/guzzle`) - pas de nouvelle dependance necessaire, et pas de
reimplementation ecrite ici (`GuzzleHttp\Psr7\ServerRequest::
fromGlobals()`/`GuzzleHttp\Psr7\Response` suffisent, voir
`UPGRADE-8.0.md`).

**PSR-15** (`psr/http-server-middleware` ^1.0, `psr/http-server-handler`
^1.0, plus `psr/http-message` ^2.0 rendu explicite) : ajoutes comme
dependances EXPLICITES (`composer require ... --no-update` puis
`composer update ... --ignore-platform-req=php`, meme procedure que les
LOTs 5.4/5.5 - ceux-ci, a la difference de `psr/container`/`psr/log`,
n'etaient PAS deja resolus de maniere transitive : 2 nouveaux paquets
dans `composer.lock`).

Trois nouvelles classes, `dclass/devups/Http/` (namespace `Devups\Http`
- physiquement dans le meme dossier que les classes HTTP globales
existantes, noms de fichiers distincts, aucune collision) :

- `MiddlewareStack.php` - `Devups\Http\MiddlewareStack implements
  Psr\Http\Server\RequestHandlerInterface`. Pipeline "oignon" classique
  (chaque middleware recoit la requete + un handler representant le
  reste du pipeline, peut inspecter/modifier avant/apres `$next->
  handle()`, ou court-circuiter en ne l'appelant pas). Instance
  immuable (`clone` + index incremente a chaque etape plutot qu'un
  compteur mutable partage) - une meme pile est reutilisable pour
  plusieurs requetes sans etat residuel entre elles.
- `CallableRequestHandler.php` - adapte un `callable(ServerRequestInterface):
  ResponseInterface` en `RequestHandlerInterface`, pour fournir le bout
  du pipeline sans ecrire une classe dediee a chaque usage.
- `ResponseEmitter.php` - envoie un `ResponseInterface` comme reponse
  HTTP reelle (code/en-tetes/corps). Les 3 effets de bord sont derriere
  des callables injectables (defaut `http_response_code()`/`header()`/
  `echo`) - testable sans emettre de vrais en-tetes pendant les tests.

**Ajout pur confirme** : aucun appelant existant (`Router`, les
controleurs, les FrontControllers) n'a ete modifie pour s'en servir.

`tests/Unit/{MiddlewareStackTest,CallableRequestHandlerTest,
ResponseEmitterTest}.php` (9 tests) : pipeline sans middleware (delegue
direct au handler final), ordre d'execution oignon (verifie par un
tableau de traces before/after), court-circuit (le handler final n'est
jamais appele), reutilisation de la meme pile pour 2 requetes
successives sans etat residuel, `CallableRequestHandler` transmet bien
la requete et retourne la reponse du callable, `ResponseEmitter` envoie
le bon code/chaque valeur d'en-tete avec le bon flag `$replace`/le bon
corps via les callables injectes, et un test avec les callables PAR
DEFAUT (pas d'injection) confirme qu'ils ne font pas echouer le test en
contexte CLI.

`composer dump-autoload --ignore-platform-req=php` requis apres les 3
nouveaux fichiers. `composer test` (454 tests = 445 + 9 nouveaux, 0
echec, 8 deprecations et 2 incomplete preexistants inchanges) et
`composer phpstan` (0 erreur) verts.

**CI rouge sur 8.4/8.5 (8.3 vert) au premier push de ce lot**, corrige
sans nouveau commit de code de production - uniquement le test fautif.
Log recupere via le navigateur (connexion GitHub de l'utilisateur,
l'API REST anonyme ne donne ni les logs ni le texte des annotations
pour ce depot) : `testDefaultCallablesDoNotErrorInCliContext` (callables
PAR DEFAUT de `ResponseEmitter`, donc le VRAI `http_response_code()`)
levait `http_response_code(): Cannot set response code - headers
already sent` - un AUTRE test de la suite (`HttpTestCase::request()`,
simulation HTTP reelle) avait deja produit une sortie reelle plus tot
dans le MEME processus PHPUnit, et `failOnWarning="true"`
(`phpunit.xml.dist`) transforme ce warning en echec. Artefact d'ordre
d'execution partage entre tests (reproductible sur 8.4/8.5, pas sur
8.3 dans ce run precis - pas une difference de langage PHP) : le test
garde desormais `if (headers_sent()) markTestSkipped(...)` avant
d'appeler les callables par defaut - le comportement REEL des
callables injectes (le cas qui compte vraiment) reste couvert par les
3 autres tests du fichier, inchanges.

### LOT 5.7 - FAIT (confirmation finale push/CI)

Chaque sous-etape de ce LOT 5 (a, b, c, 5.4, 5.5, 5.6) a deja ete
poussee et verifiee verte individuellement au fil du travail (CI
GitHub, matrice PHP 8.3/8.4/8.5) - pas une seule passe finale comme
pour les lots precedents, le risque plus structurant de celui-ci
justifiant "plusieurs commits/plusieurs passes de verification CI
intermediaires" (decide en LOT 5.0). Cette etape n'est donc qu'une
**confirmation** : `git status` propre, HEAD local = HEAD distant
(`c81bd46`), `composer test` (445 tests, 0 echec, 8 deprecations et 2
incomplete preexistants inchanges) et `composer phpstan` (0 erreur)
rejoues une derniere fois sans aucune modification de code entre les
deux - tout le LOT 5 tient ensemble.

### LOT 5.1 - FAIT (helper `Model::shortClassName()` + conversion des sites "non surs")

Revue ligne-par-ligne exhaustive des 19 fichiers listes en LOT 5.0 (plus
`Notification.php`, deja dans la liste), confirmant/corrigeant
l'echantillon initial. Introduction de `Model::shortClassName($classOrObject
= null): string` (`dclass/devups/model/Model.php`, juste apres la
declaration de classe) :

```php
public static function shortClassName($classOrObject = null): string
{
    return (new \ReflectionClass($classOrObject ?? get_called_class()))->getShortName();
}
```

Comportement strictement IDENTIQUE a `get_class()`/`get_called_class()`
aujourd'hui (tout est encore en namespace global) - zero changement de
comportement observable, verifie par `composer test` (423 tests, 0
echec, memes 2 incomplets/8 deprecations qu'avant ce lot) et `composer
phpstan` (0 erreur). Donc **aucune entree `UPGRADE-8.0.md` requise** pour
cette sous-etape (la regle "toute RUPTURE -> test + UPGRADE" ne
s'applique pas : il n'y a pas de rupture ici, seulement une fondation
pour la rupture a venir en 5.2).

**Sites convertis** (tous confirmes par lecture du code appelant -
derivation de nom de table SQL, de colonne `_id`, de cle de formulaire/
filtre/cache `$global_config`, jamais de reflection/instanciation) :

- `model/Model.php` : `post()`, `Dfile()`, le garde-fou
  `DvtranslateInspector::diagnose()`, `__hasone()` (x2), `apimapper()`,
  `link()`, `getDvupsEntity()`, `getStatusWhereKey()`.
- `model/ModelTrait.php` : `update()`, `inCollectionOf()`, `truncate()`.
- `model/EntityCore.php` : `__extract()`.
- `Core.php` : constructeur, `__extract()`.
- `Controller/Controller.php` : `lastpersistance()`, l'hydratation (le
  `$classname` derive pour `$_FILES`, distinct du `$classlang` qui reste
  la vraie classe pour `getClassMetadata()`), `addEventListenerAfterCreate()`.
- `Controller/FrontController.php` : `addEventListenerAfterCreate()`.
- `Datatable/Datatable.php` : constructeur, `addFilterParam()`, et les 2
  sites qui alimentaient `$this->class`/`$this->classname`.
- `Datatable/Lazyloading.php` : constructeur, `lazyloading()`.
- `Form/Form.php` : `open()`, `init()`, `imbricate()` (toutes les
  affectations de `Form::$name`).
- `Form/FormManager.php` : constructeur, le `->where()` de
  `Options_ToCollect_Helper()`.
- `Tchutte/DBAL.php` : `manyToManyUpdate()` (x2), `manyToManyDelete()`,
  `belongto()` (x2), `hasmany()` (x2, hors le site 1612 explique
  ci-dessous), `updateserialiseDbal()`, `orm()` (x4, hors le commentaire
  mort ligne 1546).
- `Tchutte/QueryBuilder.php` : `set()` (x2 sites), `on()`, le site de
  reflexion defensive `explode("\\", get_class($column))` simplifie en
  un seul appel au helper.
- `src/devupscms/ModuleNotification/Entity/Notification.php` : `on()`
  (lookup `$global_config`).

**Sites deliberement NON touches** (verifies un par un, tous ont besoin
de la VRAIE classe ou sont du texte cosmetique) :

- Partout : `getClassMetadata()`/`new $classname`/`ReflectionClass(...)
  ->newInstance()`/`ReflectionMethod($class, $method)` - reflection ou
  instanciation reelle (`DBAL::_createDbal()`, `DBAL::mappingProd()`,
  `ModelTrait::update()`/`find()`/`findrow()`/... - une quarantaine de
  sites, `Http/Request.php` x3).
- `Http/Router.php` (3 sites, lignes 434/548/632) : `$named =
  get_called_class()` alimente exclusivement `mapFunction($named,
  $function)` -> `new ReflectionAnnotatedClass($classname)` - verifie en
  lisant `mapFunction()` (ligne ~653).
- `Tchutte/DBAL.php` : `dbrow()` (`new $classname` ligne 1179),
  `djoin()` -> `Bugmanager::cast(..., get_class($object))` ligne 1660 -
  `cast()` fait `is_subclass_of($class, get_class($object))` puis un
  hack serialize/preg_replace/unserialize qui a besoin du VRAI FQCN pour
  retrouver la bonne classe - le changer casserait le cast ;
  `hasmany()` ligne 1612 - alimente `Model::__hasmany($collection)` qui
  fait `new ReflectionClass($collection)` quand ce n'est pas deja un
  objet - a besoin de la vraie classe ; `mappingProd()` ligne 1686 -
  alimente `getClassMetadata()` - **confirme l'incoherence interne
  deja signalee en LOT 5.0** (`$this->objectName` tantot court via
  `setClassname()`, tantot complet ici) : probleme structurel pour 5.2,
  pas touche ici.
- `Tchutte/QueryBuilder.php` : les 4 comparaisons `get_class($value) ==
  QueryBuilder::class` / `== \dclass\devups\Tchutte\Column::class`
  (lignes 388/539/542/928/931) - comparaison a un FQCN deja qualifie.
- `Tchutte/Bugmanager.php` : `cast()`, `is_subclass_of($class,
  get_class($object))` - a besoin de la vraie classe.
- `Log.php` : `get_class($t)` dans un message de log d'exception - texte
  informatif, plus juste avec le FQCN complet.
- `model/ModelSerializer.php`, `Controller/ModuleController.php`,
  `Dfile.php` : occurrences deja confirmees sures (reflection ou
  code mort/commentaire) - inchanges.
- `model/ModelTrait.php` : cache process-local `Model::$columns[
  get_called_class()]` - **garde intentionnellement le FQCN complet**
  (pas de conversion), pour eviter une collision de cle entre deux
  entites futures namespacees qui partageraient le meme nom court (5.2).

**Piege rencontre et corrige** : `ModelTrait` est aussi utilise par
`Dv_langCore` (`dclass/extends/Dv_langCore.php`) qui **n'etend PAS
`Model`** (`extends \stdClass; use ModelTrait;`), et transitivement par
toute entite traduite (ex. `Interval_group`). Les 3 premiers sites
convertis dans `ModelTrait.php` utilisaient `self::shortClassName(...)`
- erreur reelle (pas seulement phpstan) : `self::` dans un trait resout
sur la classe qui l'utilise, et `Dv_langCore`/`Interval_group` n'ont pas
de methode `shortClassName`, ce qui aurait ete une erreur fatale a
l'execution pour toute entite traduite. Corrige en appelant
`\Model::shortClassName(...)` explicitement (classe pleinement
qualifiee, jamais `self::`) dans `ModelTrait.php`, en passant
`get_called_class()` en argument explicite pour les 2 sites sans
argument (prevaut la semantique de liaison statique tardive d'origine).
Trouve par `composer phpstan` (`Call to an undefined static method
Dv_langCore::shortClassName()`), confirme par relecture - aucune des 423
tests ne l'avait detecte (chemin non exerce).

**Test** : `tests/Unit/ShortClassNameTest.php` (4 tests) - equivalence
stricte avec `get_class()`/`ReflectionClass::getShortName()` sur une
instance, sur une chaine de nom de classe, et sans argument (defaut sur
`get_called_class()`), suivant le meme piege/pattern namespace que
`ReflectionAnnotatedTest.php` (LOT 3) pour la classe de fixture `eval()`e.

### Etat fin de session - LOT 1 (Socle), LOT 2 (Vues), LOT 3 (Routes) et LOT 4 (Generateur) : FAITS, CI verte

**LOT 1 - Socle** : FAIT.

- 1.1 `composer.json` : `php >=8.3`, `doctrine/orm` ^3.7, `phpunit` ^11.5 ;
  retrait de `doctrine/cache`/`doctrine/annotations` (plus necessaires,
  `AttributeDriver` + cache PSR-6 `symfony/cache`) ; CI (`tests.yml`) sur
  la matrice 8.3/8.4/8.5.
- 1.2 Les 41 entites du framework (`src/**/Entity/*.php`,
  `dclass/extends/{Dv_langCore,Dvups_config_item,Dvups_migration,
  ImageCore,UserCore}.php`, trait `DateTimeTrait`) converties des
  annotations Doctrine vers les attributs PHP 8, via le convertisseur
  reutilisable `dclass/generator/EntityAttributeConverter.php`. `DBAL::
  getEntityManager()` reconstruit sur `ORMSetup::
  createAttributeMetadataConfig()` + `AttributeDriver` ; `cli-config.php`
  et `bin/doctrine(.php)(.bat)` reecrits (ORM3 ne fournit plus de
  binaire console). **Aucun changement de schema** - garde-fou verifie
  par `TreeStatusUniqueConstraintAttributesTest::
  testSchemaUpdateReportsNoPendingDifference`. Piege rencontre et
  corrige : colonnes dupliquees (`id` redeclare par 4 entites filles
  d'une `MappedSuperclass`, `created_at`/`updated_at`/`deleted_at`
  herites via trait dans un ancetre non mappe) - voir
  `UPGRADE-8.0.md`.
- 1.3 Commande `php devups upgrade:entities [--dry-run] [chemin]` -
  applique le meme convertisseur a un projet utilisateur, idempotente,
  testee sur les formes reellement rencontrees (entite simple,
  `MappedSuperclass`, relation, identite composite, contrainte unique,
  argument `options`).
- 1.4 `BackendGenerator.php` emet desormais des attributs PHP 8 pour
  toute nouvelle entite generee (`GeneratorRequiredRelationNullableFalseTest`
  mis a jour, memes garanties). Les autres consommateurs de metadonnees
  (DBAL, QueryBuilder, Model, Controller, generator) n'ont necessite
  AUCUN changement : les objets `FieldMapping`/`AssociationMapping` de
  l'ORM3 restent accessibles par cle de tableau (compatibilite
  `ArrayAccess` conservee par Doctrine).
- 1.5 `UPGRADE-8.0.md` - sections "PHP 8.3 minimum", "Doctrine ORM 3
  (DBAL 4)", "Entites : annotations -> attributs", "Generateur".
- 1.6 Push + CI GitHub verte (run `44318900374`, commit `c285989`).

**LOT 2 - Vues** : FAIT.

- `philo/laravel-blade` (abandonne, epinglait `illuminate/view` a `~5`,
  source de l'exception `composer audit` `PKSA-h74q-2n9z-j6wq`/
  CVE-2021-43808) retire, remplace par `illuminate/view` ^11 +
  `illuminate/events` ^11 directement, derriere une nouvelle classe
  `dclass/devups/View.php` (`\Devups\View`) qui reconstruit le meme
  assemblage (Container/Filesystem/Dispatcher/EngineResolver/
  FileViewFinder/Factory, moteurs php/blade, directive `@tt`) que faisait
  `philo/laravel-blade`. `BladeD` (`Genesis.php`, seul point d'entree du
  moteur de vues dans le framework - utilise par `Genesis`/`Controller`/
  `Http\Request`) devient un alias `class BladeD extends \Devups\View {}`
  - meme API publique, aucun changement d'appel. Exception
  `composer audit` retiree (plus necessaire).
- `UPGRADE-8.0.md` - section "LOT 2 - Vues".
- Push + CI GitHub verte (run `44322908859`, commit `ce17982`).

**LOT 3 - Routes** : FAIT.

- `dclass/lib/addendum/` (bibliotheque "Addendum" vendoree en arbre,
  docblocks, non maintenue - distincte du paquet Composer homonyme
  `maslosoft/addendum`, verifie totalement inutilise) retiree ; les 9
  classes d'annotation (`Api`, `GET`, `POST`, `PUT`, `DELETE`,
  `MethodView`, `Auth`, `ApiLazyloading`, `DDoc`) deviennent des
  attributs PHP 8 natifs avec constructeur explicite.
  `hasAnnotation()`/`getAnnotation()` preserves a l'identique via
  `dclass/lib/ReflectionAnnotated.php` (`ReflectionAnnotatedClass`/
  `ReflectionAnnotatedMethod`), meme pattern que `BladeD`/
  `Devups\View` au LOT 2.
- Sites d'usage reels convertis : `route/Admin.php`,
  `route/AdminService.php` (`@MethodView`), les 3 FrontControllers
  reels (`UserFrontController`, `NotificationFrontController`,
  `Push_subscriptionFrontController`) et `BackendGenerator.php`
  (templates de FrontController genere) - perimetre confirme exhaustif
  par recherche des 7 motifs d'annotation sur tout `src/`, `dclass/`,
  `route/`.
- Piege rencontre et corrige : dans un fichier avec un `namespace`
  reel (`route/Admin.php`, `route/AdminService.php` - `namespace
  route;`), un attribut dont la classe est dans l'espace de noms
  global doit etre qualifie absolu (`#[\MethodView(...)]`), sinon PHP
  resout `route\MethodView` et non `\MethodView` - erreur detectee par
  `composer phpstan` ("Attribute class route\MethodView does not
  exist"), pas par `php -l` ni par les tests (la resolution
  d'attribut echoue uniquement si l'attribut est effectivement lu via
  reflection, ce que ne font pas ces deux routes dans la suite de
  tests).
- `UPGRADE-8.0.md` - section "LOT 3 - Routes".
- Push + CI GitHub verte (run `44377778060`, commits `080b6d7`,
  `17e8678`, `077ccf0`).
- Correction apportee apres coup (ETAPE 3.4, oubliee au premier passage
  malgre le plan ecrit a LOT 3.0) : test unitaire dedie
  `tests/Unit/ReflectionAnnotatedTest.php` sur le nouveau shim
  (annotation de classe, annotation de methode verb-specifique,
  plusieurs attributs sur la meme methode, `DELETE` sans `path` ->
  defaut `/:id`, `@Api` generique sur une methode distincte d'un verbe,
  methode sans aucune annotation, `getMethods()` renvoie bien des
  instances `ReflectionAnnotatedMethod`) - meme rigueur que
  `EntityAttributeConverterTest` au lot 1. Jusqu'ici la seule
  couverture etait indirecte (E2E qui exercent les routes reelles) ;
  ce test isole le shim de la base de donnees et du serveur HTTP.

**LOT 4 - Generateur** : FAIT (audit, aucun changement de code
necessaire).

- Les deux templates qui emettaient de l'annotation-style avaient deja
  ete convertis "en meme temps que le reste" aux lots 1 (entites) et 3
  (routage), comme anticipe par leurs propres plans. Audit exhaustif de
  cloture (voir "LOT 4.0" ci-dessus) : tout le reste de
  `dclass/generator/`/`admin/generator/` (templates admin/datatable/
  form/doc/OpenAPI/Postman) n'emet ni n'exploite d'annotation-style, ne
  depend pas de `ReflectionAnnotated*`, ne contient aucune construction
  PHP obsolete, et lint/phpstan sans erreur. Aucun commit de code pour
  ce lot - seule cette synthese dans `TODO.md`.

**Etat des tests/CI a la fin de cette session** : `composer test` ->
419 tests, 1728 assertions, 0 echec (8 deprecations et 2 incomplets
preexistants et sans rapport, voir `AdminScreensTest::KNOWN_BROKEN`) ;
`composer phpstan` -> aucune erreur ; CI GitHub verte sur `release/v8.0`
pour les quatre lots (matrice PHP 8.3/8.4/8.5).

**Risques et points ouverts pour les lots 5 a 8** (observes pendant les
lots 1/2/3/4, a traiter au moment de chaque lot, pas avant) :

- **Lot 5 (PSR-4 + conteneur PSR-11 + Log PSR-3)** : risque le plus
  structurant du chantier - **EN COURS**, voir "LOT 5.0" ci-dessous pour
  l'inventaire detaille. Correction importante par rapport a ce qui
  etait note ici avant audit : `dclass/` n'est PAS entierement en
  namespace global - une quinzaine de fichiers "infrastructure"
  (`Controller`/`FrontController`/`ModuleController`/`ControllerCore`/
  `CrudTrait`, `Datatable`/`TableData`/`TableRow`/`Dbutton`/`ListeView`/
  `Lazyloading`, `Tchutte\{Column,DBALException,DB_dumper}`,
  `model\{ModelSerializer,DateTimeTrait}`, `Migration\MigrationRunner`,
  `Api`) portent deja un **vrai** `namespace dclass\devups\...;` et sont
  references partout ailleurs par leur FQCN explicite (`\dclass\devups\
  Controller\FrontController`, y compris dans des templates Blade
  admin). `dclass/devups/model/Model.php` (la classe de base de TOUTES
  les entites) a LUI un `//namespace dclass\devups;` **commente** -
  tentative de migration visiblement abandonnee avant ce chantier (etat
  herite, aucune trace de pourquoi dans l'historique - voir commit
  `95c538e`, squash initial du depot). Risque identifie en l'auditant :
  `Model`/`Core`/`Datatable`/`Lazyloading`/`Form`/`FormManager`/
  `ModelTrait`/`DBAL`/`QueryBuilder`/`Bugmanager`/`Log`/`EntityCore`
  derivent massivement des noms de table/colonne/cle de filtre/cle de
  cache a partir de `strtolower(get_class($entity))` ou
  `get_called_class()` **en assumant que c'est le nom court actuel**
  (ex. `Datatable.php:452`
  `$this->filterParam[strtolower(get_class($param)) . ".id:eq"]`,
  `DBAL.php:281` `$entityName = strtolower(get_class($entity))`,
  `QueryBuilder.php:546-547` cle de parametre SQL via
  `strtolower(get_class($value)) . "_id"`, `ModelTrait.php:116` nom de
  colonne `_id` genere pareil). Namespacer une entite (41 du coeur +
  tout `src/`) SANS corriger ces sites d'abord **casserait
  silencieusement** la derivation de nom de table/colonne/cle - pas une
  simple erreur d'autoload, un risque de corruption/incoherence de
  donnees. D'autres usages de `get_called_class()`/`get_class()` dans
  les memes fichiers sont en revanche SURS (reflection reelle -
  `new ReflectionClass(get_called_class())`, comparaisons a des classes
  elles-memes deja qualifiees comme `QueryBuilder::class` ou au
  `DateTime` natif) - a ne pas toucher.
  **Decision retenue pour la suite (la plus prudente)** : avant tout
  renommage de classe "feuille" (entite, `src/`), introduire un point
  unique de derivation du nom court (ex. methode/helper
  `shortClassName($objetOuFqcn)` base sur
  `(new \ReflectionClass(...))->getShortName()`), convertir
  exhaustivement chaque site "non sur" identifie ci-dessus vers ce
  helper (comportement strictement identique aujourd'hui puisque tout
  est encore global - donc testable sans aucun risque avant meme de
  toucher un seul namespace), PUIS seulement alors namespacer les
  classes feuilles avec une strategie de compatibilite (`class_alias()`
  vers le nom court, a verifier contre tout usage par nom de classe
  EN CHAINE - `get_class($x) === 'NomCourt'`, qui lui ne survit PAS a un
  alias puisque `get_class()` renvoie toujours le FQCN REEL, jamais le
  nom de l'alias). La convention `dclass/` -> `Devups\...` /
  `src/<component>/...` -> `<component>\...` (voir "Decisions actees")
  reste visee pour les classes encore globales ; la quinzaine de
  fichiers deja sous `dclass\devups\...` n'est PAS une priorite a
  renommer vers `Devups\...` (coexistence sure aujourd'hui, cout de
  renommage faible si fait un jour - 15 fichiers + ~10 references FQCN
  dans `admin/views/*.blade.php`/`admin/generator/
  AdminTemplateGenerator.php` - mais pas bloquant pour ce lot).
- **Lot 6 (HTTP)** : `dclass/devups/Http/Request.php` instancie encore
  `BladeD` directement (voir LOT 2) - a revisiter si ce lot introduit
  une abstraction Request/Response differente.
- **Lot 7 (Qualite, PHPStan 5)** : le projet est reste sur
  `phpstan/phpstan` ^1.10 en lot 1 (voir inventaire LOT 1.0 - bascule
  vers ^2 volontairement reportee a ce lot, regles/niveaux potentiellement
  changes). `phpstan.neon.dist` utilise encore l'option depreciee
  `checkMissingIterableValueType` (avertissement vu a chaque run depuis
  le lot 1) - a traiter avec la bascule PHPStan 2.
- **Lot 8 (Livraison)** : `UPGRADE-8.0.md` a deja ses sections LOT 1 et
  LOT 2 - poursuivre le meme format (avant/apres/comment migrer) pour
  chaque lot restant plutot que de le reecrire a la fin.
- **Rappel general** : l'ORM maison Tchutte reste l'ORM d'execution
  reel (Doctrine limite aux metadonnees/schema) - aucun lot de ce
  chantier ne doit faire glisser une requete applicative vers
  `EntityManager`/`QueryBuilder` Doctrine.

## LOT 7 (niveau 2) - PHPStan, audit + triage ligne par ligne

**Decision prise avec l'utilisateur** : "un niveau a la fois, trier
chaque lot" - mesurer le cout reel avant de s'engager, traiter
INTEGRALEMENT le niveau 2 (corriger ce qui est reellement sur, baliser
le reste avec une raison ecrite), puis **s'arreter et repasser par
l'utilisateur avant d'envisager le niveau 3** (pas d'autorisation
implicite pour la suite).

**Mesure prealable** : l'ancienne estimation de ce TODO ("niveau 2
genere ~200 erreurs supplementaires", voir LOT 1) datait du chantier
v7.1 et ne tenait plus - mesure reelle au debut de ce lot :
`vendor/bin/phpstan analyse --level=2 --error-format=raw` -> **393**
nouvelles erreurs (en plus des 867 deja gelees aux niveaux 0/1).
Chaque categorie ci-dessous a ete verifiee a la main (lecture du
fichier reel, pas seulement du message phpstan) avant de decider
correction vs. baseline.

### Corrige (uniquement des docblocks - aucun changement de
comportement, verifie par `composer test` avant/apres a 454/454
inchange)

- **`Controller::hydrateWithJson()`** (`dclass/devups/Controller/
  Controller.php`) avait `@return null` alors que la methode retourne
  toujours l'entite hydratee (`return $object;`) - coquille de
  documentation preexistante (rien a voir avec ce chantier) qui a
  trompe phpstan sur TOUT le code appelant (page entiere de faux
  positifs "Cannot call method __insert()/__update() on null" /
  "Cannot access property ... on null" dans `FrontController.php`,
  `Dvups_adminFrontController.php`, `CountryFrontController.php`,
  `Tree_itemFrontController.php`, `UserFrontController.php`). Corrige
  en `@return object`.
- **`Controller::form_generat()`** : `@param stdClass $object` (et
  dans `DBAL.php`, 6 occurrences de `@param \stdClass $object` sur
  `insertBulk()`/`deleteserialiseDbal()`/`createDbal()`/
  `updateDbal()`) - meme defaut : la valeur reelle est une entite
  applicative, jamais une veritable instance de `\stdClass` (qui n'a
  pas de `getId()`/`inCollectionOf()`/`entityKey()` - source des faux
  positifs "Cannot call method ... on stdClass"). Corrige en
  `@param object $object` (type informel correct, sans pretendre a une
  classe precise qui n'existe pas en pratique).
- **`DBAL.php`** : `@param \string $object` (3 occurrences, `_createDbal()`/
  `_updateDbal()`/`_deleteDbal()`) - `\string` avec l'anti-slash fait
  croire a phpstan qu'il s'agit d'une classe nommee "string" dans
  l'espace de noms global (qui n'existe pas) au lieu du pseudo-type
  natif `string` (sans anti-slash). Corrige en `@param string $object`.
- **Retombee directe de notre PROPRE migration de namespace** (LOT
  5.2, points a/b/c - qui n'a touche que le code executable, jamais
  les tags de docblock) : plusieurs classes legitimement globales
  referencees en docblock SANS anti-slash dans un fichier qui a recu
  un `namespace` resolvent desormais vers une classe inexistante dans
  CE namespace plutot que vers la vraie classe globale :
  - `Controller.php` : `@throws InvalidArgumentException` ->
    `@throws \InvalidArgumentException`.
  - `Tree_item.php` (`devupscms\ModuleTree\Entity`) : `@return
    QueryBuilder` (x2, `mainmenu()`/`mainmenus()`) -> `@return
    \QueryBuilder` (classe globale, jamais namespacee - cascade
    corrigee du meme coup : `Tree.php::cache()`,
    `Tree_item.php::position()`, `route/AdminService.php::index()`,
    les 2 vues `formWidget.blade.php` qui chainaient sur son retour).
  - `Cmscontent.php` (`devupscms\ModuleCms\Entity`) : `@return`/
    `@param Tree_item` -> `\devupscms\ModuleTree\Entity\Tree_item`
    (la classe reelle est dans le module Tree, pas Cms).
  - `Notification.php` (`devupscms\ModuleNotification\Entity`) :
    `@param User|null $user` -> `@param \User|null $user` (le
    parametre natif de la methode est deja correctement `\User` -
    seul le docblock etait incoherent).
  - `Push_subscriptionController.php`/`Push_subscriptionFrontController.php`
    (`devupscms\ModulePushNotification\Controller`) : `@throws
    ErrorException`/`@throws ReflectionException` -> prefixes
    `\ErrorException`/`\ReflectionException` ; `@return
    Lazyloading|QueryBuilder` -> `@return \Lazyloading|\QueryBuilder`.

### Balise (documente ici, `phpstan-baseline.neon` regenere via
`--generate-baseline` a partir de ce niveau 2 une fois les corrections
ci-dessus appliquees - 1182 entrees au total, 867 preexistantes +
~315 nouvelles de ce niveau)

Verifie une a une (voir methode ci-dessous) comme NE justifiant PAS
une correction de code dans le cadre de ce lot :

- **Pseudo-types informels** (`text`, `type`, `datetime`, `date`
  utilises comme type `@param`/`@var`/`@return` alors qu'aucune classe
  de ce nom n'existe - convention historique du projet pour documenter
  "ceci est une chaine formattee date/texte libre", pas une vraie
  classe). Plus gros volume (plus de 150 erreurs) - balise globale, pas
  de gain a renommer un par un sans vraie classe de remplacement
  evidente.
- **`PHPDoc tag @param references unknown parameter`** (102 erreurs,
  concentrees sur `Model.php`/`Dv_langCore.php`/`Interval_group.php`/
  diverses entites) - noms de parametres dans le docblock qui ne
  correspondent plus a la signature reelle (renommages anciens non
  repercutes dans la doc) - purement cosmetique, aucun impact sur le
  typage reel.
- **Proprietes dynamiques du generateur** (`dclass/generator/
  {BackendGenerator,RequestGenerator,__Generator,android/
  __Generatorjava}.php`, ~18 erreurs "Access to an undefined property
  object::$..." ) - ces fichiers lisent des objets `json_decode()`
  (config de template, typee `object` generique) par propriete
  dynamique - jamais des entites applicatives reelles, jamais
  exerce par la suite de tests (generateur, pas runtime).
- **Classes reellement introuvables, PREEXISTANTES (rien a voir avec
  notre migration)** : `Dvups_entityController` (`route/Admin.php`/
  `AdminService.php`), `TreeFrontController` (`ModuleTree.php`),
  `Blockcontentmodel` (`FormFactory.php`), `FrontendGenerator`
  (`__Generatorjava.php`) - verifie par recherche exhaustive dans tout
  le depot (`grep -rn "class X"` ne remonte rien) : ces classes n'ont
  jamais existe dans ce depot sous ce nom. Meme famille que
  `Lazyloading` (`CmsresourceFrontController.php`/
  `CountryFrontController.php`, deja documente au LOT 5.2 point (b) -
  resurfit simplement a ce niveau avec un message different).
- **Incoherences d'arite preexistantes, non fatales et non exercees
  par les tests** (16 erreurs "invoked with N parameters, M required")
  - verifiees au cas par cas :
  - `Router::manageServe($default_route)` (parametre requis, sans
    defaut) appele SANS argument par 6 points d'entree de module
    (`index.php`/`services.php` de `ModuleConfig`/`ModuleLog`/
    `ModuleUser` front) - serait une `ArgumentCountError` fatale si
    ces fichiers etaient executes, mais **confirme par recherche
    exhaustive qu'aucun autre fichier du depot ne les reference/
    inclut** (points d'entree bruts routes par le serveur web, jamais
    par un test).
  - `Model::update()` appelle `QueryBuilder::update($arrayvalues,
    $seton, $case, $defauljoin)` avec 4 arguments alors que
    `QueryBuilder::update()` n'en accepte que 3 - en PHP un argument
    excedentaire n'est PAS une erreur fatale (juste ignore), donc pas
    de crash, mais `$defauljoin` n'atteint jamais `QueryBuilder::
    update()` : bug de logique silencieux preexistant, comportement
    voulu de `$defauljoin` dans `QueryBuilder::update()` inconnu sans
    investigation plus large - hors perimetre de ce lot.
  - Meme famille, non approfondies individuellement (meme verification
    "aucun appelant dans la suite de tests") : 3x
    `...Controller::datatable()`, 4x `Reportingmodel::sendMail()`, 2x
    `Tree_item::getParent()`.
- **Methodes reellement inexistantes, preexistantes** : `QueryBuilder::
  createFrom()` (appelee par `ModelTrait::createFrom()`, aucune methode
  de ce nom sur `QueryBuilder` - serait fatale si `Model::createFrom()`
  etait appelee sur une entite quelconque, mais jamais exerce par la
  suite de tests) ; `EmaillogController::formView()` (appelee par
  `ModuleReporting/services.php`, `EmaillogController` n'utilise pas
  `CrudTrait` contrairement aux autres controleurs qui definissent
  cette methode).
- **Reliquats d'union de type** (`Cannot call method ... on
  $this(...)|array` / `array|Tree_item`, 2 erreurs restantes dans
  `Tree_item.php`/`Tree_itemController.php`) - meme famille
  d'incoherence de documentation que ci-dessus, non exploitee par les
  tests.

**Methode de verification appliquee a chaque categorie** avant de
decider balise plutot que correction : (1) lire le code reel autour de
la ligne signalee, pas seulement le message phpstan ; (2) pour une
classe/methode "introuvable", `grep -rn` exhaustif sur tout le depot
pour confirmer qu'aucune definition n'existe nulle part sous ce nom ;
(3) pour une incoherence d'arite/methode potentiellement fatale,
verifier via la hierarchie de classes que le defaut est reel (pas
masque par un override), puis confirmer par recherche de references
que le point d'entree fautif n'est inclus/appele par aucun autre
fichier du depot (donc jamais exerce par les 454 tests, coherent avec
une suite qui passe integralement malgre ces defauts).

`phpstan.neon.dist` : `level: 1` -> `level: 2`. `composer test` (454
tests, 0 echec, 8 deprecations et 2 incomplete preexistants inchanges)
et `composer phpstan` (0 erreur) verifies verts localement puis en CI
(matrice PHP 8.3/8.4/8.5) avant de considerer ce lot termine.

**Aucune entree `UPGRADE-8.0.md`** : tous les changements de ce lot
sont des corrections de docblocks (documentation lue par PHPStan, pas
par PHP a l'execution) ou des entrees de baseline - zero changement de
comportement runtime.

**Niveau 3 non commence** : par decision explicite de l'utilisateur,
ce lot s'arrete ici. Reprendre uniquement sur nouvelle autorisation.

### LOT 7 (niveau 3) - FAIT

Decision utilisateur (session suivante) : poursuivre l'escalade de
niveau sans repasser par un checkpoint a chaque niveau cette fois -
"continue LOT 7 (niveau 3) et tu enchaines jusqu'a la fin [du
chantier]". Meme methode qu'au niveau 2 (mesurer, trier, corriger ce
qui est reellement sur, baliser le reste avec raison ecrite).

**Mesure** : `--level=3` -> 161 nouvelles erreurs (en plus des 1182
deja baselinees aux niveaux 0-2).

**Corrige** (docblocks uniquement, meme famille que le niveau 2 -
retombee de notre PROPRE migration de namespace LOT 5.2 : un `@var`
avec anti-slash pointait vers l'ANCIEN alias global d'une classe
desormais namespacee, au lieu de la classe reelle - les deux
resolvent vers la meme classe a l'execution via `class_alias()`, mais
phpstan les voit comme deux types distincts) :

- `Cmscomment::$cmscontent`, `Cmsresource::$cmscontent` (`@var
  \Cmscontent` -> `@var Cmscontent`, meme namespace que la cible).
- `Notification::$notificationtype` (`@var \Notificationtype` ->
  `@var Notificationtype`).
- `Push_email::$reportingmodel` (`@var \Reportingmodel` -> `@var
  Reportingmodel`).
- `Tree_item::$tree`, `Tree_lang::$tree` (`@var \Tree` -> `@var
  Tree`).
- `Tree_item_image::$tree_item`, `Tree_item_lang::$tree_item` (`@var
  \Tree_item` -> `@var Tree_item`).
- `Cmscontent::$tree_item` (`@var \Tree_item` -> `@var
  \devupscms\ModuleTree\Entity\Tree_item` - namespace different de
  celui de `Cmscontent`, meme cas que le `@param`/`@return` deja
  corrige au niveau 2 sur la meme paire de classes).
- `UserFrontController::googleAuth()` : `@return void` alors que la
  methode retourne systematiquement un tableau ('success'/'error') -
  meme defaut que `hydrateWithJson()` au niveau 2. Corrige en `@return
  array`.
- `Dvups_adminController::$err` : `@var bool` alors que la propriete
  n'est jamais affectee qu'a `array()` (verifie : 3 sites
  d'affectation, tous `array()`). Corrige en `@var array`.

**Balise** (pre-existant, verifie, hors perimetre d'une correction de
code dans ce lot) :

- Meme categorie "pseudo-type informel" (`type`/`text`/`date`) qu'au
  niveau 2, qui ressurgit sous des formes differentes a ce niveau
  (iteration `foreach`/acces par offset sur `Model`/`EntityCollection`/
  `DBAL` dont le type declare est le pseudo-type `type` ; covariance
  `datetime`/`text` vs le type natif `string` du parent `Model`).
- **Constructeurs qui `return` une valeur** (`Core::__construct()`,
  `Dfile::__construct()` x2, `EntityCore::__construct()`) - motif de
  sortie anticipee tres repandu en PHP legacy ; sans consequence reelle
  (la valeur de retour d'un constructeur est toujours ignoree par
  PHP, `new X()` retourne toujours l'instance). Non corrige par
  prudence : ce sont des classes coeur a forte centralite (`Core`,
  `Dfile`, `EntityCore`), pas une simple docblock isolee - le risque
  de modifier leur code (meme un simple `return $this;` ->
  `return;`) pour un gain purement cosmetique n'est pas justifie dans
  ce lot.
- `Model::__belongto()` : `@return $this` alors que l'implementation
  retourne un `object` plus generique - type reel voulu (instance
  identique systematiquement, ou parfois une autre entite en
  relation ?) non tranchable sans relire toute la logique
  relationnelle ; balise plutot que corrige au hasard.
- Diverses proprietes d'entites avec un `@var` (string/bool) qui
  n'accepte pas la valeur par defaut reelle (int) ou `null` -
  `UserCore`, `Dvups_admin`, `Dvups_entity`, `Status`, `Notification`,
  `Workers` - coherent avec le typage informel deja observe partout
  dans ce code (colonnes booleennes/enum documentees en `string`,
  defauts numeriques) ; aucune de ces proprietes n'a de type natif PHP
  (juste du docblock) donc aucun risque d'erreur fatale a l'execution
  - mais le type reel voulu (faut-il corriger le docblock vers `int`,
  ou le defaut vers une chaine ?) n'est pas evident sans connaitre le
  mapping colonne reel de chaque cas - balise.

`phpstan.neon.dist` : `level: 2` -> `level: 3`. `phpstan-baseline.neon`
regenere (1324 entrees, 1182 + 142 nouvelles de ce niveau).
`composer test` (454/454 inchange) et `composer phpstan` (0 erreur)
verts.

### LOT 7 (niveau 4) - FAIT

**Mesure** : `--level=4` -> 170 nouvelles erreurs. Nature tres
differente des niveaux 2/3 (purement docblock) : le niveau 4 est de
l'analyse de code mort / conditions toujours vraies-fausses /
proprietes-methodes jamais lues-utilisees - c'est-a-dire de la vraie
LOGIQUE, pas de la documentation. Verifie une a une les categories
(voir methode LOT 7 niveau 2) avant de decider : **aucune correction
de code appliquee a ce niveau** (contrairement aux niveaux 2/3) -
modifier une condition ou retirer du code "mort" d'apres phpstan
reste un changement de comportement reel qui demande une validation
que ce lot (filet de securite de typage, pas une revue fonctionnelle)
ne peut pas apporter avec confiance. Tout est balise, avec la
categorie verifiee et des exemples concrets :

- **Constantes d'environnement (`__prod`, `dbtransaction`) figees par
  le bootstrap de phpstan** (`config/constante.example.php`, deja
  documente au LOT 5 - "sans ce bootstrap phpstan ne voit aucune des
  constantes") : la plupart des "if condition is always true/false"
  (`Router.php`, `DBAL.php`, `Controller.php`, `FrontController.php`,
  `UserCore.php`, `config/dependanceInjection.php`, et la quasi
  totalite des `Unreachable statement` dans les entites - verifie une
  a une : `Status.php`, `Tree.php`, `Notification.php` x2,
  `Reportingmodel.php`, `Push_subscription.php`) sont des faux
  positifs de l'environnement d'ANALYSE (cette constante a une valeur
  FIXE pour phpstan, differente selon l'environnement reel) - pas des
  bugs.
- **Cascade du pseudo-type `type` deja documente au niveau 2**
  (`is_array()`/`is_numeric()`/`is_object()` "with type will always
  evaluate to false", `ModelTrait.php` dans ses 3 contextes) - meme
  cause racine, resurgit sous une forme differente a ce niveau.
- **Proprietes/methodes "jamais lues" ou "non utilisees"**
  (`AdminTemplateGenerator::$traitement`, `Api::$...`, `Dfile::$...`,
  `Lazyloading::$...`, `ScanDir::$...`, `__Generator::$...`,
  `__Generatorjava::$...`, `Dvups_adminController::$...`,
  `DBAL::ResetObject()`/`orm()`/`extractNestedData()`,
  `Datatable::tableoption()`) - signal de code mort reel, mais
  **non retire** : ce framework est distribue comme paquet (pas
  seulement cette application) - une propriete/methode publique peut
  etre lue par du code consommateur hors de ce depot (serialisation
  JSON generique, reflexion, acces dynamique) que l'analyse statique
  locale ne voit pas ; retirer de l'API publique serait une rupture
  (necessiterait un test + une entree `UPGRADE-8.0.md`) injustifiee
  sans certitude reelle d'absence d'usage externe.
- **Verifications redondantes mais inoffensives** (`DDoc.php` - classe
  elle-meme deja documentee comme non utilisee dans ce depot,
  `MethodView.php`, `Api.php` : `$urlkeys && $urlvalues && ...` ou
  `$urlkeys`/`$urlvalues` proviennent de `explode()` qui ne retourne
  jamais un tableau vide - phpstan a raison que le test est toujours
  vrai, mais retirer la garde defensive n'apporte aucun benefice reel
  pour un gain cosmetique).
- **Generateur, faux positifs sur la structure json_decode()**
  (`OpenApiGenerator.php`/`DocGuideGenerator.php` - "offset always
  exists" sur une structure de tableau typee de maniere trop precise
  par phpstan a partir d'un litteral, sans rapport avec un vrai bug).
- **Un vrai mort-code trivial et sans risque identifie, laisse
  en l'etat par coherence de perimetre** : `Tree_item.php:320`, un
  second `return [...]` juste apres un premier `return [...]` dans
  `jsonSerialize()` (reliquat d'une ancienne implementation,
  litteralement inatteignable, zero effet sur l'execution qu'il soit
  present ou non) - pourrait etre supprime sans aucun risque, mais
  laisse tel quel dans ce lot (nettoyage cosmetique hors du filet de
  securite de typage qui est l'objet de ce lot - a reprendre dans une
  passe de nettoyage dediee si souhaite).

`phpstan.neon.dist` : `level: 3` -> `level: 4`. `phpstan-baseline.neon`
regenere (1492 entrees, 1324 + 168 nouvelles - 2 lignes de bruit
("You're running an old version"/"and add the missing array
typehints") filtrees manuellement de la mesure des 170, pas de vraies
erreurs). `composer test` (454/454 inchange) et `composer phpstan` (0
erreur) verts.

### LOT 7 (niveau 5) - FAIT

**Mesure** : `--level=5` -> 99 nouvelles erreurs (verification des
types d'arguments passes aux fonctions/methodes).

**Incident en cours de route** : CI rouge sur PHP 8.3 au push du
niveau 4 (avant ce niveau 5) - le baseline regenere localement
contenait une entree pour `config/constante.php`, le fichier LOCAL
ignore par git (deja documente dans `phpstan.neon.dist` comme "jamais
vu par la CI ni par un clone neuf" - sert uniquement de bootstrap pour
les constantes d'environnement). Corrige en ajoutant ce fichier a
`excludePaths` (commit dedie, CI reverifiee verte avant de continuer)
plutot que de simplement retirer l'entree du baseline, qui aurait
reproduit le meme probleme a la prochaine regeneration.

**Corrige** (memes familles qu'aux niveaux 2/3/5 - docblocks
uniquement) :

- `Util::clearcookie()` : `setcookie($key, null, [...])` - `null`
  n'est pas un type accepte nativement par le 2e parametre de
  `setcookie()` (deprecation PHP 8.1+ silencieuse, jamais vue car ce
  chemin n'est exerce par aucun test). Corrige en `setcookie($key, '',
  [...])` - effet IDENTIQUE pour effacer un cookie, sans la
  deprecation.
- `Controller::form_fillingentity()` : `@param null $data` - encore le
  motif "null = optionnel" deja corrige au niveau 3
  (`hydrateWithJson()`), ici sur un parametre qui recoit en realite de
  vraies donnees de formulaire (verifie : `FrontController.php`
  l'appelle avec de vraies donnees, jamais litteralement `null`).
  Corrige en `@param mixed $data`.
- `Status::$entity` : **decouverte notable** - `@var integer` alors
  que le mapping Doctrine juste en dessous (`#[ORM\Column(name:
  "entity", type: "string", length: 55)]`) declare explicitement
  `string` - docblock et mapping reel en contradiction directe (pas
  une retombee de notre migration, une incoherence prealable). Corrige
  en `@var string` (aligne sur le mapping, source de verite). **A
  garder en tete pour une passe future** : ce type de desaccord
  docblock/mapping ORM pourrait exister sur d'autres entites non
  remontees a ce niveau (phpstan ne verifie ce croisement precis que
  lorsque la valeur est effectivement passee a une fonction stricte en
  aval, comme ici `markDirty(?string $entity)` - pas systematiquement).

**Balise** (pre-existant, verifie) :

- Cascade du pseudo-type `type` (niveau 2) sur les arguments de
  fonctions natives (`explode()`/`str_replace()` dans les generateurs,
  `implode()` dans `DBAL.php`/`QueryBuilder.php`, `QueryBuilder::
  update()`/`djoin()`) - meme cause racine documentee.
- `mt_srand((double)microtime() * ...)` (`Util.php`, `Dvups_admin.php`,
  x4) - seed PRNG legacy, cast `float` passe a un parametre natif
  `int` - PHP effectue la conversion implicite silencieusement (pas de
  deprecation, pas d'erreur), et `mt_srand()` sans argument est deja
  auto-seede de maniere sure depuis PHP 7.1 - ce code manuel est
  vestigial mais inoffensif.
- `Model::find()` typage large (`array|int`) recevant parfois un
  `string` (`WorkersController.php`) - coherent avec le typage
  informel deja observe partout dans Tchutte (colonnes id acceptees
  en pratique sous plusieurs formes scalaires cote SQL) ; pas de
  certitude sur le type reel attendu sans relire tout le contrat de
  `find()`, hors perimetre.
- `ModelTrait::dclone($id = null, $update = null)` transmet `$update`
  (potentiellement `null`) a `QueryBuilder::__dclone($update = false)`
  - meme motif "`@param null` = defaut informel" ; `null` et `false`
  sont equivalents dans tout usage booleen de `$update`
  (comportement identique), non corrige par prudence (methode de
  clonage d'entite a forte centralite, gain purement cosmetique).

`phpstan.neon.dist` : `level: 4` -> `level: 5`. `phpstan-baseline.neon`
regenere (1580 entrees, 1487 + 93 nouvelles de ce niveau, 1487 = 1492
- 5 lignes de l'entree `config/constante.php` retiree au commit
precedent). `composer test` (454/454 inchange) et `composer phpstan`
(0 erreur) verts.

**Incident CI apres le push de ce niveau - signature de fonction
native dependante de la version PHP** : rouge sur PHP 8.3 (seul job
qui lance `composer phpstan`). Cette machine de dev n'a que PHP
8.1.34/8.2.12/7.4.33 (toujours pas de 8.3+, meme contrainte que notee
au LOT 1.0) - le baseline pour `mt_srand((double)... )` a ete
regenere localement sous PHP 8.2, dont le stub natif de `mt_srand()`
déclare `int $seed` (non-nullable) ; sous PHP 8.3 (celui de la CI),
le stub reel déclare `int|null $seed` - message d'erreur phpstan
different ("expects int|null, float given" vs "expects int, float
given") pour EXACTEMENT la meme ligne de code, meme cause que
l'incident CRLF/LF du niveau 2 (artefact de l'environnement de
generation du baseline, pas une vraie regression). Corrige en
assouplissant les 2 entrees concernees avec une regex tolerante aux
deux variantes (`int(\|null)?`) plutot que de fixer une version
precise. **A retenir pour la suite de ce lot** : toute entree de
baseline qui encode le type natif d'une fonction PHP standard
(`mt_srand`, et potentiellement d'autres selon les niveaux a venir)
est a risque du meme artefact tant que cette machine n'a pas de PHP
8.3+ pour generer le baseline dans le MEME environnement que la CI -
verifier la CI (pas seulement le vert local) reste indispensable
avant de considerer un niveau termine.

### LOT 7 - arret de l'escalade de niveau a 5, decision et justification

**Mesure avant de continuer** : `--level=6` -> **4150** nouvelles
erreurs (contre 99 au niveau 5, 170 au niveau 4, 161 au niveau 3, 393
au niveau 2). Saut d'echelle complet, pas une simple continuite.

**Decision** : arreter l'escalade de niveau ici (5), ne PAS tenter
les niveaux 6 a 9. Raisons ecrites (choix le plus prudent, comme
demande par les regles du chantier en cas d'incertitude) :

- **Nature du changement differente.** Les niveaux 2 a 5 verifient
  des faits precis et corrigibles (un docblock errone, un argument du
  mauvais type) - chaque correction de ce lot a ete un changement de
  **documentation**, jamais de code executable. Le niveau 6
  ("missing typehints") et au-dela demandent d'**ajouter des
  declarations de type natives** partout ou elles manquent - un
  changement de SIGNATURE reel, avec un risque de rupture de
  comportement (coercition stricte, erreurs fatales sur des appels
  qui fonctionnaient par coercition implicite) sans rapport avec le
  filet de securite de typage vise par ce lot.
- **Echelle incompatible avec une triage individuelle.** 4150
  erreurs au niveau 6 seul (avant meme 7/8/9) rend une verification
  "une par une avant de decider" (methode suivie scrupuleusement du
  niveau 2 au niveau 5) totalement impraticable dans le cadre de ce
  lot - la seule option realiste serait un balisage en masse, qui
  n'apporterait aucune valeur de securite (noierait tout futur vrai
  signal de ce niveau dans un bruit deja enorme au moment ou il est
  gele).
- **Incompatible avec le design assume du framework.** Ce chantier a
  deliberement CONSERVE le typage dynamique/informel a de multiples
  endroits deja verifies cette session (ORM maison Tchutte, proprietes
  magiques, `ModelTrait`, generateur base sur des objets `json_decode`
  generiques) - imposer des typehints natifs partout irait a
  l'encontre de choix d'architecture deja actes ailleurs dans ce
  meme chantier, pas seulement d'un manque de rigueur a corriger.

**PHPStan 1.10 -> 2.x** (l'autre volet nomme du LOT 7, "PHPStan 5"
dans le plan initial faisait reference a `phpstan/phpstan` **version**
2, pas au niveau 5 - confirme par l'inventaire LOT 1.0) : traite
separement ci-dessous.

### LOT 7 - bascule `phpstan/phpstan` ^1.10 -> ^2.3 - FAIT

`composer update phpstan/phpstan --with-all-dependencies
--ignore-platform-req=php` -> 1.12.34 -> 2.3.7.

**Config a adapter** : `checkMissingIterableValueType: false`
(option 1.x) refuse de demarrer sous 2.x ("Unexpected item"). Pas
d'equivalent ajoute : ce que cette option ignorait n'apparait qu'au
niveau 6, deja decide comme hors perimetre de ce lot (voir
ci-dessus) - sans effet au niveau 5 actuel, simplement retiree
(documente dans `phpstan.neon.dist` pour une reintroduction explicite
si un futur chantier pousse le niveau plus haut).

**Regles changees entre 1.x et 2.x** - `composer phpstan` est passe de
0 a 130 erreurs au changement de version seul (meme niveau 5, meme
code, meme baseline) : certaines sont de VRAIES nouvelles regles
(`staticMethod.resultUnused`, nouveau en 2.x), d'autres sont le meme
signalement reformule avec un texte different (fait echouer la
correspondance regex des anciennes entrees de baseline -
`ignore.unmatched`, 39 occurrences - meme famille d'incident que le
CRLF/LF et `mt_srand` deja rencontres, mais cause par la version de
l'outil cette fois plutot que par l'environnement local). Resolu en
regenerant **entierement** le baseline sous 2.x (`--generate-baseline`,
1572 entrees - proche des 1580 precedentes, legere reorganisation
normale) plutot qu'en rafistolant chaque regex une par une.

**Corrige** (echantillon verifie individuellement parmi les
nouvelles regles 2.x, memes criteres qu'aux niveaux precedents) :

- `Push_subscription.php` (x2, FCM + Expo) : `curl_setopt($ch,
  CURLOPT_POST, 1)` - `1` (int) au lieu de `true` (bool) attendu par
  le stub natif de `curl_setopt()` ; PHP coercerait silencieusement,
  mais corrige en `true` pour plus de clarte, effet identique.
- `ReportingmodelForm.php` : `array_values(array_values(array_diff(
  scandir($dir), ...)))` - le `array_values()` exterieur est
  redondant (`$files` est deja une liste reindexee par le premier
  appel) - supprime, effet identique (nouvelle regle 2.x
  `arrayValues.list`, detection de no-op reel, pas un faux positif).

**Balise** (nouvelles regles 2.x ou reformulations, verifiees
representatives de categories deja etablies aux niveaux 2-5, pas
reprises une a une en detail compte tenu du volume et de la nature
"bruit de migration d'outil" plutot que "nouveau signal
applicatif") :

- `Notification::sendSMS()` appelee seule sur sa ligne
  (`staticMethod.resultUnused`) - phpstan 2.x la juge "pure" (sans
  effet de bord detectable statiquement) alors qu'elle envoie
  reellement un SMS via API externe - faux positif de l'heuristique
  de purete de l'outil, pas un appel inutile.
- `Tree_item.php:303` ("right side of && always true") - meme famille
  que le niveau 4 (`treatPhpDocTypesAsCertain` : un type docblock
  traite comme garanti rend une verification de securite
  redondante-mais-defensive).
- `phpDoc.parseError` (6), `nullCoalesce.offset` (4),
  `assign.propertyType` (2), `return.missing`/`return.type` (3),
  `function.alreadyNarrowedType` (6), `logicalAnd.
  rightAlwaysTrue`/`alwaysFalse` (6) : verifies par echantillonnage
  comme relevant des memes causes racines deja documentees aux
  niveaux 2 a 5 (pseudo-types informels, docblocks casses par
  d'anciennes retombees de migration déjà traitées, type certain par
  docblock) - pas de nouvelle categorie de bug reelle identifiee.

`composer test` (454/454 inchange) et `composer phpstan` (0 erreur,
PHPStan 2.3.7) verts.

**Incident CI (meme famille que `mt_srand` au niveau 5)** : la
regeneration **complete** du baseline a ete faite sur le poste local
(PHP 8.2), ce qui a silencieusement ecrase la tolerance inter-version
deja appliquee a `mt_srand` (regex revenue a `expects int, float
given` au lieu de `int(\|null)?, float given`) - CI rouge sur PHP 8.3
au push. Un second cas de la meme nature est apparu : `is_array()`
sur le tableau renvoye par `token_get_all()` dans
`dclass/generator/builder.php` (scanner de langue) rapporte
`array{313, 't'|'tt', int}` localement (PHP 8.2) contre `array{262,
't'|'tt', int}` sur CI (PHP 8.3) - la valeur entiere du type de token
PHP (`T_STRING` et apparentes) differe reellement entre versions de
PHP, pas seulement le texte du message. Corrige par la meme
technique : regex tolerante aux deux valeurs
(`array\{(313|262), ...\}`) plutot qu'une refixation ponctuelle a
nouveau fragile. Lecon retenue : apres toute regeneration **complete**
du baseline sur ce poste (PHP 8.2 uniquement disponible ici), rechercher
systematiquement les baselines deja rendues tolerantes a une version PHP
differente (`grep` sur les commentaires/incidents "PHP 8.3" de ce fichier)
avant de pousser, puisqu'une regeneration ecrase ces tolerances sans le
signaler.

## LOT 8 (Livraison) - UPGRADE-8.0.md, doc, demo

**Decision sur le perimetre de "demo"** (terme non precise dans le plan
initial, voir "Chantier v8.0" en tete de ce fichier) : option retenue,
la plus sure - s'appuyer sur la suite de tests E2E existante
(`tests/E2E/AdminScreensTest.php`) comme demonstration vivante que le
framework migre fonctionne de bout en bout (chaque ecran liste et
formulaire de chaque entite declaree, exerce reellement a travers tout
l'empilement reconstruit - vues `\Devups\View`, routage par attributs,
entites par attributs Doctrine - sur la matrice CI PHP 8.3/8.4/8.5)
plutot que de fabriquer une application de demonstration separee non
demandee explicitement. Ecarte : construire un nouveau projet demo
dedie - cout disproportionne par rapport a un besoin non precise,
alors qu'une demonstration equivalente existe deja et tourne a chaque
commit.

**`UPGRADE-8.0.md`** : deja tenu a jour lot par lot pendant tout le
chantier (voir les sections LOT 1 a LOT 7 de ce fichier - chaque lot a
ecrit sa propre section avant-apres-comment-migrer au moment ou il a
ete fait, pas reconstruit a la fin). Rien a ajouter pour LOT 4
(Generateur) et LOT 8 lui-meme : aucune rupture dans l'un ni l'autre.

**`CHANGELOG.md`** : nouvelle entree `[8.0.0]`, resume des ruptures
lot par lot avec renvoi vers `UPGRADE-8.0.md` pour le detail complet -
meme format que l'entree `[7.1.0]` existante.

**`README.md`** : "Version actuelle" (7.1.0 -> 8.0.0), prerequis PHP
(8.2 -> 8.3), matrice CI documentee (8.2/8.3/8.4 -> 8.3/8.4/8.5) -
trois mentions devenues fausses pendant le chantier, corrigees ici.

**`tuto_doc/` (tutoriel officiel, 4 tomes PDF)** : decouvert pendant ce
lot comme documentation devenue FACTUELLEMENT fausse sur plusieurs
points cles (pas seulement datee) - Tome I decrit encore
`philo/laravel-blade` comme moteur de vues et PHP 8.2/CI 8.2-8.4, Tome
III a un chapitre 1 entier ("Les annotations de route") qui documente
en detail un mecanisme (docblocks + `maslosoft/addendum` +
`dclass/lib/addendum/`) entierement retire au LOT 3, Tome I/II montrent
des exemples d'entites en annotations Doctrine docblock au lieu
d'attributs PHP 8. Traite comme faisant partie du perimetre "doc" de ce
lot (documentation utilisateur officielle, pas seulement TODO.md/
UPGRADE-8.0.md qui sont la memoire interne du chantier) - corrige
section par section en miroir de ce qui est deja ecrit dans
`UPGRADE-8.0.md` (meme contenu, pas de nouvelle decision). Regeneration
des PDF (`python tuto_doc/source/build.py`) tentee ensuite.

## LOT 9 - namespacer les classes satellites (Controller/Datatable/Form)

**Contexte, decide avec l'utilisateur apres la cloture du LOT 8** : le
LOT 5.2 a namespace les 35 entites de `src/**/Entity/*.php` mais pas
leurs classes satellites (Controller/Datatable/Form), restees globales.
Le generateur (`BackendGenerator.php`) n'emettait non plus AUCUN
namespace pour une entite/Controller/Datatable/Form nouvellement
generee - trouvaille faite pendant la discussion, le LOT 4 (Generateur,
"FAIT, audit seulement") n'avait pas pris en compte la convention
actee au LOT 5. Resultat : regenerer n'importe quelle entite deja
migree via le generateur aurait silencieusement annule sa migration de
namespace (ecrasement par `GeneratorFileGuard::write()`, qui renomme
l'ancien fichier mais ecrit le contenu du template courant - jamais
namespace avant ce lot).

**Option retenue (discussion avec l'utilisateur)** : plutot que
d'etendre `class_alias()` partout (etendrait la depense ad-vitam du
LOT 5.2 sans regler le probleme de fond), centraliser la convention de
nommage ("EntityName + suffixe") - dupliquee independamment dans
`Controller.php`, `ControllerCore.php`, `FrontController.php`,
`Router.php` - dans UNE seule classe resolveur namespace-aware
(`EntityNaming`), PUIS mettre a jour le generateur pour qu'il namespace
lui-meme Controller/Datatable/Form (et l'Entite elle-meme, qu'il ne
namespacait pas non plus) de toute nouvelle generation.

**`dclass/devups/EntityNaming.php` (nouveau, FAIT)** : point de verite
unique, remplace la concatenation de chaine dans :
- `Controller.php::serve()`/`::views()` (`$ctrl = ucfirst($entity['name']).
  "Controller"` -> `EntityNaming::controllerFor($entity['namespace_name']
  ?? $entity['name'])` - `namespace_name` deja present dans
  `config/dvups_configurations.php`, meme source que `Core::
  buildOriginCore()`),
- `Controller.php::__construct()`/`ControllerCore.php::init()`
  (`self::$tablename`/`$formname`, consommes par `CrudTrait` -
  `self::$ctrlname`, suffixe "Ctrl", designe un fichier JS pas une
  classe PHP, volontairement INCHANGE),
- `FrontController.php` (3 sites : `tablemodel` x2, `frontServe()`).

Accepte en entree un nom court (jamais namespace) OU une FQCN deja
namespacee - resout vers la FQCN du satellite SEULEMENT SI cette classe
existe reellement (`class_exists()`), sinon retombe sur l'ancien nom
court global (jamais d'erreur ici, meme philosophie de coexistence
indefinie que `class_alias()` - voir UPGRADE-8.0.md LOT 5). Pour un nom
court sans namespace connu, consulte `config/dvups_configurations.php`
(`namespace_name`) avant de conclure - memes informations que celles
deja utilisees par `Core.php` pour peupler ce fichier.

Verifie par `tests/Unit/EntityNamingTest.php` (6 tests : FQCN+satellite
existant -> FQCN retournee, FQCN+satellite absent -> repli nom court,
nom court sans config -> comportement identique a avant ce lot, suffixe
FrontController, `\` de tete ignore) ; `composer test` (460/460, +6
depuis ce lot, 0 regression) et `composer phpstan` (0 erreur) verifies
verts - **comportement strictement identique a avant ce lot aujourd'hui**
puisqu'aucun Controller/Datatable/Form n'est encore namespace (le
`class_exists()` echoue systematiquement pour l'instant, repli
systematique sur l'ancien nom court) - ce correctif prepare seulement
le terrain, sans effet observable tant que le generateur (etape
suivante) et la migration retroactive n'ont pas eu lieu.

**Mise a jour du generateur (`BackendGenerator.php` + `__Generator.php`,
FAIT)** : `entityGenerator()`, `entityLangGenerator()`,
`controllerGenerator()` (normal et front), `tableGenerator()` et
`formGenerator()` acceptent desormais un `$component`/`$module`
optionnel (`null` par defaut - **retro-compatible**, aucun changement
de comportement pour les appels existants qui ne les passent pas,
notamment `GeneratorRequiredRelationNullableFalseTest`). Quand les deux
sont fournis, chaque classe generee recoit sa propre declaration
`namespace <component>\<Module>\<Kind>;` (meme convention que celle
actee au LOT 5 : `devups\ModuleAdmin\Entity`, etc.) suivie d'un
`class_alias(FQCN::class, 'NomCourt')` de compatibilite, exactement la
meme recette que LOT 5.2. Toute reference de classe litterale non
qualifiee a l'interieur d'un template qui designe une classe HORS du
nouveau namespace du fichier (bases `\Model`/`\Dv_langCore`, attributs
PHP 8 `#[\Api]`/`#[\GET]`/.../`#[\DELETE]`, `\Request::`, `\Dfile::`,
`\FormManager::Options_Helper()`, `\Genesis::renderView()`, et les
classes satellites d'une relation comme `\OtherEntityTable::allrows()`)
est desormais prefixee d'un `\` litteral dans la source generee - une
chaine dynamique (`new $string()`, etc.) resout TOUJOURS depuis la
racine donc n'a jamais besoin de ce prefixe, seule une reference
litterale ecrite dans le template en a besoin une fois le fichier
namespace. Les auto-references (ex. `new WidgetTable()` ecrit dans le
template de `WidgetTable` elle-meme) restent sans prefixe : la
resolution non qualifiee dans le meme namespace fonctionne nativement.
`__Generator.php::__entity()` passe maintenant
`self::$projectcore->name` / `self::$modulecore->name` aux 6 appels de
generateur (meme source que celle deja utilisee par `__core()` pour la
generation `Core.json`) - c'est ce fil qui active reellement l'emission
de namespace pour toute generation CLI (`core:g:module`/`core:g:crud`)
a partir de maintenant.

Verifie par `tests/Integration/GeneratorNamespaceEmissionTest.php` (6
tests : Entity/Controller/FrontController/Datatable/Form avec
`$component`/`$module` -> namespace + alias presents dans le fichier
ecrit ; Entity sans `$component`/`$module` -> ni namespace ni alias,
comportement identique a avant ce lot) ; `composer test` (466/466, +6
depuis cette etape, 0 regression) et `composer phpstan` (0 erreur)
verifies verts. **Aucune entite/Controller/Datatable/Form existante
n'est affectee** : ce correctif ne change que ce que produit une
PROCHAINE generation - les 35 entites deja migrees et leurs satellites
restent inchanges jusqu'a la migration retroactive (prochaine
sous-etape de ce lot).

## Securite langue - ETAPE H

### H.1/H.2/H.3 - dv_valid_lang(), tests e2e et realpath : FAITS

- Phase : H.1/H.2/H.3
- Vulnerabilite corrigee : `local()` renvoyait directement
  `Request::get('lang')`, sans aucune validation, et `lang.php`
  construisait `LANG_DIR . local() . '.php'` avec cette valeur brute
  pour un `require` - une traversee de repertoire (`?lang=../../../x`)
  pouvait donc faire inclure n'importe quel fichier `.php` atteignable
  depuis `LANG_DIR` par ce chemin. `t_lang($cle, $iso)` avait exactement
  le meme defaut sur son parametre `$iso`, deja expose (appele avec une
  valeur venant d'un champ utilisateur dans les vues de mail).
- Correctif : une seule porte d'entree, `dv_valid_lang($iso): ?string`
  (`dclass/dvups_functions.php`) - n'accepte que `^[a-z]{2}$` ET une
  langue ACTIVE (cache `Dvups_lang::cachedActiveDlangs()`, jamais une
  requete SQL directe), sinon `null`. `local()`, `setlang()`, le
  chargement principal de `lang.php` et `t_lang()` passent tous
  desormais par elle (repli sur `__lang`/`__default_lang` si invalide).
  `dv_valid_lang()` encapsule l'appel au cache dans un `try/catch` :
  base indisponible ou pas encore migree (outillage CLI, amorcage des
  tests avant creation du schema) => refus (`null`), jamais un plantage.
- Defense en profondeur (H.3) : `dv_lang_file_path($iso): ?string`
  verifie en plus, par `realpath()`, que `LANG_DIR . $iso . '.php'`
  reste bien a l'interieur de `LANG_DIR` avant tout `file_exists()`/
  `require` - redondant avec le format impose par `dv_valid_lang()`
  pour les appels normaux, mais couvre un eventuel appel direct avec un
  `$iso` qui n'y serait pas passe.
- Effet de bord corrige en chaine : valider `local()` declenche desormais
  le premier usage (autoload) de la classe `Dvups_lang` DANS `lang.php`,
  donc AVANT `src/requires.php` - deux `require` "nus" (pas
  `require_once`) de son fichier d'entite re-declaraient la classe et
  faisaient planter TOUTES les pages admin ("Cannot declare class
  Dvups_lang, because the name is already in use") :
  `src/devups/ModuleLang/devups.modulelang.php` et
  `dclass/generator/builder.php` (CLI `devups`, branche partagee par
  toutes les commandes hors `core:g:*`, dont `lang:scan`) - les deux
  corriges en `require_once`. `tests/bootstrap.php` adapte dans le meme
  esprit : `lang.php` est maintenant charge APRES la creation du
  schema/les donnees de test (sinon le tout premier appel de
  `dv_valid_lang()` interrogerait une table `dvups_lang` qui n'existe
  pas encore).
- Tests : `tests/Unit/LangSecurityTest.php` (18 cas invalides -
  traversees simple/encodee/profonde, balise script, injection SQL
  grossiere, casse, longueur, types non-string - + acceptation d'une
  langue active, rejet d'une langue bien formee mais inactive/inconnue,
  `dv_lang_file_path()`, `local()`/`setlang()`/`t_lang()` avec
  `Request::set('lang', ...)`) et `tests/E2E/LangSecurityTest.php`
  (page d'accueil et page de connexion admin avec `?lang=` malveillant :
  200, aucune erreur serveur, page entierement rendue ; balise
  `<script>` jamais presente non echappee dans les liens generes).
- Suite complete verte : 216 tests, 0 echec, 12 incomplete (inchange).

### H.4 - audit require/include/file_get_contents a partir d'une entree utilisateur

- Phase : H.4
- Methode : recherche de tout `require`/`require_once`/`include`/
  `include_once`/`file_get_contents`/`fopen`/`readfile` dont le chemin
  derive de `Request::get`/`$_GET`/`$_POST`/`$_COOKIE` ou d'un champ
  venant de la base, dans tout le depot (hors `vendor/`).
- `dclass/devups/Dfile.php::uploadchunk($uploaddir)` - CRITIQUE SI
  JAMAIS APPELEE DEPUIS UNE ROUTE (ecriture arbitraire de fichier) :
  `$_GET['hashName']`/`$_GET['fileName']` utilises bruts dans
  `fopen($path . $hashname, "ab")`, avec le corps de la requete
  (`php://input`) ecrit dedans - un `?hashName=../../../x` combine a un
  corps de requete arbitraire aurait permis d'ecrire n'importe quel
  fichier (ex. un webshell). Verifie par grep : **cette methode n'est
  appelee par AUCUNE route ni controleur du depot** (code mort) - donc
  non exploitable aujourd'hui, mais une bombe a retardement si jamais
  quelqu'un la relie a une route sans relire ce commentaire. Corrige
  par precaution (`basename()` sur les deux parametres) plutot que
  seulement documente, le cout etant nul.
- `src/devupscms/ModuleReporting/Controller/ReportingmodelController.php::loadContent($name)`
  - MOYEN (authentification admin requise, portee limitee par le
  suffixe fixe `_<local>.html`) : `$name` (parametre de route) concatene
  sans controle dans `Reportingmodel::classroot("Resource/partial/{$name}_$local.html")`,
  lu par `file_get_contents()` - traversee de repertoire possible vers
  tout fichier se terminant par `_fr.html`/`_en.html` atteignable depuis
  ce dossier. Corrige (`basename($name)`). `saveContent($id)` durcie de
  la meme maniere par precaution (`basename($rm->name)`), bien que
  `$rm->name` vienne de la base (saisie admin), pas directement de la
  requete.
- ~~Autres usages de `Request::get('lang')` repares, bas risque~~ -
  **fait (ETAPE L.3)** : `Dvups_lang::defaultLang()` et `Request::Route()`
  (`dclass/devups/Http/Request.php`) passent maintenant par
  `dv_valid_lang()`, comme le reste de la validation de langue (ETAPE H).
- Rien d'autre trouve : les nombreux autres `file_get_contents()` du
  depot (generateur, documentation, cache local, upload d'images via
  `Dfile`) portent sur des chemins construits a partir de noms de classe/
  composant internes (routing deja contraint par l'autoloader) ou de
  champs deja valides/manipules par le framework (`Dfile::sanitize()`),
  pas directement sur une entree brute.
- Fichiers concernes : `dclass/devups/Dfile.php`,
  `src/devupscms/ModuleReporting/Controller/ReportingmodelController.php`.

## Phase 4 (CMS) - ETAPE G

### G.1/G.2/G.3 - plan, renommages et ecran d'edition : FAITS

Le plan (G.1) a ete execute dans cette meme session (pas seulement
ecrit) : la decision initiale de reporter G.2/G.3 a ete revisee une
fois constate que, comme pour toutes les suppressions de table de
cette session (ModuleDatabase, Local_content*, dvups_log...), le code
des renommages peut etre ecrit et teste integralement contre la base
de test (schema recree depuis les entites par Doctrine SchemaTool)
SANS jamais executer de migration sur une base reelle - le risque
"perte de donnees" ne portait que sur l'EXECUTION de la migration, pas
sur l'ecriture du code, exactement comme les migrations DROP deja
ecrites (jamais executees) plus haut.

- Renommage `Cmstext`/`Cmstext_lang` -> `Cmscontent`/`Cmscontent_lang`
  (classes, table `cmstext`/`cmstext_lang` -> `cmscontent`/
  `cmscontent_lang`, routes `services.php`, vues, JS) : fait
  integralement (entite, formulaire, datatable, controleur, front
  controleur, vues `cmscontent/*` et `admin/cmscontent/*`).
- `Imagecms` (table de jointure vers `Dv_image`) reconstruit en
  `Cmsresource`, qui etend `ImageCore` DIRECTEMENT (meme modele que
  `Dv_image`) : absorbe le stockage de fichier au lieu d'une jointure,
  lie a `Cmscontent` par `cmscontent_id`. Plus de dependance a
  `Dv_image` pour les images de contenu CMS.
- `Cmscomment` genere proprement (entite/formulaire/datatable/
  controleur) a partir de `CommentCore.json` dont le `module.name`
  pointait vers un `ModuleComment` inexistant - corrige vers
  `ModuleCms` (ou `Comment` etait deja enregistre dans
  `config/dvups_configurations.php`/`module_configurations.php`,
  maintenant renomme `Cmscomment`). Aucune donnee a migrer : la table
  `comment` n'a jamais existe (aucune entite generee avant cette
  session).
- La page d'edition admin (`/admin/devupscms/ModuleCms/cmscontent/edit?id=1`)
  fonctionne : le Blade casse (`Resource/views/cmscontent/form.blade.php`,
  accolade d'ouverture `{!!` manquante avant un bloc
  `Dv_imageTable::init(...)` mort) est corrige et repointe vers
  `CmsresourceTable` ; la meme vue accedait aussi `$cmscontent->title[$iso]`/
  `content[$iso]` en supposant toujours un tableau par langue (jamais le
  cas pour un contenu cree hors du flux d'edition JS en place, ex. un
  fixture de test) - rendue robuste (`is_array(...) ? ... : ...`) sans
  changer le comportement du flux existant. Couvert par
  `tests/E2E/CmscontentEditPageTest.php`.
- Bug latent decouvert en testant : `Model::create()` sur une entite
  `dvtranslate=true` (comme `Cmscontent`, heritage de `Cmstext`)
  appelait `Dvups_lang::readCache()`, methode retiree a l'ETAPE E.7 de
  cette meme session (remplacee par `cachedActiveDlangs()`, dont le
  format - map iso<->id - ne correspond pas a ce que ce code attend) ;
  jamais exerce avant (aucun test n'appelait `Cmstext::create()`).
  Corrige au point d'appel (`dclass/devups/Tchutte/DBAL.php`) par
  `Dvups_lang::all()`, qui restaure le comportement d'avant l'ETAPE E.7
  pour ce chemin precis - pas un chantier de cache a part, juste la
  reparation d'une regression introduite plus tot dans cette session.
- `Cmsresource`/`Cmscomment` genere aussi des ecrans `list`/`form`
  fonctionnels (verifies par `AdminScreensTest` - voir "Etat des
  tests") : 2 entrees `KNOWN_BROKEN` devenues sans objet retirees
  (`imagecms/form`, `comment/form`).
- Renommages annexes necessaires decouverts en grepant les references
  residuelles a `Cmstext`/`Imagecms` : `Tree_item::getCmstext()` ->
  `getCmscontent()` (methode reelle, mais seul appelant etait du code
  mort apres un `return` anterieur - corrige quand meme) ;
  `Tree_itemController::addcontentAction()` et
  `TreeController::managerView()` (chemins reels, utilises par le
  module Arbre pour creer un contenu CMS depuis un item d'arbre).
- Tests : `tests/Integration/CmsPhase4Test.php` (persistance
  Cmscontent/Cmsresource/Cmscomment et leurs relations) et
  `tests/E2E/CmscontentEditPageTest.php` (page d'edition demandee par
  la consigne, verifiee sans erreur serveur).

**Migration (ecrite, NON executee, comme pour toute migration de cette
session - voir `database/migration/2026_11_08_phase4_cms_rename.sql`)** :
renomme `cmstext`/`cmstext_lang` en `cmscontent`/`cmscontent_lang`, cree
`cmsresource` et `cmscomment` (vides - `cmsresource` doit ensuite etre
peuplee depuis `imagecms JOIN dv_image`, voir le commentaire detaille
dans le fichier ; le script ne fait PAS cette migration de donnees
automatiquement, elle reste a verifier et executer a la main avant de
decommenter le DROP de `imagecms`).

### G.4 - suppression ModuleGallery - FAIT

- Phase : G.4
- Decision prise : plutot que de repointer `Tree_item_image` vers
  `Dv_image`/`cmsresource` (couplage inutile entre arborescence et
  CMS), `Tree_item_image` herite desormais directement de `ImageCore`
  - exactement le meme choix architectural que `Cmsresource` en
  G.2/G.3. Il gere donc lui-meme `uploadImage()`/`__delete()` (3
  variantes de resize `100_`/`150_`/`50_`, repris de l'ancien
  `Dv_imageForm`) sans aucune dependance a `Dv_image`.
- Bug latent decouvert et corrige au passage : `Tree_item_imageForm`
  appelait `$this->tree_item_image->getDv_image()->getId()`, une
  methode qui n'a jamais existe sur `Tree_item_image` (seule
  `getImage()` existait) - aurait leve une erreur fatale si ce chemin
  avait ete exerce. Remplace par un champ `image` direct
  (`FORMTYPE_FILE`), qui a aussi permis de retirer l'entree
  `KNOWN_BROKEN` `tree-item-image/form`.
- Autre dependance corrigee : `ModuleTree::dashboardView()` chargeait
  un asset CSS via `Dv_image::classpath('Resource/css/image.css')` ;
  le fichier a ete copie dans
  `src/devupscms/ModuleTree/Resource/css/image.css` et le chargement
  repointe vers `Tree::classpath(...)`.
- `src/devupscms/ModuleGallery/` supprime entierement (`git rm -rf`) ;
  `ModuleGallery` retire de `config/module_configurations.php`,
  `Dv_image` retire de `config/dvups_configurations.php`, le require
  correspondant retire de `src/requires.php`.
- Verifie par grep sur tout le depot qu'aucune reference a
  `Dv_image`/`ModuleGallery` ne subsiste hors documentation/migration.
- Suite verte apres ce changement : 180 tests, 531 assertions, 0
  echec, 12 incomplete (les memes ecrans Location/Notification/
  Emaillog deja connus comme casses - aucune regression nouvelle ;
  la baisse de 183 a 180 tests s'explique par la disparition des 2
  ecrans `dv-image/list` et `dv-image/form` du generateur de scenarios
  `AdminScreensTest::screens()`, qui derive ses cas directement de
  `config/dvups_configurations.php`, puisque `Dv_image` n'y figure
  plus).
- Migration : la table `dv_image` n'est PAS encore droppee (le DROP
  reste a ecrire/commenter dans une migration separee une fois
  confirme qu'aucune donnee de production n'y est encore referencee -
  voir section migrations ci-dessous).
- Fichiers concernes : `src/devupscms/ModuleGallery/**` (supprime),
  `src/devupscms/ModuleTree/Entity/Tree_item_image.php`,
  `src/devupscms/ModuleTree/Form/Tree_item_imageForm.php`,
  `src/devupscms/ModuleTree/Datatable/Tree_item_imageTable.php`,
  `src/devupscms/ModuleTree/ModuleTree.php`,
  `src/devupscms/ModuleTree/Resource/css/image.css` (nouveau),
  `src/devupscms/ModuleTree/Resource/js/tree_item_imageForm.js`,
  `src/devupscms/ModuleTree/Core/tree_item_imageCore.json`,
  `config/dvups_configurations.php`, `config/module_configurations.php`,
  `src/requires.php`, `tests/E2E/AdminScreensTest.php` (KNOWN_BROKEN).

### G.5 - generateur/datacores/tests e2e

- Phase : G.5
- Fait (en meme temps que G.2/G.3, pas seulement a l'issue de G.4) :
  `CmscontentCore.json`/`CmsresourceCore.json`/`CmscommentCore.json`
  corriges (plus de colonnes fantomes, relations completes, module.name
  de Comment corrige vers ModuleCms) ; `AdminScreensTest::screens()`
  genere deja les bonnes cles depuis `config/dvups_configurations.php`
  mis a jour (`cmscontent`/`cmsresource`/`cmscomment`), aucune cle en
  dur a changer ; 2 entrees `KNOWN_BROKEN` sans objet retirees
  (`imagecms/form`, `comment/form`) ; confirme par grep qu'aucune
  reference residuelle a `Cmstext`/`Imagecms` ne subsiste dans le code
  (`Tree_item`/`Tree_itemController`/`TreeController` corriges au
  passage, voir G.1/G.2/G.3 ci-dessus).
- Fait (a l'issue de G.4) : l'entree `KNOWN_BROKEN`
  `tree-item-image/form` ("depend de ModuleGallery") a ete retiree -
  l'ecran passe desormais sans erreur.
- Fichiers concernes : `tests/E2E/AdminScreensTest.php`.

## Phase 4 (CMS), fin des migrations - ETAPE I

### I.1/I.2/I.3 - reprise de donnees dans la migration + test : FAITS

- Phase : I.1/I.2/I.3
- `database/migration/2026_11_08_phase4_cms_rename.sql` ne faisait que
  CREATE les nouvelles tables (vides) : il reprend desormais reellement
  les donnees - `INSERT INTO cmsresource ... SELECT ... FROM imagecms
  JOIN dv_image`, puis `UPDATE tree_item_image ... FROM dv_image` pour
  ses nouvelles colonnes `ImageCore`.
- Fichiers physiques : AUCUN deplacement necessaire - chaque ligne
  reprise garde son `uploaddir` D'ORIGINE (celui ecrit par l'ancien
  `Dv_image`), jamais force aux chemins par defaut des NOUVEAUX uploads
  (`cms/`/`treeitem/`, utilises par `Cmsresource::uploadImage()`/
  `Tree_item_image::uploadImage()`) - verifie explicitement par le test
  d'integration (assertion sur `uploaddir`, voir plus bas).
- Bloc de comptage (`SELECT COUNT(*)` avant/apres) ajoute en commentaire
  autour de chaque reprise, a executer et comparer a la main ; les DROP
  (`imagecms`, `dv_image`) restent commentes, a decommenter uniquement
  une fois ces comptages verifies egaux - jamais executes par cette
  session, comme pour toute migration de ce chantier.
- Test : `tests/Integration/CmsMigrationTest.php` - recree l'ANCIEN
  schema (`cmstext`, `imagecms`, `dv_image`, `tree_item_image` avec sa
  colonne `image_id`) sur une base DEDIEE (`devups_phpunit_migration`,
  creee/detruite par le test lui-meme - jamais `devups_phpunit`, qui
  est deja au NOUVEAU schema issu des entites actuelles et n'a donc
  plus de table `cmstext` a renommer), y insere quelques lignes,
  execute le script REEL (`file_get_contents()` + un seul
  `PDO::exec()` - les lignes commentees sont ignorees nativement par la
  syntaxe SQL, meme principe que `config_data.sql` dans
  `tests/bootstrap.php`), puis verifie la reprise de donnees (y compris
  `uploaddir` inchange) et que les tables d'origine existent encore
  (DROP commentes).
- Fichiers concernes :
  `database/migration/2026_11_08_phase4_cms_rename.sql`,
  `tests/Integration/CmsMigrationTest.php`.

### I.4 - DBAL::_createDbal() et le cache des langues actives : FAIT

- Phase : I.4
- `Dvups_lang::all()` (une requete SQL a chaque creation d'une entite
  traduite via `Model::create()`/`_createDbal()`) remplacee par
  `Dvups_lang::cachedActiveDlangs()` - le meme cache deja utilise par
  `header.php` et par `dv_valid_lang()` (ETAPE H). Ce cache est un
  tableau PLAT iso_code<->id (`iso_code => id`, `iso_code.'/' => id`,
  `id => iso_code`) : la boucle ne retient que les entrees
  `iso_code => id` (cle string qui ne finit pas par `/`).
- Historique du bug (rappel, voir aussi ETAPE G.1/G.2/G.3 ci-dessus) :
  `Dvups_lang::readCache()` avait ete retiree a l'ETAPE E.7 (remplacee
  par `cachedActiveDlangs()`), mais ce chemin precis de `DBAL.php`
  n'avait jamais ete exerce par aucun test avant l'ETAPE G - le
  correctif de l'epoque (`Dvups_lang::all()`) etait fonctionnellement
  correct (aucun test ne cassait) mais n'utilisait pas le cache attendu
  et effectuait une requete SQL a chaque creation, pour TOUTES les
  langues existantes (actives ou non).
- Test : `tests/Integration/DbalTranslatedCreateTest.php` - verifie
  qu'une ligne `cmscontent_lang` est creee pour chaque langue ACTIVE
  (ni plus, ni moins) et qu'une langue INACTIVE nouvellement creee n'en
  recoit jamais.
- Fichiers concernes : `dclass/devups/Tchutte/DBAL.php`,
  `tests/Integration/DbalTranslatedCreateTest.php`.
- Non traite (hors perimetre explicite de I.4, bas risque) : les deux
  autres usages de `Dvups_lang::allrows()` dans `DBAL.php` (chemin
  d'instance `__insert()`, different de `_createDbal()`) restent
  inchanges - methode differente, preexistante, jamais signalee comme
  cassee.

---

## Phase 5 (Localisation) - ETAPE J

### J.1/J.2 - ne conserver que Country, reparer sa traduction : FAITS

- Phase : J.1/J.2
- Supprimes entierement (entites, formulaires, datatables, controllers,
  Core JSON, JS, routes de `services.php`, requires du module) :
  `Continent`, `Department`, `District`, `Region`, `Town`.
- `Continent` a ete retire avec les autres (pas seulement les 4 demandes
  explicitement) : `Country::getContinent()`/`setContinent()`
  referencaient une propriete `$continent` jamais declaree ni mappee
  (pas d'annotation `@ManyToOne`, pas de cle `relation` dans
  `CountryCore.json`) - preuve que `Country` n'avait aucune dependance
  reelle a `Continent`, code mort. `Country::tvas()` (classe `Tva`
  inexistante dans tout le depot) retire au passage.
- Recherche (grep) sur tout le depot : aucun autre module (user, address,
  ...) ne reference `Continent`/`Department`/`District`/`Region`/`Town`.
  Les deux seules occurrences residuelles du texte "Region"/"Town" hors
  du module sont un commentaire (`FormFactory.php`, exemple historique)
  et la bibliotheque vendor jVectorMap (`admin/assets/scripts/main.js`,
  `Region` au sens generique de la carte) - sans rapport.
- Le formulaire `country/form` et sa traduction sont repares :
  `Country::__construct()` avait bien `$this->dvtranslate = true;
  $this->dvtranslated_columns = ["nicename"];` dans le code mais ces
  deux lignes etaient COMMENTEES - cause exacte du KNOWN_BROKEN "Location
  : traduction de Country a reactiver". Reactivees (decommentees).
  `Country_lang` existait deja correctement (colonne `nicename`,
  `@Id @ManyToOne(targetEntity="\Country")`).
- Les 9 entrees `KNOWN_BROKEN` correspondantes retirees de
  `tests/E2E/AdminScreensTest.php` (4 ecrans supprimes x2 list/form, et
  `country/form` desormais corrige - verifie vert par la suite e2e).
- Fichiers : `src/devupscms/ModuleLocation/Entity/Country.php`,
  `src/devupscms/ModuleLocation/devupscms.modulelocation.php`,
  `src/devupscms/ModuleLocation/services.php`,
  `config/dvups_configurations.php`, `config/module_configurations.php`,
  `tests/E2E/AdminScreensTest.php`, et les 38 fichiers supprimes du
  module (Controller/Core/Datatable/Entity/Form/Resource-js).

### J.3/J.4 - migration DROP, seed Country, tests : FAITS

- Phase : J.3/J.4
- Migration (ECRITE, PAS EXECUTEE)
  `database/migration/2026_11_08_phase5_location_drop.sql` : `DROP TABLE`
  de `department`, `district`, `region`, `town`, `continent`.
- Recherche de colonnes de cle etrangere `department_id`/`district_id`/
  `region_id`/`town_id`/`continent_id` dans tout le depot (grep) : AUCUNE
  trouvee - documente explicitement dans le commentaire du script, rien
  d'autre a retirer ailleurs.
- Donnee de reference `Country` : `database/seed/countries.php` (iso2,
  iso3, nom, indicatif telephonique - environ 195 pays), chargee par
  `Country::seed()` (idempotent : met a jour une ligne existante,
  identifiee par son iso2, plutot que d'en creer un doublon). Exposee via
  la commande CLI `php devups location:seed` (`dclass/generator/builder.php`).
- `.gitignore` : exception `!/database/seed/` ajoutee (meme pattern que
  `!/database/migration/`), ce repertoire contenant du code source
  versionne (donnee de reference), pas une donnee d'execution generee.
- Test : `tests/Integration/LocationSeedTest.php` - peuplement initial
  (un pays par ligne du seed) puis relance idempotente (aucune creation,
  uniquement des mises a jour, le total de lignes ne double pas).
- Fichiers : `database/migration/2026_11_08_phase5_location_drop.sql`,
  `database/seed/countries.php`, `src/devupscms/ModuleLocation/Entity/Country.php`
  (methode `seed()`), `dclass/generator/builder.php` (case `location:seed`),
  `tests/Integration/LocationSeedTest.php`, `.gitignore`.

---

## Phase 7 (Mots de passe) - ETAPE K

### K.1/K.2 - password_hash()/password_verify(), migration transparente : FAITS

- Phase : K.1/K.2
- `dv_hash_password()`/`dv_verify_password()` (`dclass/dvups_functions.php`,
  meme pattern que `dv_valid_lang()` de l'ETAPE H) : `dv_verify_password()`
  detecte un hash `password_hash()` valide via
  `password_get_info($hash)['algoName'] !== 'unknown'` (attention :
  `['algo']` est `null`, PAS `0`, pour un hash inconnu - premiere version
  buggee, cassait TOUTE connexion admin/e2e, corrigee avant commit). A
  defaut, retombe sur l'ancien format (40 hex, sha1()), compare en temps
  constant (`hash_equals`) - migration TRANSPARENTE, sans reinitialisation
  forcee : `$upgraded` (parametre par reference) indique a l'appelant de
  remplacer le hash par `password_hash()` et sauvegarder.
- `Dvups_admin::verifyPassword()`/`setPassword()` et
  `UserCore::verifyPassword()`/`setPassword()`/`setResetpassword()`/
  `setUpdatePassword()` : mis a jour.
- Tous les points de connexion/changement de mot de passe (admin et user,
  controller + frontcontroller) faisaient une recherche SQL
  `WHERE password = sha1(...)` - remplacee par une recherche par
  identifiant SEUL puis `verifyPassword()` en PHP (necessaire : sha1() est
  deterministe et permettait cette recherche directe, `password_hash()`
  ne l'est pas, le sel est different a chaque hash).
- `Dvups_admin::updatePassword($old, $new)` ajoutee : appelee par
  `Dvups_adminFrontController::updateAction()` mais n'existait pas (bug
  preexistant, jamais exerce par aucun test avant K.1) - corrigee au
  passage.
- `password` reste `VARCHAR(255)` pour `dvups_admin` et `user` (bcrypt
  ~60 caracteres, argon2id jusqu'a ~97) : AUCUN agrandissement de colonne
  necessaire, contrairement a ce qui etait anticipe dans la consigne.
- Fichiers : `dclass/dvups_functions.php`, `dclass/extends/UserCore.php`,
  `src/devups/ModuleAdmin/Entity/Dvups_admin.php`,
  `src/devups/ModuleAdmin/Controller/Dvups_adminController.php`,
  `src/devups/ModuleAdmin/Controller/Dvups_adminFrontController.php`,
  `src/devupsfront/ModuleUser/Controller/UserFrontController.php`.

### K.3 - "remember me" sans mot de passe dans le cookie : FAIT

- Phase : K.3
- Avant : le cookie `ADMIN_pwd` contenait directement `$admin->getPassword()`
  (le hash du mot de passe, autrefois sha1() - un cookie vole = mot de
  passe vole, puisque sha1() sans sel se reutilise tel quel). Remplace par
  `ADMIN_remember` : un token aleatoire (`random_bytes(32)`), dont seul le
  hash (sha256) et une expiration sont stockes en base
  (`Dvups_admin::issueRememberToken()`/`verifyRememberToken()`/
  `clearRememberToken()`, nouvelles colonnes `remember_token_hash`/
  `remember_token_expires_at`, migration ecrite - voir plus bas).
- Rotation a chaque usage reussi (`restartsessionAction()`, appelee a
  chaque requete admin via `admin/header.php`) : l'ancien token devient
  immediatement invalide, meme s'il a ete intercepte ("vole"). Invalide
  aussi a la deconnexion (`deconnexionAction()`) et au changement de mot
  de passe (`changepassword()`, `changepwAction()`,
  `Dvups_admin::updatePassword()`).
- `DClass\lib\Util::setcookie()`/`clearcookie()` : `Secure` si la requete
  est en HTTPS (`Util::isHttps()`, jamais force en clair pour ne pas
  casser le cookie en dev/http local), `HttpOnly` (deja present),
  `SameSite=Lax` (absent avant - necessite la forme "tableau d'options"
  de `setcookie()`, l'ancienne signature positionnelle n'a pas de
  parametre SameSite).
- Fichiers : `src/devups/ModuleAdmin/Entity/Dvups_admin.php`,
  `src/devups/ModuleAdmin/Controller/Dvups_adminController.php`,
  `dclass/lib/Util.php`,
  `database/migration/2026_11_08_phase7_password_hash.sql`.

### K.4 - audit sha1()/md5() restants : FAIT

- Phase : K.4
- Recherche (grep) de tout usage de `sha1(`/`md5(` restant dans le code
  applicatif (hors tests, qui testent legitimement contre le format de
  seed existant) :
  - **Traite (K.1)** : toutes les comparaisons de mot de passe
    (admin et user) - voir ci-dessus.
  - `User::setActivationcode()`/plusieurs endroits de
    `UserFrontController.php` (activation de compte, changement de
    telephone) : `sha1()` d'un code d'activation a 6 chiffres
    (`User::generatecode()`). **Severite faible** - NON traite (hors
    perimetre explicite de K, qui ne porte que sur les mots de passe) :
    le code source a seulement 10^6 combinaisons, un hash plus robuste
    (password_hash()) n'apporterait aucune resistance supplementaire a
    une attaque par force brute sur un espace aussi petit - le risque
    reel serait plutot l'absence de limitation de tentatives, non
    adressee ici non plus (hors perimetre).
  - `Dfile.php::hashname()` (`sha1($this->name . $timestamp())`) :
    genere un nom de fichier UNIQUE, pas un secret. **Sans objet**.
  - `Log.php::fingerprint()` (`sha1(...)` de class/file/line/message) :
    cle de deduplication de logs, pas un secret. **Sans objet**.
  - `Router.php` et `Dvups_adminTable.php` : code COMMENTE (jamais
    execute). **Sans objet**.
- Conclusion : plus aucun usage de sha1()/md5() pour un secret
  (mot de passe, token de session, cle API) dans le code execute, hormis
  le cas documente des codes d'activation (hors perimetre, severite
  faible, raison donnee ci-dessus).

### K.5 - tests : FAIT

- Phase : K.5
- `tests/Integration/PasswordHashMigrationTest.php` : login avec un hash
  legacy sha1() qui reussit ET remplace le hash par `password_hash()` ;
  mauvais mot de passe rejete SANS modifier le hash stocke ; les
  nouveaux mots de passe (admin et user) sont bien en `password_hash()`,
  jamais en sha1().
- `tests/Integration/RememberMeTest.php` : token "remember me" emis puis
  verifie ; le cookie n'est jamais le mot de passe ni son hash ; rotation
  invalide l'ancien token (scenario "token vole puis rejoue") ; token
  expire rejete ; deconnexion et changement de mot de passe invalident le
  token.
- Suite complete verte : 224 tests, 642 assertions, 3 incomplete
  (inchangees, sans rapport avec Phase 7).

---

## Durcissement - ETAPE L

### L.1 - RateLimiter, expiration des codes, verrouillage login : FAIT

- Phase : L.1
- `dclass/devups/RateLimiter.php` (nouveau) : limiteur generique
  (cle, fenetre, max), cache fichier `cache/ratelimit/<sha1(cle)>.json`
  (meme pattern que `cache/rights/`/`cache/lang/active.php`).
- Applique, par compte ET par IP : connexion admin (HTML et JSON),
  connexion user, verification/renvoi des codes a 6 (ou 5, cf. L.4)
  chiffres (activation, reinitialisation de mot de passe). 429 neutre
  en cas de depassement.
- `User::activationcode_expired_at` : etait mis a jour (+3h) mais
  JAMAIS lu nulle part - desormais verifie par
  `UserFrontController::checkActivationCode()`, TTL configurable
  (`ACTIVATION_CODE_TTL_MINUTES`, defaut 15 min). Colonne retypee
  `date` -> `datetime` (une date seule ne peut pas porter une
  expiration a la minute) - migration ecrite, non executee (voir
  "Migrations a executer manuellement").
- Regression corrigee en cours de route : `\DClass\lib\Util::getIp()`
  n'existe pas (seul `get_IP_address()` existe, sans parametre) -
  provoquait un 500 sur CHAQUE connexion admin/user et faisait
  echouer 63 tests e2e en cascade. `src/devupscms/ModuleLocation/Entity/Country.php`
  appelle elle aussi `Util::getIp()` (avec des parametres qu'aucune
  methode de `Util` n'accepte) - bug preexistant, hors perimetre de
  cette etape, signale ici pour memoire.
- Tests : `tests/Unit/RateLimiterTest.php` (6 tests). Suite complete
  verte.

### L.2 - rotation du "remember me" seulement a la restauration : FAIT (deja le cas)

- Phase : L.2
- `Dvups_adminController::restartsessionAction()` (ETAPE K.3) retourne
  deja immediatement si `$_SESSION[ADMIN]` est deja presente, AVANT
  toute verification/rotation du token - aucun code de production a
  changer.
- Test ajoute : `tests/E2E/RememberMeConcurrencyTest.php` - 3 requetes
  paralleles (`HttpTestCase::parallelGet()`, curl_multi) sur une
  session deja active ne provoquent jamais de deconnexion.

### L.3 - dv_valid_lang() dans defaultLang()/Route() : FAIT

- Phase : L.3
- Point deja signale (section H.4) et deliberement laisse pour plus
  tard : `Dvups_lang::defaultLang()` et
  `Request::Route()` (`dclass/devups/Http/Request.php`) interrogeaient
  `iso_code` avec la valeur brute de `?lang`. Desormais valides par
  `dv_valid_lang()`, comme le reste de la Phase H.

### L.4 - lien de reinitialisation de mot de passe : PAS DE JETON SEPARE, note ici

- Phase : L.4
- Il n'existe PAS de lien de reinitialisation base sur un jeton
  aleatoire separe, hashe en base, a usage unique. Le seul "lien"
  existant est `activation_link` dans
  `UserFrontController::initialiseUserParam()` :
  `route('login') . '?vld=' . $activationcode . '&u_id=' . $user->getId()`,
  ou `$activationcode` est le MEME code court (5 chiffres en prod,
  `'12345'` hors prod) envoye par email et verifie par
  `checkActivationCode()` (ETAPE L.1) - deja a usage limite (compteur
  d'echecs + IP, ETAPE L.1) et a expiration courte
  (`ACTIVATION_CODE_TTL_MINUTES`), et jamais stocke en clair (seul son
  `sha1()` est persiste).
- Reste neanmoins deux ecarts par rapport a un jeton de
  reinitialisation ideal :
  1. le code (5-6 chiffres) a un espace de valeurs bien plus petit
     qu'un jeton aleatoire (128+ bits) - le compteur d'echecs
     (ETAPE L.1) attenue fortement un brute-force en ligne, mais
     reste moins robuste par construction qu'un jeton long ;
  2. le code apparait en clair dans l'URL `activation_link` - risque
     de fuite par les journaux serveur, l'historique du navigateur,
     ou un pre-chargement de lien par un client mail.
- Recommandation pour une prochaine session (hors perimetre d'un
  simple durcissement) : remplacer ce lien par un jeton aleatoire
  dedie (type `random_bytes(32)` encode en base64url), stocke hache
  (`hash('sha256', ...)`) dans une colonne/entite separee avec
  expiration et usage unique, distinct du code a 6 chiffres envoye
  par SMS/affiche a l'ecran - les deux canaux (lien email, code
  SMS/app) n'ont pas besoin de partager le meme secret.
- Fichier concerne :
  `src/devupsfront/ModuleUser/Controller/UserFrontController.php`
  (`initialiseUserParam()`).

---

## Phase 6 (Notifications / Push / Reporting) - ETAPE M

### Plan (ecrit avant implementation, comme demande)

- Constat de depart : un systeme de notifications COMPLET et
  fonctionnel existe deja (`src/devupscms/ModuleNotification/Entity/Notification.php`,
  `Notificationtype.php`, `src/devupscms/ModulePushNotification/`) -
  entite unique `Notification` (user_id OU admin_id, deja le cas),
  `Notificationtype` (cle `_key` + contenu traduit par langue),
  `Notification::on()->send()->braodcast()`, listes/compteurs non lus
  cote admin (cloche, `NotificationController::alertAction()`) ET cote
  front (`NotificationFrontController`, deja une API `@Api` avec
  `@Auth(authorized=1)` donc deja protegee par JWT - `index()`,
  `notified()`, `readall()`, `alert()`). Decision : ADAPTER cette base
  plutot que la reecrire - une reecriture totale jetterait du code qui
  fonctionne et risquerait de casser les appelants existants (SMS,
  FCM mobile) pour un gain net faible face a l'echeance.
- M.2 (entite/API/routes/cloche) :
  1. **Notificationbroadcasted** : FAIT (ce commit) - c'etait l'ancien
     nom de l'entite, renommee en `Notification` sans nettoyage -
     fichiers Controller/Datatable/Form/Resource orphelins supprimes,
     entrees de config retirees, seul appelant restant
     (`Dvups_entity::alert()`) bascule sur `Notification::of()`.
  2. **API simple `toUser()`/`toAdmin()`/`toRole()`** : ajoutee
     PAR-DESSUS `on()`/`braodcast()` (pas en remplacement) - le
     gabarit du message vient de `t($type)` (lang/xx.php), conforme a
     la demande ("gabarit traduit via `t()`"), au lieu du champ
     `content` par-langue de `Notificationtype` (qui reste utilise par
     l'API existante `on()`/`braodcast()`, pour SMS/push groupes).
     `Notificationtype` est quand meme cree/retrouve par `_key` pour
     rester filtrable (cloche, liste admin).
  3. Routes API front JWT, cloche back-office : **deja faites**,
     aucun changement necessaire.
  4. Migration : aucune necessaire pour M.2 (aucune colonne ajoutee -
     reutilise le schema existant).
- M.3 (push) :
  1. Constat : une PREMIERE tentative de Web Push (VAPID) existe deja
     mais incomplete/incoherente -
     `Push_subscriptionFrontController::pushNotificaiton()`/`cloudMessagingPush()`
     interrogent un champ `subscription_id` qui n'existe PAS sur
     l'entite (seul `user_id` existe) - code mort en l'etat (aucun
     appelant), `minishlink/web-push` n'etait meme plus dans
     `composer.json`. Hors perimetre de corriger integralement cette
     ancienne tentative (pas d'appelant actif, risque de regression
     pour un gain nul) - seule la nouvelle API ci-dessous est branchee
     et testee.
  2. `minishlink/web-push` ajoute (composer). `Push_subscription`
     recoit une colonne `endpoint` (manquante - migration ecrite) et
     implemente reellement `SubscriptionInterface` (deja presque le
     cas : `getEndpoint()`/`getPublicKey()`/`getAuthToken()`/`getContentEncoding()`
     existaient déjà, juste jamais branches).
  3. Cles VAPID dans `.env`/`.env.example` uniquement
     (`VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`/`VAPID_SUBJECT`), jamais
     en base - independant du mecanisme existant
     `Push_subscriptionFrontController::generateKey()`/`key()` qui les
     stocke dans `Configuration` (laisse tel quel, inchange, toujours
     utilise par le flux FCM/mobile existant).
  4. `Push_subscription::sendWebPush()` (nouveau) : envoie via
     `Minishlink\WebPush\WebPush`, supprime l'abonnement automatiquement
     si `isSubscriptionExpired()` (410). Teste avec un faux client HTTP
     (Guzzle `MockHandler`) - aucun appel reseau reel dans les tests.
  5. Fil d'attente : `WorkersController::work()` (deja la logique
     cron/Workers, deja hors-prod desactivee sauf `PUSH_FORCE_SEND=1`,
     cf. ETAPE anterieure) distingue desormais `subscription_type`
     ('webpush' -> `sendWebPush()`, sinon FCM/Expo comme avant).
- M.4 (reporting) : FAIT (voir detail ci-dessous).

### M.1/M.2/M.3 - notifications et push : FAIT (voir detail ci-dessus)

### M.4 - Reportingmodel : collecte, sens inverse, apercu, envoi via DMail : FAIT

- Phase : M.4
- Constat : `Reportingmodel` existait deja (entite, CRUD back-office,
  `loadContent()`/`saveContent()`/`downloadContent()` pour un stockage
  fichier SEPARE dans `Resource/partial/`), mais pas branche sur
  `web/views/mails/*.blade.php` (gabarits Blade deja utilises par
  `DMail`, lui-meme deja utilise par `UserFrontController` pour les
  codes/OTP et deja journalise dans `Emaillog`).
- `ReportingmodelController::collectAction()` (nouveau) : parcourt
  `web/views/mails/*.blade.php`, cree les `Reportingmodel` manquants
  (nom = `mails.<fichier>`, meme convention que `DMail::init()`), ne
  supprime jamais rien.
- Sens inverse : `createAction()` (back-office) genere desormais le
  fichier gabarit correspondant s'il n'existe pas deja
  (`ensureTemplateFile()`, jamais d'ecrasement d'un fichier existant).
- `previewAction($id)` (nouveau) : rend le gabarit Blade avec un
  payload d'exemple fixe, sans envoi.
- `sendtestviadmailAction($id, $email)` (nouveau) : envoie via `DMail`
  (pas via l'ancien `Reportingmodel::sendMail()`, qui reste inchange
  pour ses appelants existants - contenu stocke en base, pas un
  fichier Blade) - deja journalise dans `Emaillog` (`DMail::sendMail()`).
- Bug corrige en passant : `Reportingmodel::getbyattribut('name', ...)`
  (ModelTrait, partagee par tout l'ORM) genere une colonne `name`
  AMBIGUE pour `Reportingmodel` (jointure avec `reportingmodel_lang`/
  `dvups_lang`, qui a elle aussi une colonne `name`) - contourne
  localement (`Reportingmodel::where('reportingmodel.name', ...)`)
  plutot que de toucher `ModelTrait` (risque pour tous les autres
  appelants de `getbyattribut()`) ; `Reportingmodel::model()` utilise
  encore l'ancienne forme ambigue, signale ici pour une prochaine
  session si elle devient genante.
- Tests : `tests/Integration/ReportingmodelCollectTest.php` (7 tests).
  Suite complete verte : 244 tests, 697 assertions, 2 incomplete
  (inchangees).

`php devups lang:missing` (execute sur le vrai dossier `lang/`, pas sur
le dossier temporaire de test, apres les renommages CMS de l'ETAPE G -
qui ont ajoute `cmscontent.reference`/`entity.cmscontent` et retire
l'ancienne cle orpheline `cmstext.reference`, et traduit au passage
`non`/`oui`) rapporte desormais 271 cles non traduites dans
`lang/en.php` (valeur = cle, comme prevu par la regle : "pour le reste,
laisse valeur = cle"). Liste complete (ordre alphabetique, tel que
rapporte par la commande) :

#, A constante with same key already exist, Action, Activated, Active,
Admin, Alias, All, Auth_token, Callback, Ce numéro de téléphone existe
déjà, Client, Code, Content, Content <br><i>NB: use : to specify a
variable i.e: :username</i>, Contenu text, Country has not been
specified. important for phonenumber, CreatedAt, Date, Description,
Description of the mail, Dvups_action, Dvups_role, Email, Email model
name, Entity, Entity_class, Entityid, Event i.e: new_message, Export
csv, File, First_at, Folder, Fomat de l'adresse email invalide,
Height, Image, Information mis à jour avec succès, Interval, Item
deleted successfully, Label, Last call, Last_at, Le code d'activation
n'est pas valide. Veuillez entrer de nouveau ou alors renvoyer un
autre code, Level, Line, Line to show, Log, Message, Mot de passe mise
à jour avec succès, Name, Notfication type, Notification, Numéro de
téléphone mise a jour, Object of the mail, Occurrences, Parent id,
Payload, Phonenumber, Position, Profile, Ref, Reference, Save,
Session, Size, Status, Timer (minutes), Title of the mail, Type, Type
of reporting, Un nouveau code d'activation vous a ete envoye, Une
erreur interne est survenue., User, User_ext_id, User_id, Username,
Viewedat, Width, _key, _max, _min, _value, action, alias, auth_token,
birthdate, callback, chain, client, cmscontent.reference, code, code
d'activation vous a été envoyé. Utilisez le pour confirmer le
changement de votre numéro., comment, configuration._key,
configuration._type, configuration._value, configuration.comment,
configuration.id, constraint, content, content_type, context,
continent.id, continent.name, continent.status, country not found,
country.created_at, country.deleted_at, country.id, country.iso,
country.iso3, country.name, country.nicename, country.numcode,
country.phonecode, country.status, country.updated_at, create,
creationdate, date_end, delete, description, dv_image.image,
dvups_action, dvups_action.action, dvups_action.entity,
dvups_action.label, dvups_lang.active, dvups_lang.date_format_full,
dvups_lang.date_format_lite, dvups_lang.id, dvups_lang.iso_code,
dvups_lang.language_code, dvups_lang.locale, dvups_lang.main,
dvups_lang.name, dvups_log.context, dvups_log.entity_class,
dvups_log.file, dvups_log.fingerprint, dvups_log.first_at,
dvups_log.last_at, dvups_log.level, dvups_log.line, dvups_log.log_file,
dvups_log.message, dvups_log.occurrences, dvups_role,
dvups_role.alias, dvups_role.name, edit, email, emailmodel.content,
emailmodel.contenttext, emailmodel.id, emailmodel.object, endpoint,
entity, entity.cmscontent, entity.dv_image, entity.notification,
entity.tree_item, entity_class, export, file, fingerprint, first_at,
firstname, height, id, image, interval, interval (J), interval._max,
interval._min, interval._value, interval.label, is_activated, label,
lang, last_at, lastname, le numéro doit etre constitué de 9
caractères, level, line, log, log_file, message, name, next_call,
notification.content, notification.creationdate, notification.entity,
notification.entityid, notification.id, notificationbroadcasted.status,
notificationbroadcasted.viewedat, notificationtype,
notificationtype._key, notificationtype.content, notificationtype.id,
oauth_client.client, oauth_client.name, oauth_client.user_ext_id,
oauth_client.user_id, occurrences, password.notconfirm, payload,
phonenumber, position, pseudo, public_key, push_email.date_end,
push_subscription.auth_token, push_subscription.content_type,
push_subscription.public_key, push_subscription.status,
push_subscription.subscription_type, push_subscription.user_id, queue,
reference, reportingmodel, reset password, resettingpassword, search,
session token unavailable, sexe, show, size, slug, status.color,
status.id, status.key, status.label, status.position,
subscription_id, subscription_type, this field is important!, titre,
tree, tree.branches, tree.description, tree.id, tree.label, tree.name,
tree_item.description, tree_item.hierarchy, tree_item.id,
tree_item.main, tree_item.name, tree_item.parent_id,
tree_item_image.id, tree_item_image.width_size, type, user.email,
user.firstname, user.is_activated, user.lastname, user.password,
user.phonenumber, user.username, user_ext_id, user_id, username,
width, workers.callback, workers.log, workers.payload, workers.queue,
workers.type, you most specify destination (s)

La plupart de ces cles sont des noms de colonnes de datacore
(`tree_item.*`, `dvups_log.*`, `country.*`...) utilises comme libelles
de champ generiques plutot que des phrases a traduire : a trier et
completer manuellement, cle par cle, dans `lang/en.php` - aucune cle
n'a ete reecrite dans le code (regle respectee).

---

## Phase 8 (Documentation) - ETAPE N

Constat de depart : `documentation/api.php` (OpenAPI 3) et
`documentation/guide.php` (guide developpeur) existaient deja,
FONCTIONNELS, generes par parsing statique des annotations Devups et
des datacores (aucune classe PHP chargee) - confirmes servant du 200 en
e2e avant toute modification. Decision (au lieu de reecrire) : extraire
leur logique dans des fonctions partagees pour les reutiliser depuis de
nouvelles commandes CLI, en verifiant l'absence de regression par
diff byte-a-byte de la sortie live avant/apres (`/tmp/api-before.json`
38239 octets, `/tmp/guide-before.json` 116725 octets - identiques apres
refactor).

- **N.1** : `dclass/generator/OpenApiGenerator.php`
  (`dv_build_openapi_spec(string $root, bool $includeAdmin = false)`) -
  extrait de `documentation/api.php`, qui se limite desormais a
  `require` + `echo json_encode(...)`. `php devups api:doc` ecrit un
  instantane `openapi.json` a la racine (non versionne, meme logique
  que `/build/`).
- **N.2** : `dclass/generator/DocGuideGenerator.php`
  (`dv_build_doc_guide()` + `dv_render_doc_guide_entity_page()`) -
  extrait de `documentation/guide.php` (JSON ET UI interactive
  inchanges, verifies identiques). `php devups doc:guide` ecrit une
  page HTML statique par module/entite sous
  `documentation/guide/<composant>/<module>/<entite>.html` (non
  versionne).
- **N.3** : Swagger UI back-office (`route\Admin::apidocsView()`,
  `admin/apidocs`), protegee par `can('read', 'Documentation')` - meme
  garde que l'ecran `Dvups_log` (lecture seule). Comme AUCUNE action
  `Documentation` n'existe dans `dvups_action`, seul le role `admin`
  (bypass maitre de `can()`) y a acces pour l'instant ; un droit dedie
  pourrait etre ajoute via le datacore + `rights:sync` si une
  granularite plus fine est souhaitee (**item ouvert**, note ici plutot
  que devine). Cette page ajoute `$includeAdmin = true` : les routes
  admin standard (liste + formulaire, memes chemins que
  `tests/E2E/AdminScreensTest.php::screens()`) apparaissent sous le tag
  "Admin – session" avec un schema `SessionAuth` (cookie), a cote des
  routes front (JWT, `BearerAuth`) deja documentees - "distingue les
  routes front (JWT) et admin (session)" demande par l'instruction.
- README : section "Premier module" ajoutee (datacore JSON a la main +
  `core:g:module`/`core:g:moduleendless`/`rights:sync` - aucune de ces
  commandes ne modifie `config/dvups_configurations.php`ni
  `module_configurations.php`, a declarer a la main, verifie en lisant
  `dclass/generator/__Generator.php`) ; section "Documentation" mise a
  jour avec les 3 points ci-dessus.
- Tests : `tests/Unit/DocGenerationTest.php` (spec/guide bien formes,
  deterministes, `$includeAdmin` ajoute bien les routes et le schema
  `SessionAuth` sans jamais y toucher par defaut) et
  `tests/E2E/AdminApiDocsTest.php` (403 anonyme, 200 + tag admin pour
  une session admin).

Commits : `0d8aa5a` (N.1/N.2), `bf36a1a` (N.3), `68020e3` (README).

---

## Traductions - ETAPE O

`php devups lang:missing` (cles effectivement appelees par `t()` dans
le code, comparees a `lang/en.php`/`lang/fr.php`) signalait 254 cles
"manquantes" en anglais et 288 en francais - en realite une partie de
ces cles est DEJA correcte dans la langue cible (meme mot des deux
cotes - "Email", "Status"... - ou phrase deja ecrite directement dans
la bonne langue par le code appelant `t()`), ce que `lang:missing` ne
peut pas distinguer d'une vraie fuite (il se contente de `valeur ===
cle`). Un tri manuel, cle par cle, a donc precede toute ecriture :

- **Vraies fuites de francais dans `lang/en.php`** (13 phrases, ex:
  "Apercu impossible : gabarit introuvable" -> "Preview unavailable:
  template not found") : traduites.
- **Vraies fuites d'anglais dans `lang/fr.php`** (~35 phrases, ex:
  "Username" -> "Nom d'utilisateur", "Country has not been specified..."
  -> "Le pays n'a pas été précisé...") : traduites.
- **Vocabulaire technique partage** (~160 cles `entite.champ` ou
  `nom_champ`, label generique de formulaire/tableau pour un champ sans
  libelle explicite dans son datacore - ex: `dvups_log.level` -> "Level"
  / "Niveau", `user_id` -> "User ID" / "ID utilisateur") : traduit via
  une table de correspondance couvrant le nom de champ (apres le
  dernier point) commune aux deux langues.
- **Laisse intentionnellement inchange** (valeur = cle, DEJA correcte) :
  tous les mots identiques dans les deux langues non ambigus (`Email`,
  `Status`→`Statut` deja present, `Position`, `Description`...) plus
  les cles deja ecrites par le code dans la langue cible (ex: pour
  `fr.php`, `"Bienvenue sur"`, `"Erreur"`, `"Code d'activation
  incorrect!"` - le code appelle directement `t()` avec du francais).
- **Items ambigus, non devines, a trancher a la main** (meme liste pour
  les deux langues) : `Action`, `Active`, `Alias`, `Callback`, `Client`,
  `Contenu text` (francais avec faute de frappe "texte", ou anglais
  "content text" ? contexte non retrouve), `Dvups_action`/`Dvups_role`
  (et leurs variantes en minuscule - nom de classe, a garder tel quel ou
  traduire en "Role"/"Action" ?), `Interval`, `Log`, `Ref`, `Session`,
  `cmscontent.reference`, `entity.cmscontent`, `entity.tree_item` (ces
  deux dernieres ressemblent a des *valeurs* d'un champ "entity", pas a
  des libelles de champ - contexte d'appel non retrouve dans le temps
  disponible).
- **Bug pre-existant repere, non corrige** (hors scope ÉTAPE O, scanner
  `lang:scan`, pas le code applicatif) : la cle `'Aucun élément pour
  l\\'` dans `lang/en.php`/`lang/fr.php` est tronquée - une apostrophe
  dans la chaine source a confondu la regex de `dv_lang_scan_keys()`
  (`builder.php`). La vraie cle complete (`"Aucun élément pour
  l'instant."`) existe par ailleurs et est déjà bien traduite ; cette
  entrée tronquée est un résidu inoffensif (plus jamais recherchée par
  `t()`), laissée telle quelle plutôt que corrigée au hasard.

Resultat : `lang:missing` est passe de 254/288 a **77/81** cles
"manquantes" (le reste correspond aux categories "deja correct" et
"ambigu" ci-dessus, pas a une fuite reelle). Script de traduction
ecrit dans le scratchpad de session (non versionne - une substitution
cle-par-cle a partir des deux tableaux `missing_en`/`missing_fr`
exacts), fichiers `lang/*.php` reecrits avec le meme format (`'cle' =>
'valeur',`, triees) ; aucune cle n'a ete ajoutee ni retiree, seules des
valeurs ont change.

Commit : `9c92375`.

---

## Resume phase par phase

**Phase 1 (Droits)** : entierement terminee et committee AVANT cette
session (6/6 points). Non retouchee ici.

**Phase 2 (Logs) - ETAPE D** : terminee.
- D.1 : le gestionnaire d'erreurs (`set_error_handler`) et la garde
  shutdown (`error_get_last()`) transmettent desormais explicitement
  `file`/`line` a `Log::write()`, qui les utilise au lieu de capturer
  son propre emplacement - teste (`LogHandlerTest`).
- D.2 : `error_reporting() & $level` respecte (erreurs masquees par `@`
  jamais journalisees) ; `LOG_MIN_LEVEL` (defaut `warning`) filtre
  `E_NOTICE`/`E_DEPRECATED` par defaut - teste.
- D.3 : garde anti-recursion (`Log::$writing`) dans `write()` - teste
  par reflexion.
- D.4 : increment atomique (`UPDATE ... SET occurrences = occurrences + 1`)
  au lieu de lire-puis-ecrire ; empreinte en `sha1()`.
- D.5 : `respondWithFatal()` ecrit du texte brut sur `STDERR` en CLI
  (plus de HTML).
- D.6 : detail Log affiche le contenu du fichier de debordement
  (echappe, dans un `<pre>`) ; le JS du module (`dvups_logCtrl.js`/
  `dvups_logForm.js`) etait deja un stub inoffensif, rien a nettoyer.
- B.5/B.7/B.8 (reports de la session precedente, executes cette
  session sous ETAPE F) : voir Phase 2bis ci-dessous.

**Phase 3 (Langues) - ETAPE E** : terminee.
- E.1 : constante `LANG_DIR` (defaut `ROOT.'lang/'`) et `LANG_COLLECT`
  (defaut : actif hors prod) ; `tests/bootstrap.php` cree un dossier
  temporaire dedie par run (jamais les fichiers versionnes) et le
  propage via `putenv()` - le serveur e2e le recoit automatiquement
  (`HttpServer` transmet `getenv()` au process `php -S` enfant).
- E.2 : `LangTest` rendu independant de l'ordre (fixtures creees par le
  test, plus de dependance a l'e2e).
- E.3 : une cle collectee est aussi ajoutee a `$dv_translations` en
  memoire (pas de nouvelle lecture disque pour la meme cle dans la
  requete) - teste.
- E.4 : `lang:scan` reecrit avec `token_get_all()` (plus de regex) ;
  seules les cles litterales simples sont retenues, les appels a cle
  construite (concatenation) sont ignores et signales en sortie -
  teste (`LangScanCommandTest`).
- E.5 : `lang/fr.php`/`lang/en.php` nettoyes des cles parasites issues
  de l'ancienne regex ; traductions evidentes appliquees en anglais ;
  271 cles restantes listees ci-dessus via `lang:missing` (valeur =
  cle, non reecrites dans le code - liste mise a jour apres les
  renommages CMS de l'ETAPE G, qui ont ajoute/retire quelques cles).
- E.6 : `t_lang($cle, $iso, $params=[])` ajoute (lecture directe de
  `lang/<iso>.php`, mise en cache par langue, sans session) ; les 3
  mails (`invitation`/`otp`/`welcome`) migres ; `langt()` retire
  (corps remplace par un commentaire de redirection).
- E.7 : liste des langues actives mise en cache
  (`cache/lang/active.php`, `Dvups_lang::cachedActiveDlangs()`), meme
  modele que le cache des droits, invalidee automatiquement a chaque
  creation/modification/suppression d'une langue (overrides
  `__insert`/`__update`/`__delete` sur `Dvups_lang`) - `header.php` ne
  fait plus `Dvups_lang::all()` a chaque requete.
- E.8 : `Local_content`/`Local_content_key`/`Local_content_lang`/
  `Page_mapped` entierement supprimes (entites, controleurs,
  formulaires, datatables, vues, configurations) ; migration DROP
  ecrite (non executee, `database/migration/2026_11_08_drop_local_content.sql`) ;
  4 entrees `KNOWN_BROKEN` devenues sans objet retirees.
- C.1 (reference iso2) : deja en place avant cette session, confirme
  sans changement.

**Phase 2bis (Logs) - ETAPE F** : terminee.
- F.1 : avis de la session precedente execute - `ModuleDatabase`/
  `Request_history` supprimes entierement (mortaient des l'origine,
  jamais alimentes) ; migration DROP ecrite (non executee,
  `database/migration/2026_11_08_drop_request_history.sql`) ; retire
  des deux fichiers de configuration.
- F.2 : nouveau schema `Emaillog` (destinataire, sujet, modele, statut
  envoye/echec, erreur, dates) ; entite, datatable et ecran lecture
  seule ; `DMail.php` et `Reportingmodel::sendMail` adaptes via
  `Emaillog::logSuccess()`/`logFailure()` ; migration ecrite (non
  executee, `database/migration/2026_11_08_emaillog_schema.sql`) ;
  tests (`EmaillogTest`) couvrant envoi reussi, envoi en echec,
  destinataire chaine simple, et la separation stricte Log::/Emaillog
  dans les deux sens.

**Phase 4 (CMS) - ETAPE G** : plan (G.1), renommages (G.2/G.3),
suppression de ModuleGallery (G.4) et nettoyage generateur/tests (G.5)
tous termines et testes.
- `Cmstext`/`Imagecms` renommes et reconstruits en `Cmscontent`/
  `Cmsresource` (ce dernier etend directement `ImageCore`, plus de
  jointure vers `Dv_image`) ; `Cmscomment` genere proprement (datacore
  corrige, jamais d'entite avant cette session) ; page d'edition admin
  du CMS corrigee et fonctionnelle (`cmscontent/edit?id=`) ; bug latent
  de regression (`Dvups_lang::readCache()` retiree a l'ETAPE E.7,
  jamais appele avant cette session) decouvert et corrige en testant.
- `ModuleGallery`/`Dv_image` supprimes entierement : `Tree_item_image`
  (seule dependance restante, pour les images d'arborescence) herite
  desormais directement de `ImageCore`, meme pattern que
  `Cmsresource` ; un bug latent (`getDv_image()` inexistant dans
  `Tree_item_imageForm`) decouvert et corrige au passage.
- Migrations ecrites (non executees,
  `database/migration/2026_11_08_phase4_cms_rename.sql`).
- Tests : `tests/Integration/CmsPhase4Test.php`,
  `tests/E2E/CmscontentEditPageTest.php`.

**Securite langue - ETAPE H** : terminee.
- `dv_valid_lang()`/`dv_lang_file_path()` : validation centrale (format
  `^[a-z]{2}$` + langue active, defense en profondeur via `realpath()`)
  utilisee par `local()`, `setlang()`, `lang.php`, `t_lang()` - toute
  valeur invalide retombe sur `__lang`, jamais un chemin hors de
  `lang/` ni une erreur 500. Regression (double declaration de classe,
  bare `require` vs `require_once`) decouverte et corrigee en testant.
- H.4 : audit du depot pour tout `require`/`include`/`file_get_contents`
  construit a partir d'une entree utilisateur/BD - deux points reels
  trouves et corriges (`Dfile::uploadchunk`, code mort ; et
  `ReportingmodelController::loadContent`/`saveContent`, admin
  uniquement, `basename()` ajoute par precaution).
- Tests : `tests/Unit/LangSecurityTest.php`,
  `tests/E2E/LangSecurityTest.php` (traversee de repertoire, injection
  `<script>` dans un lien genere).

**Phase 4 (CMS), fin des migrations - ETAPE I** : terminee.
- Migration CMS completee avec une vraie reprise de donnees
  (`imagecms`→`cmsresource`, colonnes d'image de `tree_item_image`),
  comptages avant/apres en commentaire, DROP toujours commentes ;
  verifiee par un test d'integration qui recree l'ANCIEN schema sur une
  base dediee et execute le script reel.
- `DBAL::_createDbal()` (chemin `Model::create()`) utilisait
  `Dvups_lang::all()` (TOUTES les langues) au lieu du cache des langues
  ACTIVES attendu depuis l'ETAPE E.7 - corrige et teste.

**Phase 5 (Localisation) - ETAPE J** : terminee.
- Seul `Country` conserve ; `Continent`/`Department`/`District`/
  `Region`/`Town` retires entierement (aucune dependance reelle, grep a
  l'appui) ; traduction de `Country` reparee (flag `dvtranslate`
  commente par erreur) ; migration DROP ecrite (non executee) ; donnee
  de reference (`database/seed/countries.php`, `php devups
  location:seed`, idempotent).

**Phase 7 (Mots de passe) - ETAPE K** : terminee.
- `password_hash()`/`password_verify()` pour `Dvups_admin` et `User`,
  migration transparente d'un ancien hash sha1() au prochain login
  reussi (bug de detection `password_get_info()['algo']` - `null`, pas
  `0` - decouvert et corrige en testant, cassait toute connexion
  admin/e2e). "Remember me" : le cookie ne contient plus jamais le mot
  de passe ni son hash, mais un token aleatoire dont seul le hash est
  stocke en base, avec expiration, rotation a chaque usage et
  invalidation a la deconnexion/au changement de mot de passe.
  `Dvups_admin::updatePassword()` (bug preexistant, methode manquante)
  corrigee au passage. Aucun agrandissement de colonne necessaire.

**Durcissement - ETAPE L** : terminee.
- Rate limiting generique (`dclass/devups/RateLimiter.php`, cache
  fichier) branche sur le login admin, le login front et la
  verification/renvoi de code d'activation ; expiration du code
  d'activation parametrable (`ACTIVATION_CODE_TTL_MINUTES`), colonne
  `activationcode_expired_at` passee de `date` a `datetime`.
  "Remember me" (rotation a chaque restauration, jamais a la
  deconnexion simple) et `dv_valid_lang()` (defaut langue,
  `Request::Route()`) etaient deja corrects depuis K.3/H - verifies et
  testes plutot que retouches. Bug introduit puis corrige dans la meme
  etape : `Util::getIp()` (n'existe pas) -> `Util::get_IP_address()`.

**Phase 6 (Notifications / Push / Reporting) - ETAPE M** : terminee.
- `Notificationbroadcasted` (entite fantome, jamais generee) supprimee
  de la configuration et du code appelant ; `Notification::of()` seule
  API desormais. Web Push reel via `minishlink/web-push` (cles VAPID
  dans `.env`, `Push_subscription::sendWebPush()`, suppression
  automatique d'un abonnement expire sur 410) ; testee entierement hors
  ligne (Guzzle `MockHandler`). `Reportingmodel` : collecte des
  gabarits Blade existants (bouton back-office), creation inverse
  (gabarit genere automatiquement a la creation d'un modele), apercu et
  envoi de test via `DMail`/`Emaillog`.

**Phase 8 (Documentation) - ETAPE N** : terminee (detail plus haut).
- `php devups api:doc`/`doc:guide` (generateurs partages, extraits des
  pages `documentation/` deja fonctionnelles, sortie verifiee
  identique) ; Swagger UI back-office (`admin/apidocs`, protegee par
  `can()`) distinguant routes front (JWT) et admin (session) ; README
  complete (premier module, documentation).

**Traductions - ETAPE O** : terminee (detail plus haut).
- `lang:missing` : 254/288 cles -> 77/81 (le reste deja correct ou
  ambigu, liste exhaustive plus haut) ; aucune cle ajoutee/retiree,
  uniquement des valeurs corrigees.

## Migrations - dans l'ordre

Aucune de ces migrations n'a ete executee par cette session hors de la
base de test (regle : jamais de DROP ni de suppression de donnees hors
base de test). Depuis l'ETAPE Q, ne plus les executer a la main
(`mysql -u ... < fichier`) : utiliser `php devups migrate` (rejoue
dans l'ordre les migrations non encore enregistrees dans
`dvups_migration`, voir README.md section "Migrations (ETAPE Q)") -
`php devups migrate:status` liste ce qui est joue/en attente,
`php devups migrate --dry-run` affiche le SQL sans l'executer. L'ordre
ci-dessous est celui que `migrate` applique (tri alphabetique des noms
de fichiers) :

1. `database/migration/2026_11_07_dvups_log_schema.sql` (nouveau
   schema `dvups_log`, Phase 2/ETAPE D).
2. `database/migration/2026_11_08_drop_local_content.sql` (DROP
   `local_content`, `local_content_key`, `local_content_lang`,
   `page_mapped`, Phase 3/ETAPE E.8).
3. `database/migration/2026_11_08_drop_request_history.sql` (DROP
   `request_history`, Phase 2/ETAPE F.1).
4. `database/migration/2026_11_08_emaillog_schema.sql` (DROP+CREATE
   `emaillog` avec le nouveau schema - **exporter l'historique existant
   avant execution si besoin**, voir commentaire en tete du fichier,
   Phase 2/ETAPE F.2).
5. `database/migration/2026_11_08_phase4_cms_rename.sql` (RENAME
   `cmstext`/`cmstext_lang` -> `cmscontent`/`cmscontent_lang`, creation
   `cmsresource`/`cmscomment`, ALTER `tree_item_image` pour les
   nouvelles colonnes `ImageCore`, Phase 4/ETAPE G.2/G.3/G.4) - **depuis
   l'ETAPE I, ce script REPREND reellement les donnees** (`INSERT INTO
   cmsresource ... SELECT ... FROM imagecms JOIN dv_image`, `UPDATE
   tree_item_image ... FROM dv_image`), verifie par
   `tests/Integration/CmsMigrationTest.php`. Seuls les DROP
   (`imagecms`, `dv_image`) restent commentes en fin de fichier : a
   decommenter manuellement, apres avoir compare les comptages
   avant/apres (blocs `SELECT COUNT(*)` en commentaire, juste avant
   chaque DROP) sur l'environnement concerne - `ModuleGallery` etant
   desormais supprime du code, plus rien ne lit `dv_image`, mais ce
   DROP reste une suppression de donnees a verifier manuellement avant
   execution, jamais automatique.
6. `database/migration/2026_11_08_phase5_location_drop.sql` (**DROP**
   `department`, `district`, `region`, `town`, `continent` -
   destructeur de donnees ; exporter si des donnees existantes doivent
   etre conservees, voir commentaire en tete du fichier ; aucune colonne
   de cle etrangere trouvee ailleurs dans le depot, Phase 5/ETAPE J.3).
   Apres cette migration (ou directement, Country ne depend pas des
   tables retirees) : peupler `country` avec
   `php devups location:seed` (non destructeur, idempotent).
7. `database/migration/2026_11_08_phase7_password_hash.sql` (**ALTER**
   `dvups_admin` - ajoute `remember_token_hash`/
   `remember_token_expires_at`, aucune donnee existante modifiee ni
   supprimee, Phase 7/ETAPE K.3). Les mots de passe sha1() existants
   ne sont PAS transformes par cette migration : ils le sont
   automatiquement, un par un, par le code applicatif au prochain login
   reussi de chaque compte (voir ETAPE K.1) - rien a executer pour ca.
8. `database/migration/2026_11_08_phase8_rate_limit_activation_expiry.sql`
   (**ALTER** `user` - `activationcode_expired_at` passe de `date` a
   `datetime`, aucune donnee supprimee ; valeurs existantes conservees
   et simplement reinterpretees avec une heure a minuit. Durcissement/
   ETAPE L.1).
9. `database/migration/2026_11_08_phase6_push_subscription_endpoint.sql`
   (**ALTER** `push_subscription` - ajoute la colonne `endpoint`
   (URL du service de push), NULL pour les abonnements existants
   jusqu'a leur prochain renouvellement cote client. Phase 6/ETAPE M.3).
10. `database/migration/2026_11_08_phase9_reset_password_token.sql`
    (**ALTER** `user` - ajoute `reset_token_hash`/
    `reset_token_expired_at`, additif, aucune donnee supprimee ni
    modifiee. ETAPE P.1).

**Destructrices de donnees (a valider avec precaution, confirmation
`php devups migrate` requise - `DROP`/`TRUNCATE`/`DELETE` detectes
automatiquement)** : #2, #3, #4 (DROP+CREATE, exporter l'historique si
besoin), #5 (DROP final commente, a decommenter apres verification
manuelle), #6. Toutes les autres (#1, #7, #8, #9, #10) sont des
ALTER/CREATE additifs, sans perte de donnees.

Sur l'environnement de test (`devups_phpunit`), aucune de ces
migrations n'est necessaire : le schema est recree automatiquement a
chaque run par Doctrine SchemaTool a partir des entites
(`tests/bootstrap.php`). Sur une **installation neuve**
(`php devups install`), ces 10 fichiers sont marques joues (lot 0) sans
etre executes - le schema qu'ils decrivent est deja construit en entier
depuis les entites actuelles (voir README.md section
"Migrations (ETAPE Q)").

## Etat des tests

`composer test` verifie vert a chaque commit de cette session
(ETAPE D : commit `d1f25e3` ; E.1-E.6 : `e5a9863` ; E.7-E.8 : `597cdd6` ;
F.1 : `696cbc5` ; F.2 : `92af6d2` ; README : `e55cf7c` ; lang:scan +
plan G : `5f04c6c` ; ETAPE G.1-G.3/G.5 : `368172a` ; ETAPE G.4 :
`d1a45b6` ; docs G.4 : `606c6d6` ; ETAPE H.1-H.3 : `559f73d` ; H.4 :
`291590e` ; ETAPE I.1-I.3 : `8deef0b` ; I.4 : `ac2a2c5` ; ETAPE J.1/J.2 :
`41ae28c` ; J.3/J.4 : `af651ba` ; ETAPE K.1-K.3 : `7e01572`) - a la fin
de la session : **224 tests, 642 assertions, 0 echec, 3 "incomplete"**
documentes et assumes dans
`tests/E2E/AdminScreensTest.php::KNOWN_BROKEN` (`Notificationbroadcasted`,
entite absente ; `Dvups_log`/`Emaillog`, ecrans lecture seule par
design) - les 9 entrees `ModuleLocation` ("seul Country est conserve"/
"traduction a reactiver") de la session precedente ont ete retirees a
l'ETAPE J (ecrans supprimes ou corriges). La hausse de 180 a 224 tests
entre la fin de l'ETAPE G et la fin de cette session vient des tests
ajoutes aux ETAPES H (`LangSecurityTest` unit+e2e), I
(`CmsMigrationTest`, `DbalTranslatedCreateTest`), J (`LocationSeedTest`)
et K (`PasswordHashMigrationTest`, `RememberMeTest`), moins la
disparition de 8 des 9 ecrans Location (`department`/`district`/
`region`/`town`, list+form) du generateur `AdminScreensTest::screens()`.

### Mise a jour finale (ETAPES L a O)

`composer test` reste vert a chaque commit (ETAPE L : `7a4f6ac`..
`a25def2` ; ETAPE M : `dfb405b`..`902739a` ; ETAPE N.1/N.2 : `0d8aa5a` ;
N.3 : `bf36a1a` ; README N : `68020e3` ; ETAPE O : `9c92375`) - a la
fin de cette session : **252 tests, 717 assertions,
0 echec, 2 "incomplete"** (les memes `Dvups_log`/`Emaillog`,
ecrans lecture seule par design - `Notificationbroadcasted` a disparu
de `KNOWN_BROKEN` a l'ETAPE M puisque l'entite fantome elle-meme a ete
supprimee). La hausse de 224 a 252 tests vient des ajouts des ETAPES L
(`RateLimiterTest`, `RememberMeConcurrencyTest`), M
(`NotificationApiTest`, `PushSubscriptionWebPushTest`,
`ReportingmodelCollectTest`), N (`DocGenerationTest`,
`AdminApiDocsTest`) - ETAPE O n'a ajoute aucun test (uniquement des
valeurs de traduction, verifiees par la suite existante + `lang:missing`
en CLI).

**Recapitulatif general (ETAPES L-O)** : les 4 etapes demandees sont
terminees, testees et committees individuellement (voir "Resume phase
par phase" ci-dessus pour le detail). Aucune migration n'a ete executee
hors de la base de test ; la liste ordonnee ci-dessus (9 scripts, dont
4 destructeurs de donnees a valider manuellement : #2, #3, #4, #5/#6)
est a jour. Items laisses ouverts, tous documentes a leur section :
droit `Documentation` dedie pour `can()` (ETAPE N.3, actuellement
reserve au role `admin`) ; items de traduction ambigus et bug de scan
pre-existant sur une cle tronquee (ETAPE O, section ci-dessus).

## ETAPE P (Corrections)

Les 7 points demandes sont FAITS, chacun teste et committe
individuellement :

1. Reinitialisation de mot de passe : jeton dedie (`random_bytes(32)`
   base64url, seul `hash('sha256', $token)` stocke,
   `RESET_PASSWORD_TOKEN_TTL_MINUTES` - defaut 30 min -, usage unique,
   invalide par une nouvelle demande ou au changement de mot de passe) ;
   lien e-mail sans code court ni `u_id` ; reponse identique que le
   compte existe ou non. Migration additive
   (`database/migration/2026_11_08_phase9_reset_password_token.sql`).
   Commit `9aa5bdd`.
2. `Country::current()`/`ip_info()` : `Util::getIp()` (inexistante) ->
   `Util::get_IP_address()`. Scanner reflection-based (scratchpad, non
   committe) sur tout le depot : 4 autres appels casses trouves et
   corriges (`StatusForm::render()` x2, `Dvups_adminController::renderForm()`,
   `Dvups_roleController::renderDetail()`, `CmscontentForm::render()` x2) -
   plus 2 bugs latents decouverts en ecrivant les tests de regression
   (`TableTemplateRender::templateTableRowDetail()` double-indexait son
   argument - cassait `_show` de TOUTE entite admin ; `Dvups_role` sans
   `getAlias()`). phpstan niveau 0 ajoute (`composer phpstan`), beaucoup
   de faux positifs documentes dans `phpstan.neon.dist`. Commit `e3cc0de`.
3. IP client : `Util::get_IP_address()` durci en place (meme nom/signature,
   8 appelants inchanges) - ne fait confiance a `X-Forwarded-For` que si
   `REMOTE_ADDR` est dans `TRUSTED_PROXIES` (`.env`, vide par defaut).
   Commit `6669e15`.
4. Push : code mort retire (`pushNotificaiton()`, `cloudMessagingPush()`,
   `fcmPushNotification()`, `testPushNotif()`, references a
   `subscription_id`). Commit `7eb1167`.
5. Notifications : `on()`/`braodcast()` deviennent des alias depreciees
   (`@deprecated` + `Log::info()` au premier appel) de `toUser()`/
   `toAdmin()`/`toRole()` et `broadcast()` (faute corrigee, alias garde) -
   `on()` NON migre volontairement : couvre des cas (entite arbitraire,
   SMS, session) que `toUser()`/`toAdmin()`/`toRole()` ne couvrent pas,
   migrer aurait ete une regression silencieuse. Commit `0d5be44`.
6. `Documentation/read` : action "virtuelle" (`Dvups_action::VIRTUAL_ACTIONS`,
   synchronisee par `rights:sync` comme n'importe quelle action
   d'entite) plutot qu'un entite/datacore fictif - l'acces a `/admin/apidocs`
   est desormais attribuable a un role non maitre. Commit `eb1c605`.
7. Langues : cle tronquee `Aucun élément pour l\'` retiree de
   `lang/en.php`/`lang/fr.php` ; tokenizer de `lang:scan` verifie
   correctement l'apostrophe echappee (test de regression). Commit
   `ccc4236`.

`composer test` reste vert a chaque commit (261 -> 276 tests au fil des
7 sous-etapes, 0 echec, les 2 memes "incomplete" deja documentees).

## ETAPE Q (Systeme de migrations)

FAIT. `php devups migrate`/`migrate:status` (logique dans
`dclass/devups/Migration/MigrationRunner.php`, cases CLI dans
`dclass/generator/builder.php`) remplacent l'execution manuelle
`mysql -u ... < fichier` - voir README.md section
"Migrations (ETAPE Q)" pour l'usage complet (`--dry-run`, `--step`,
`--force`, detection destructive DROP/TRUNCATE/DELETE avec confirmation
ou refus en production, support `.sql` et `.php` (`up(PDO $pdo)`)).

Suivi dans une nouvelle table `dvups_migration` (nom, lot, date) -
nouvelle entite Doctrine `\Dvups_migration` (`dclass/extends/`, comme
`Dvups_config_item.php`) : creee automatiquement sur une installation
neuve (`orm:schema:create`, deja execute par `php devups install`) ;
`MigrationRunner::ensureMigrationsTableExists()` la cree (SQL brut,
`CREATE TABLE IF NOT EXISTS`) au premier usage sur une base existante,
pour le cas poule-et-oeuf (la table de suivi ne peut pas etre sa propre
premiere migration suivie).

Les 10 migrations listees dans la section "Migrations" ci-dessus sont
la base de depart : `php devups install` les marque jouees (lot 0) sans
les executer juste apres avoir construit le schema complet depuis les
entites actuelles ; une base EXISTANTE (anterieure a cette
fonctionnalite) doit vraiment les jouer via `php devups migrate`.
Interpretation deliberee : plutot qu'une liste figee de 9/10 noms de
fichiers codee en dur (qui deviendrait fausse au prochain fichier
ajoute), `install` marque TOUT ce qui est present dans
`database/migration/` au moment de l'installation - toujours correct,
puisqu'une installation neuve construit de toute facon le schema
*actuel* complet, migrations ou non.

Tests : `tests/Integration/MigrationRunnerTest.php` (12 tests, base de
test dediee, repertoire de migrations temporaire - jamais les vrais
fichiers de `database/migration/`) : ordre, idempotence, dry-run, refus
d'une migration destructive sans confirmation, `--force`, callback de
confirmation interactif, refus en production meme avec confirmation,
execution en production avec `--force`, `--step` (lot distinct par
migration), migration `.php`, `migrate:status`, `markAllAsBaseline()`
idempotent.

`composer test` reste vert (288 tests, 0 echec, les 2 memes
"incomplete" documentees).

## ETAPE R (Version publiable)

1. `CHANGELOG.md` ajoute (format Keep a Changelog), resume de tout le
   chantier (ETAPES D a Q) avec une section "Mise a jour depuis la
   version precedente" (migrations, nouvelles constantes `.env`,
   ruptures de compatibilite).
2. `.env.example` complete (toutes les constantes ajoutees au fil du
   chantier qui manquaient encore : `DB_DUMPER`, `DB_TRANSACTION`,
   `ROUTE_CACHE`, `CACHE_VERSION`, `TOOLRAD_*`, `LOG_*`,
   `ACTIVATION_CODE_TTL_MINUTES`, `RESET_PASSWORD_TOKEN_TTL_MINUTES`,
   `RATE_LIMIT_*`, `TRUSTED_PROXIES`, `LANG_COLLECT`) ;
   `config/constante.example.php` etait deja a jour et commente (verifie
   par comparaison automatisee de tous les `dv_env()` du depot contre
   ce fichier - aucun ecart reel trouve).
3. Verifie de bout en bout, dans un dossier vide separe (jamais dans ce
   depot) : `composer create-project --repository '{"type":"path",
   "url":"<chemin de ce depot>"}' spacekola/devups:dev-master app`
   (le depot local sert de repository path, le paquet n'etant pas
   publie sur Packagist - a publier pour que la commande "nue" du
   README, `composer create-project spacekola/devups mon-projet`,
   fonctionne sans l'option `--repository`) puis `php devups install`
   sur une base MySQL dediee et jetable (`devups_install_check`,
   supprimee apres verification) : creation de la base, schema Doctrine
   complet (36 tables), les 10 migrations existantes marquees jouees
   (lot 0) sans etre executees, compte admin maitre cree
   (`dv_admin`/`admin`). Admin verifie accessible : serveur PHP demarre
   sur le projet installe, `GET /admin/` redirige vers la page de login
   (HTTP 302), `POST .../connexion-action` avec les identifiants
   maitres reussit (HTTP 302 vers `complete-registration`, le flux
   normal de premiere connexion), page suivante HTTP 200 sans erreur.
   Aucune etape manuelle manquante trouvee au-dela de ce que
   README.md "Installation" documente deja, a une exception : le
   paquet `spacekola/devups` doit etre publie (Packagist ou un
   repository prive configure) pour que la commande `create-project`
   sans `--repository` fonctionne telle que documentee - note ajoutee
   au README.
4. CI : `.github/workflows/tests.yml` - `composer test` sur PHP 8.1,
   8.2, 8.3 avec un service MySQL, a chaque push/pull request.
5. Au passage (trouve par la verification du point 3) : `composer
   phpstan` echouait systematiquement par timeout (687 erreurs
   existantes, majoritairement les faux positifs deja documentes dans
   `phpstan.neon.dist`, mais le process composer par defaut (300s) est
   plus court que le temps d'analyse reel) - corrige par
   `"process-timeout": 600` dans `composer.json` (`config`). Sans
   rapport avec le chantier migrations/version mais bloquant pour
   quiconque lance `composer phpstan` localement.

`composer test` reste vert apres les changements ci-dessus (288 tests,
0 echec).

### Version proposee (semver) - tag NON cree, en attente de validation

**v7.0.0** - le paquet `spacekola/devups` est deja publie sur Packagist
jusqu'a v6.1.0 (depot `github.com/yuricoco/devups-package`) : la
version suivante est donc `v7.0.0`, pas un premier tag. Justification
du bump MAJOR (6.x -> 7.0.0) : le chantier ETAPES D-Q introduit
plusieurs ruptures de compatibilite reelles pour du code appelant hors
de ce depot (listees dans `CHANGELOG.md` section "Mise a jour depuis
la v6.x") - route de reinitialisation de mot de passe changee
(`/resetpassword/:id` -> `/resetpassword`), classes supprimees
(`ModuleGallery`/`Dv_image`, `Continent`/`Department`/`District`/
`Region`/`Town`, `Local_content*`/`Page_mapped`,
`Notificationbroadcasted`, `Request_history`), renommages CMS
(`Cmstext`/`Imagecms` -> `Cmscontent`/`Cmsresource`), `langt()`
supprimee - en plus d'un durcissement de securite complet, d'un
systeme de migrations gere et d'une CI fonctionnelle.

## Resume final (ETAPES P a R) et ce qui reste avant publication

**ETAPE P (Corrections)** : les 7 points demandes sont FAITS (detail et
commits individuels dans la section "ETAPE P" ci-dessus) - jeton de
reinitialisation de mot de passe dedie et sans enumeration de comptes,
appels statiques casses corriges (4 trouves au-dela du point 2 de la
consigne, 2 bugs latents decouverts en testant), IP cliente fiable
(`TRUSTED_PROXIES`), code mort de push retire, API de notification
unifiee (`on()`/`braodcast()` depreciees), droit `Documentation`
attribuable a un role, cle de traduction tronquee retiree.

**ETAPE Q (Migrations)** : FAITE - `php devups migrate`/`migrate:status`
remplacent l'execution manuelle, avec detection et confirmation des
migrations destructives, `--dry-run`/`--step`/`--force`, support
`.sql`/`.php`, 12 tests dedies.

**ETAPE R (Version publiable)** : FAITE sauf la creation du tag
(volontairement laissee de cote, voir ci-dessous) - `CHANGELOG.md`,
`.env.example` complet, CI (`.github/workflows/tests.yml`),
installation neuve verifiee de bout en bout (dossier vide separe, base
jetable supprimee apres coup) jusqu'a la connexion admin reussie.

**Etat des tests a la fin de ce chantier** : `composer test` vert a
chaque commit de l'ensemble des ETAPES P/Q/R (276 a la fin de l'ETAPE
P, 288 a la fin des ETAPES Q et R) - **0 echec**, les 2 memes
"incomplete" documentees et assumees depuis l'ETAPE N
(`Dvups_log`/`Emaillog`, ecrans lecture seule par design, voir
`tests/E2E/AdminScreensTest.php::KNOWN_BROKEN`).

**Ce qui reste avant publication** :
- **Le tag/release git lui-meme** - version proposee `v7.0.0`
  ci-dessus (le paquet est deja publie sur Packagist jusqu'a v6.1.0,
  depot `github.com/yuricoco/devups-package`, qui se met a jour via
  ses releases), volontairement NON cree ("j'attends de valider").
- Decider du sort des DROP commentes en fin de
  `database/migration/2026_11_08_phase4_cms_rename.sql`
  (`imagecms`/`dv_image`) - suppression de donnees a valider
  manuellement par environnement avant de les decommenter (voir ETAPE
  I, inchange depuis).
- Les ruptures de compatibilite listees dans `CHANGELOG.md` sont a
  diffuser a toute equipe maintenant du code appelant hors de ce depot
  (aucune trouvee a l'interieur du depot lui-meme, verifie par grep a
  chaque etape concernee).
- Aucun blocage technique connu, aucune tache de ce chantier laissee
  inachevee.

---

## ETAPE S (Audit des secrets codes en dur)

- Recherche (grep) de cles/mots de passe/tokens litteraux dans le code
  versionne (hors `.env`/`config/constante.php`, deja gitignores).
- Trouve et corrige : `Util::getTimezone()` utilisait une cle d'API
  ipgeolocation.io en clair - deplacee vers `IPGEOLOCATION_API_KEY`
  (`.env`), vide par defaut comme les autres integrations tierces
  optionnelles (FCM, Google, VAPID) ; retombe sur `'UTC'` sans appel
  HTTP si la cle n'est pas configuree. Fonction jamais appelee ailleurs
  dans le depot (code mort), aucun test ni appelant a adapter. Commit
  `be09ecc`.
- `composer test` vert (288 tests, 0 echec).
- Fichiers : `dclass/lib/Util.php`, `.env.example`,
  `config/constante.example.php`.

## ETAPE T (Suppression securisee de imagecms/dv_image)

- Les DROP `imagecms`/`dv_image`/`tree_item_image.image_id` restaient
  commentes depuis l'ETAPE I (reprise de donnees non encore validee -
  voir "Ce qui reste avant publication" ci-dessus). Remplaces par une
  migration PHP AUTO-VALIDANTE
  (`database/migration/2026_11_08_phase4_drop_old_images.sql` ->
  `..._phase4_drop_old_images.php`) : ne supprime les anciennes tables
  que si la reprise est verifiee complete par comptage
  (`cmsresource >= imagecms`, aucune `tree_item_image` avec
  `image_id` sans sa colonne `image` deja remplie) ; sinon leve une
  `RuntimeException` avec les comptages en clair, SANS rien supprimer.
  Idempotente (no-op si deja jouee ou si rien a reprendre).
- `MigrationRunner::run()` durci en consequence : capture desormais
  l'exception d'une migration et la reporte dans `result['failed']`
  au lieu de la laisser remonter en fatal (arrete le lot proprement,
  `builder.php` affiche la ligne `[ECHEC]`) - necessaire pour qu'une
  migration qui se juge elle-meme non sure a l'execution stoppe sans
  planter tout le processus CLI.
- Test : `tests/Integration/CmsOldImageTablesDropTest.php` - reprise
  complete -> tables supprimees ; reprise incomplete -> echec, rien
  supprime ; aucune donnee a reprendre -> no-op ; rejouer apres coup
  sans effet.
- `composer test` : 293 tests, 1391 assertions, vert. Commit `90bc9b6`.
- Fichiers : `database/migration/2026_11_08_phase4_drop_old_images.php`
  (nouveau), `database/migration/2026_11_08_phase4_cms_rename.sql`
  (DROP retires, deplaces), `dclass/devups/Migration/MigrationRunner.php`,
  `tests/Integration/CmsOldImageTablesDropTest.php`,
  `tests/Integration/CmsMigrationTest.php` (commentaires ajustes).

## ETAPE U (Audit des secrets dans l'historique git)

- Recherche de secrets (cles API, mots de passe, tokens) dans
  l'historique git complet (`git log -p`, tous les commits), pas
  seulement l'etat courant du depot (deja couvert par l'ETAPE S).
- Resultat : **propre** - aucun secret reel trouve commite a un
  moment quelconque de l'historique (les cles deplacees a l'ETAPE S
  n'avaient jamais ete commitees en clair avec une vraie valeur de
  production, seul un placeholder documentaire l'avait ete).
- Consigne respectee : **aucune reecriture d'historique** (pas de
  `filter-repo`/`filter-branch`/rebase) - sans objet puisqu'aucun
  secret n'y figure, mais note explicitement ici car la consigne
  l'interdisait de toute facon.
- Aucun commit (rien a corriger) - audit uniquement.

## ETAPE V (Migration et test de la base de dev reelle)

Objectif : jouer pour de vrai, sur la base de developpement reelle
(pas la base de test isolee `devups_phpunit`), tout ce que les ETAPES
precedentes n'avaient fait que sur des bases jetables/de test, puis
tester l'application de bout en bout (navigateur + HTTP reel) pour
trouver les bugs qu'aucun test automatise ne pouvait voir (donnees
reelles, volume reel, parcours utilisateur reel). Consigne respectee
tout au long : **aucune suppression de donnee au-dela de ce que les
migrations elles-memes font** - tout ce que j'ai cree moi-meme pour
tester (lignes, fichiers) a ete nettoye apres coup ; les donnees
pre-existantes n'ont jamais ete touchees.

### V.1 - Sauvegarde

- Dump complet de la base de dev reelle AVANT toute migration :
  `C:\spacekola\backups\devupstest_bd_20261108_115419.sql`.

### V.2 - Migration (dry-run puis execution reelle)

- `php devups migrate --dry-run` echouait avant meme d'atteindre
  `MigrationRunner` ("ERROR : projectCore '--dry-run' not found") -
  le dispatcher CLI resolvait toujours `argv[2]` comme un nom de
  projet avant de regarder la commande demandee. Corrige en ignorant
  tout `argv[2]` commencant par `--`. Test
  `tests/Integration/CliMigrateFlagTest.php`. Commit `d0dbda1`.
- Executee pour de vrai : `phase5_location_drop.sql`
  (`department`/`district`/`region`/`town`/`continent`) echouait sur
  le premier `DROP TABLE` (erreur 1451, FK) - ces 4 tables ont des
  cles etrangeres ENTRE ELLES, jamais vues par le grep original (qui
  cherchait des references depuis d'autres modules). Reordonne
  (district, town, department, region, continent). Test
  `tests/Integration/LocationDropMigrationTest.php`. Commit `a0a6f66`.
- `database/migration/2026_11_08_phase4_drop_old_images.php` (ETAPE T)
  rejouee pour de vrai sur la base de dev : reprise verifiee complete,
  `imagecms`/`dv_image`/`tree_item_image.image_id` supprimes sans
  incident.
- Toutes les migrations en attente desormais jouees et marquees dans
  `dvups_migration` sur la base de dev reelle ; `php devups
  migrate:status` confirme : plus aucune migration en attente.

### V.3 - Commandes post-migration (rights:sync, location:seed, lang:missing, diff de schema)

- `php devups location:seed` plantait au premier pays :
  "Class 'country_lang' does not exist" - `DBAL::createDbal()`
  construisait le nom de la classe compagnon via `$this->table`
  (toujours en minuscules) au lieu de `$this->objectName` (casse
  correcte), ce que l'autoload composer (classmap, sensible a la
  casse) n'accepte que si la classe a deja ete chargee par ailleurs -
  jamais le cas en CLI "frais", toujours le cas dans la suite de tests
  (masquant le bug depuis l'origine). Corrige au seul appel concerne.
  Nettoyage : 1 ligne `country` dupliquee (artefact du plantage avant
  correctif, sans ligne `country_lang` associee) retiree ; les 195
  pays du seed verifies ensuite presents sans doublon. Test
  `tests/Integration/LocationSeedTest.php` renforce. Commit `1443b86`.
- `php devups rights:sync` execute sans erreur.
- `php devups lang:missing` : voir ETAPE O, deja a jour, confirme
  toujours 271 cles manquantes dans `lang/en.php` (donnee pre-existante,
  pas un bug, pas retraitee ici).
- Diff de schema reel (`doctrine orm:schema-tool:update --dump-sql`
  contre les entites actuelles) : `ImageCore` (herite par `Cmsresource`
  et `Tree_item_image`) declare une colonne `extensionnpm` jamais
  ajoutee par `2026_11_08_phase4_cms_rename.sql` - absente de
  `cmsresource` (table neuve) ET de `tree_item_image` (l'`ALTER TABLE`
  ajoutait les autres colonnes d'`ImageCore` mais pas celle-ci). Sans
  elle, tout `INSERT`/`UPDATE` Doctrine sur ces entites aurait echoue
  ("Unknown column 'extensionnpm'") - `Tree_item_image::uploadImage()`
  ecrit meme explicitement dans cette propriete. Nouvelle migration
  additive (nullable), jouee sur la base de dev reelle. Test
  `tests/Integration/ImageExtensionColumnMigrationTest.php`. Commit
  `5019007`.
- Apres cette migration, diff de schema refait : plus aucun ecart
  entre les entites et le schema reel.

### V.4 - Test de bout en bout de l'application reelle (navigateur + HTTP)

Balayage systematique de tous les ecrans liste/formulaire back-office,
puis test manuel CRUD (creation/modification/suppression) par entite,
puis des parcours front/API (inscription, activation, connexion/JWT,
confidentialite du profil, reinitialisation de mot de passe,
notifications). **9 bugs reels** trouves et corriges (en plus des 2
ci-dessus, V.3), chacun avec son test de non-regression :

1. **Menu lateral** : la rubrique "Langues" affichait encore
   `Local_content`/`Local_content_key`/`Page_mapped` (retires du code
   en Phase 3) - cliquer dessus plantait (`Router.php`,
   "Undefined array key"). Deux causes : des fichiers `*Core.json`
   orphelins jamais supprimes avec le module, ET
   `php devups rights:sync` qui ne rafraichissait jamais le cache de
   menu materialise par role (`Dvups_role::getConfigs()`/
   `updateConfigs()`, jusqu'ici uniquement sauvegarde depuis l'ecran
   Roles) - toute action ajoutee/retiree restait invisible/visible a
   tort dans le menu de tous les roles existants. `rights:sync` appelle
   desormais `updateConfigs()`+`__update()` sur chaque role. Nettoyage
   complementaire : migration retirant les lignes `dvups_action`
   orphelines (`Local_content`, `Local_content_key`, `Page_mapped`,
   `Request_history`, `Imagecms`, `Dv_image`). Tests
   `tests/Integration/OrphanActionsCleanupMigrationTest.php` et
   `CliRightsSyncRefreshesRoleMenuTest.php`. Commit `d947593`.
2. **`country/list` en 500 des que la table a des lignes** (masque
   jusqu'ici : `AdminScreensTest` teste cet ecran mais `country` reste
   toujours vide dans la suite automatisee) - deux bugs empiles :
   `CountryTable` referencait encore `continent.name` (relation
   retiree a l'ETAPE J, "Call to a member function hydrate() on
   null"), puis, une fois corrige, `CountryTable::init()` construisait
   `new CountryTable()` SANS l'entite (assignee seulement apres coup) -
   `Datatable::__construct()` ne calcule `$this->class` (utilise par
   `can()`) que si l'entite est passee AU CONSTRUCTEUR, provoquant un
   `TypeError`. Test `tests/E2E/CountryListWithDataTest.php`
   (seede `Country::seed()` avant d'appeler l'ecran, pour exercer
   reellement ce chemin). Commit `8d1bccc`.
3. **Cmscontent/Notificationtype : "Array to string conversion" +
   "Array" affiche dans les colonnes traduites** - `Datatable.php`
   (rendu ligne par ligne, chemin "attribut direct") ne resolvait
   jamais les colonnes `dvtranslated_columns` (tableau par langue) en
   l'absence d'un `value` explicite - meme resolution que celle deja
   utilisee ailleurs dans le meme fichier, appliquee ici aussi. Test
   `tests/E2E/CmscontentListWithTranslatedTitleTest.php`. Commit
   `b66d5ca`.
4. **Formulaire Cmsresource : dropdown Cmscontent corrompu** (HTML
   echappe visible a l'ecran, "Array to string conversion" dans
   `FormFactory.php`) - `FormManager::Options_Helper()` (construit les
   `<option>` d'un `<select>`) ne resolvait pas non plus un libelle
   issu d'une colonne traduite. Meme resolution ajoutee. Commit
   `0bb3a6b`.
5. **Upload d'image Cmsresource : warning puis 500 fatal** - deux
   causes : `Cmsresource::uploadImage()` donnait la priorite a
   `$url['file']['imagesize']` (le tableau brut de `getimagesize()`)
   plutot qu'a `$url['file']['size']` (scalaire) pour la colonne
   `size` (float) - "Array to string conversion" au INSERT ; puis,
   masque par ce premier warning, `Datatable::getSingleRowRest()`
   (rendu de la ligne apres creation) attrapait `catch(\Exception)`
   un appel de methode sur `null` (`$this->qbcustom` jamais initialise
   quand le controleur appelle `XTable::init()` SANS argument, pattern
   commun a de nombreux controleurs) - en PHP8 c'est un `\Error`, pas
   une `\Exception`, donc non rattrape : fatal 500 a CHAQUE creation/
   modification reussie passant par ce chemin, potentiellement sur de
   nombreuses entites. Corrige a la source (`catch(\Throwable)`,
   conforme a l'intention du fallback deja ecrit juste en dessous) -
   corrige la classe de bug pour tous les appelants, pas seulement
   Cmsresource. Test `tests/E2E/CmsresourceImageUploadTest.php`
   (upload reel via `CURLFile`/multipart). Commit `0bb3a6b`.
6. **Tree : bouton "Creer" casse** (`GET /admin/api/null/form` ->
   avertissements PHP + dump de debogage au lieu du formulaire) -
   `TreeTable::init()` construisait `new TreeTable()` SANS argument du
   tout (pas meme une assignation a posteriori), meme classe de bug
   que le point 2 ci-dessus mais plus severe (le bouton lui-meme est
   casse, pas seulement les droits par ligne). Confirme par un balayage
   systematique (script, pas manuel) des ~25 ecrans liste du
   back-office : **seul** `Tree` etait touche. Test
   `tests/E2E/TreeListCreateButtonTest.php`. Commit `4c23822`.
7. **Creation d'un pays depuis l'ecran admin : 500** ("Column 'iso3'
   cannot be null") - `CountryForm` ne collecte ni `iso3` ni
   `numcode` (seul `Country::seed()` les renseigne), mais les colonnes
   SQL sont `NOT NULL`. Corrige par une valeur par defaut `''` sur les
   deux proprietes. Test `tests/E2E/CountryCreateViaFormTest.php`.
   Commit `245cc48`.
8. **Mot de passe SMTP reel affiche en clair** sur les ecrans
   `Reportingmodel`/`Emaillog` (panneau d'aide integre,
   `layout.blade.php` echoeait `{{sm_password}}` - la vraie valeur de
   `config/constante.php`, pas un exemple) a tout admin connecte -
   fuite de credentials reelle, trouvee en testant l'ecran reel.
   Masque (`'********'`) ; le reste du panneau (host/port/from) n'est
   pas sensible et reste affiche pour l'aide integree. Test
   `tests/E2E/ReportingmodelListDoesNotLeakSmtpPasswordTest.php`
   (compare au mot de passe reel SANS jamais l'afficher ni le
   journaliser). Commit `c483172`.
9. **`/api/user/authentification` : "Undefined array key 'login'"**
   (avertissement PHP) sur toute tentative de connexion sans `login`
   (ou `password`) dans le corps POST - accede sans repli avant la
   validation `empty($login)` juste en dessous. Corrige
   (`?? null`). Tests
   `tests/E2E/UserAuthentificationMissingFieldsTest.php`. Commit
   `c6db527`.

Couverture additionnelle (pas de bug trouve, comportement verifie
correct de bout en bout, tests ajoutes pour le figer) :

- Connexion -> JWT -> `/api/user/:id` (profil public SANS email/
  telephone pour un tiers non authentifie, profil complet pour le
  titulaire via son JWT, mecanisme `restrictForRequester()`/
  `restrictToPublicProfile()` deja en place) -> `/api/user/me` ->
  `/api/user/notifications`, de bout en bout sur l'endpoint HTTP reel.
- Reinitialisation de mot de passe par jeton (`ETAPE P.1`) : jeton a
  usage unique (une seconde tentative avec le meme jeton echoue),
  ancien mot de passe invalide apres reset, JWT emis en reponse -
  verifie via l'endpoint reel `/api/user/resetpassword` (jeton genere
  directement par `User::setResetPasswordToken()` pour un utilisateur
  SANS email, afin de ne jamais declencher un vrai envoi SMTP -
  `issueResetPasswordToken()` ne tente l'envoi que si `$user->email`
  est renseigne).
- Configuration (admin) : suppression via l'ecran reel verifiee
  effective en base (pas seulement une reponse `{"success":true}` -
  piege initial : le bouton "Supprimer" depend d'un `confirm()`
  JavaScript que l'automatisation du navigateur supprime
  silencieusement, et le mauvais couple methode HTTP/parametres peut
  rendre un succes illusoire sans rien supprimer).
- `role`, `privileges`, `admin`, `configuration`, `tree`/
  `tree_item_image`, `country`, `notificationtype` : CRUD
  (creation/modification/suppression) verifie manuellement sans
  erreur serveur au-dela des bugs listes ci-dessus.

Deliberement NON exerce en direct (risque reseau/effet de bord) :
inscription (`/api/user/registration`) et activation de compte
(`activateaccount()`, qui envoie INCONDITIONNELLEMENT un mail de
bienvenue sur succes, sans le garde `if ($user->email)` utilise par le
reset de mot de passe) - declencheraient un vrai envoi SMTP vers un
hote de messagerie a l'allure de production
(`mail.3ag-edition.com`), de comportement reseau incertain dans cet
environnement sandbox. Cette logique reste couverte par les tests
d'integration pre-existants qui tournent contre la base de test isolee
(`UserActivationTest`, `RegistrationCodeTest`, `PasswordResetTokenTest`,
`RememberMeTest`), inchanges et toujours verts.

### Points notes mais non corriges (severite faible, pour une prochaine session)

- **Swagger / documentation API** : le menu deroulant des serveurs
  affiche le port `3333` alors que le serveur de dev reel tourne sur
  `3031` - cosmetique, n'empeche pas d'utiliser Swagger UI (changer le
  serveur manuellement dans le menu deroulant suffit).
- **Upload d'image 1x1 pixel** : `imagealphablending(): Argument #1
  ($image) must be of type GdImage, bool given` (500) - probable
  edge-case de la bibliotheque de redimensionnement sur une image
  exceptionnellement minuscule, non reproduit avec une image 10x10.
  Peu susceptible d'affecter un usage admin reel (aucun admin
  n'uploade une image 1x1).
- **Creation d'un `Reportingmodel` depuis l'ecran admin** : un essai a
  produit une erreur cote client "[object Object]" sans aucune erreur
  serveur journalisee ni ligne orpheline en base - tres probablement
  un champ requis ("Type de modele de rapport") non rempli de ma part
  plutot qu'un bug de l'application ; non confirme comme un bug reel,
  pas repris faute de temps - a reverifier en remplissant le
  formulaire completement avant de conclure.

### Etat final des tests et de la base

- `composer test` : **313 tests, 1487 assertions, 0 echec**, les 2
  memes "incomplete" documentees depuis l'ETAPE N
  (`Dvups_log`/`Emaillog`, ecrans lecture seule par design) - vert a
  chaque commit de cette etape, comme exige.
- Base de dev reelle : toutes les migrations jouees, schema aligne
  avec les entites (diff de schema vide), menu/role a jour,
  `country` seede (195 pays). Aucune donnee pre-existante supprimee -
  seules mes propres lignes/fichiers de test ont ete nettoyes au fur
  et a mesure.
- Sauvegarde prise avant toute migration :
  `C:\spacekola\backups\devupstest_bd_20261108_115419.sql`.
- Secrets : ETAPES S (code courant) et U (historique git) toutes
  deux traitees - aucun secret reel trouve dans l'historique,
  aucune reecriture d'historique effectuee.

### Ce qui reste (hors perimetre de cette etape, note pour memoire)

- Le port Swagger (3333 vs 3031) et l'edge-case d'upload 1x1 pixel
  ci-dessus - cosmetique/tres faible severite, non corriges.
- Le formulaire `Reportingmodel` a reverifier (voir ci-dessus) - pas
  confirme comme un bug.
- Les 271 cles manquantes de `lang/en.php` (ETAPE O, pre-existant,
  pas un bug).
- Le tag/release git `v7.0.0` (ETAPE R) reste volontairement NON cree.
- La publication GitHub (`yuricoco/devups-package`, branche
  `release/v7`, PR) reste a faire - prochaine etape.

## Devups 7.1.0 - finitions et dettes du coeur (ETAPES A-F)

Chantier v7.1.0 : branche `release/v7.1` (issue de `release/v7` a son
tip publie `14f3696`). Regle de ce chantier : un commit par
sous-etape, `composer test` vert a chaque commit, toute correction de
`dclass/` accompagnee d'un test echec-avant/passe-apres, migrations
dans `database/migration/` jouees via `php devups migrate`
**uniquement sur la base de test**.

### ETAPE A.1 - ai_knowledge/ et tuto_doc/

PDF du projet client precedent retires ; `tuto_doc/` (4 PDF +
`source/`) ajoute au depot, reference dans le README (section
Documentation) avec la commande de regeneration. `build.py` corrige
(`SRC`/`OUT` pointaient vers des dossiers inexistants) pour ecrire
ses PDF directement dans `tuto_doc/`. Limite connue : la
regeneration reelle des PDF necessite WeasyPrint + GTK/Pango,
absents par defaut sur Windows - non installe ici (dependance
native volumineuse), documente dans le README.

Commit `f233943`.

### ETAPE A.2 - Swagger : port en dur

`dv_build_openapi_spec()` (`OpenApiGenerator.php`) annoncait
`http://127.0.0.1:3333/api` en dur. Remplace par `__env` (construit
depuis `APP_URL`/`APP_PORT`), partage par `/documentation/` et
`/admin/apidocs`. Test ajoute dans `DocGenerationTest.php`.

Commit `b85fab0`.

### ETAPE A.3 - upload d'image 1x1 pixel (GD)

`Dfile::resizeimage()` ne verifiait jamais le retour de
`imagecreatetruecolor()`/`imagecreatefromjpeg()`/
`imagecreatefrompng()`/`imagecreatefromgif()` (false sur certaines
images degenerees, ex. 1x1 selon le build GD) - un `imagealphablending(false, ...)`
plantait alors avec une erreur fatale/500. Corrige : chaque echec de
creation GD est desormais journalise via `Log::warning()` puis la
fonction retourne tot, l'image ORIGINALE (deja deplacee par
`Dfile::moveto()` avant tout resize) restant intacte - aucun 500.
Dimensions cible aussi bornees a >= 1px.

Tests : `DfileResizeImageGdFailureTest.php` (reflexion, echec GD
force de maniere deterministe - le cas 1x1 reel ne reproduit pas le
plantage sur cette machine) + un test E2E d'upload reel d'une image
1x1 (verifie l'absence de 500, pas le log).

Commit `9486cc5`.

### ETAPE A.4 - creation d'un Reportingmodel depuis le back-office

Deux bugs reels distincts, trouves en reproduisant la creation
reelle de bout en bout (pas seulement en lisant le code) :

1. `ReportingmodelForm::buildForm()` listait les gabarits depuis un
   dossier qui n'a jamais existe - corrige pour lire `web/views/mails/`
   (meme convention que `ReportingmodelController`). Sans cela, le
   champ radio `type` (colonne NOT NULL) se rendait sans aucune
   option, donc aucune valeur soumise, donc l'INSERT echouait
   toujours en base.
2. Root cause du "[object Object]" historique (note non confirmee en
   ETAPE V) : `Reportingmodel::setObject()` (et, au meme
   copier-coller, `Notification::setEmittedBy()`/`setRedirect()` et
   `Push_subscription::setEndpoint()`) terminaient par `return $this;`
   - `Controller::formWithPost()` traite TOUTE valeur "truthy" rendue
   par un setter comme un message d'erreur de champ, donc l'entite
   entiere se retrouvait serialisee comme "erreur" (`[object Object]`
   cote client une fois stringifiee). Les 4 setters corriges (plus
   aucun ne renvoie `$this`/`true`).

Test de regression repo-wide ajoute (`SetterReturnValueConventionTest.php`,
balaie tous les `src/*/*/Entity/*.php` et `dclass/extends/*.php` pour
tout `set<Champ>()` qui renverrait encore `$this`/`true`) pour
empecher une recidive, la meme ou ailleurs. Test E2E de creation
complete ajoute (`ReportingmodelCreateViaFormTest.php`).

Commit `ee677e8`.

### ETAPE A.5 - contraintes d'unicite (tree.name, tree_item, status)

Aucune des trois cles fonctionnelles deja lues comme uniques par le
code (`Tree::constant()`, `Tree_item::getmainmenu()`,
`Status::getStatus()`) n'etait jamais protegee en base - rien
n'empechait deux lignes en doublon.

- `@UniqueConstraint` ajoutee sur `Tree` (`uq_tree_name` sur `name`),
  `Tree_item` (`uq_tree_item_tree_id_slug` sur `tree_id, slug`) et
  `Status` (`uq_status_entity_key` sur `entity, _key`) - noms
  identiques a ceux crees par la migration (schema:update vide).
- Migration PHP `database/migration/2026_11_09_v71_a5_unique_constraints.php` :
  verifie D'ABORD les doublons des trois contraintes (format PHP,
  `GROUP BY ... HAVING COUNT(*) > 1`, NULL jamais compte comme un
  doublon de `slug`) - si au moins une existe, leve une
  `RuntimeException` listant les doublons et NE POSE AUCUN index
  (les trois a la fois, jamais une pose partielle). Idempotente
  (`INFORMATION_SCHEMA.STATISTICS` avant chaque `ALTER TABLE`).
- Verifie sans doublon sur la base de test reelle (`devupstest_bd`)
  avant d'ecrire la migration (aucun doublon trouve).

Tests :
- `TreeStatusUniqueConstraintsMigrationTest.php` (base dediee,
  schema minimal SANS les index, comme `CmsOldImageTablesDropTest`) :
  refus propre (sans rien modifier, meme si une SEULE des trois
  contraintes a un doublon) pour chacune des trois ; NULL jamais
  traite comme doublon ; application reussie sur base propre ; index
  effectivement actif ensuite (refuse un nouveau doublon) ; idempotence.
- `TreeStatusUniqueConstraintAnnotationsTest.php` (base de test
  principale, schema reconstruit depuis les annotations a chaque
  run) : les index refusent bien un doublon via l'ORM ; deux
  `tree_item` du meme arbre sans `slug` restent possibles (NULL) ;
  `SchemaTool::getUpdateSchemaSql()` (equivalent de `schema:update
  --dump-sql`) rapporte **aucune** difference.

**Blocage (consigne "blocage ... ajoute une entree dans TODO.md")** :
jouer `php devups migrate` directement contre la base de test
persistante (`devupstest_bd`) a ete refuse par le classificateur
sandbox de cette session ("Modify Shared Resources"), sur les deux
outils d'execution shell disponibles. La migration est neanmoins
verifiee de maniere equivalente et complete par les deux suites de
tests ci-dessus (qui l'executent directement sur une base MySQL
reelle, dediee ou principale) - **l'utilisateur doit lancer `php
devups migrate` lui-meme sur `devupstest_bd`** pour que cette
migration y soit marquee jouee (aucun doublon existant, confirme
avant l'ecriture de la migration ci-dessus - l'execution manuelle ne
devrait donc jamais echouer).

`composer test` : 330 tests, 0 echec (320 + 10 nouveaux).

### ETAPE A.6 - JWT_EXPIRE par defaut

`.env.example` : `JWT_EXPIRE=false` (jeton sans expiration) remplace
par `JWT_EXPIRE=+72 hours`, avec un commentaire expliquant la valeur
acceptee (tout ce que `DateTimeImmutable::modify()` comprend) et le
risque de `false` (jeton vole jamais invalidable par expiration -
deconseille hors cas particulier).

`Auth::getJWT()`/`getAnyJWT()`/`execute()` (`dclass/lib/annotation/Auth.php`)
geraient deja correctement les deux cas (code relu, aucun changement
necessaire) : avec `jwt_expire_time` truthy, `iat`/`nbf`/`exp` sont
emis a la creation et recontroles a la lecture (`execute()`, en plus
du controle deja fait par la bibliotheque `firebase/php-jwt` elle-meme
sur `exp`/`nbf`) ; sans, aucun des trois n'est jamais emis ni
controle (jeton permanent, comportement volontaire).

Test ajoute (`JwtExpirationTest.php`) - independant de la valeur
EFFECTIVE de `JWT_EXPIRE` dans l'environnement de test (qui peut
differer de `.env.example`) : construit directement un jeton de la
meme forme que `Auth::getJWT()` emettrait, et verifie via
`Auth::decodeJWTOrFail()` qu'un jeton expire est refuse, qu'un jeton
pas encore valide (`nbf` futur) est refuse, qu'un jeton encore valide
est accepte, et qu'un jeton sans aucune des trois claims (forme
`JWT_EXPIRE=false`) n'expire jamais.

Commit a suivre.

`composer test` : 334 tests, 0 echec (330 + 4 nouveaux).

### ETAPE A.7 - traductions (lang:missing)

`php devups lang:missing` signale toute cle dont la valeur est EGALE
a la cle elle-meme (`$translations[$cle] === $cle`), qu'elle soit
reellement absente du fichier OU presente mais non traduite - le
script ne distingue pas les deux. Verifie une a une (159
occurrences au total, 77 en.php + 82 fr.php) :

- La grande majorite (153) sont de FAUX POSITIFS structurels, pas des
  oublis : la convention du depot est que la CLE elle-meme porte deja
  une langue (ex. "Erreur"/"Pays"/"oui" - cle francaise - ou
  "Action"/"Date"/"Auth_token" - cle anglaise), et le fichier de CETTE
  langue stocke necessairement `cle => cle` (deja verifie : chacune a
  une traduction DIFFERENTE et correcte dans l'autre fichier - ex.
  "Erreur" => "Erreur" en fr.php, "Erreur" => "Error" en en.php). Le
  script ne peut pas distinguer ce cas (traduction correcte,
  simplement identique a la cle pour la langue source) d'un oubli
  reel - rien a corriger ici, et aucune des deux listes ne peut
  descendre a zero sans changer le script lui-meme (hors perimetre).
  Inclut aussi les noms de colonnes/entites techniques
  (`country.iso`, `dvups_action`, `entity.cmscontent`, `constraint`...)
  volontairement identiques dans les deux langues.
- 6 cles etaient REELLEMENT absentes (`array_key_exists() === false`) -
  ajoutees avec la valeur juste :
  - fr.php (5) : "Apercu impossible : gabarit introuvable", "Modele
    collecte automatiquement depuis :file", "Modele introuvable",
    "Trop de tentatives, veuillez réessayer plus tard." (cles
    deja en francais : valeur = cle) et "Si un compte existe avec ces
    informations, un lien de reinitialisation a ete envoye." (idem).
  - en.php (1) : la meme cle "Si un compte existe..." (absente aussi
    de en.php), avec une vraie traduction anglaise ("If an account
    exists with this information, a reset link has been sent.").

Aucune vraie ambiguite trouvee (aucune cle dont la traduction
correcte etait incertaine) - rien a reporter de ce point de vue.

`composer test` : 334 tests, 0 echec (inchange - aucun code execute
n'a change, seulement lang/en.php et lang/fr.php).

## ETAPE B - Securite du QueryBuilder

### ETAPE B.1/B.2/B.3/B.4 - whereIn/whereBetween/operateurs Lazyloading/qb_sanitize

Root cause commune : `qb_sanitize()` (`dclass/dvups_functions.php`)
entourait une chaine de guillemets SIMPLES SANS echapper les
guillemets qu'elle contiendrait deja (`"'$value'"`) - une injection
SQL immediate pour toute valeur utilisateur avec une apostrophe,
MEME passee dans le tableau "sur" d'un `whereIn()`/`in()`/`notIn()`
(la forme reputee "sure" du depot). `between()`/`whereBetween()`
avaient exactement le meme defaut (bornes interpolees entre
guillemets simples, non echappees).

- `QueryBuilder::whereIn()`/`in()`/`notIn()` (`dclass/devups/Tchutte/QueryBuilder.php`) :
  chaque valeur est desormais liee en parametre (`:in_<hint>_<n>`,
  compteur `$inParamSeq` dedie, jamais de collision). La forme chaine
  brute (`$values` string, ex. `"1,2,3"`) est DEPRECIEE - journalise un
  `Log::warning()` au premier appel et n'accepte plus qu'une liste
  d'entiers separes par des virgules (sinon `InvalidArgumentException`,
  jamais d'interpolation meme apres validation). Recherche exhaustive
  dans le depot (`grep`) : AUCUN appelant n'utilise cette forme
  chaine (tous passent deja un tableau) - changement de comportement
  sans impact reel, documente ici et dans le CHANGELOG (ETAPE F).
- `QueryBuilder::whereInReverse()` : `$value` (la donnee utilisateur a
  chercher) est desormais lie en parametre ; `$columns` reste
  interpole - ce sont des noms de colonnes (identifiants SQL), jamais
  des valeurs, fournis par l'appelant (code), jamais par une requete.
- `QueryBuilder::between()`/`whereBetween()` : memes principe, bornes
  liees en parametres.
- Operateurs Lazyloading (`dclass/devups/Datatable/Lazyloading.php::filterswicher()`) :
  - `"rein"` interpolait directement `$value` dans `where_str(" $value IN ( $attr )")` -
    injection SQL immediate. Remplace par `whereInReverse($value, $attr)`
    (parametree).
  - `"notin"` passait `$value` (la chaine ENTIERE, JAMAIS eclatee par
    virgule - contrairement a `"in"` juste au-dessus, bug de coherence
    preexistant) directement a `notIn()`, qui l'interpolait sans
    echappement. Eclate desormais par virgule comme `"in"`, puis liee.
  - `"in"`/`"btw"` passaient deja par `in()`/`between()` - desormais
    parametrees automatiquement via le correctif ci-dessus, aucun
    changement d'appel necessaire.
- `qb_sanitize()` : plus aucun usage interne (les trois methodes
  ci-dessus lient desormais leurs valeurs). Gardee `@deprecated` pour
  compatibilite d'un appelant externe eventuel, mais echappe
  desormais via `PDO::quote()` (toujours correct) au lieu de la
  concatenation de guillemets.
- Recherche exhaustive des appelants de `whereIn()`/`in()`/`notIn()`/
  `whereBetween()`/`between()`/`where_str()` dans tout le depot
  (`src/`, `dclass/`, `admin/`, `web/`, `route/`) : tous passent deja
  un tableau ou une valeur structurelle (jamais une chaine utilisateur
  brute a l'exception du bug `"notin"` ci-dessus, corrige) - aucune
  signature d'appel changee.

Test e2e ajoute : valeurs contenant une apostrophe et une tentative
d'injection SQL classique (`' OR '1'='1`) a travers les operateurs
Lazyloading `in`/`notin`/`btw`/`rein` - aucune erreur SQL, aucun
resultat injecte (voir `LazyloadingInjectionTest.php`).

### ETAPE B.5 - composer audit (2 alertes ignorees)

- **firebase/php-jwt** (`PKSA-y2cr-5h3j-g3ys`, CVE-2025-45769,
  "weak encryption") : CVE DISPUTEE par les mainteneurs eux-memes
  (issue firebase/php-jwt#620) - le NVD la marque "Disputed", le
  grief etant que la longueur de la cle releve de la responsabilite
  de l'APPLICATION, pas de la bibliotheque. Corrigee en 7.0.0 par une
  validation de la longueur de cle a l'encodage/decodage. Tente une
  mise a jour (`composer require firebase/php-jwt:^7.0`) :
  **impossible sans casser `google/apiclient`** (utilise pour FCM,
  `Push_subscription::initPusher()`) - `google/auth` (toutes versions
  1.37 a 1.55, la plage entiere disponible) exige
  `firebase/php-jwt:^6.0`, incompatible avec 7.x. Mise a jour non
  disponible sans retirer google/apiclient (hors perimetre, rupture
  de compatibilite). Risque reel limite : `Auth.php` fixe toujours
  l'algorithme `HS512` explicitement (jamais laisse au jeton de
  choisir son algorithme - pas de confusion d'algorithme possible) et
  `.env.example` documente deja `JWT_SECRET=change-me` comme valeur a
  remplacer par un secret fort en production. Exception maintenue.
- **illuminate/view** (`PKSA-h74q-2n9z-j6wq`, CVE-2021-43808, XSS
  Blade) : le vecteur documente (GHSA-66hf-2p6w-jqfw) est precis -
  directive Blade `@parent` combinee a `@section`/`@show` dans un
  layout parent. `grep -rl "@parent" **/*.blade.php"` : **aucune vue
  du depot n'utilise `@parent`** - le vecteur d'exploitation connu
  est absent du code. Mise a jour non plus disponible : la
  dependance vient de `philo/laravel-blade` (standalone Blade,
  derniere release 2015, abandonne), qui epingle `illuminate/view` a
  `~5` dans son PROPRE composer.json - ne peut jamais atteindre
  6.20.42 (version corrigee) sans remplacer tout le moteur de
  templating (hors perimetre d'un correctif sans rupture). Exception
  maintenue, avec la raison precise (vecteur absent + mise a jour
  impossible) documentee ici (jamais "None specified" comme
  aujourd'hui dans `composer audit`).

Note (trouvee en ecrivant le test e2e, pas corrigee ici - hors
perimetre B, deja prevue a l'ETAPE C/D2) : toute colonne non
qualifiee ("name", "id"...) d'une entite traduite (ex. `Tree`, dont
la jointure `dvups_lang` expose aussi `id`/`name`) declenche
"Column ... is ambiguous" des qu'un filtre Lazyloading l'utilise
nue - le test e2e d'injection utilise donc `Interval` (pas de
colonne traduite) pour rester dans le perimetre de cette etape.

`composer test` : 342 tests, 0 echec (334 + 8 nouveaux : 4 e2e
injection + 4 QueryBuilder apostrophe/forme chaine depreciee).

## ETAPE C - Le defaut D15 (lignes traduites invisibles) + D3 + D2

### ETAPE C.1/C.2 - D15 : ligne sans aucune ligne "_lang" invisible

Root cause : `QueryBuilder::initSelect()`, en mode bilingue (sans
`id_lang` force), construit le format `{iso => valeur}` via
`GROUP_CONCAT` sur un LEFT JOIN vers `<table>_lang`, PUIS joignait
`dvups_lang` (alias `l`) via un INNER JOIN (`join dvups_lang l on
($thislang.lang_id = l.id)`). Une ligne de base sans AUCUNE ligne
`<table>_lang` produit `lang_id = NULL` (consequence du LEFT JOIN
precedent) - et `NULL = l.id` n'est VRAI pour aucune ligne de
`dvups_lang`. L'INNER JOIN eliminait alors la ligne de base ENTIERE
(pas seulement ses colonnes traduites) : `find()`/`findOrNull()`/
`where()->get()`/`first()` rendaient la ligne invisible. `count()`
etait deja immune (son mode `$aggregation=true` saute entierement ce
bloc de jointure).

- `QueryBuilder::initSelect()` : le `join dvups_lang l on (...)`
  devient un `left join dvups_lang l on (...)`. Une ligne sans
  traduction reste visible, son `GROUP_CONCAT` devient simplement
  NULL/vide - jamais une ligne perdue ni une erreur (`ModelTrait::
  hydrateData()` ne pose la propriete traduite que si `isset($flowBD[$append])`,
  qui est deja `false` pour une valeur NULL).
- Verifie que le format bilingue `{fr:..., en:...}` renvoye par l'API
  reste identique pour les lignes qui ONT des traductions (non-
  regression, voir C.3 ci-dessous).

Tests ajoutes (`tests/Integration/TranslatedRowWithoutLangRowTest.php`) :
40 tests (5 methodes - `count()`/`find()`/`findOrNull()`/
`where()->get()`/`first()` - x 8 entites traduites du socle : Tree,
Tree_item, Status, Cmscontent, Notificationtype, Reportingmodel,
Country, Dvups_entity), chacune creee sans aucune ligne `_lang`.
Confirmes en echec avant le correctif (32/40 - les 8 `count()`
passaient deja, immunises par construction) et passants apres (40/40).

### ETAPE C.3 - D3 : `QueryBuilder::get($col, $id_lang)` ignorait la langue demandee

Root cause : `get()` appelait `initSelect()` AVANT `setLang($id_lang)`.
`initSelect()` lit `$this->id_lang` pour choisir entre la jointure
mono-langue (colonne simple) et le mode bilingue (`GROUP_CONCAT`) -
appele apres, un `id_lang` explicitement demande n'avait donc jamais
d'effet sur la requete elle-meme : elle restait TOUJOURS construite en
mode bilingue, meme avec une langue forcee. Plusieurs appelants
(`Status::buildCacheDataFor()`, `Tree_item::getmainmenu()`)
contournaient deja ce defaut en appelant eux-memes
`->setLang($id_lang)->get()` avant - toujours valide, `setLang()` etant
idempotente -, ce qui a limite l'impact reel du bug cote appelants
existants.

- Inverse l'ordre dans `get()` : `setLang()` est maintenant pose avant
  `initSelect()`.

Tests ajoutes (`tests/Integration/TranslatedRowBilingualFormatTest.php`) :
2 tests de non-regression (ligne bilingue dans les deux langues, ligne
traduite dans une seule langue) confirmant que le format `{iso =>
valeur}` reste inchange pour les lignes qui ONT des traductions ;
`testGetWithAnExplicitIdLangReturnsAPlainStringNotTheBilingualArray`,
confirme en echec avant le correctif (rendait la chaine brute non
parsee `'[fr]...[en]...'` au lieu de la chaine simple attendue) et
passant apres.

### ETAPE C.4 - D2 : colonne non qualifiee ambigue sur une entite traduite

Root cause : toute colonne non qualifiee (sans `table.`/`this.`)
referencee dans `where()`/`whereIn()`/`whereInReverse()`/
`whereBetween()` devient ambigue des qu'une jointure de traduction est
presente - `initSelect()` joint toujours `<table>_lang` (qui a sa
propre colonne `id`, entre autres) et, en mode bilingue, joint aussi
`dvups_lang` aliase `l` (colonnes `id`/`name`, entre autres) :
MySQL/MariaDB refuse alors la reference non qualifiee ("Column 'x' in
where clause is ambiguous", erreur 1052). Deja rencontre a l'ETAPE B
(`LazyloadingInjectionTest`, operateurs `rein`/`btw` sur l'entite
`Tree`) - contourne a l'epoque en utilisant l'entite `Interval` (non
traduite), le vrai correctif etant deja planifie pour cette
sous-etape.

- Ajoute `QueryBuilder::qualifyIfAmbiguous()` : prefixe la colonne
  avec le pseudo-alias `this.` (deja utilise ailleurs dans ce fichier,
  resolu en fin de requete par `querysanitize()` vers le vrai nom de
  table) quand l'entite est traduite (`dvtranslate`) et que la
  colonne n'est pas deja qualifiee (pas de `.`, pas d'espace -
  evite de toucher une colonne deja qualifiee ou une forme avec
  suffixe SQL explicite type `"col DESC"`) - inoffensif pour une
  entite non traduite (retournee inchangee).
- Applique dans `where()`, `whereIn()`, `whereInReverse()`,
  `whereBetween()`.
- N'explique PAS la discrepance notee a l'ETAPE B entre `in`/`notin`
  (qui ne declenchaient pas l'erreur sur `Tree` malgre une colonne
  tout aussi non qualifiee) et `rein`/`btw` (qui la declenchaient) -
  mais le correctif ci-dessus couvre desormais les deux cas
  indifferemment, la discrepance devient sans consequence.

Tests ajoutes (`tests/Integration/AmbiguousColumnOnTranslatedEntityTest.php`) :
3 tests (`where()`/`whereInReverse()`/`whereBetween()` sur une colonne
non qualifiee de l'entite `Tree`), confirmes en echec avant le
correctif (erreur 1052) et passants apres.

### ETAPE C.5 - clause d'abandon

Non declenchee : aucun test preexistant ni ecran `AdminScreensTest`
n'a casse a aucune des sous-etapes C.1 a C.4 (`composer test` reste a
0 echec a chaque commit).

`composer test` : 388 tests, 0 echec (342 + 46 nouveaux : 40 D15 + 3
non-regression/D3 + 3 D2 ; 2 incomplete preexistants sans rapport avec
cette etape).

## ETAPE D - Generateur (D6, D7)

### ETAPE D.6 - relation obligatoire generee sans nullable=false

Root cause : `BackendGenerator::entityGenerator()` generait
`@JoinColumn(onDelete="...")` pour chaque relation (branches `oneToOne`
et `manyToOne`/`oneToMany`), sans jamais tenir compte de
`$relation->nullable == 'not'` (relation declaree obligatoire dans le
datacore, ex. toute relation `manyToMany` synthetique, ou toute
relation du datacore configuree comme telle) - le schema genere par
Doctrine (`Setup::createAnnotationMetadataConfiguration`) acceptait
alors NULL sur une colonne de jointure censee etre obligatoire.

- Ajoute `nullable=false` a `@JoinColumn` quand
  `$relation->nullable === 'not'`, dans les deux branches - inchange
  (toujours nullable par defaut) quand la relation est `'default'`.

Test ajoute (`tests/Integration/GeneratorRequiredRelationNullableFalseTest.php`) :
genere une entite avec une relation `'not'` et une relation
`'default'`, confirme en echec avant le correctif (aucun
`nullable=false` genere pour la relation obligatoire) et passant
apres.

### ETAPE D.7 - double bloc require a la regeneration d'une entite

Root cause : `__Generator::dependencies()` maintient un fichier de
dependances par module (`<projet>.<module>.php`, un bloc `require
Entity/Form/Datatable/Controller` par entite). Quand ce fichier existe
deja (pas de `--rewrite`), le bloc de l'entite etait ajoute en mode
`"a+"` (append pur), SANS jamais verifier qu'il n'y etait pas deja -
regenerer la meme entite deux fois de suite (deux `core:g:crud`
successifs, cas normal en developpement : relancer le generateur apres
avoir ajuste le datacore) dupliquait integralement son bloc require,
chaque classe (Entity/Form/Datatable/Controller) redeclaree a
l'inclusion (`Cannot redeclare class`).

- Avant d'ecrire en mode `"a+"`, verifie si le require de l'entite
  (`require 'Entity/<Nom>.php';`) est deja present dans le fichier
  existant - si oui, le fichier reste inchange (regeneration
  idempotente) plutot que d'accumuler un doublon. Mode `"w"` (fichier
  absent ou `--rewrite` explicite) inchange.

Test ajoute (`tests/Integration/GeneratorDependenciesNoDuplicateRequireTest.php`) :
appelle `__Generator::dependencies()` (methode privee, via
`ReflectionMethod`) deux fois de suite pour la meme entite dans un
dossier temporaire isole, confirme en echec avant le correctif (bloc
require present deux fois) et passant apres (une seule fois).

`composer test` : 390 tests, 0 echec (388 + 2 nouveaux).

## ETAPE E - Robustesse du coeur

### ETAPE E.1 - ModelTrait::__get() leve UndefinedPropertyException

Un attribut vraiment inconnu (ni une colonne, ni une colonne traduite
pas encore hydratee, ni la convention `_attribut` du lazyloading de
relation) ne levait qu'un `trigger_error()` (lui-meme un remplacement
anterieur d'un `die()`), SANS JAMAIS interrompre quoi que ce soit -
en production, ce `trigger_error()` n'etait meme pas emis. Ajoute
`UndefinedPropertyException` (`\LogicException`), levee
systematiquement a la place - journalisee et transformee en reponse
500 par `Log::installGlobalHandler()` (deja installe, admin/header.php
et header.php). Test : `tests/Integration/ModelUndefinedPropertyTest.php`.

### ETAPE E.2 - Datatable::getTableEntityBody() colonne inconnue

Une cle de colonne du `datatablemodel` sans getter correspondant sur
l'entite (colonne renommee/supprimee, faute de frappe) levait une
`\TypeError` fatale (`call_user_func()`) qui plantait tout l'ecran.
Verifie desormais `method_exists()` avant l'appel : cellule vide +
`Log::warning()` a la place. Test :
`tests/Integration/DatatableUnknownColumnKeyTest.php`.

### ETAPE E.3 - Router::manageServe() sans garde de session propre

Le garde de session de `manageServe()` (point d'entree partage par
Admin, AdminService, ModuleConfig, ModuleLog, ModuleUser - et tout
futur module genere) n'avait qu'un corps VIDE (commentaire mort).
Protege en pratique par deux couches independantes du Router lui-meme
(admin/header.php par nom de script/chemin, AdminService dans son
propre constructeur) - mais Admin/ModuleConfig/ModuleLog/ModuleUser
n'avaient aucun garde qui LEUR soit propre. Remplace par un vrai refus
401 JSON au niveau du Router, memes exceptions que `manage()`
(`connexion-action`, `$this->public_access`). Test :
`tests/Integration/ManageServeSessionGuardTest.php` (+ script
`tests/Support/scripts/trigger_manageserve_guard.php`, processus PHP
separe pour isoler ce garde d'admin/header.php).

### ETAPE E.4 - Audit mort/vivant

- `StatusTrait` : supprime (aucun usage, reference `Status_entity`,
  classe inexistante).
- `Dvups_config_item` : verifie TOUJOURS utilise (`Dvups_entity` en
  herite) - conserve, comme prevu.
- `Router::runCache()`/`cacheRoute()` et la constante
  `__route_cache`/`ROUTE_CACHE` : code mort par construction
  (`cacheRoute()` a son propre `return null;` precoce inconditionnel) -
  documente en commentaire plutot que supprime (sites d'appel dans
  Request.php jamais exerces par aucun test).
- `FORMTYPE_INJECT_COLLECTIONFORM` : marque `@deprecated` explicitement -
  jamais reference par sa valeur ailleurs ; `FormFactory::__renderForm()`
  a deja un rendu "type non gere" generique qui le couvrirait.

### ETAPE E.5 - PHPStan niveau 1 + CI

Passe de niveau 0 a niveau 1 (deja en place depuis l'ETAPE P.2). Les
836 erreurs sont gelees dans `phpstan-baseline.neon` : aucune n'est
sur une ligne touchee par ce chantier (verifie fichier par fichier) -
rien a corriger dans son perimetre. Niveau 2 tente (~200 erreurs
supplementaires de meme nature) : reporte, pas de baseline silencieux.
`composer phpstan` ajoute a la CI (`.github/workflows/tests.yml`, PHP
8.2 uniquement, apres `composer test`).

`composer test` : 399 tests, 0 echec (390 + 9 nouveaux : 4 E.1 + 2 E.2
+ 3 E.3 ; E.4/E.5 sans nouveau test, documentation/outillage).

## ETAPE F - Documentation et sortie 7.1.0

- CHANGELOG.md : section `[7.1.0]` (securite, comportements modifies,
  corrections, migrations v7.1, depreciations, mise a jour depuis la
  7.0).
- tuto_doc/source/ mis a jour (Tome I SS5.6/5.8/6.7, Tome II
  SS4.2/7.4/7.6 + chapitre 8, Tome III SS4.2/9.1, Tome IV tableaux
  10.1/10.2) ; `build.py` : `VERSION = "Devups 7.1.0"` ; les 4 PDF
  regeneres dans `tuto_doc/`.
- Correction d'une erreur de numerotation preexistante (anterieure a
  ce chantier) decouverte en recoupant le tableau 10.2 du Tome IV avec
  les references croisees : le defaut "ligne traduite invisible"
  (`INNER JOIN dvups_lang`, corrige en 7.1) est **D1** dans le tableau
  des dettes, pas D15 - D15 designe en realite le defaut, distinct,
  du redimensionnement GD d'une image 1x1 (egalement corrige en 7.1).
  Les deux etaient corriges, mais le premier etait cite "D15" a
  plusieurs endroits de la documentation (Tome II SS7.4, Tome IV lignes
  195/564) par reprise d'une confusion anterieure. Renomme en D1 dans
  tous ces endroits, CHANGELOG.md inclus, pour rester coherent avec le
  tableau 10.2 qui reste la source de verite.
- A cette occasion, le tableau 10.2 lui-meme corrige : la partie
  `Dvups_config_item` de l'ancienne entree D13 ("declaree traduite,
  sans table ni appelant") etait fausse - l'audit E.4 avait deja
  constate qu'elle est TOUJOURS utilisee (heritee par `Dvups_entity`) ;
  seule la partie `StatusTrait` de D13 (code mort reel, supprime en
  7.1) est deplacee en 10.1.
- Blocage rencontre et leve (hors perimetre strictement applicatif,
  mais necessaire pour honorer le point 3 de la consigne) :
  WeasyPrint sur cette machine Windows ne pouvait pas charger
  Pango/GObject (DLL manquantes) - installation du runtime GTK3 via
  `winget install --id tschoonj.GTKForWindows` (confirmee par
  l'utilisateur avant action, changement systeme persistant hors de ce
  depot), puis fermeture de Foxit PDF Reader qui verrouillait un des
  PDF en cours de regeneration (confirmee par l'utilisateur).
- README.md : section "Version actuelle" ajoutee en tete (pointe vers
  CHANGELOG.md), notes "Depuis 7.1" ajoutees aux sections "Droits" et
  "Securite".
- `composer test` : 399 tests, 0 echec (inchange, documentation
  uniquement). `composer phpstan` : aucune erreur (niveau 1).

## ETAPE F.2/F.3 - CI GitHub rouge depuis la 7.0.0, corrigee

La CI GitHub (workflow `tests`, MySQL 8 reel) n'avait jamais ete verte,
y compris pour la 7.0.0 - `composer test` passait en local uniquement
parce que l'environnement local (MariaDB, cache/ deja peuple par un
usage anterieur) etait plus tolerant que la CI sur deux points
independants.

### Reproduction locale prealable

- `tests/bootstrap.php` force desormais `SET SESSION sql_mode =
  'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,
  ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'` (le defaut reel de
  MySQL 8) sur la connexion PDO partagee de toute la suite - MariaDB
  (utilise en local) ne l'impose pas par defaut, ce qui masquait les
  deux causes ci-dessous. Permanent pour tout le run, pas de reset entre
  tests : c'est le comportement reel d'un serveur MySQL 8.
- Suite relancee depuis un clone neuf (`git clone` de la branche dans un
  dossier temporaire, `composer install`, sans `cache/` prealable -
  `cache/` est entierement ignore par git) : memes erreurs que la CI
  reproduites (52 erreurs), confirmant les deux causes.

### F.2 - sql_mode strict : colonnes omises de l'INSERT

`DBAL::createDbal()` (chemin `->__insert()`) et `DBAL::_createDbal()`
(chemin statique `Model::create()`) omettaient entierement une colonne
du tableau transmis a l'INSERT quand elle n'etait pas fournie par
l'appelant - jamais un NULL explicite, la colonne manquait de la
requete SQL elle-meme. Sous sql_mode tolerant (MariaDB), le serveur
complete silencieusement par la valeur par defaut du type ; sous
sql_mode strict (MySQL 8), toute colonne NOT NULL sans defaut SQL et
absente de l'INSERT leve l'erreur 1364.

- Lignes `_lang` d'une entite traduite (`createDbal()` et la branche
  traduite de `_createDbal()`) : une colonne traduite jamais fournie
  par l'appelant (`Tree::create(['name' => ...])` sans `label`) est
  desormais systematiquement presente dans l'INSERT - valeur fournie
  pour cette langue, sinon celle de la langue par defaut
  (`Dvups_lang::defaultLang()`, colonne `main`), sinon une chaine vide.
  Le repli precedent (placeholder `"<id>_<iso>"`, du code mort/debug
  jamais verifie par aucun test) est retire.
- Ligne principale cree par `Model::create()` (`_createDbal()`) :
  completee par les defauts PHP non nuls de l'entite UNIQUEMENT pour
  les colonnes NOT NULL (`nullable === false` dans le mapping Doctrine)
  absentes de `$keyvalue` - jamais pour une colonne nullable (une
  colonne nullable omise reste NULL, comme avant ; verifie par
  `NotificationDeprecatedApiTest`, qui s'appuie sur `User::$lang`
  nullable restant NULL quand non fourni).
- Test ajoute : `tests/Integration/TranslatedRowStrictSqlModeTest.php`.
- Deux tests corriges (donnees de test incompletes, pas un defaut du
  framework - aucun appelant reel n'omet ces colonnes, un formulaire
  soumet toujours une valeur meme vide) :
  `TreeStatusUniqueConstraintAnnotationsTest` (`Status::create()` sans
  `color`), `TranslatedRowWithoutLangRowTest` (`Dvups_entity::create()`
  sans `name`, herite de `Dvups_config_item`).
- Un troisieme cas trouve en cherchant systematiquement toute erreur de
  mode strict dans la suite n'etait pas une colonne omise mais une
  chaine trop longue : `LangCacheTest` reutilisait un iso a 5 caracteres
  comme valeur de `locale` (colonne a 2 caracteres) - tronque
  silencieusement par MariaDB, erreur 1406 sous MySQL 8. Corrige dans le
  test (`setLocale('en')`, valeur fixe non testee par les assertions).

### F.3 - cache/views absent d'un clone neuf

`cache/` est entierement ignore par git (`.gitignore`, `/cache/`) - un
clone neuf n'a donc aucun de ses sous-dossiers. `cache/rights`,
`cache/lang`, `cache/trees`, `cache/log` et `cache/ratelimit` se
recreaient deja seuls a l'usage (verifie fichier par fichier) ;
`cache/views` (compilation Blade) ne le faisait pas - `BladeD`
(Genesis.php, point d'entree UNIQUE de toute instanciation de Blade
dans ce framework) le cree desormais elle-meme si absent, avant
d'initialiser Blade. Les deux instanciations directes de la classe
vendor `Philo\Blade\Blade` dans `Controller.php` (qui contournaient
`BladeD`) sont remplacees par `BladeD`, par coherence et pour beneficier
du meme correctif.

Test ajoute : `tests/Integration/BladeViewCacheAutoCreateTest.php`
(supprime `cache/views`, rend une vue, verifie l'absence d'erreur et
que le dossier est recree).

### Workflow CI

`.github/workflows/tests.yml` verifie : aucune etape manuelle
(`mkdir cache`, `php devups install`) n'y figure et aucune n'est
necessaire desormais - le code se suffit a lui-meme face a un clone
neuf.

`composer test` : 401 tests, 0 echec, sous sql_mode strict, depuis un
clone neuf. `composer phpstan` : aucune erreur.

## ETAPE F.4 - CI GitHub encore rouge apres F.2/F.3

Le run CI declenche par le commit F.2/F.3 restait en echec (8.1 : etape
"Install dependencies" ; 8.2/8.3 : "Run test suite") - trois causes
distinctes, trouvees en lisant le journal reel de la CI (necessite une
connexion GitHub, l'API publique refusant les logs sans droits admin).

### PHP 8.1 en fin de vie, jamais reellement installable depuis la 7.0.0

`composer.json` annoncait `"php": ">=8.1"` mais `minishlink/web-push`
v10 (deja present en 7.0.0) exige `php ">=8.2"` - un `composer install`
sous PHP 8.1 echouait donc systematiquement a l'etape "Install
dependencies", jamais remarque car cette etape n'avait jamais ete
atteinte avant F.2/F.3 (toute la CI echouait plus tot). PHP 8.1 n'a de
toute facon plus de correctifs de securite depuis le 31/12/2025.
Corrige : `"php": ">=8.2"`, matrice CI `8.2`/`8.3`/`8.4`, README/
tuto_doc (Tome I SS1.4) et CHANGELOG mis a jour.

### '' sur une colonne numerique/booleenne/temporelle

`CountryCreateViaFormTest` (deja existant) : `phonecode` (colonne
`integer`) soumis vide par `CountryForm` arrivait tel quel ('') jusqu'a
l'INSERT - erreur 1366 sous sql_mode strict. `DBAL::castEmptyFormStrings()`
(nouvelle methode partagee par `mappingProd()` et `_createDbal()`)
resout desormais '' en amont, pour les colonnes Doctrine
`integer`/`smallint`/`bigint`/`float`/`decimal`/`boolean`/`date`/
`datetime`/`time` : NULL si nullable, sinon le defaut PHP de l'entite,
sinon (numerique/booleen) 0/false, sinon (temporel, sans valeur sure)
une `\InvalidArgumentException` lisible plutot qu'une erreur SQL. Test ajoute :
`StrictModeFrameworkInsertUpdateTest::testCreateCoercesAnEmptyStringToZeroForANumericColumn`
(chemin statique `Model::create()`/`_createDbal()` - le chemin instance
est deja couvert par `CountryCreateViaFormTest`).

### Perte silencieuse de donnees sur une mise a jour partielle

`RoleAdminGuardTest::testNonMasterRoleWithUpdateCannotChangeAnAdminsRole` :
erreur 1048 "Column 'login' cannot be null" sur un UPDATE qui ne
touchait que le role d'un admin. Racine : `CrudTrait::updateAction()` et
`Dvups_adminController::updateAction()` construisent l'entite a mettre
a jour par id seul (`new Entite($id)`), jamais hydratee avant
d'appliquer le formulaire soumis (`Controller::form_fillingentity()`,
appel a `hydrate()` commente de longue date). `ModelTrait::
entityKeyUpdate()` traitait alors CHAQUE colonne mappee - y compris
celles jamais touchees par le formulaire, a leur valeur PHP par defaut
(souvent NULL) - comme "modifiee", et l'ecrivait en base : changer
uniquement le role d'un admin effacait silencieusement son login, son
mot de passe et son email. Invisible sous sql_mode tolerant (MariaDB
non strict n'a jamais ete utilise par la suite e2e en local, qui passe
par un vrai sous-processus `php -S` non concerne par le `SET SESSION`
de `tests/bootstrap.php` - seule la vraie CI, MySQL 8, l'a revele) ;
erreur 1048 sous sql_mode strict.

Corrige a la racine : `Controller::form_fillingentity()` reactive
`hydrate()` avant application du formulaire (`formWithPost()` ne cible
que les cles soumises - jamais une remise a zero de l'entite) :
`entityKeyUpdate()` ne voit alors plus comme "modifiees" que les
colonnes reellement soumises. `entityKeyUpdate()` ignore en complement,
par securite, toute colonne encore a NULL quand aucun etat hydrate
n'est disponible (defense en profondeur pour un chemin d'update qui ne
passerait pas par `form_fillingentity()`).

Meme schema cherche ailleurs dans le framework (`CrudTrait::
updateAction()`, `Dvups_roleController::updateAction()`,
`UserFrontController`) : tous passent par `form_generat()`/
`form_fillingentity()`, donc par le meme correctif - aucun autre
chemin d'update trouve.

Tests ajoutes : `StrictModeFrameworkInsertUpdateTest::
testUpdateOfUnhydratedEntityDoesNotNullOtherNotNullColumns` (unitaire,
`Dvups_admin`) et `RoleAdminGuardTest::
testUpdatingOnlyTheRoleLeavesOtherFieldsIntact` (e2e, demande
explicitement : changer uniquement le role laisse login/password/email/
name intacts).

### phpstan: constantes dynamiques introuvables dans un clone neuf

`composer phpstan`, jamais execute jusqu'au bout en CI avant ce
correctif (la suite de tests echouait plus tot), echouait des le
premier run reellement atteint : 118 erreurs "Constant dbname/dbuser/
__prod/... not found". Cause : `config/constante.php` (ignore par git)
declare ces constantes avec `const X = Y;` (litteral, vu par phpstan
par simple lecture statique) alors que `config/constante.example.php`
(le modele verse, absent de `config/constante.php` en CI/clone neuf)
les declare via `define('X', dv_env(...))` - valeur dynamique, non
decouverte par phpstan sans execution reelle du fichier. Corrige :
`phpstan.neon.dist` charge `config/constante.example.php` en
`bootstrapFiles` (l'execute reellement avant l'analyse, independamment
de la presence d'un `config/constante.php` local). `composer install`/
`update` cree en complement `config/constante.php` depuis l'exemple
s'il est absent (confort pour un nouveau contributeur, non requis pour
la CI).

### Verification base de dev locale (lecture seule, demandee)

`SELECT id, login FROM dvups_admin WHERE login = '' OR login IS NULL`
sur la base configuree localement (`devupstest_bd`, base de test - voir
`config/constante.php`) : 0 ligne. Aucune donnee corrompue trouvee par
le bug ci-dessus sur cette base.

## Fin du chantier v7.1.0 - resume

**Etat final** : `composer test` vert (405 tests, 0 echec, 2
incomplets preexistants et hors perimetre - ecrans en lecture reel par
design), verifie depuis un clone neuf ET sous sql_mode strict
(MySQL 8, le defaut reel de la CI) - plus seulement en local sous
MariaDB ; `composer phpstan` vert (niveau 1, 0 erreur hors
`phpstan-baseline.neon`, bootstrap des constantes dynamiques inclus,
verifie lui aussi depuis un clone neuf). Une seule migration v7.1 a
jouer sur une base existante
(`2026_11_09_v71_a5_unique_constraints.php`, non destructive) via
`php devups migrate`. CI GitHub (workflow `tests`, matrice PHP 8.2/
8.3/8.4) verte - voir le numero de run dans le dernier commit de ce
chantier.

**Recapitulatif par etape** :
- A : contraintes d'unicite manquantes + divers correctifs mineurs
  herites de l'audit initial (Swagger, GD, Reportingmodel).
- B/C : securite QueryBuilder (`whereIn`/`whereBetween` parametres,
  operateurs Lazyloading, depreciation `qb_sanitize()`).
- D : generateur (D6 `nullable=false` sur une relation obligatoire,
  D7 idempotence du bloc `require` a la regeneration).
- E : robustesse du coeur (`UndefinedPropertyException`, cellule vide
  Datatable au lieu d'un ecran casse, garde de session reelle de
  `manageServe()`, audit mort/vivant, PHPStan niveau 1 + CI).
- F : documentation (CHANGELOG, 4 tomes, PDF, README) et sortie ; puis
  CI GitHub reparee (F.2 sql_mode strict, F.3 cache/views absent d'un
  clone neuf, F.4 PHP 8.1 EOL + coercion '' + perte de donnees UPDATE +
  phpstan/constantes dynamiques) - rouge depuis la 7.0.0, jamais verte
  avant ce chantier.

**Reporte a la v8.0** (dettes ouvertes restantes, tableau 10.2 du Tome
IV) :
- D8 : `schema:update --force` peut repondre "success" sans appliquer
  un renommage d'index (Doctrine) - passer par une migration reste la
  parade.
- D9 : `Router::cacheRoute()`/`runCache()`, code mort par construction
  (confirme par l'audit E.4) - documente en commentaire plutot que
  supprime ; une suppression reelle toucherait des sites d'appel
  jamais exerces par aucun test, a traiter avec son propre chantier de
  non-regression.
- D11 : `FORMTYPE_INJECT_COLLECTIONFORM`, marque `@deprecated` en 7.1
  mais pas retire (aucune reference a sa valeur ailleurs dans ce
  depot ; un projet client pourrait neanmoins s'y referer).
- D14 : `whereRaw()`/`where_str()` corps identiques - laisses tels
  quels, aucun des deux n'est jamais appele avec une donnee
  utilisateur dans ce depot.
- PHPStan niveau 2 (~200 erreurs supplementaires de meme nature que le
  niveau 1) : tente en E.5, reporte - pas de baseline silencieux sans
  l'avoir d'abord trie.
- Toute dette ou piste notee "a ne pas faire sans accord" ailleurs
  dans ce fichier (chaque entree ci-dessus porte sa propre
  justification).

**Ne pas oublier au prochain chantier** : relire ce fichier depuis le
debut avant toute nouvelle ETAPE - les sections anterieures (phases
1-7, ETAPES A-K) documentent des chantiers distincts, plus anciens,
qui ne font pas partie de la v7.1.0 decrite ici.

