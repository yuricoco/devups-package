# TODO

Suivi des points ouverts, decisions prises, et blocages rencontres
pendant le chantier (droits / logs / langues / cms). Chaque entree :
phase, ce qui reste, pourquoi, fichiers concernes.

Ce fichier remplace entierement la version precedente : les points
Phase 2 (B.2/B.5/B.6/B.7/B.8) et Phase 3 (C.1/C.4/C.5/C.6/C.7) qu'elle
documentait comme ouverts ont ete traites dans cette session (voir
"Resume phase par phase" plus bas). Seul le point Phase 4 (CMS) reste
ouvert, voir ci-dessous.

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

