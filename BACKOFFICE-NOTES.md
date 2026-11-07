# Back-office — base de référence visuelle et audit du datacore

Journal des décisions prises en continu. **Lecture seule sur tout le
dépôt** — ce fichier et le rapport dans `../aiMemory/` sont les deux seuls
écrits. Aucune commande `devups` qui écrit n'a été lancée
(`dvups_:update`/`dvups_:implement`/`core:g:*`/`database:*`/`install`
: ni exécutées ni approchées).

---

## 1 — Méthodologie du tableau des 29 modules × 2 univers

Pour chaque module, l'« entité sondée » est la **première** entité trouvée
sur le disque (`Core/<Entité>Core.json`, triée par nom de fichier) — pas
une entité choisie à la main. Vérifié a posteriori sur 5 modules
(Contest, Config, Catalog, Lang, Kola) que ce choix coïncide avec le
premier lien réel du menu latéral : coïncidence confirmée, pas supposée
pour l'ensemble des 29.

**Mesuré deux fois, pas une** : d'abord le code HTTP seul (tous à `200`,
y compris des pages qui plantent — confirmé que PHP en mode `__prod=false`
ne renvoie pas de code 5xx sur une fatale non interceptée), puis le corps
de la réponse, grepé pour `Fatal error`/`Parse error`/`Uncaught` et pour
le motif de méthode absente (`You may create method`). **Le code HTTP
seul aurait menti** — c'est la mesure la plus importante de ce chantier
méthodologiquement : ne jamais conclure d'un `200` sans lire le corps.

Session admin réelle obtenue par le vrai formulaire
(`admin/devups/ModuleAdmin/dvups-admin/connexion-action`), jamais la
production.

## 2 — Étape 7 sacrifiée, comme prévu par l'ordre de priorité du prompt

Le temps est allé aux étapes 1 à 4 (le cœur annoncé) et à une étape 5/6
mesurées mais moins creusées. L'étape 7 (poids réseau, requêtes SQL
comptées) n'a pas été commencée — c'était la première à sacrifier,
explicitement.

## 3 — `function style()`/`function script()` : raisonné, pas forcé

Le test empirique (forcer deux layouts de module dans la même requête)
n'a pas été tenté, faute de temps — ce n'était pas le cœur du chantier.
Ce qui est confirmé par lecture et par les mesures de l'étape 5 : rien
dans le mécanisme de rendu n'empêche structurellement un double-`include`
de layout dans une même requête — la garantie « un seul module par
requête » est une **convention d'usage**, pas une protection du langage
ni du framework. À tester pour de vrai avant de l'unifier (phase 2).

## 4 — Aucune donnée personnelle dans les captures

Les seules données utilisateur visibles dans les écrans capturés sont des
comptes de test générés automatiquement par les chantiers précédents de
cet engagement (ex. `sched_own_1791888132`, motif `sched_*`/des entiers
bruts) — jamais un nom, un courriel ou un numéro de téléphone réels.
Rien n'a donc été masqué : il n'y avait rien à masquer. Si une capture
future (hors de ce chantier) montre de vraies données, la règle reste de
les flouter avant de livrer.

## 5 — Largeur mobile : 390 px littéral

Utilisé tel quel (iPhone 12/13 standard), sans chercher un autre point de
rupture — c'est la valeur donnée par le prompt.

---

# Chantier [dvups_module-retrait] — arrêté à la condition d'arrêt de l'étape 1.b

Session interrompue par une limite d'usage, avant toute écriture de code
(confirmé : `git status --short` ne montre que ce fichier, rien dans
`src/`, `admin/views/`, `dclass/devups/Core.php` n'a été touché).

## Étape 1.b — statut de `Dvups_entity` : PAS le même paradigme, par mesure

Table `dvups_entity` en base : **69 lignes réelles**, non vide.

Grep sur tout `backend/` (hors `vendor/`) : 19 fichiers référencent
`Dvups_entity`, contre ~15 occurrences attendues pour `Dvups_module`/
`Dvups_component` au total — déjà un volume différent.

**Usages réellement vivants trouvés** (code non commenté, qui s'exécute) :
- `route/Admin.php:70` et `route/AdminService.php:165` :
  `(new Dvups_entityController())->exportCsv();` — confirme l'export CSV
  cité par l'addendum ;
- `route/WebService.php:59` : `'dvups_entity' => \Dvups_entity::class,`
  dans le tableau **`public_access`** du chemin générique JWT — **`Dvups_entity`
  est aujourd'hui un type d'entité exposé publiquement par l'API
  générique** (`/api/{action}/dvups_entity`), au même titre que `follow`,
  `post`, `contest`. Ce n'est pas un vestige : c'est une entité active du
  système de permissions API.

**Usages commentés/morts trouvés** (donc PAS un argument contre) :
`dclass/devups/Http/Router.php` (3 occurrences, toutes en commentaire),
`dclass/devups/model/Model.php` (docblock + ligne commentée),
`dclass/devups/Controller/Controller.php:156,170,171` — **vérifié en
détail** : les trois sont à l'intérieur d'un même bloc `/* ... */` mort
(une branche d'erreur jamais atteinte), pas du code qui s'exécute.

**Conclusion, mesurée pas supposée** : `Dvups_entity` ne partage pas le
sort de `Dvups_module`/`Dvups_component`. Ces derniers n'avaient **aucun**
chemin vivant non commenté nulle part (confirmé dans la session
précédente : l'univers B entier plante avant même d'utiliser la classe).
`Dvups_entity`, lui, a au moins deux chemins vivants et une vraie table de
69 lignes. **C'est la table de référence des entités, pas un paradigme
retiré** — elle n'a simplement jamais eu de `Dvups_entityCore.json`
(comme `ModuleSticker`), pour une raison différente et non élucidée plus
avant (probablement : outillage interne non prévu pour passer par le
CRUD généré).

**Conséquence sur le périmètre** : `Dvups_entity`, `Dvups_entityController`,
`entitywidget.blade.php` (étape 3.c du prompt, qui consomme
`$entity->label[local()]`/`$entity->route()`) ne doivent **pas** être
supprimés avec le reste du paradigme `Dvups_module`. Le prompt anticipait
déjà cette possibilité ("si elle reste la table de référence... continue
sans y toucher") — donc le chantier n'est pas élargi, mais l'étape 3.c
doit être traitée différemment de ce qu'un retrait pur aurait fait :
`entitywidget.blade.php` ne peut pas être supprimé sans vérifier d'abord
si `Dvups_entity`/ses vues restent nécessaires ailleurs — à faire au
prochain passage.

## Ce qui reste à faire (rien commencé au-delà de 1.b)

Étapes 1.a (inventaire exhaustif complet et compté), 2 (vérification
`ModuleUser` + retrait univers B), 3, 4 : **non commencées**. Aucun
fichier supprimé, aucun `*Core.json` créé, `dvups_:update` jamais lancée.

---

# Chantier [dvups_module-retrait], reprise — étape 1 complétée

## Correction confirmée (demandée en tête de prompt)

`Dvups_entityController` : confirmé **inexistant** nulle part dans le
dépôt (`find`/`grep` sur toute la classe). `ModuleConfig/Controller/` ne
contient que `ConfigurationController.php` et `Dvups_logController.php`.
Les deux appels (`route/Admin.php:70`, `route/AdminService.php:165`)
sont du code mort qui ferait une fatale `Class not found` s'il était
atteint. **Signalé, non corrigé** — ménage de l'univers A, hors périmètre.

## Étape 1.a — l'inventaire exhaustif

**Tables en base** (comptées, pas touchées) :
- `dvups_module` : **24 lignes**
- `dvups_component` : **6 lignes**

Les deux tables existent et portent de vraies données. Elles ne sont pas
supprimées ni modifiées par ce chantier (règle dure n°7) — seul le code
qui les lisait/écrivait est concerné, et ce chantier ne touche que des
fichiers, jamais la base.

**Fichiers de code/vue/JSON/JS** (grep sur tout `backend/` hors
`vendor/`) : **41 fichiers, 58 occurrences** (compte par ligne
correspondante — `grep -c`).

**Hors inventaire, signalé mais non concerné** (pas du code) :
- 7 fichiers SQL (`database/backup/*.sql`, `database/migration/*.sql`
  ×4, `dclass/generator/config_data.sql`, `src/devups/dvupsadmin_bd.sql`)
  — des dumps/migrations historiques de la base, pas du code à nettoyer ;
- 2 `error_log` (`ModuleNotification/error_log`, `ModuleReporting/error_log`)
  — des artefacts d'exécution (logs PHP), pas du code.

**Classification en quatre colonnes** (sur les 41 fichiers de code) :

1. **Vivantes** : les **22** `index.php` de module qui appellent
   `Dvups_module::init('Module<X>')` (liste à l'étape 2) — à supprimer
   avec le fichier entier. Le seul autre appel vivant trouvé,
   `Dvups_role::updateConfigs()`/`setModules()`/`setComponents()` dans
   `Dvups_role.php`, est en réalité un **nom de paramètre** (colonne 3,
   pas colonne 1 — précision ci-dessous).
2. **En commentaire** : `Datatable.php` (×2), `FormManager.php` (×2),
   `Router.php` (×2, dont la ligne `$moduledata->dvups_entity = ''`
   signalée par l'addendum comme amorcée mais dormante),
   `AdminTemplateGenerator.php` (×1), `Notification.php` (×1),
   `Controller.php` (×2 sur 2 — à vérifier une par une, voir étape 2),
   `ModuleController.php`, `ModuleNotification.php`/`ModuleLang.php`
   (occurrences doublées : le nom de la classe module **et** une
   référence au paradigme).
3. **Noms de paramètre/variable** (pas la classe — à renommer par
   hygiène seulement, non fait ici car « sans urgence ») :
   `Dvups_role::setModules($dvups_module)`/`setComponents($dvups_component)`
   dans `Dvups_role.php` et dans les deux vues
   `formWidget.blade.php`/`formWidgetGroup.blade.php` (qui itèrent sur
   des variables `$dvups_module`/`$dvups_component` — à vérifier si ce
   sont vraiment des nnoms de variable ou un vrai usage de classe, voir
   le détail lors du passage sur ces fichiers), et les 4 fichiers JS
   (`dvups_moduleCtrl.js`, `dvups_moduleForm.js`,
   `dvups_componentCtrl.js`, `dvups_componentForm.js`) qui sont
   l'outillage admin **pour gérer les tables** `dvups_module`/
   `dvups_component` elles-mêmes (CRUD generique sur ces deux tables,
   cohérent avec le fait qu'elles existent encore en base avec des
   données) — à laisser, ce sont des vues legitimes sur des tables
   encore réelles, pas un vestige de paradigme de routage.
4. **Colonnes/tables en base** : `dvups_module` (24 lignes),
   `dvups_component` (6 lignes) — signalées ci-dessus, non touchées.

## Précision importante, trouvée en vérifiant le détail des 22 `index.php`

**6 modules ont un `index.php` qui NE référence PAS `Dvups_module`** du
tout, bien qu'ils fassent partie des modules mesurés avec `index.php`
le 25/10 :
- `ModuleConfig`, `ModuleMessage`, `ModuleComment`, `ModuleMessenger` :
  leur `index.php` appelle directement `(new Module<X>('layout'))->manage()`
  / `->manageServe()` — **le même mécanisme que `admin/index.php`** (univers
  A), avec un `die;` présent. Ce ne sont pas des vestiges de
  `Dvups_module` : ce sont des portes d'entrée redondantes mais
  fonctionnellement équivalentes à l'univers A, et **hors du paradigme
  que ce chantier retire** ;
- `ModuleKola`, `ModuleRelation` : leur `index.php` est une **troisième
  génération**, antérieure à `Dvups_module` — un `switch(Request::get('path'))`
  manuel qui appelle des contrôleurs un par un (`KolaController`,
  `FollowController`…), sans jamais référencer `Dvups_module`.

**Décision, la plus conservatrice** : ces 6 fichiers **ne sont pas
supprimés par ce chantier**. Le périmètre de ce prompt est « le paradigme
`Dvups_module`/`Dvups_component` » — ces 6 fichiers n'en relèvent pas,
même s'ils posent par ailleurs d'autres problèmes (routes redondantes,
code très ancien). Les toucher serait élargir le périmètre sans mandat.
**Signalé pour un chantier séparé**, pas corrigé ici.

**Les 22 modules réellement concernés** (ceux qui seront traités à
l'étape 2) :
`ModuleAdmin`, `ModuleConfig`… non — correction, `ModuleConfig` est dans
les 6 exclus. Liste exacte des 22 :
`ModuleAdmin`, `ModuleDatabase`, `ModuleLang`, `ModuleCms`, `ModuleData`,
`ModuleGallery`, `ModuleLocation`, `ModuleNotification`,
`ModulePushNotification`, `ModuleReporting`, `ModuleTree`,
`ModuleAnalytic`, `ModuleSlide`, `ModuleUser`, `ModuleComicbook`,
`ModuleContest`, `ModuleSubvention`, `ModuleCatalog`, `ModuleOrder`,
`ModulePayment`, `ModuleWallet`, `ModulePost`.

## Étape 2 — le cas `ModuleUser`, condition d'arrêt vérifiée

`src/devupsfront/ModuleUser/index.php` est en réalité **deux bootstraps
concaténés** : le premier bloc (`($app = new ModuleUser('layout'))->manage(); die;`)
s'exécute **en premier et termine toujours le script** — c'est le même
mécanisme qu'`admin/index.php`. Le second bloc, plus bas dans le fichier,
contient le **vrai** appel `Dvups_module::init('ModuleUser')`, mais il est
**mort : jamais atteint**, à cause du `die;` du premier bloc.

**Conséquence pour la mesure du 25/10** : le corps de 28 288 octets
rendu sans fatale sur `Dvups_module` ne venait donc pas d'un univers B qui
fonctionne — il venait du **premier bloc**, qui est déjà, de fait, une
redite de l'univers A, et qui plantait pour une raison sans rapport
(`Call to undefined method POST::classview()` dans `Notification.php`,
déclenché par le rendu d'un widget de notification partagé).

**Condition d'arrêt : NE se déclenche PAS.** L'univers B de `ModuleUser`
ne rend aucune page réelle que l'univers A ne saurait pas servir — le
code qui y correspond vraiment (le second bloc, `Dvups_module::init`)
n'a jamais tourné. Le chantier continue.

## Étape 3 — ce qui a été fait

**a. `toolbar.blade.php` supprimé.** Son seul appelant réel était
`admin/views/layout/layout_module.blade.php` (les deux autres "matchs"
— `dialogform.blade.php`, `layout.blade.php` — étaient juste la classe
CSS `card-toolbar`, pas un `@include`). **Et `layout_module.blade.php`
n'est lui-même extend par aucun module** (`grep` sur tout le dépôt :
zéro résultat) — c'est un layout mort. Changement à l'écran : **aucun**,
pour la même raison. La référence a quand même été retirée (plutôt que
laissée pointer vers un fichier supprimé, qui ferait planter ce layout
mort s'il était un jour réutilisé).

**b. Les 3 layouts (`ModuleLang`, `ModuleOrder`, `ModuleWallet`) alignés**
sur le patron commun (`ModuleContest`), identiques au contenu près
(différence de fin de ligne/espace blanc non chassée, sans effet). Le fil
d'Ariane **porté dans `moduleheaderwidget.blade.php`** avec les classes
`.breadcrumb`/`.breadcrumb-item` — **vérifié stylées par le thème**
(2 règles dans `admin/main.css`), contrairement à `.page-header`/
`.page-title` que les 3 anciens layouts utilisaient (**0 règle** dans
`main.css` — portage brut les aurait rendues sans style). « Some text »
**laissé en place pour l'instant** : sa suppression est explicitement
l'étape 4.d, faite dans ce fichier à ce moment-là plutôt que maintenant,
pour que chaque commit corresponde à une étape.

**c. `entitywidget.blade.php` — issue retenue : adapté au tableau.**
**19 fichiers** `overview.blade.php` l'appellent (pas zéro : l'univers B
parti, ils restent atteignables par l'univers A, `admin.layout`/
`admin.overview`). Tous consommaient `$moduledata->dvups_entity` (forme
objet) — remplacé par `$moduledata['entities']` dans les 19. Deux étaient
déjà neutralisés par une faute de frappe préexistante et sans rapport
(`@ foreach`/`@ include` avec un espace, donc des directives Blade
inertes) — `ModuleTree` et partiellement `ModuleCatalog` — **laissés tels
quels**, pas corrigés : la faute de frappe est un défaut séparé, pas le
paradigme retiré.

`entitywidget.blade.php` réécrit pour le tableau : `$entity['label'] ??
$entity['name']`, lien reconstruit `admin/{path}{name}/list` (même
patron que `moduleheaderwidget.blade.php`). **Le défaut de la requête
`COUNT` par entité réglé en passant** : la ligne a été retirée purement
et simplement plutôt que remplacée par un mécanisme de comptage par lot
— inventer ce mécanisme n'est pas dans le contrat de cette étape, et le
retirer élimine le problème de N+1 requêtes sans rien inventer.

⚠️ **Découverte non prévue, signalée, non corrigée** : deux fichiers
*de plus* consomment la forme objet et n'étaient dans aucun inventaire —
`src/devupscms/ModuleCms/Resource/views/layout.blade.php` et
`src/devupscms/ModuleLocation/Ressource/views/layout.blade.php`
(orthographe « Ressource » déjà présente sur le disque, pas une faute de
frappe de ma part). Ils sont extends par leurs `overview.blade.php` **non-admin**
respectifs (`Resource/views/overview.blade.php`, pas
`Resource/views/admin/overview.blade.php`) — un **quatrième** chemin de
rendu, dont l'atteignabilité réelle après le retrait de l'univers B n'a
pas été établie dans le temps de ce chantier. **Non touchés** : ni les 3
layouts explicitement nommés par le prompt, ni leur atteignabilité
confirmée — élargir leur aurait été un pari, pas une mesure.

**Vérification** : `ModuleAdmin` dashboard (univers A) plante toujours —
mais pour la raison déjà connue et hors périmètre
(`ArgumentCountError` sur `ModuleAdmin::__construct()`, le prompt 1.2) :
la correction d'`overview.blade.php`/`entitywidget.blade.php` est donc
correcte par lecture mais **pas démontrable à l'écran** tant que ce
défaut séparé n'est pas réglé. `dvups_admin/list` (liste générée,
inchangée) : toujours sans erreur, zéro régression confirmée sur ce qui
était déjà mesurable.

## Étape 4 — attributs et libération de `dvups_:update`

**a.** `modulestickerCore.json` posé (`name`, `project`, `label`="Stickers",
`icon`="fas fa-icons"), sans `listentity` (écrasé par le scan de toute
façon).

**b.** `label` posé sur les 6 `<projet>Core.json` (ajouté sans toucher au
reste du contenu, y compris le fossile `listmodule` d'`edition3agCore.json`
— JSON revalidé après coup). `label`+`icon` posés sur les **29**
`module<x>Core.json` (28 existants + Sticker), valeurs plausibles en
français, icônes Font Awesome v5 (`fas fa-...`).

**c.** Les deux chemins vérifiés : `Core::updateDvupsTable()`
(`dclass/devups/Core.php:232-235`) étendu pour propager `label`/`icon`
dans `module_configurations.php` (avec repli sur `name`/l'engrenage par
défaut si absents) ; `Dvups_role::updateConfigs()` confirmé **ne pas**
avoir besoin de changement — il rejoue `Core::buildOriginCore()` et
sérialise les objets décodés tels quels, donc les nouveaux attributs y
arrivent sans rien toucher (vérifié par lecture de son code, pas supposé).

**d. Consommés** : `sidebar.blade.php` (icône du module, label du module
pour le texte du menu, label du projet pour le titre de section — l'appel
`getFavicon()` en commentaire supprimé, pas décommenté) ;
`moduleheaderwidget.blade.php` (icône, label, « Some text » retiré sans
remplacement).

**e. `dvups_:update` lancée, après sauvegarde** des deux fichiers de
config hors dépôt. **Comparaison, pas le message affiché** (qui dit
« already uptodate » comme toujours, confirmé encore une fois menteur) :
- `dvups_configurations.php` : **zéro clé perdue**, **une clé gagnée**
  (`shopping\ModuleSticker\Entity\Sticker`) — l'entrée `Sticker` posée à
  la main le 18/10 est maintenant **générée par le scan**, à l'identique
  (`path`/`module`/`name`/`namespace`/`component` tous conformes) ;
- `dvups_role.modules` : **28 → 29** ;
- `dvups_role.entities` : **85 → 86 uniques** (87 brutes) — exactement la
  prédiction du prompt.

**Captures avant/après** (décrites, prises pendant la session) :
- Menu latéral — avant : icône d'engrenage identique pour `ModuleAdmin`,
  `ModuleConfig`, etc., titres de section bruts `DEVUPS`/`DEVUPSCMS`
  (collés, sans espace). Après : une icône différente et pertinente par
  module (bouclier pour Administration, base de données, trophée pour
  Concours...), titres de section `DEVUPS`/`DEVUPS CMS` (espacé, label
  réel) ;
- En-tête de module (`ModuleContest`/liste) — avant : « ModuleContest »
  en toutes lettres, icône d'engrenage générique. Après : **« Concours »**,
  icône trophée, fil d'Ariane « Dashboard / Concours ».

**Régression univers A, mesurée une dernière fois sur les 5 mêmes
modules après `dvups_:update`** : identique à avant (4/5 « OK », 1/5
`ModuleUser` toujours cassé pour la même raison préexistante et sans
rapport). **Zéro régression confirmée.**

**`ModuleSticker` maintenant visible dans le menu** (c'était l'objectif de
l'étape 4a) mais sa liste plante encore — `Class "StickerController" not
found" : **attendu, pas une régression** : le module n'a jamais eu de
`Controller`/`Datatable`/`Form`/`Resource/views` admin (confirmé le 25/10),
seulement son entité et son contrôleur front. Déclarer le module dans le
datacore est le préalable à construire son back-office de validation —
pas une promesse que ce back-office existe déjà.

---

## 4 — Réparation 1 : `Request::Module()` instancie sans argument

**Le défaut** (`dclass/devups/Http/Request.php`, `Request::Module()`) :
`$app = new $ctrl();` sans argument, alors que `Module<X>::__construct($route)`
exige un paramètre dans 16 des 19 modules qui ont un fichier `Module<X>.php`
(confirmé par lecture de `ModuleController extends Router` puis de
`Router::__construct($default_url)` : `$route` y est une chaîne, jamais
l'objet `\Router`/`Admin` que `Controller::viewsModule()` passe en premier
argument à `Request::Module()` — deux paramètres homonymes, deux types
différents). `ArgumentCountError` sur tout tableau de bord de module,
confirmé le 25/10 sur `ModuleContest`.

**Recensement des 29 `Module<X>.php`** (lu, pas deviné) :
- **10 modules sans fichier `Module<X>.php` du tout** : `ModuleDatabase`,
  `ModuleCms`, `ModuleGallery`, `ModuleLocation`, `ModuleReporting`,
  `ModuleAnalytic`, `ModuleOrder`, `ModuleSticker`, `ModuleKola`,
  `ModulePost` — la réparation ne les aide pas (ils échoueront sur
  « Class not found », un défaut différent et préexistant) ;
- **`ModuleLang`** : `__construct()` à zéro argument — vérifié sans risque
  (PHP tolère un argument positionnel surnuméraire sur un constructeur à 0
  paramètre, testé en isolation : `new Foo("x")` contre
  `function __construct(){}` ne lève rien) ;
- **`ModuleNotification`/`ModuleTree`** : `__construct($route = 'dashbaord')`
  (faute de frappe déjà présente, non touchée, hors mandat) — déjà
  tolérants avant la réparation ;
- **Les 16 autres** : `__construct($route)` obligatoire — exactement le cas
  cassé, exactement ce que la réparation débloque.

**Correctif** : `new $ctrl('layout')` — `'layout'` choisi parce que c'est la
valeur utilisée par toutes les instanciations réelles historiques du dépôt
(anciens `index.php` de modules, retirés au chantier précédent), et parce
que la fonction appelée ensuite (`$function = $path."View"`) attend une
méthode nommée d'après le **segment d'URL**, pas d'après ce paramètre —
les deux sont indépendants. `php -l` propre.

**Correction d'une fausse mesure en cours de séance** : mon premier jet de
mesure a testé `admin/<projet>/<Module>/dashboard` pour les 5 modules
comme demandé littéralement dans le prompt — **mauvais segment d'URL**.
Recensement des méthodes `*View()` réelles : 17 modules sur 19
(dont les 5 premiers échantillonnés : Contest, Admin, Payment, Comment,
PushNotification) exposent `layoutView()`, pas `dashboardView()` ; seuls
`ModuleTree` et `ModuleNotification` exposent `dashboardView()`. Le
chemin `/dashboard` littéral du prompt n'est donc correct que pour 2
modules sur 19 — voir point 10 du rapport (« ce qui était faux dans le
prompt »).

**Mesure avant/après, 7 modules, 6 projets** (chemin corrigé à
`/layout` ou `/dashboard` selon la méthode réelle du contrôleur) :

| Module | Projet | Avant (25/10) | Après |
|---|---|---|---|
| ModuleContest | edition3ag | `ArgumentCountError` | **rend** (93 070 o) |
| ModuleAdmin | devups | `ArgumentCountError` | **rend** (93 016 o) |
| ModulePayment | shopping | `ArgumentCountError` | **plante différemment** : `Class "Payment_method" not found` — défaut préexistant, sans rapport, même famille que `ModuleKola`/`KolaTable` déjà hors mandat |
| ModuleComment | spacekola | `ArgumentCountError` | **rend** (91 612 o) |
| ModulePushNotification | devupscms | `ArgumentCountError` | **rend** (92 412 o) |
| ModuleTree | devupscms | `ArgumentCountError` | **rend** (103 005 o) |
| ModuleNotification | devupscms | `ArgumentCountError` | **rend** (139 468 o) |

**6/7 passent de « plante » à « rend »**, 1/7 (`ModulePayment`) plante
toujours mais pour une raison entièrement différente et préexistante
(classe manquante, pas un défaut d'arguments) — **zéro régression**,
et cette dernière case était déjà listée dans les 11 listes qui
plantent pour leur propre raison, hors mandat de ce chantier.

**Confirmation visuelle** (la vérification manquante du chantier
précédent, explicitement demandée) : `admin/edition3ag/ModuleContest/layout`
ouvert au navigateur après connexion admin réelle. L'en-tête affiche
« Concours » (pas « ModuleContest »), fil d'Ariane « Dashboard / Concours »,
icône trophée — confirme le label/icône de module posé à l'étape 4a du
chantier précédent. Trois tuiles d'entité (`CONTEST`, `EVALUATION`,
`PARTICIPANT`), chacune avec un lien « Details » vérifié par
`read_page` : `admin/edition3ag/ModuleContest/<Entité>/list` — label
correct (replié sur `name`, le label d'entité n'est pas encore câblé,
c'est l'objet de l'étape 4a de ce chantier-ci) et lien correct. C'est la
première confirmation visuelle que `entitywidget.blade.php` et
`overview.blade.php` (retrait du N+1, chantier précédent) fonctionnent
réellement à l'écran.

---

## 5 — Réparation 2 : `$formfield` fuyait entre champs (corruption de
données, pas d'affichage)

**Le défaut** (`FormFactory::__renderForm()`, boucle `foreach
($entitycore->field as $key => $field)`) : un `if/elseif` sans `else`
(lignes 397-432 avant correctif). Quand `$field['type']` ne correspond à
aucune branche, `$formfield` n'est jamais réassigné — il garde la valeur
HTML du **champ précédent**, `name=` d'origine compris. Confirmé le 25/10
par test en mémoire : un champ DATETIME après un champ texte affichait le
bon libellé mais soumettait la valeur du champ texte sous le `name` du
champ texte. C'est de la **corruption de données silencieuse à la
soumission**, pas un défaut d'affichage.

**Corrigé, les deux defenses demandées, pas une seule** :
1. `$formfield = '';` ajouté en tête de boucle (avant toute autre
   affectation) — rend la fuite structurellement impossible même si une
   future branche oublie d'assigner `$formfield` ;
2. `else` explicite ajouté après `FORMTYPE_FILEMULTIPLE` : en dev
   (`!__prod`, convention déjà en usage dans
   `dclass/devups/Datatable/Lazyloading.php:433`, pas inventée pour
   l'occasion) un commentaire HTML nomme le type et le champ non gérés ;
   en production, rien — ni le type, ni le champ, ni aucun détail
   technique (le `$formfield = ''` du point 1 suffit, aucune ligne
   supplémentaire nécessaire côté prod).

**`FORMTYPE_DATETIME`** : la constante existait déjà
(`FormManager.php:17`, `'datetime'`) mais aucune branche ne la rendait.
Choix (lu, pas deviné) : **ajoutée au premier groupe `in_array(...)`**
aux côtés de `FORMTYPE_DATE`, pas de branche séparée — `__input()`
construit `type='" . $field['type'] . "'"` directement depuis la chaîne
du type, donc `FORMTYPE_DATE` et `FORMTYPE_DATETIME` s'y comportent déjà
à l'identique sans rien ajouter. Note pour mémoire, pas pour ce
chantier (« tu ne touches pas au style ») : le HTML5 valide est
`type="datetime-local"`, pas `type="datetime"` (déprécié) — un
navigateur dégrade ça en champ texte simple, fonctionnellement correct
(la valeur part bien dans le POST) mais sans le widget de sélection.
Améliorer ça est une question d'UX, hors mandat ici.

**`FORMTYPE_INJECT_COLLECTIONFORM` : non implémenté, comme demandé.**
Grep sur tout `src/` : **aucun écran du dépôt ne déclare ce type**
(zéro occurrence de `injectioncollection`). Lui inventer un rendu serait
une décision de conception sans aucun exemple réel à suivre. Le `else`
le rend visible (commentaire en dev) sans décider pour lui.

**`FORMTYPE_FILEMULTIPLE`, trois bugs sur quatre lignes, les trois
corrigés** :
- `$input . ' multiple />';` était une expression orpheline — la
  concaténation ne modifiait rien, l'attribut `multiple` et la
  fermeture `/>` n'atteignaient jamais le HTML produit. Corrigé en
  construisant `$input` directement avec `multiple />` inclus ;
- `$file` n'était défini que dans le `if ($field['value'])` — utilisé
  juste après sans condition (`$formfield = $file . $input;`), notice
  PHP « Undefined variable » si `$field['value']` est faux. Corrigé par
  `$file = "";` avant le `if`.
- Grep sur tout `src/` : **aucun écran du dépôt n'utilise ce type
  actuellement** (zéro occurrence de `filemultiple` dans un Core.json
  ou un `*Form.php`) — code mort aujourd'hui, mais corrigé intégralement
  comme demandé (pas conditionné à un usage actuel).

**Effet de bord découvert, non corrigé, hors mandat** : l'attribut
`id=` de chaque champ est déjà, avant ce correctif, décalé d'un champ
(`FormFactory::$fieldid` est réaffecté après l'appel à
`serialysedirective()` qui le consomme, lignes 387/394 — ordre déjà
présent avant ce chantier, confirmé par lecture du diff). Visible dans
le test 4-champs ci-dessous (`id='test-title'` sur le champ
`published_at`). Pré-existant, sans rapport avec la fuite de
`$formfield`, et pas dans le périmètre de cette réparation — signalé,
pas touché.

**Preuve 1 — formulaire de test en mémoire, 4 champs** (texte, DATETIME,
type inventé `'nawak'`, FILEMULTIPLE), HTML complet :
```html
<table class='table'><tr  id='test_form[title]' class=' form-group ' ><td><label class='dv_label ' >Titre </label></td><td ><input class='form-control' id='' type='text' name='test_form[title]' value='VALEUR-TEXTE-TEMOIN'  /></td></tr><tr  id='test_form[published_at]' class=' form-group ' ><td><label class='dv_label ' >Date de publication </label></td><td ><input class='form-control' id='test-title' type='datetime' name='test_form[published_at]' value='2026-10-25 10:00'  /></td></tr><tr  id='test_form[mystere]' class=' form-group ' ><td><label class='dv_label ' >Champ type inconnu </label></td><td ><!-- [FormFactory] type de champ non gere : 'nawak' (champ 'mystere') --></td></tr><tr  id='test_form[pieces]' class=' form-group ' ><td><label class='dv_label ' >Pieces jointes </label></td><td ><input class='form-control' id='test-mystere' type='file' name='test_form[pieces][]' value='' multiple /></td></tr></table>
```
Vérifié programmatiquement : `title` garde son `name=` propre ;
`published_at` rend `type='datetime'` avec son propre `name=` (DATETIME
fonctionne) ; le champ `mystere` **ne porte plus** `name='test_form[title]'`
(la fuite d'avant-correctif, zéro fuite maintenant) ; `pieces` porte bien
`multiple />` ; zéro notice « Undefined variable » sur toute l'exécution.

**Preuve 2 — formulaire réel `contest` (id=7), avant/après, octet pour
octet** : rendu via `git stash` (FormFactory.php seul) puis
`ContestForm::init(Contest::find(7))->buildForm()->renderForm()`,
sortie capturée dans les deux états. **Correction au passage du prompt** :
le formulaire `contest` a **12 champs**, pas 11 (`active`,
`admin_status`, `title`, `thematic`, `description`, `date_start`,
`date_end`, `cover`, `reference`, `awards`, `presentation_rules`,
`enrolment_rules` — le treizième, `cmstext.id`, est en commentaire dans
`ContestForm.php`, non actif). Aucun de ces 12 champs n'utilise
DATETIME, FILEMULTIPLE ni un type absent — la preuve porte donc
uniquement sur la non-régression du chemin déjà fonctionnel. **3946
octets avant, 3946 octets après, `diff` vide, md5 identique
(`cf389a92f985fe761b7389c7242c41ea`)** — zéro régression sur le seul
vrai formulaire mesuré.

---

## 6 — Réparation 3 : `Core::updateDvupsTable()` mentait toujours

**Le défaut** : `$updated = false;` posé en tête de fonction, jamais
réassigné, retourné tel quel — le CLI `dvups_:update` affiche donc
« already uptodate » **quoi qu'il se soit réellement passé**. Confirmé
100% menteur par deux sessions précédentes qui avaient dû s'en méfier
systématiquement (`BACKOFFICE-NOTES.md`, étape 4e du chantier
précédent).

**Corrigé** (approche la plus simple/sûre suggérée par le prompt,
retenue telle quelle) : comparer le contenu généré à ce qui est déjà
sur disque **avant** d'écrire, pour les deux fichiers de config, et
comparer aussi les trois colonnes CSV du rôle 1 (`components`/
`modules`/`entities`) avant/après leur réassignation. `$updated` =
OU logique des trois comparaisons. **Rien d'autre touché dans cette
fonction** : ni le double-indexage de `dvups_configurations` (toujours
deux clés par entité, vérifié ci-dessous), ni la portée du rôle 1.

**Preuve dans les deux sens, après sauvegarde des deux fichiers de
config hors dépôt** (`config_backup_r3_4a/`, horodatage avant toute
exécution de `dvups_:update` cette session) :
- 1er `php devups dvups_:update` (avec aussi le câblage du `label`
  d'entité de l'étape 4a ci-dessous, posé dans le même passage de
  code) → **« Data admin updated with success »** — correct, le
  contenu généré différait bien de ce qui était sur disque (ajout du
  `label`) ;
- 2e `php devups dvups_:update`, lancé immédiatement après, sans aucun
  changement de code ni de données entre les deux → **« Data admin
  already uptodate »** — correct, rien n'avait changé.

**Diff des deux fichiers de config, avant/après** (comparaison
programmatique clé par clé, pas un diff texte brut) :
- `dvups_configurations.php` : **0 clé perdue, 0 clé gagnée**, **173
  entrées modifiées** (le double-indexage namespace+nom court, pour
  ~86 entités uniques × 2 — confirme que le double-indexage n'a pas
  été touché), chacune ayant gagné exactement la clé `label` ;
- `module_configurations.php` : **0 clé perdue, 0 clé gagnée**, les 87
  entités de `entities[]` portent désormais toutes une clé `label`
  (`0 sans label sur 87`).

Le message CLI reflète maintenant la réalité dans les deux sens — c'est
la preuve demandée. Le premier run « updated » ci-dessus inclut aussi
le câblage du `label` d'entité de l'étape 4a (posé dans le même
passage de code, voir section suivante) — les deux sont documentés
séparément mais ont été mesurés ensemble, un seul `dvups_:update`
couvrant les deux.

---

## 7 — Étape 4a : `label` d'entité câblé, mais correction majeure au
passage du prompt

**Établi avant de toucher quoi que ce soit** (demandé explicitement) :
`sidebar.blade.php` lit `$admin->_dvups_role->getConfigs()`, donc
`dvups_role.configs` — **pas** `module_configurations.php`. Le prompt
anticipait que, dans ce cas, « le label d'entité y est déjà via les
objets bruts du scan ». **Faux, vérifié en lisant le code, pas
supposé** : `Dvups_role::updateConfigs()` rejoue
`Core::buildOriginCore()` avec un callback de filtre qui, pour les
composants et les modules, retourne un simple booléen (l'objet brut du
scan est conservé tel quel — **c'est pour ça que le label de module,
lui, arrivait déjà "gratuitement"**) — mais pour les **entités**
spécifiquement, le callback retourne autre chose : il **remplace**
l'objet scanné par l'entrée `$dvups_configurations[NomEntite]`
(`Core::findentitycore()` ligne ~123, `Dvups_role.php` ligne ~138).
Cette entrée-là n'avait jamais de `label`. Donc même avec un `label`
posé dans un `Core/<Entité>Core.json`, **il n'aurait jamais atteint
`sidebar.blade.php`** sans câbler aussi `$dvups_configurations`, pas
seulement `$entities[]`. Les deux sont câblés (voir diff de la section
précédente).

**Correction majeure, à nommer explicitement (point 10 du rapport)** :
le prompt affirme que le `label` d'entité a été « ajouté aux 87
fichiers `Core/<Entité>Core.json` précédemment ». **Vérifié
programmatiquement (décodage JSON, pas un grep texte qui confondrait
un champ `label` imbriqué dans `attribut` avec l'attribut d'entité) :
0 sur 87 fichiers `Core/<Entité>Core.json` ont un attribut `label` au
niveau entité.** Le chantier précédent a posé `label`/`icon` sur les
**29 fichiers `module<x>Core.json`** (niveau module, déjà exploité et
visuellement confirmé — « Concours » au tableau de la réparation 1) —
pas sur les 87 fichiers d'entité. Aucune entité du dépôt n'a donc de
libellé personnalisé aujourd'hui ; le câblage posé ici est un repli
sur `name` partout, strictement identique à l'affichage actuel, mais
pas inerte : dès qu'un `label` sera posé sur un `Core/<Entité>Core.json`,
il s'affichera sans aucun changement de code supplémentaire.

**Preuve que le mécanisme fonctionne quand la donnée existe** (pas
testable en conditions réelles aujourd'hui, 0/87) : script isolé
reproduisant exactement le code ajouté à `Core.php`, avec une fausse
entité portant un `label` → propagé correctement dans les deux
tableaux (`dvups_configurations` et `entities[]`) ; une seconde fausse
entité sans `label` → repli confirmé sur `name`. Les deux assertions
passent.

**Consommé dans les 3 vues** :
- `entitywidget.blade.php` : avait déjà `$entity['label'] ?? $entity['name']`
  depuis le chantier précédent (non modifié ici) ;
- `moduleheaderwidget.blade.php` ligne 40 : `$entity['name']` brut →
  `$entity['label'] ?? $entity['name']` ;
- `sidebar.blade.php` ligne 70 : `$entity['name']` brut →
  `$entity['label'] ?? $entity['name']`.

**Capture avant/après** : aucune différence visuelle possible à
produire honnêtement aujourd'hui, puisque 0/87 entités ont un label
réel — le texte affiché (nom de l'entité) est strictement identique
avant et après sur les 7 modules mesurés. Documenté comme tel plutôt
que de fabriquer une capture qui ne montrerait rien de réel.

**`dvups_:update` relancée** après réapplication du câblage (backup
préalable, `config_backup_4a_final/`) : **« Data admin already
uptodate »** — cohérent, le contenu généré est désormais identique à
ce qui est déjà sur disque (labels déjà posés par le run précédent).

**Régression, mesurée sur les mêmes 7 modules qu'à la réparation 1** :
identique — 6/7 rendent, `ModulePayment` plante toujours pour la même
raison préexistante et sans rapport (`Payment_method` introuvable).
Zéro régression.

---

## 8 — Étape 4b : le compte par entité restauré, sans toucher à `src/`

**Contrainte découverte avant d'écrire quoi que ce soit** : le prompt
demande que le comptage soit « appelé une fois par `overview.blade.php`,
passant une carte entité => compte à `entitywidget` ». Mais
`overview.blade.php` **n'existe pas dans `admin/`** — c'est un fichier
**par module**, ~20 exemplaires, tous sous `src/<projet>/<Module>/Resource/views/admin/overview.blade.php`,
donc tous en lecture seule pour ce chantier (règle dure n°1, aucune
exception hors étape 4d). Impossible d'appeler quoi que ce soit
« depuis `overview.blade.php` » sans violer le périmètre d'écriture.

**Choix retenu (le plus conservateur compatible avec la règle
d'écriture, documenté comme demandé par MODE CONTINU)** : tout faire
depuis `entitywidget.blade.php` seul (le seul des deux fichiers
concernés qui soit dans le périmètre), en s'appuyant sur deux faits
vérifiés par lecture :
1. `overview.blade.php` fait `@foreach($moduledata['entities'] as $entity) @include("default.entitywidget") @endforeach`
   — Blade partage par défaut les variables de la vue appelante avec
   la vue incluse, donc `$moduledata` (liste complète des entités du
   module) est visible depuis `entitywidget.blade.php` sans rien
   changer à `overview.blade.php` ;
2. une variable `global` initialisée à la première inclusion et
   réutilisée aux suivantes obtient « une requête par page », pas
   « une requête par entité », sans dépendre de l'ordre d'inclusion
   ni d'un fichier externe — le serveur de dev PHP traite une requête
   HTTP par processus, donc aucune fuite entre pages.

**La requête** : whitelist via `information_schema.tables` (jamais
d'interpolation brute d'un nom d'entité — les noms injectés dans le
`UNION ALL` sont ceux que MySQL vient lui-même de confirmer comme
tables réelles du schéma courant, pas les noms d'entité bruts), puis
un seul `UNION ALL` sur les tables confirmées. Entité sans table
réelle → absente du résultat → repli choisi : **rien affiché**, pas un
mauvais chiffre (le repli de secours mentionné par le prompt, retenu
directement plutôt qu'en dernier essai — la whitelist rend l'« union
fragile » hypothétique du prompt non observée).

**`ModuleSticker`, le cas nommé par le prompt** : la table `sticker`
existe réellement (3 lignes, vérifié par requête directe) — ce n'est
pas un cas « sans table », c'est `StickerController` qui manque
(confirmé étape 4 du chantier précédent). Le cas réel « sans table »
trouvé par recensement complet des 86 entités du rôle 1 contre
`information_schema.tables` : **7 entités sans table**, dont `comment`
(entité de `ModuleCms` — un des 10 modules sans `Module<X>.php`,
injoignable par une page admin réelle aujourd'hui, donc non testable
en conditions réelles) et les 6 entités `kola*` (`ModuleKola`,
explicitement hors mandat, `Class "KolaTable" not found`).

**Preuve 1 — mesurée en conditions réelles**, `admin/edition3ag/ModuleContest/layout`
(3 entités) : CONTEST **8**, EVALUATION **81**, PARTICIPANT **307** —
vérifiés un par un contre `SELECT COUNT(*) FROM <table>` en base,
identiques. `admin/devups/ModuleAdmin/layout` (3 entités) :
Dvups_admin **7**, Dvups_right **4**, Dvups_role **3** — identiques en
base également.

**Preuve 2 — repli gracieux, isolée** (`comment` injoignable en
conditions réelles) : script reproduisant exactement la requête de
`entitywidget.blade.php`, avec un mélange de 3 tables réelles + `comment`
(vraie entité sans table) + un nom entièrement inventé → les 3 tables
réelles renvoient leur vrai compte, les deux autres : rien affiché.
Zéro erreur, zéro mauvais chiffre.

**Mesure du nombre de requêtes** (le livrable explicitement demandé) :
instrumentation temporaire (compteur de fichier, retirée avant commit,
jamais présente dans l'état final), une page à 3 entités
(`ModuleContest`) → **1** déclenchement du bloc de requête. Avant ce
chantier (état laissé par le chantier précédent) : **0** requête, le
nombre était retiré, pas réduit — restauré à **1** par page de module,
jamais par entité.

**Régression, mêmes 7 modules** : identique — 6/7 rendent, `ModulePayment`
inchangé. Zéro régression.

---

## 9 — Étape 4c : les 29 `module<x>Core.json` réindentés, preuve
sémantique

**Pas de reformatage en retour** (aurait produit un second diff géant,
explicitement exclu par le prompt). À la place : décodage JSON de la
version « avant » (`git show 8570681^:<fichier>`, le commit juste
avant celui qui a posé `label`/`icon`) et de la version « après »
(fichier courant), comparaison **clé par clé** (pas un diff texte brut
qui confondrait réindentation et changement sémantique), sur les 29
fichiers exacts touchés par ce commit
(`git diff --name-only 8570681^ HEAD -- "src/*/Module*/module*Core.json"`
→ 29 fichiers, pas un de plus ni de moins).

**Résultat : 29/29 identiques**, à l'exception des deux attributs
`label` et `icon` (aucune autre clé perdue, gagnée ou modifiée sur
aucun des 29). Zéro déviation.

**Vérification annexe de l'exemple cité par le prompt** (`modulecontestCore.json`,
« 14 055 → 18 220 octets ») : mesuré **13 647 → 18 220** — la valeur
« après » est exacte, la valeur « avant » citée par le prompt est
~400 octets au-dessus de ce que montre `git show`. Écart mineur, sans
incidence sur la preuve sémantique (qui ne dépend pas du nombre
d'octets), signalé pour l'exhaustivité du point 10 du rapport.

---

## 10 — Étape 4d : les deux fichiers orphelins, **outcome (a) retenu,
supprimés**

**Établi avant de toucher à quoi que ce soit, par preuve, pas par
supposition** — deux faits indépendants, chacun suffisant seul :

1. **`ModuleCms`** : la seule entrée jamais capable d'appeler ce
   couple de fichiers était `src/devupscms/ModuleCms/index.php`,
   supprimée par le chantier précédent (`git log`, commit `bdc44a5`,
   « retrait de l'univers B »). Son contenu avant suppression
   (`git show bdc44a5^:...`) confirme : `case 'layout':
   Genesis::renderView("overview");` — qui résout bien
   `Resource/views/overview.blade.php` (le fichier non-admin), lequel
   `@extends('layout')` → `Resource/views/layout.blade.php` (l'autre
   fichier nommé par le prompt). **Mais** cette même ligne supprimée
   avait déjà `//$moduledata = Dvups_module::init('ModuleCms');` **en
   commentaire** — donc `$moduledata` n'était déjà plus défini quand
   ce chemin était encore techniquement atteignable : cassé avant
   d'être orphelin, pas seulement orphelin ;
2. **`ModuleLocation`** : encore plus net. L'ancien `index.php`
   (même commit, même preuve) pointait `$viewdir[]` vers
   `__DIR__.'/Resource/views'` (orthographe correcte) et appelait
   `Genesis::renderView("admin.overview")` — qui résout
   `Resource/views/admin/overview.blade.php`, la vue admin **déjà en
   service aujourd'hui**, pas `Ressource/views/overview.blade.php`
   (avec le double-s, la faute d'orthographe réelle sur le disque).
   **Le dossier `Ressource/` n'a jamais été atteint par cet
   `index.php`, même avant sa suppression** — c'est un reliquat d'un
   renommage `Ressource` → `Resource` jamais nettoyé, antérieur au
   retrait de l'univers B lui-même.

**Recherche exhaustive de tout autre appelant** : grep de
`Genesis::renderView("(admin.)?overview"` sur tout le dépôt — aucun
des 29 `Module<X>.php` actuels n'appelle la forme non-admin pour
`ModuleCms` ou `ModuleLocation` (ni n'existe pour ces deux modules, 2
des 10 confirmés sans `Module<X>.php` à la réparation 1) ; grep du
chemin `Ressource/` ou des deux fichiers par nom — aucune autre
référence dans tout `src/`, `dclass/`, ni `admin/`.

**Trouvaille annexe, non traitée (hors mandat, nommée pour mémoire)** :
`src/devupscms/ModuleCms/Resource/views/cmstext/index.blade.php`
`@extends('layout')` — dépend lui aussi du `layout.blade.php`
supprimé, mais n'est nommé par aucun des deux fichiers commissionnés
ici. Son propre appelant (`CmstextController::listView()`) rend en
réalité `cmstext.form`, jamais `cmstext.index` — probablement déjà
mort avant même le retrait de l'univers B. Signalé, pas touché (hors
des deux fichiers nommés par le prompt).

**Décision : outcome (a), supprimés** — les 4 fichiers (2 `layout.blade.php`
+ leurs 2 `overview.blade.php` non-admin) :
- `src/devupscms/ModuleCms/Resource/views/layout.blade.php`
- `src/devupscms/ModuleCms/Resource/views/overview.blade.php`
- `src/devupscms/ModuleLocation/Ressource/views/layout.blade.php`
- `src/devupscms/ModuleLocation/Ressource/views/overview.blade.php`

**Pas touché** : le reste de `Ressource/js/*` (dupliqué par rapport à
`Resource/js/*`, hors mandat, signalé pour un futur chantier) ; les
vues `front/layout.blade.php`/`front/overview.blade.php` de
`ModuleLocation` sous `Resource/` (orthographe correcte, un chemin de
rendu front séparé, potentiellement vivant, pas nommé par le prompt).

**Régression, mêmes 7 modules, après suppression** : identique — 6/7
rendent, `ModulePayment` inchangé. Zéro régression (attendu : ces
fichiers n'étaient appelés par aucun chemin vivant).

---

# Chantier du 26/10 (suite) — finir le socle

## 11 — Point 1 : le lien du menu pointait sur une méthode qui n'existe pas

**Confirmé, et c'est l'utilisateur qui l'a trouvé, pas moi la dernière
fois** : `sidebar.blade.php:63` génère
`admin/{{component}}/{{module}}/dashboard` pour **tous** les modules,
pas `/layout`. `Request::Module()` en tire `$function = 'dashboardView'`.
Or seuls `ModuleTree` et `ModuleNotification` exposent réellement cette
méthode — les 16 autres modules-avec-fichier exposent `layoutView()`.
La réparation 1 du 26/10 avait mesuré `/layout` (le chemin qui marche)
et conclu « 6/7 rendent » — vrai du chemin, pas du lien cliqué. Vu de
l'écran, le tableau de bord restait mort pour ces 16 modules.

**Vérifié avant d'écrire** (demandé explicitement) :
- `ModuleController` ne déclarait **ni** `layoutView()` ni
  `dashboardView()` — aucun conflit de nom à gérer ;
- recensement complet des 19 `Module<X>.php` : **16 avec `layoutView()`
  seul** (pas 17 — correction de mon propre compte du 26/10, voir point
  13), **2 avec `dashboardView()` seul** (`ModuleTree`,
  `ModuleNotification`), et **1 avec ni l'un ni l'autre** : `ModuleLang`.
  16 + 2 + 1 = 19, exact ;
- **`ModuleLang` n'étend même pas `ModuleController`** — c'est une
  classe à part, namespace `devups\ModuleLang` (minuscule), dispatch
  interne (`web()`/`services()`/`webservices()`), un vestige d'une
  génération antérieure au cadre `Module<X> extends ModuleController`.
  Testé directement (`admin/devups/ModuleLang/layout`) : **`Class
  "ModuleLang" not found`**, fatale, à la résolution du nom de classe —
  avant même d'atteindre la vérification de méthode. `ModuleLang` était
  déjà dans la liste des 11 modules exclus du mandat (25/10, « CE QUE TU
  NE FAIS PAS ») pour une raison différente et sans rapport ; cette
  session confirme juste que son défaut est encore plus en amont que
  prévu. **N'est pas concerné par ce correctif, ne peut pas l'être.**
  Conséquence directe : le cas « fichier présent, ni `layoutView()` ni
  `dashboardView()`, échec propre demandé » n'a **aucune occurrence
  réelle** à ce jour — `ModuleController::dashboardView()` gère quand
  même ce cas (repli `var_dump`+`die`, même formulation que
  `Request::Module()`), par défense structurelle, pas parce qu'un module
  l'exercerait aujourd'hui.

**Correctif retenu (3e voie, comme demandé)** : `dashboardView()` posé
dans `ModuleController` (la classe de base, 29 modules en héritent) —
appelle `layoutView()` si elle existe, sinon échoue proprement (même
message que `Request::Module()`, pas une fatale). `sidebar.blade.php`
non touché (lecture seule, délibéré). Les 2 modules qui définissent
déjà leur propre `dashboardView()` l'emportent par résolution de
méthode PHP normale — rien de spécial à coder pour eux.

**Mesure avant/après, les mêmes 7 modules, par le lien du menu
(`/dashboard`), pas `/layout`** :

| Module | Avant (`/dashboard`) | Après (`/dashboard`) |
|---|---|---|
| ModuleContest | « méthode manquante » (`dashboardView` absente) | **rend** |
| ModuleAdmin | « méthode manquante » | **rend** |
| ModulePayment | « méthode manquante » | plante différemment (`Payment_method` introuvable, préexistant, sans rapport) |
| ModuleComment | « méthode manquante » | **rend** |
| ModulePushNotification | « méthode manquante » | **rend** |
| ModuleTree | rendait déjà (`dashboardView()` propre) | rend (inchangé) |
| ModuleNotification | rendait déjà (`dashboardView()` propre) | rend (inchangé) |

**5 modules sur 7 passent de « méthode manquante » (le menu menait à un
`die()`) à « rend »** ; 2 rendaient déjà et continuent de rendre
(prouve que leur propre `dashboardView()` est toujours prioritaire,
zéro régression sur ce point précis) ; 1 plante pour une raison
différente, préexistante, déjà identifiée à la réparation 1.

**Capture demandée** (geste réel, jamais fait avant) : connexion admin,
clic sur « Concours » dans le menu latéral pour déplier son sous-menu,
clic sur « Dashboard » — URL atteinte : `admin/edition3ag/ModuleContest/dashboard`
(confirmée dans la barre d'adresse du navigateur, pas tapée à la main).
Rendu : en-tête « Concours », onglets Dashboard/Contest/Evaluation/
Participant, tuile CONTEST avec le compte **8** (étape 4b du 26/10,
toujours exacte). Capture conservée dans la session.

**Régression, 7 modules, mesurée après ce correctif, par `/layout` ET
par `/dashboard`** : identique sur les deux chemins aux résultats du
26/10 et à l'« après » ci-dessus. Zéro régression.

---

## 12 — Point 2 : `type="datetime"` corrigé en `type="datetime-local"`

**Défaut** : `FORMTYPE_DATETIME` ajouté au groupe `in_array` du 26/10,
mais `__input()` construit `type='" . $field['type'] . "'"` directement
depuis la chaîne du type (`'datetime'`) — HTML produit :
`type="datetime"`, déprécié et dégradé en champ texte libre par tout
navigateur, sans sélecteur ni validation. Pertinent pour la
« publication programmée », qui a motivé l'implémentation.

**Corrigé, sans renommer la constante** (publique, potentiellement
utilisée ailleurs, comme demandé) : `__input()` calcule un
`$htmltype` distinct de `$field['type']` — traduit `FORMTYPE_DATETIME`
en `'datetime-local'` pour l'attribut HTML seulement, laisse tout le
reste (`FORMTYPE_DATE` → `'date'`, etc.) inchangé. Les trois
occurrences de `type='" . $field['type'] . "'"` dans `__input()`
(sans langue, tableau de valeurs par langue, valeurs vides par langue)
utilisent désormais `$htmltype`.

**Preuve en mémoire** (texte rendu complet) :
```html
<input class='form-control' id='' type='datetime-local' name='test_form[published_at]' value='2026-10-26T10:00'  />
...
<input class='form-control' id='test-published_at' type='date' name='test_form[date_start]' value='2026-10-26'  />
```
`FORMTYPE_DATETIME` → `type='datetime-local'` confirmé ; `type='datetime'`
brut n'apparaît plus nulle part ; `FORMTYPE_DATE` → `type='date'`
**toujours**, régression possible explicitement vérifiée, absente.

**Non-régression sur le formulaire réel `contest`** (aucun champ
DATETIME, deux champs DATE — `date_start`/`date_end`) : rendu
re-capturé après ce correctif, **3946 octets, `diff` vide** contre la
capture de la réparation 2 du 26/10 — identique, et les deux
`type='date'` toujours présents.

---

## 13 — Point 3 : l'`id=` décalé d'un champ, ordre établi puis corrigé

**Ordre réel établi avant de toucher à quoi que ce soit** (demandé
explicitement) : dans `__renderForm()`, `$field['directive'] =
FormFactory::serialysedirective($directive);` (qui **lit**
`FormFactory::$fieldid` pour l'attribut `id=`, seulement s'il n'est pas
déjà posé) était appelé **avant** que `$fieldname`/`$fieldid`/
`$entityattrib` du champ courant ne soient (ré)assignés. Donc chaque
`serialysedirective()` consommait la valeur de `$fieldid` posée au tour
de boucle **précédent** — décalage d'un champ, confirmé par lecture
avant tout correctif.

**Vérifié que `serialysedirective()` n'a besoin de rien d'autre** : elle
ne lit que `FormFactory::$fieldid` (un `if (!isset($directives["id"]))`
simple), aucune autre dépendance à l'ordre précédent — pas un design
intentionnel, un bug de séquencement pur.

**Corrigé** : les trois affectations déplacées **avant** l'appel à
`serialysedirective()`, dans le même ordre relatif entre elles.

**Établi avant de corriger (demandé)** : les champs `FORMTYPE_RADIO`
(`__radio()`) ne portent **jamais** d'attribut `id=` — ignoré par
conception, confirmé en lisant `__radio()`. Sur les 12 champs du
formulaire `contest`, 2 sont des radios (`active`, `admin_status`) :
non concernés par ce bug, ni avant ni après, puisqu'ils n'émettent
jamais d'`id=`.

**Mesure avant/après, formulaire réel `contest`, les 12 champs** :

| Champ | `name=` | `id=` avant | `id=` après |
|---|---|---|---|
| active | radio, pas d'id | — | — |
| admin_status | radio, pas d'id | — | — |
| title | `contest_form[title]` | `contest-admin_status` ❌ | `contest-title` ✅ |
| thematic | `contest_form[thematic]` | `contest-title` ❌ | `contest-thematic` ✅ |
| description | `contest_form[description]` | `contest-thematic` ❌ | `contest-description` ✅ |
| date_start | `contest_form[date_start]` | `contest-description` ❌ | `contest-date_start` ✅ |
| date_end | `contest_form[date_end]` | `contest-date_start` ❌ | `contest-date_end` ✅ |
| cover | `contest_form[cover]` | `contest-date_end` ❌ | `contest-cover` ✅ |
| reference | `contest_form[reference]` | `contest-cover` ❌ | `contest-reference` ✅ |
| awards | `contest_form[awards]` | `contest-reference` ❌ | `contest-awards` ✅ |
| presentation_rules | `contest_form[presentation_rules]` | `contest-awards` ❌ | `contest-presentation_rules` ✅ |
| enrolment_rules | `contest_form[enrolment_rules]` | `contest-presentation_rules` ❌ | `contest-enrolment_rules` ✅ |

**Correction à la prédiction du prompt** : « 11 sur 12, le premier
portant un id vide ou absent » — **en réalité 10 sur 12** (les 2
champs radio ne portent jamais d'`id=`, décalés ou non) étaient
décalés, **tous les 10**, et **aucun n'avait un id vide** : le premier
champ affecté (`title`) héritait de l'id généré pour `admin_status`
(le champ radio juste avant lui), lui-même jamais utilisé puisque les
radios l'ignorent — la chaîne de décalage traverse les champs sans
id= sans s'interrompre, elle ne les saute pas. **10/10 corrigés,
0/10 encore décalé après le correctif.**

---

## 14 — Point 4 : les 10 modules sans `Module<X>.php` — mesure seule,
rien écrit

**Q1 — apparaissent-ils dans le menu ?** Oui, les 10 (`ModuleDatabase`,
`ModuleCms`, `ModuleGallery`, `ModuleLocation`, `ModuleReporting`,
`ModuleAnalytic`, `ModuleOrder`, `ModuleSticker`, `ModuleKola`,
`ModulePost`) figurent dans `dvups_role.modules` (vérifié par requête
directe), donc dans le menu latéral. Vérifié par un clic réel :
`admin/devupscms/ModuleGallery/dashboard` → `Fatal error: Uncaught
Error: Class "ModuleGallery" not found` — une fatale brute, pas même
le message propre de `Request::Module()` (qui ne s'exécute jamais, la
résolution du nom de classe échoue avant). **10 entrées de menu dont le
Dashboard est garanti mort, confirmé.**

**Q2 — le générateur sait-il produire ce fichier ?** Oui,
`core:g:module` (`builder.php:396` → `__Generator::module()` →
`__Generator::__module()`) écrit un fichier **byte-pour-byte
identique au patron réel** : `extends ModuleController`,
`__construct($route) { parent::__construct($route); }`,
`layoutView() { Genesis::renderView("admin.overview"); }`,
`helloService()`. **Mais** `module()` ne s'arrête pas là : il crée
aussi les dossiers `Entity/Datatable/Core/Controller/Form/Resource`
(inoffensif, protégé par `file_exists()`), puis boucle sur
**chaque entité du module** et appelle `__entity()`, qui écrit sans
condition (`fopen(..., 'w')`, vérifié dans
`BackendGenerator::entityGenerator()`/`controllerGenerator()`/
`tableGenerator()`/`formGenerator()` — **aucun `file_exists()` nulle
part dans ces quatre fonctions**) les fichiers `Entity/<X>.php`,
`Controller/<X>Controller.php`, `Datatable/<X>Table.php`,
`Form/<X>Form.php` de **chaque** entité.

**Q3 — un fichier minimal suffit-il, ou faut-il autre chose ?**
**Ça dépend du module, pas une réponse unique** — recensement de
l'existant pour les 10 :

| Module | Controller | Datatable | Form | Vues admin | Verdict |
|---|---|---|---|---|---|
| ModuleDatabase | 1 | 1 | 1 | 3 | scaffolding déjà complet |
| ModuleCms | 2 | 2 | 2 | 9 | scaffolding déjà complet |
| ModuleGallery | 1 | 1 | 1 | 7 | scaffolding déjà complet |
| ModuleLocation | 6 | 6 | 6 | 3 | scaffolding déjà complet |
| ModuleReporting | 3 | 3 | 3 | 8 | scaffolding déjà complet |
| ModuleAnalytic | 2 | 2 | 2 | 3 | scaffolding déjà complet |
| ModuleOrder | 2 | 3 | 2 | 3 | scaffolding déjà complet |
| ModulePost | 2 | 2 | 4 | 6 | scaffolding déjà complet |
| **ModuleSticker** | 0 | 0 | 0 | 0 | **rien côté admin** (seuls `Entity/Sticker.php` et son `FrontController` existent) |
| **ModuleKola** | 1 (pas par entité) | **0** | 6 (partiel) | 0 | **incohérent** — 5 `Core.json` d'entité mais 2 classes `Entity/` seulement, zéro `Datatable` (`Class "KolaTable" not found`, défaut déjà connu et hors mandat) |

Pour **8 des 10 modules**, `admin/<module>/layoutView()` trouverait déjà
`Resource/views/admin/layout.blade.php` **et**
`admin/overview.blade.php` en place (vérifié sur `ModuleGallery` et
`ModuleDatabase`) — **un fichier `Module<X>.php` minimal, du même
patron que `ModuleContest.php` (409 octets), suffirait vraisemblablement**
pour ces 8 — non testé (création de fichier hors mandat de ce point,
mesure seule). **Mais `core:g:module` ne doit pas être lancé pour les
produire** : il écraserait sans condition les `Controller`/`Datatable`/
`Form`/`Entity` déjà en place pour ces 8 modules (confirmé `fopen(...,
'w')`, aucune garde) — un fichier écrit à la main, calqué sur le
patron du générateur, est la voie sûre.

**`ModuleSticker` et `ModuleKola` ont besoin de plus** : Sticker n'a
aucune admin (`Controller`/`Datatable`/`Form`/vues) à brancher — un
fichier `Module<X>.php` seul pointerait `layoutView()` vers un
`admin.overview` qui n'existe pas encore, nouvelle fatale différente.
Kola a un existant incohérent (entités partiellement scaffoldées,
`Datatable` totalement absent) — nécessite une inspection entité par
entité, pas un remplissage uniforme.

**Verdict pour le prochain chantier** : « 409 octets de passe-partout
× 8 » est plausible et resterait une heure — **× 10 ne l'est pas**,
Sticker et Kola demandent un travail distinct, plus long, par module.

---

# Chantier du 26/10 (suite 2) — la garde, la dégradation propre, le JSON
indenté, les huit modules

## 15 — Point 1 : le générateur renomme, il n'écrase plus

**Recensement complet, demandé avant d'écrire quoi que ce soit** (la
liste du prompt vérifiée et complétée) :

**`dclass/generator/__Generator.php`** :

| Ligne | Écriture | Avant ce chantier |
|---|---|---|
| 502 | `Core/<Entité>Core.json` | **non gardée** |
| 531 | `Resource/js/<entité>Ctrl.js` | gardée (`if (!file_exists(...))`) |
| 540 | `Resource/js/<entité>Form.js` | gardée |
| 607 | `<module>Core.json` | gardée |
| 640 | `Module<X>.php` (classe module) | **non gardée** |
| 678 (`moduledependencies()`) | `<projet>.<module>.php` (chargeur) | **non gardée** |
| 724 | `.htaccess` | **non gardée** |
| 742 | `index.php` (patron univers B, retiré le 25/10 ailleurs dans le dépôt — signalé, pas corrigé ici) | **non gardée** |
| 802 (`dependencies()`) | `<projet>.<module>.php` (chemin alternatif) | **conditionnelle** — `if(!file_exists($filename) \|\| $rewrite)` : écrit neuf (mode `w`) si absent ou `$rewrite`, sinon **ajoute** (mode `a+`, ne perd jamais le contenu déjà là) |
| 868 (`init()`) | `config/constante.php` | **non gardée, mais fonction morte** — jamais appelée depuis `builder.php` (vérifié, aucun `__Generator::init()` dans le dispatch CLI), hors du risque réel |

**`dclass/generator/BackendGenerator.php`** :

| Ligne | Écriture | Avant ce chantier |
|---|---|---|
| 26 | `Entity/<X>.php` | **non gardée** |
| 374 | `Entity/<X>_lang.php` | **non gardée** |
| 746 | `Controller/<X>FrontController.php` | **non gardée** |
| 771 | `Controller/<X>Controller.php` | **non gardée** |
| 801 | `Datatable/<X>Table.php` | **non gardée** |
| 930 | `Core/<X>Core.json` (chemin alternatif, `coreGenerator()`) | dossier gardé, **fichier non gardé** |
| 1005 | `PostmanDoc/<X>_pmd.json` (doc API, pas du code) | dossier gardé, **fichier non gardé** |
| 1180 | `Form/<X>Form.php` | **non gardée** |
| 1335 | `Resource/views/<X>/detail.blade.php` | **non gardée** |
| 1371 | `Resource/views/admin/<X>/formWidget.blade.php` | dossier gardé, **fichier non gardé** |

**Hors périmètre d'écriture de ce chantier, signalé, non modifié** —
`admin/generator/AdminTemplateGenerator.php` écrit aussi 4 fichiers sans
garde (`layout.blade.php`, `overview.blade.php`, `index.blade.php`,
`detail.blade.php`, lignes 169/187/237/273) : même défaut, même
dossier `dclass/generator/` requis pour l'appeler mais le fichier
lui-même vit sous `admin/`, hors du périmètre d'écriture autorisé
(« `dclass/generator/`, `dclass/devups/`... »). **Non gardé après ce
chantier — à trancher séparément.**

**La garde posée** (`dclass/generator/GeneratorFileGuard.php`, nouveau
fichier, seule classe ajoutée) :
- `write($path, $content)` — si `$path` existe et que `--force` n'est
  pas actif, le renomme **en place**, suffixe `.AAAA-MM-JJ_HHMMSS.old`,
  anti-collision (`_2`, `_3`... si deux renommages tombent dans la même
  seconde — mesuré, pas supposé, voir preuve du cas 3) ;
- `writeEntityCoreJson($path, $content)` — même garde, mais déplace
  l'ancien dans un **sous-dossier `Core/_old/`**, jamais à côté du neuf ;
- `printSummary()` — liste sur la sortie standard ce qui a été renommé
  et vers où, appelée à la fin de `core:g:module` et `core:g:crud`
  (`builder.php`) ;
- `$force` — activé par `--force` en position `$argv[3]`, **convention
  reprise de `schema:update --force` (`builder.php:173-180`)**, pas
  inventée. Pour `core:g:crud`, `isset($argv[3])` signifiait jusqu'ici
  « variante java » sans condition — `--force` y est explicitement
  exclu de ce branchement pour ne pas le détourner.

**Le risque de chargement du `.old`, mesuré, pas supposé** : aucun
`glob()` dans tout `dclass/` ni `admin/` (vérifié). Aucun `scandir()`
sans filtre d'extension, **sauf un** : `Core::findentitycore()`
(`Core.php:114-133`), qui scanne `Core/` et fait `json_decode()` sur
**tout fichier qui n'est pas un dossier**, sans vérifier l'extension —
un `.old` laissé directement dans `Core/` y serait lu comme une entité
fantôme, polluant `Core::buildOriginCore()` et donc
`dvups_:update`/`dvups_role.configs`. **C'est pour ça que
`writeEntityCoreJson()` utilise un sous-dossier.**

**Correction au passage (§15 se trompait, signalé et corrigé en §16
ci-dessous) : le `!is_dir(...)` n'existait, avant le 26/10 suite 3, que
dans la branche SANS callback.** La branche à callback
(`if (is_callable($next)) { if ($entity = $next(...)) ... }`) ne
testait rien du tout — un nom de dossier comme `_old` lui était passé
tel quel. La raison pour laquelle un sous-dossier restait sans danger
jusqu'ici était un accident du callback *appelant*
(`Dvups_role::updateConfigs()`, `Dvups_role.php:137-138`, qui fait
`in_array(strtolower($core), $ents)` — toujours faux pour `_old`,
renvoie `null`), pas une protection de `findentitycore()` elle-même.
Le `is_dir()` manquant est maintenant posé dans les deux branches,
indépendamment de ce qu'un callback ferait (voir §16). Pour les
fichiers `.php` (`Controller/`,
`Datatable/`, `Form/`, `Entity/`), ni `scandir()` ni `glob()` ne les
énumère nulle part — seul le chargeur `<projet>.<module>.php` les
`require` par **nom exact**, jamais par motif ; le classmap Composer
(`composer.json` : `"classmap": ["dclass/", "src/"]`) ne retient que
les fichiers finissant en `.php`, qu'un suffixe `.old` exclut
automatiquement. **Sûr de renommer en place pour tout sauf `Core/`.**
`.gitignore` non touché — si les `.old` devaient y figurer, c'est une
décision du propriétaire, signalée ici, pas prise.

**Les quatre cas prouvés, dossier temporaire hors dépôt**
(`$SCRATCH/guard_test/sandbox`) :
1. **Fichier absent** → écrit, `VERSION-1`, zéro renommage journalisé ;
2. **Fichier présent** → renommé (`Controller.php.2026-10-26_124512.old`)
   et neuf écrit (`VERSION-2`) ; ancien relu **identique** à l'original
   (`VERSION-1`) ;
3. **Deux générations de suite**, dans la même seconde d'horloge (cas
   réel le plus défavorable, pas un cas de laboratoire) → **3 fichiers
   `.old` distincts**, aucun écrasé (`_2`, `_3` générés par la boucle
   anti-collision), contenu final `VERSION-4` correct ;
4. **`--force`** → écrase (`VERSION-FORCED`), **zéro nouveau
   renommage** (3 `.old` avant, 3 après).

**La preuve qui compte** : copie réelle de
`src/edition3ag/ModuleContest/Core/ContestCore.json` dans le dossier
temporaire, `Controller/ContestController.php` écrit à la main avec
trois méthodes métier (`activeAction()`, `winner()`, `homepage()` —
calquées sur l'exemple du prompt), puis
`(new BackendGenerator())->controllerGenerator($entity)` **exécutée
réellement** (la fonction appelée par `core:g:crud`, pas une
réimplémentation) dessus. Résultat : `Controller/ContestController.php.<horodatage>.old`
créé, **diff vide** contre l'original écrit à la main — zéro ligne
perdue — et le nouveau `ContestController.php` est le patron générique
(`listView()` seulement, ni `activeAction` ni `winner` ni `homepage`,
confirmé absents du nouveau).

**Aucune commande `core:g:*` lancée contre `src/`** à aucun moment de
ce point — uniquement le dossier temporaire hors dépôt, comme exigé.

---

# Chantier du 26/10 (suite 3) — trois trous bouchés, puis la dégradation,
le JSON indenté, les huit modules

## 16 — Point 0.a : `rename()`/`mkdir()` enfin vérifiés

**Le trou, nommé par le propriétaire** : `GeneratorFileGuard::rename()`
(privée, invoquée par `write()`) ne testait pas la valeur de retour de
`rename()`. Sous Windows, avec le projet ouvert dans un éditeur pendant
que le générateur tourne, `rename()` peut échouer — et la fonction
continuait, `write()` appelait `file_put_contents()` juste après :
**l'original était écrasé**, exactement la perte que la garde existe
pour empêcher, en plus d'une entrée mensongère dans `$renamed` qui
affirmait l'ancien sauvé.

**Corrigé** : `rename()` (devenue `renameIfPresent()`) et `mkdir()`
(dans `writeEntityCoreJson()`) sont désormais vérifiées. En cas
d'échec : **rien n'est écrit pour ce fichier-là**, un message est
imprimé immédiatement sur la sortie standard (pas seulement dans
`printSummary()`, pour rester visible même si le run s'arrête avant la
fin), et `self::$failed[]` journalise la raison. `$renamed` ne
contient désormais que des renommages **réellement réussis**. Les
autres fichiers du même run ne sont pas affectés — la garde refuse un
fichier, elle n'avorte pas la commande entière.

**La preuve, dossier temporaire hors dépôt** — trois tentatives pour
faire échouer `rename()` réellement, documentées honnêtement :
1. **Handle ouvert dans le même processus PHP** (`fopen(..., 'r+')`
   puis `rename()` direct) : **a réussi**. Windows ne bloque pas un
   renommage depuis le processus qui détient lui-même le handle ;
2. **`flock(LOCK_EX)` depuis un second processus PHP** (pour simuler
   un éditeur externe) : **a aussi réussi**. Le verrou `flock()` de PHP
   est consultatif, pas un verrou de partage NTFS au sens de
   `CreateFile` ;
3. **Attribut Windows lecture-seule** (posé via PowerShell
   `Set-ItemProperty -Name IsReadOnly`) : **a aussi réussi** — sur ce
   système, le processus PHP CLI a manifestement un droit NTFS
   suffisant pour renommer un fichier en lecture seule (le
   renommage est une opération sur l'entrée de répertoire, pas une
   écriture dans le fichier ; la lecture seule bloque l'écriture du
   contenu, pas le déplacement).

**Trois tentatives indépendantes, trois échecs à reproduire la panne
réelle** — un résultat mesuré, pas une impasse cachée. Le prompt
autorisait explicitement cette issue (« si tu n'arrives pas à
reproduire l'échec de façon fiable, dis-le et prouve le chemin
d'erreur autrement, en forçant `rename()` à échouer sur une
destination invalide »). **Fait** : `mkdir()` forcé à échouer en
plaçant un **fichier** (pas un dossier) à l'emplacement exact où
`writeEntityCoreJson()` doit créer `Core/_old/` — échec garanti,
indépendant des aléas NTFS. Résultat : `writeEntityCoreJson()` retourne
`false`, `$failed` contient 1 entrée, `$renamed` reste vide,
**le `Core.json` d'origine reste inchangé** (`{"version":1}`, confirmé
par relecture), et le message d'échec s'affiche immédiatement. Le
chemin de code qui suit un échec de `mkdir()` est **rigoureusement le
même** que celui qui suivrait un échec de `rename()` (`reportFailure()`
→ `return false` → `file_put_contents()` jamais atteint) — la preuve
vaut donc pour les deux, même si `rename()` seul n'a pas pu être cassé
directement.

## 17 — Point 0.b : la justification de `Core/_old/` était fausse,
corrigée, et le vrai trou bouché

**Ce que le §15 affirmait** : un dossier est ignoré par `!is_dir(...)`
dans `Core::findentitycore()`, « dans les deux branches (avec et sans
callback de filtrage par rôle) ». **Faux, relu dans le code avant de
corriger quoi que ce soit** : seule la branche **sans** callback avait
ce test. La branche à callback

```php
if (is_callable($next)) {
    if ($entity = $next(str_replace("Core.json", "", $file), 'entity'))
        $entitycores[] = $entity;
```

ne teste rien du tout — un nom de dossier (`_old`) est passé tel quel
à `$next()`. Ce qui rendait le sous-dossier sans danger jusqu'ici
était un **accident du callback appelant**,
`Dvups_role::updateConfigs()` (`Dvups_role.php:137-138`) :
`in_array(strtolower($core), $ents)` est toujours faux pour `_old`
(absent de la liste du rôle), donc renvoie `null`, donc rien n'est
ajouté — pas une protection de `findentitycore()` elle-même.

**Corrigé à la source** (`dclass/devups/Core.php`,
`findentitycore()`) : un test `is_dir($dir . "/" . $file)` unique,
placé **avant** les deux branches, `continue` immédiat si vrai — un
dossier n'est plus jamais une entité, indépendamment de ce qu'un
callback ferait. Le commentaire de `writeEntityCoreJson()` et le §15
ci-dessus sont corrigés avec la vraie raison, pas l'ancienne
justification inventée.

**Mesure obligatoire après ce changement** — sauvegarde préalable des
deux fichiers de `config/` **et** de `dvups_role.configs` (colonne
JSON, pas seulement les fichiers) hors du dépôt, puis
`php devups dvups_:update` : **« Data admin already uptodate »**.
Comparaison clé par clé : `dvups_configurations.php` — 0 clé perdue, 0
clé gagnée, 0 entrée modifiée ; `module_configurations.php` —
identique (`===`) ; `dvups_role.configs` — identique (`===`, 22482
octets avant et après). **Zéro différence, comme attendu** : aucun
`Core/` réel du dépôt ne contient de sous-dossier `_old/`
aujourd'hui, ce correctif est une protection pour l'avenir, pas un
changement de comportement présent.

**Incident d'environnement, sans rapport avec ce correctif** : le
serveur de dev PHP (`127.0.0.1:3334`) s'est arrêté pendant cette
session (connexion refusée, constaté en testant la régression des 7
modules) — relancé (`php -S 127.0.0.1:3334`), session admin
réauthentifiée, 7 modules remesurés : identique à la base du 26/10
(6/7 rendent, `ModulePayment` plante pour la même raison
préexistante). Noté pour ne pas confondre une coupure d'infrastructure
avec une régression de code si le même symptôme (réponse 0 octet)
réapparaît.

## 18 — Point 0.c : le journal et `--force` sur les 13 commandes
`core:g:*`, pas 2

**Le trou** : `printSummary()` et `--force` n'étaient branchés que sur
`core:g:crud` et `core:g:module` — les onze autres commandes
(`core:g:controller`, `core:g:frontcontroller`, `core:g:form`,
`core:g:table`, `core:g:entity`, `core:g:views`, `core:g:formwidget`,
`core:g:viewswidget`, `core:g:component`, `core:g:moduledependencies`,
`core:g:moduleindex`, `core:g:moduleservices`) passent toutes par
`GeneratorFileGuard` (renommage actif) mais sans que le développeur
sache jamais ce qui a été déplacé, ni puisse forcer.

**Corrigé une fois, pas douze** : le `switch ($argv[1])` de
`builder.php` est un bloc unique (`if (isset($argv[2])) { ... switch
(...) { ... } }`). `GeneratorFileGuard::$force` est positionné
**avant** le `switch`, par `in_array('--force', $argv, true)` —
recherché n'importe où dans `$argv`, pas à un index fixe, puisque la
position du flag diffère déjà d'une commande à l'autre
(`core:g:entity` utilise `$argv[3]` pour `--lang`, `core:g:crud` pour
la variante java). `GeneratorFileGuard::printSummary()` est appelé
**après** la fermeture du `switch`, une seule fois, pour les 13
commandes.

**L'exclusion « variante java » de `core:g:crud` préservée,
explicitement vérifiée** : `isset($argv[3]) && $argv[3] !== '--force'`
— si `--force` est passé en position 3, il ne bascule plus vers
`__Generatorjava::crud()`. Les anciens réglages spécifiques à
`core:g:crud`/`core:g:module` (vérification de `--force` et appel à
`printSummary()` dans chaque `case`) sont retirés, remplacés par le
mécanisme générique.

**Preuve, logique isolée** (la détection par position fixe aurait
échoué sur au moins deux cas ci-dessous) :

| Commande simulée | `--force` détecté |
|---|---|
| `core:g:crud ... --force` | OUI |
| `core:g:module ... --force` | OUI |
| `core:g:controller ... --force` | OUI |
| `core:g:entity ... --lang --force` | OUI (coexiste avec `--lang`) |
| `core:g:crud ...` (sans flag) | NON |

---

## 19 — Point 1 : « contenu non monitoré, veuillez contacter le
développeur »

**Les 12 écrans, classés en trois familles, erreur réelle citée**
(mesuré par une requête réelle contre une session admin, pas supposé) :

**Famille 1 — classe absente du disque, « non généré »** (6/12) :

| Écran | Erreur réelle |
|---|---|
| `ModuleKola/kola/list` | `Class "KolaTable" not found` (Datatable jamais écrite) |
| `ModuleComicbook/book_stats/list` | `Class "Book_statsController" not found` (Controller absent) |
| `ModuleRelation/follow/list` | `Class "FollowTable" not found` (Datatable absente) |
| `ModuleUser/relation/list` | `Class "RelationController" not found` (`Entity/Relation.php` existe, pas son Controller) |
| `ModuleNotification/notificationbroadcasted/list` | `Class "Notificationbroadcasted" not found` (entité jamais écrite — `Notification.php`/`Notificationtype.php` existent, pas elle) |
| `ModulePayment` (tableau de bord) | `Class "Payment_method" not found` (aucun Core.json, aucune Entity, aucune trace nulle part — jamais commencée) |

**Famille 2 — la classe existe, le code est faux (bug réel, pas
touché)** (6/12) :

| Écran | Erreur réelle |
|---|---|
| `ModuleWallet/wallet/list` | `class Wallet does not have a method "getReference"` |
| `ModuleComment/postcomment/list` | `class Postcomment does not have a method "getUsername"` |
| `ModulePost/post/list` | `Call to a member function hydrate() on int` |
| `ModuleAnalytic/analytic/list` | `Call to undefined method Analytic::classpath()` |
| `ModuleOrder/orders/list` | `Class "Order" not found` — **ambigu, résolu explicitement** : l'entité réelle s'appelle `Orders` (pluriel, `Entity/Orders.php` existe), `CrudTrait.php` la référence au singulier par erreur. Signature d'erreur identique à la famille 1, cause différente — voir le mécanisme ci-dessous |
| `ModuleLang/page_local_content/list` | `TypeError: Lazyloading::lazyloading(): Argument #2 ($qbcustom) must be of type ?QueryBuilder, bool given` |

**Famille 3 — entité absente du datacore** : 0/12 observée. Les 12
URLs testées viennent toutes de `module_configurations.php` (donc du
datacore par construction) — ce cas resterait théorique pour une vue
qui référencerait une entité directement, sans passer par le scan.
Nommé, pas rencontré.

**La dégradation, posée à un seul endroit, dans le cadre** —
`dclass/devups/Http/Request.php`, deux points d'entrée partagés :
- `Request::Controller()` — le choix, et pourquoi : c'est la fonction
  **unique** par laquelle arrivent les trois chemins d'un écran
  d'entité (liste, détail, formulaire), vérifié en lisant
  `Controller::views()` (`Controller.php:67-83`), leur appelant commun ;
- `Request::Module()` — même mécanisme, pour le tableau de bord de
  module (le cas `ModulePayment`, qui n'est pas un écran d'entité).

Deux niveaux de garde, pour couvrir les deux profondeurs où l'erreur
apparaît réellement (mesuré sur les 6 cas de la famille 1, pas
supposé) :
1. **`class_exists($ctrl)` avant `new $ctrl()`** — couvre le cas où le
   Controller lui-même manque (`Book_statsController`,
   `RelationController`) ;
2. **`try/catch` autour de l'appel de la méthode** — couvre le cas où
   le Controller existe mais référence une classe absente plus loin
   dans son code (`KolaTable`, `FollowTable`,
   `Notificationbroadcasted`) ou dans un template Blade compilé
   (`Payment_method`).

**`\Throwable`, pas seulement `\Error`** — correction en cours de
test, mesurée : le cas `ModulePayment` ne lève pas une `\Error` brute
mais une `Symfony\Component\Debug\Exception\FatalThrowableError`
(vérifié en lisant `vendor/symfony/.../FatalThrowableError.php` :
`extends FatalErrorException extends \ErrorException extends
\Exception`) — un handler Symfony legacy enregistré sur le pipeline de
rendu Blade convertit la fatale PHP en exception avant qu'elle
remonte. `catch (\Error $e)` manquait ce cas précis ; `catch
(\Throwable $e)` couvre les deux hiérarchies.

**Le filtre « classe absente », pas « n'importe quelle erreur »** :
`isClassNotFoundError()` ne reconnaît que les messages commençant par
`Class "X" not found` — tout le reste (`TypeError`, « method manquante
sur une classe existante », etc.) est **relancé tel quel**, jamais
transformé en message degradé. **Un raffinement a été nécessaire
après avoir trouvé `ModuleOrder` en testant** : son message correspond
au motif `Class "X" not found` alors que ce n'est **pas** un cas « non
généré » (l'entité `Orders` existe réellement). Corrigé par
`!class_exists($nom . 's')` — si une classe très proche (le pluriel du
nom manquant) existe déjà, ce n'est pas une absence de génération,
c'est un bug de nommage, et l'erreur continue de remonter sans
dégradation. Vérifié que ce filtre n'affaiblit aucun des 5 autres cas
réels (aucun n'a de variante au pluriel qui existe).

**L'écran dégradé reste un écran du back-office** : nouveau fichier
`dclass/devups/Resource/views/not_monitored.blade.php` (dans le
périmètre d'écriture — aucune vue de `src/` ni `admin/views/`
touchée), `@extends('layout.layout_module')` — le layout partagé
réel (`admin/views/layout/layout_module.blade.php`), pas un nom
halluciné : la première tentative utilisait `admin.layout_module`,
qui n'existe pas (le namespace `admin.*` dans ce dépôt désigne les
vues **par-module** sous `Resource/views/admin/`, jamais
`admin/views/`lui-même — `layout.*` est le nom qui pointe vers
`admin/views/layout/`, confirmé en relisant comment
`ModuleCms/.../admin/layout.blade.php` résout `layout.layout`).
Résultat : menu latéral, fil d'Ariane, en-tête de module — pas de page
blanche, pas de `var_dump`, pas de trace PHP. Le message exact, centré,
suivi en petit de `{{ entité }} / {{ module }}` (ou seulement le
module pour un tableau de bord). **Rendu via `$blade->view()->file(...)`**
(l'API Laravel qui accepte un chemin absolu plutôt qu'un nom résolu
par convention), donc sans dépendre d'un fichier `Resource/views`
propre à chaque module cassé.

**Aucune fuite de trace ni de chemin serveur** vérifiée sur les 6
écrans de la famille 1 (grep de `C:\spacekola`, `Fatal`, `Stack trace`
et des noms de classe internes — absents).

**Les trois chemins vérifiés sur un même cas** (`ModuleUser/relation`,
Controller absent, donc identique sur les trois) :
`relation/list`/`relation/detail`/`relation/form` → message affiché
sur les trois. Et, pour montrer que la distinction famille 1/famille 2
tient **par chemin, pas par entité** : `ModuleKola/kola/list`
(`KolaTable` absente, famille 1, message affiché) contre
`ModuleKola/kola/form` (`Call to undefined method Kola::classpath()`,
famille 2, plante toujours — la même entité, deux chemins, deux
verdicts différents, chacun correct pour sa propre erreur).

**Mesure avant/après, les 12 écrans** : 6/6 famille 1 passent de
« fatale brute » à « message affiché » ; 6/6 famille 2 plantent
**toujours**, comme attendu, erreur réelle inchangée.

**Deux captures, atteintes par un clic dans le menu** (envoyées dans
la session) : `ModuleKola` → « Kola » déplié → lien « Kola » cliqué →
URL `admin/spacekola/ModuleKola/kola/list` confirmée ; `Paiements` →
déplié → lien « Dashboard » cliqué → URL
`admin/shopping/ModulePayment/dashboard` confirmée.

**Régression découverte en testant, sans rapport avec ce point** :
`admin/edition3ag/ModuleContest/evaluation/list` plante
(`Call to undefined method Evaluation::classpath()`) — **vérifié
pré-existant** en rejouant la même requête avec `Request.php` remis à
l'état d'avant ce point (`git stash`) : même erreur, donc pas une
régression introduite ici, un écran jamais testé jusqu'à maintenant.
Famille 2, hors mandat.

**Régression, 7 modules de référence + échantillon d'écrans qui
fonctionnaient déjà** : identiques, sauf `ModulePayment` qui passe de
« plante » à « message dégradé » — une amélioration attendue, pas une
régression.

---

## 20 — Point 2 : le datacore JSON garde son indentation

**Recensement, demandé avant d'écrire** : exactement 3 écritures de
`*Core.json` sans drapeau, confirmées par `grep` sur
`dclass/generator/` — `__Generator.php:502` (entité),
`__Generator.php:606` (module), `BackendGenerator.php:922`
(`coreGenerator()`, chemin alternatif). **Pas de quatrième** : les 3
autres `json_encode()` du même dossier ne touchent pas un `Core.json`
(`Genesis::json_encode()` — réponse API JSON, sans rapport ;
`BackendGenerator.php:945` — corps d'une requête cURL ;
`BackendGenerator.php:1000` — `PostmanDoc/<X>_pmd.json`, documentation
Postman, pas un fichier lu par le scan du datacore, hors mandat de ce
point). `android/__Generatorjava.php:378` écrit aussi un `<module>Core.json`
sans drapeau ni garde — hors des deux fichiers nommés par le prompt
pour ce chantier, signalé, non touché.

**Corrigé, les trois** : `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
| JSON_UNESCAPED_UNICODE` sur les 3 sites. Même forme que les 29
`module<x>Core.json` déjà ré-indentés à l'étape 4c (indentation à 4
espaces, le défaut de `JSON_PRETTY_PRINT`).

**Vérifié avant d'écrire** (seule régression possible) : les **4**
lectures de `*Core.json` dans tout le dépôt
(`Core.php:72/101/175/128`, `__Generatorjava.php:144`) passent
**toutes** par `json_decode(file_get_contents(...))`, sans
`trim`/`explode`/regex nulle part — le passage au multiligne ne peut
rien casser.

**Preuve, dossier temporaire hors dépôt** : entité factice avec un
namespace (`\`) et des caractères accentués (`é`, `à`), rendue à la
fois **avec** et **sans** les drapeaux pour comparer directement :

```
sans drapeaux : {"name":"evenement", ... "label":"Événement à thème", ..., "path":"edition3ag\/ModuleContest\/", "namespace":"edition3ag\\ModuleContest\\Entity\\", ...}

avec drapeaux (indenté) :
{
    "name": "evenement",
    "label": "Événement à thème",
    "path": "edition3ag/ModuleContest/",
    "namespace": "edition3ag\\ModuleContest\\Entity\\",
    ...
}
```

Confirmé : `path` (avec `/`) passe de `\/` à `/` ; `label` (accentué)
passe de `Événement...` à `Événement à thème` lisible ; le
`namespace` reste échappé `\\` **dans les deux cas** — c'est la
syntaxe JSON elle-même qui l'exige pour un antislash littéral, aucun
drapeau ne change ce point précis (vérifié pour ne pas le confondre
avec un bug). `json_decode()` relit l'objet produit **identique** à
l'objet avant écriture (`==`).

**`dvups_:update` après sauvegarde des deux fichiers de `config/` et
de `dvups_role.configs`** : « Data admin already uptodate » (attendu —
ce point change le générateur, aucun `Core.json` réel n'a été
régénéré). Comparaison : `dvups_configurations.php` identique (`===`),
`module_configurations.php` identique (`===`), `dvups_role.configs`
identique (`===`). **Zéro différence.**

**Aucun des 87 `Core.json` d'entité existants reformaté** — ce point
change uniquement le générateur, comme demandé.

---

## 21 — Point 3 : les huit `Module<X>.php`, écrits à la main

**Patron recopié** du `$moduleclass` réel de `__Generator.php:618-640`
(byte pour byte, vérifié par `diff` contre
`src/edition3ag/ModuleContest/ModuleContest.php` avec le nom de
classe substitué — y compris les fins de ligne CRLF et les espaces en
fin de ligne, hérités du fait que `__Generator.php` lui-même est
enregistré en CRLF, donc le literal de chaîne qu'il contient aussi).

**Trois vérifications par module, avant d'écrire** :

| Module | Projet | `overview.blade.php` sous `admin/` | Chargeur `<projet>.module<x>.php` |
|---|---|---|---|
| ModuleDatabase | devups | présent | `devups.moduledatabase.php`, chargé par `requires.php` |
| ModuleCms | devupscms | présent | `devupscms.modulecms.php`, chargé |
| ModuleGallery | devupscms | présent | `devupscms.modulegallery.php`, chargé |
| ModuleLocation | devupscms | présent, sous `Resource/` (bien orthographié — confirmé, pas `Ressource/`) | **présent sous un autre préfixe** : `marketplace.modulelocation.php`, chargé |
| ModuleReporting | devupscms | présent | `devupscms.modulereporting.php`, chargé |
| ModuleAnalytic | devupsfront | présent | `devupsfront.moduleanalytic.php`, chargé |
| ModuleOrder | shopping | présent | **présent sous un autre préfixe** : `buyamsellam24.moduleorder.php`, chargé |
| ModulePost | spacekola | présent | `spacekola.modulepost.php`, chargé |

**Correction à mon propre premier passage** : une recherche par préfixe
littéral (`<projet>.<module>...`) déclarait `ModuleLocation` et
`ModuleOrder` sans chargeur — **faux**, leurs chargeurs existent sous
un préfixe différent (`marketplace`/`buyamsellam24`, des alias de
projet visiblement hérités d'une genèse antérieure), et sont bien
`require`-és dans `src/requires.php`. Trouvé en lisant `requires.php`
directement plutôt qu'en devinant le nom de fichier.

**`ModuleDatabase` seul n'a réellement aucun chargeur**, ni sous son
propre préfixe ni sous un alias — vérifié que ça ne bloque rien quand
même : ses classes déjà existantes (`Request_historyController`, par
exemple) sont déjà chargeables via le classmap Composer
(`composer.json` : `"classmap": ["dclass/", "src/"]`) **indépendamment**
de `requires.php` — confirmé par `class_exists()` direct, sans jamais
passer par le chargeur absent.

**Trouvaille bloquante, mesurée, pas supposée** : un fichier **neuf**
(écrit par cette session, jamais vu par Composer) n'est **pas**
automatiquement chargeable — prouvé avec une classe factice
(`_AutoloadProbe`) : `class_exists()` renvoie `false` tant que
`composer dump-autoload` n'a pas tourné. **`composer dump-autoload`
exécuté une fois**, après avoir écrit les 8 fichiers — `vendor/` est
ignoré par git (`git check-ignore` confirmé), donc **zéro diff dans
le dépôt**, aucune commande `core:g:*` impliquée, rien dans `src/`
modifié par cette commande. Les 8 classes sont devenues chargeables
immédiatement après (`class_exists()` → `true` pour les 8).

**Mesure, module par module, par le lien du menu (`/dashboard`)** :

| Module | Avant (établi le 26/10) | Après |
|---|---|---|
| ModuleDatabase | `Class "ModuleDatabase" not found` | **rend** (93 541 octets) |
| ModuleCms | `Class "ModuleCms" not found` | **rend** (94 764 octets) |
| ModuleGallery | `Class "ModuleGallery" not found` | **rend** (91 621 octets) |
| ModuleLocation | `Class "ModuleLocation" not found` | **rend** (95 680 octets) |
| ModuleReporting | `Class "ModuleReporting" not found` | **rend** (96 634 octets) |
| ModuleAnalytic | `Class "ModuleAnalytic" not found` | **rend** (92 562 octets) |
| ModuleOrder | `Class "ModuleOrder" not found` | **rend** (97 815 octets) |
| ModulePost | `Class "ModulePost" not found` | **rend** (93 353 octets) |

**8 sur 8 se réveillent avec les 409 octets de passe-partout** — le
scénario optimiste envisagé le 26/10 se confirme intégralement pour
ces huit modules (`ModuleSticker` et `ModuleKola`, exclus du mandat,
non touchés).

**Régression, 7 modules de référence + échantillon des 12 écrans
classés au point 1** : identiques — `ModulePayment` toujours dégradé
(pas une régression, le comportement voulu), les 6 écrans « famille 1 »
toujours dégradés, `php -l` propre sur les 8 nouveaux fichiers et sur
les fichiers du cadre touchés plus haut dans ce chantier.

---

# Chantier du 26/10 (suite 4) — le socle visuel : jetons, contradictions
CSS, icônes, layout

## 22 — Point 0 : les six captures de référence, avant toute ligne de CSS

**Trois écrans** choisis pour couvrir les trois familles de rendu,
contest id=7 (même jeu de données tout du long) :
1. **Liste** — `admin/edition3ag/ModuleContest/contest/list` ;
2. **Formulaire** — **établi en capturant, pas supposé** : `contest/form`
   n'est **pas** une page navigable — `ContestController::formView()`
   retourne un tableau `['success'=>true,'form'=>...]` consommé en
   AJAX par un bouton `onclick="model._edit(...)"` qui injecte le HTML
   dans `#dialog-container` (vérifié : un `curl` direct sur
   `contest/form?id=7` renvoie 0 octet). La capture reproduit donc le
   **geste réel** : ouvrir `contest/list`, cliquer le bouton « edit »
   de la ligne id=7 (`<button onclick="model._edit('7','contest',this)">`),
   capturer la modale ouverte — c'est le seul formulaire qu'un
   utilisateur voit jamais à l'écran ;
3. **Détail avec datatables liés** —
   `admin/edition3ag/ModuleContest/contest/detail?id=7` (page réelle,
   confirmée par `curl`) — trois tables composées en ligne (Evaluation
   visible, filtrée sur l'entité jointe), conforme au patron cité.

**Six captures, 1440×900 et 390×844**, aucune donnée personnelle
(contest id=7, déjà utilisé dans les sessions précédentes, pas une
fiche utilisateur). Conservées dans
`visual_baseline/01_list_1440_avant.jpg` … `06_detail_390_avant.jpg`
(hors dépôt, horodatage + MD5 relevés pour la comparaison finale).
**Repli méthodologique noté** : les captures du navigateur sont des
JPEG compressés — un MD5 identique prouverait l'identité, mais un MD5
différent ne prouve pas à lui seul une différence visuelle (la
compression peut varier d'un rendu à l'autre même sans changement
réel) ; la preuve définitive sera l'inspection visuelle côte à côte,
le MD5 n'est qu'un indice rapide.

**Observation en passant, hors mandat de ce point** : à 390 px, la
modale du formulaire ne remplit pas la largeur du viewport (zone grise
visible à droite, overflow horizontal) — défaut déjà présent dans
l'état de référence, pas introduit par ce chantier, signalé pour
mémoire, pas corrigé ici (pas dans les quatre défauts du point 4).

---

## 23 — Point 1 : un étage de jetons, purement additif

**Correction au passage du prompt, mesurée avant d'écrire** : « `main.css`
n'a aucune propriété personnalisée (`--` : 0 occurrence) » est **faux**
— `main.css` déclare **30** propriétés `--` sur `:root`
(`--primary`, `--success`, `--danger`, `--warning`, `--blue`,
`--breakpoint-*`, etc.), le jeu de variables que Bootstrap 4 génère
lui-même. La substance du point tient quand même : ce sont des
variables **utilitaires du framework**, pas un vocabulaire de jetons
pour ce back-office précis (aucun `--sidebar-width`,
`--header-height`, etc.) — il n'y a donc rien à défaire pour les
jetons de **ce** chantier, mais les noms choisis ci-dessous sont
préfixés `--dv-` pour ne jamais entrer en collision avec les 30 déjà
là.

**Nouveau fichier** `admin/assets/css/dv_theme.css` — un seul bloc
`:root`, aucune règle de sélecteur. Chargé entre `main.css` et
`dv_style.css` dans `layout.blade.php`.

**Chaque valeur relevée dans `main.css`, source commentée en regard** :

| Jeton | Valeur | Source dans `main.css` |
|---|---|---|
| `--dv-sidebar-width` | `250px` | **pas** le défaut du thème (`280px`, `.app-sidebar{width:280px}`) — la valeur **affichée aujourd'hui**, parce que `dv_style.css` la surcharge déjà à `250px`. C'est justement cette surcharge incomplète (le pied de page encore à 280) qu'un seul jeton doit résoudre au point 2 |
| `--dv-header-height` | `60px` | `.app-header{height:60px}` |
| `--dv-radius` | `.25rem` | rayon le plus fréquent (33 occurrences), celui de `.card` |
| `--dv-spacer` | `1rem` | unité Bootstrap, `.p-3`/`.m-3{padding\|margin:1rem}` |
| `--dv-color-bg-app` | `#f1f4f6` | `.app-container{background:#f1f4f6}` |
| `--dv-color-bg-card` | `#fff` | `.card{background-color:#fff}` |
| `--dv-color-border` | `rgba(26,54,126,.125)` | `.card{border:1px solid rgba(26,54,126,.125)}` |
| `--dv-color-text` | `#495057` | `body{color:#495057}` |
| `--dv-color-text-muted` | `#6c757d` | `.text-muted{color:#6c757d}` |
| `--dv-color-primary` | `#3f6ad8` | `:root{--primary:#3f6ad8}` (généré par le thème) |
| `--dv-color-success` | `#3ac47d` | `:root{--success:#3ac47d}` |
| `--dv-color-danger` | `#d92550` | `:root{--danger:#d92550}` |
| `--dv-color-warning` | `#f7b924` | `:root{--warning:#f7b924}` |
| `--dv-font-family` | pile système (`-apple-system, …`) | `body{font-family:…}`, aucune police web externe chargée |
| `--dv-font-size-sm/base/lg/xl` | `.875rem / .88rem / 1.25rem / 2rem` | 4 crans, les tailles les plus fréquentes mesurées (10/10/12/3 occurrences) |
| `--dv-shadow-sm` / `--dv-shadow-md` | `.shadow-sm` / `.shadow` | 2 des 3 niveaux Bootstrap déjà fournis — `.shadow-lg` écarté, rarement utile en back-office |

**Preuve que la couche est purement additive** : `dv_theme.css` ne
contient **aucun sélecteur**, seulement des déclarations sur `:root` —
par construction, un fichier qui n'adresse aucun élément ne peut rien
changer visuellement tant que rien ne le consomme (`var(--dv-…)`
n'apparaît nulle part ailleurs à ce stade). Vérifié, pas seulement
supposé : lecture des styles **calculés** avant/après chargement
(`getComputedStyle`) sur la liste — largeur de la sidebar (`250px`),
hauteur de l'en-tête (`60px`), rayon de carte (`4px` = `.25rem`), fond
de carte (`rgb(255,255,255)`), police du corps — **identiques**, et
`--dv-sidebar-width` lisible sur `:root` confirmant le fichier chargé.
**Repli méthodologique** : la comparaison JPEG brute (taille de
fichier, MD5) s'est révélée bruitée — le même viewport 1440×900
produit des captures de poids différent d'une prise à l'autre (mise à
l'échelle de l'outil de capture, pas du contenu), inutilisable comme
preuve fine. La preuve retenue est la **garantie structurelle** du
fichier (zéro sélecteur) combinée à l'**égalité des styles calculés**
— plus rigoureuse qu'un diff de pixels, qui aurait pu mentir dans les
deux sens (faux positif par antialiasing, faux négatif par
compression). Inspection visuelle de la liste et du formulaire à
1440 px : structurellement identiques à l'œil.

---

## 24 — Point 2 : les trois contradictions de `dv_style.css`, levées

**(a) 250 contre 280** : confirmé exactement comme mesuré par le
prompt — `main.css` fixe `.app-sidebar{width:280px}` (le défaut du
thème), `dv_style.css` le surcharge à `250px` dans 3 des 4 usages, et
laisse le 4e (`.app-main__outer .app-wrapper-footer{left:280px;
width:calc(100% - 280px)}`) au défaut jamais surchargé. Les 4 usages
reliés à `var(--dv-sidebar-width)` (250px).

**Mesure explicite, bord gauche du pied de page** (`getBoundingClientRect()`,
1440 px, via `git stash` pour isoler l'état « avant ») :
- **avant** : `footer.left = 280px`, `sidebar.width = 250px` —
  **30 px** d'écart, confirmé ;
- **après** : `footer.left = 250px`, `sidebar.width = 250px` —
  **alignés**.

**(b) `.torder` déclarée deux fois** : établi **avant** de corriger
que la comparaison visuelle demandée est **impossible** — le HTML qui
porterait cette classe est commenté dans
`dclass/devups/Datatable/Datatable.php` (lecture seule, confirmé par
`grep`), `.torder` n'existe donc sur **aucun** écran rendu aujourd'hui
(vérifié : zéro élément `[class*="torder"]` dans le DOM de la liste
et du détail). Décision par complétude de la déclaration, pas par
comparaison visuelle (documentée comme telle, pas déguisée en mesure
qu'elle n'est pas) : gardée celle avec `z-index`/`padding`
(nécessaires pour positionner et rendre cliquables les deux flèches
haut/bas du HTML commenté), supprimée celle qui n'avait ni l'un ni
l'autre.

**(c) `thead` sticky sans `top`** : confirmé — glissait sous l'en-tête
fixe. Le commentaire `/*top: 98px;*/` remplacé par
`calc(var(--dv-header-height) + 44px)` — **mesuré sur la page réelle,
pas deviné** : `.align-items-start` (la ligne de filtre, elle-même
sticky à `top: var(--dv-header-height)`) mesure **43.1875px** de haut
(`getBoundingClientRect()`), donc le `thead` doit s'ancrer à la
hauteur d'en-tête **plus** cette hauteur-là pour ne pas chevaucher la
ligne de filtre — pas à la hauteur d'en-tête seule. Vérifié après
coup (`getComputedStyle`) : `thead.top = 104px` (60+44),
`align-items-start.top = 60px`. **Écart au prompt, documenté** : la
lettre de l'instruction (« même jeton, même mesure ») suggérait de
réutiliser la seule valeur de 60px pour les deux — la mesure réelle
montre que ça superposerait le `thead` et la ligne de filtre
(résultat visuellement vérifié par un défilement de la page réelle,
capture prise, aucun chevauchement).

**Bizarreries tranchées** :
- **keyframes** : `-webkit-animation: loading …` référençait un
  keyframe `loading` **introuvable** nulle part dans le dépôt
  (`main.css`, `dv_style.css`, ni ailleurs sous `admin/assets/` —
  vérifié par grep). `spin`, lui, **est** défini dans `main.css`.
  Correction au passage du prompt : puisque la propriété standard
  `animation: spin` (valide) coexistait déjà avec la ligne
  `-webkit-animation: loading` (invalide), et que tout navigateur
  actuel (webkit ou non) applique la propriété standard, **le
  chargeur tournait déjà dans la quasi-totalité des cas** — pas « la
  moitié » comme le prompt le supposait. Corrigé quand même, les deux
  lignes disent maintenant `spin` ;
- **couleurs en dur** : les **trois** — `whitesmoke`/`#f5f5f5` (seule
  occurrence de cette teinte dans `main.css` : un état `:hover` du
  plugin tiers FullCalendar, aucun rapport avec une couleur de
  palette), `rgba(51,122,183,0.3)` (confirmé : Bootstrap 3, ne
  correspond ni à `--primary` #3f6ad8 ni à `--info` #16aaff),
  `#004282` (aucun équivalent trouvé) — **n'ont aucun équivalent dans
  le thème**. Signalées en commentaire dans le CSS et ici, **laissées
  telles quelles** : décision de palette qui appartient au
  propriétaire, par la règle 5.

**Régression, fonctionnelle** : les 7 modules de référence, les 6
écrans dégradés du chantier précédent, et les 8 nouveaux
`Module<X>.php` — identiques à avant ce point.

---

## 25 — Point 3 : convergence des icônes sur les deux polices réellement
chargées

**Mesuré, confirmé** : seules `fa-solid-900.*` (Font Awesome 5, solid
uniquement) et `Pe-icon-7-stroke.*` existent dans
`admin/assets/fonts/`. `mdi`/`linearicons` : 0 occurrence de fichier
de police dans le dépôt, confirmé.

**Inventaire exhaustif, avant toute correction** (`grep` sur
`admin/views/` et `src/**`, lecture seule pour `src/`) :

| Famille | `admin/views/` | `src/` (lecture seule) |
|---|---|---|
| `mdi mdi-*` | 3 occurrences, 2 classes (`mdi-settings`, `mdi-logout`) — `navbartop.blade.php` uniquement | 0 |
| `pe-7s-*` | 6 occurrences, 5 classes | 1 (`pe-7s-angle-down`) |
| `fa fa-*` (syntaxe FA4) | 17 occurrences, 11 classes | 40 classes uniques |
| `fas/far/fab/fal fa-*` (syntaxe FA5) | 4 occurrences, 4 classes | 32 classes uniques |

Aucune autre famille trouvée.

**1. `mdi` remplacées** — `navbartop.blade.php` : `mdi-settings` →
`fas fa-cog`, `mdi-logout` → `fas fa-sign-out-alt` (les deux glyphes
confirmés présents dans `main.css`). **Capture avant/après envoyée
dans la session** : avant, aucune icône (espace vide) devant
« Settings »/« Logout » ; après, l'engrenage et la flèche de sortie
visibles.

**2. Syntaxe FA4 avec police FA5 — mesurée, pas supposée** :
`main.css` contient bien `.fa,.fas{font-family:'Font Awesome 5
Free';font-weight:900}` — une règle de compatibilité qui fait hériter
la syntaxe FA4 (`fa fa-x`) du même rendu que `fas fa-x`. Vérifié pour
les **12** classes FA4 citées ou trouvées dans `admin/views/`
(`angle-down`, `share`, `ellipsis-v`, `calendar`, `star`, `edit`,
`angle-right`, `angle-left`, `angle-double-right`,
`angle-double-left`, `eye`, `external-link-alt`) : **les 12** ont leur
glyphe `.fa-x:before{content:...}` défini dans `main.css`. Confirmé à
l'écran (pas seulement par lecture de règle) : le bouton « edit » et
le chevron du menu déroulant, zoomés sur une capture réelle, affichent
bien leurs icônes. **12 sur 12 s'affichent déjà, 0 corrigée** — rien à
faire, contrairement à ce que le prompt anticipait.

**3. `fa-w-6`** — retiré de `sidebar.blade.php:33`. **Trouvaille non
citée par le prompt** : la même classe, sur le même bouton
(`mobile-toggle-header-nav`), existe **aussi** dans
`navbartop.blade.php:29` — corrigée de la même façon, par cohérence
(même défaut exact, même solution déjà actée).

**4. Icône de module dé-imbriquée** — `sidebar.blade.php:54-56`
portait un `<i class="metismenu-icon">` contenant un second `<i
class="{{ $module['icon'] }}">` imbriqué. Réduit à une seule balise
`<i class="metismenu-icon {{ $module['icon'] ?? 'fas fa-fw fa-cog' }}">`,
conforme au patron déjà utilisé ailleurs dans le même fichier (ligne
64 : `<i class="metismenu-icon"></i>` seule). Vérifié dans le HTML
rendu (`<i class="metismenu-icon fas fa-user-shield">`, etc. — une
seule balise) et à l'écran : les icônes de module ont maintenant la
même taille/position que l'icône « Dashboards » codée en dur, qui
servait de référence.

**Aucune police ajoutée ni supprimée.**

**Régression, fonctionnelle** : les 7 modules de référence —
identiques.

---

## 26 — Point 4 : quatre défauts de layout qui se voient tout de suite

**4a — `<title>` figé.** Tous les 29 modules/écrans partageaient
`<title class="mdi mdi-sort-variant">Dashboard | Dvups Admin</title>` —
deux onglets ouverts étaient indiscernables, et la `class` sur un
`<title>` n'a aucun effet (inerte, supprimée). `@yield('title')` ne
suffisait pas : chaque module (~19) a sa **propre** copie de
`admin/layout.blade.php` sous `src/` (lecture seule, hors périmètre),
chacune avec `@section('title', 'Page Title')` codé en dur — la
remplacer aurait exigé de toucher `src/`. Résolu entièrement dans
`layout.layout` (le seul fichier global réellement traversé par tous
les écrans), en PHP brut : `$moduledata['label']` (global déjà peuplé
par `Controller::views()`/`viewsModule()` à chaque rendu, vu au
chantier précédent) + le 4ᵉ segment de l'URL comme nom d'entité
(aucun global ne le porte), au format `<Entité> · <Module> |
<PROJECT_NAME>`, repli propre sur `Dashboard` si les deux manquent.
Vérifié par `curl` sur 7 URLs couvrant dashboard global, dashboard de
module, liste, détail, et un écran dégradé du chantier précédent —
tous corrects, y compris `ModulePayment` (le bon chemin est
`admin/shopping/ModulePayment/...`, pas `admin/boutique/...` comme le
libellé français du menu le suggérerait — erreur de ma première
tentative de test, pas une régression). Deux onglets distincts
capturés via le navigateur, titres bien différenciés.

**4b — `mm-active` figé sur « Dashboards ».**
`sidebar.blade.php:43` portait `class="mm-active"` en dur sur le lien
global, actif sur **toutes** les pages quelle que soit la page
courante. Mesure avant d'écrire, comme demandé : lu le bundle
`admin/assets/scripts/main.js` (pas deviné) — les constantes réelles
de metisMenu sont `ACTIVE:"mm-active"`, `SHOW:"mm-show"`,
`COLLAPSE:"mm-collapse"` ; au chargement, le plugin **cherche** les
éléments qui portent déjà `mm-active`
(`.find(parentTrigger+"."+ACTIVE)`) pour déplier leurs ancêtres — il
ne l'efface jamais, il ne fait que s'en servir, et n'ajoute
`mm-collapse` (replié) qu'aux éléments qui n'en portent pas. Poser la
classe côté serveur est donc sans danger. Seconde mesure, faite en
cours de route par un `grep` de `main.css` : la règle de surbrillance
visuelle réelle est `a.mm-active{...}` — cible le `<a>`, pas le
`<li>`. D'où un placement scindé : le `<li>` du module (le
« parentTrigger » qui enveloppe le `<ul class="mm-collapse">`) pour le
dépliage JS, le `<a>` de chaque lien feuille (dashboard du module,
chaque entité, et le lien global) pour la surbrillance visuelle. Un
closure `$dv_is_current($path)` compare le chemin courant
(`\Router::$uri`) au chemin de chaque lien, avec la même logique de
préfixe que les `href` déjà générés juste à côté, pour rester
cohérent avec eux. Vérifié sur le HTML rendu (4 URLs : dashboard
global, dashboard de module, liste d'entité, dashboard de module) —
exactement 2 occurrences de `mm-active` par page (le `<li>` du module
courant + le `<a>` de la page courante), le lien global « Dashboards »
correctement **non actif** (`class=""`) dès qu'on est sur une page de
module — le bug exact que corrige ce point. Confirmé à l'écran : le
module « Concours » se déplie automatiquement, « Contest » est
surligné.

**4c — deux artefacts de démo ArchitectUI.**
`<meta name="description">` disait encore « This is an example
dashboard created using build-in elements and components. » — remplacé
par `<?= PROJECT_NAME ?> — back-office d'administration`. Le
`<meta name="viewport">` portait `maximum-scale=1, user-scalable=no`
(bloque le pincer-zoomer — un souci d'accessibilité sur un
back-office consulté depuis un téléphone) — retiré ; vérifié par
lecture du DOM dans le navigateur (`document.querySelector('meta
[name="viewport"]').content` → `"width=device-width, initial-scale=1,
shrink-to-fit=no"`, zoom tactile restauré).

**4d — doctype et `http-equiv` redondants.**
`<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">` →
`<!DOCTYPE html>`. Décision conservatrice sur les trois
`<meta http-equiv>` qui suivaient : seul `Content-Type" content="text
/html; charset=utf-8"` double littéralement `<meta charset="utf-8">`
déjà présent une ligne plus bas — supprimé. `X-UA-Compatible` (mode
IE) et `Content-Language` ne font doublon avec rien de déjà déclaré —
conservés, aucune raison mesurée de les retirer. L'attribut
`xmlns="http://www.w3.org/1999/xhtml"` sur `<html>` n'était pas visé
par le prompt (qui ne parle que du doctype et des `http-equiv`) —
laissé tel quel, hors périmètre de ce point. « Les deux doctypes sont
en mode standards, donc rien ne devrait bouger » — vérifié, pas
supposé : les trois écrans de référence (liste, formulaire en
modale via `model._edit('7','contest',this)`, détail) capturés après
coup à l'écran, aucun décalage de box-model observé, notamment sur le
formulaire (le plus susceptible d'un tel décalage).

**Régression, fonctionnelle** : les 7 modules de référence et les
écrans dégradés du chantier précédent — identiques. **Correction en
cours de route** : une première mesure sur `ModulePayment/dashboard`
avait montré une fatale brute (`View [admin.overview] not found`) —
rejouée 4 fois de suite ensuite, systématiquement absente (200, page
complète de 90 836 o, message dégradé « non monitoré » affiché comme
attendu, identique au comportement acté au chantier précédent, aucune
fatale). La première observation était très probablement un artefact
transitoire du `rm -f cache/views/*.php` lancé juste avant (régénération
du cache de vues Blade en cours au moment de la requête) — pas une
régression réelle, corrigé dans cette note pour ne pas laisser une
mesure non reproductible comme conclusion.

---

## 27 — Point 5 : mesure du poids, zéro diff de code

**Chargés sur chaque page, sans exception** (`layout.blade.php`,
global) :

| Fichier | Poids (transfert réel, mesuré) | Ligne |
|---|---|---|
| `admin/assets/scripts/main.js` | **1 031 125 o** (~1 007 Kio) | `layout.blade.php:138` |
| `admin/main.css` | 347 435 o | `layout.blade.php:44` |
| `admin/assets/plugins/jquery/jquery.min.js` | 86 655 o | `layout.blade.php:140` |
| `admin/assets/plugins/sweetalert2/sweetalert2.min.css` | 15 301 o | `layout.blade.php:45` |
| `admin/assets/plugins/notify.min.js` | 14 080 o | `layout.blade.php:141` |
| `admin/assets/css/dv_style.css` | 6 230 o | `layout.blade.php:48` |
| `admin/assets/css/dv_theme.css` | 3 040 o | `layout.blade.php:47` |
| polices FA5 + Pe-7-stroke | 79 512 o + 58 856 o | `main.css` (`@font-face`) |

**Chargés seulement là où c'est utilisé** (vérifié par `grep` sur
`admin/views/` et `src/**`, un fichier/une ligne par famille, lecture
seule pour `src/`) :

| Plugin | Poids sur disque | Où (exemples, pas exhaustif) |
|---|---|---|
| `plugins/tinymce/tinymce.bundle.js` | 1 556 934 o | `src/edition3ag/ModuleContest/Resource/views/admin/form.blade.php:137` (+ 4 autres modules : Cms, Reporting, Tree, Comicbook) |
| `plugins/codemirror/lib/codemirror.js` | 407 920 o | `src/devupscms/ModuleNotification/Resource/views/admin/emailmodel/form.blade.php:114` (+ ModuleReporting) |
| `plugins/jquery-ui-1.12.1/jquery-ui.min.js` | 251 809 o | `src/edition3ag/ModuleContest/Resource/views/admin/form.blade.php:138` (+ Cms, Reporting, Comicbook, Catalog) |
| `plugins/vue.min.js` | 93 680 o | `src/devups/ModuleDatabase/Resource/views/admin/overview.blade.php:36` (+ 5 autres modules) |
| `plugins/colorpicker/js/evol-colorpicker.min.js` | 10 832 o | `src/devupscms/ModuleData/Form/StatusForm.php:56` (seul usage) |

Aucun de ces cinq n'apparaît dans `layout.blade.php` ni dans aucun
fichier du périmètre d'écriture de ce chantier — tous chargés depuis
`src/` (lecture seule), module par module, jamais globalement.
Confirmé en pratique, pas seulement par lecture de code : le réseau du
navigateur sur l'écran de liste (aucun de ces cinq fichiers) ne montre
**aucune** de ces requêtes.

**Trouvaille mesurée, non anticipée par le prompt** : sur l'écran de
formulaire (`contest/form`, ouvert en modale comme établi au point 0),
ni `tinymce.bundle.js` ni `jquery-ui.min.js` ne sont chargés, **alors
que** `form.blade.php:137-138` les demande bien via
`Form::addjs()`/`Form::addcss()`. Vérifié sur le corps brut de
`/admin/api/contest/form?1&id=7` (`curl`, lecture seule) : zéro
occurrence de `tinymce`/`jquery-ui`/`codemirror`. Explication probable
(non vérifiée plus avant, hors mandat « mesure, ne corrige pas ») :
les appels `addjs()`/`addcss()` alimentent une section Blade
(`@yield('jsimport')`/`@yield('cssimport')`) rendue par
`layout.blade.php` autour du `@yield('content')` — une section absente
quand la vue est rendue seule, en fragment AJAX, pour la modale.
Signalé, pas corrigé : un correctif toucherait soit `src/` (hors
périmètre), soit le mécanisme de rendu AJAX des modales (hors mandat
de ce point, qui est mesure seule).

**Poids réel, requête par requête, mesuré dans le navigateur**
(`performance.getEntriesByType`, `transferSize` — pas le poids sur
disque, le poids réseau réel) :

- **Écran liste** (`contest/list`, à froid) : **1 808 969 o, 17
  requêtes**. `main.js` (1 031 125 o) représente à lui seul **57 %**
  du poids total de la page.
- **Écran formulaire** (même page + modale ouverte) : **1 821 552 o,
  18 requêtes** — seulement **+12 583 o** par rapport à la liste seule
  (le corps JSON du formulaire, 4 823 o, et `dform.js`, 7 760 o) : la
  trouvaille ci-dessus explique ce delta anormalement faible pour un
  écran censé charger un éditeur riche et un date-picker.

**`main.js` avant `jquery.min.js` dans `layout.blade.php:138-140` —
est-ce que `main.js` utilise jQuery ? Lu, pas deviné** : oui,
`main.js` référence bien jQuery (8 occurrences), mais il s'agit d'un
bundle Webpack qui embarque **sa propre copie interne** de jQuery
3.3.1 (confirmé par la présence littérale du message d'erreur UMD de
jQuery lui-même, `"jQuery requires a window with a document"`, et de
la chaîne de version `"3.3.1"`, dans le code minifié) plus des
composants Bootstrap 4 (`_jQueryInterface`, motif `alert.js`) qui
consomment cette copie interne via le système de modules du bundle,
pas via `window.jQuery` global. **`main.js` ne dépend donc pas du
`jquery.min.js` chargé après lui** — l'ordre actuel ne le casse pas.
Non corrigé, conformément à la consigne (« ne corrige pas, mélanger
l'ordre des scripts mélangerait deux risques dans un seul restyle »).

---

# Chantier « moteur de liste » — tri, `TableTemplateRender.php`, état vide, palette

Nouveau chantier, nouveau périmètre d'écriture :
`admin/generator/TableTemplateRender.php`, `admin/views/default/`,
`admin/assets/css/`, `dclass/devups/Datatable/Datatable.php`,
`BACKOFFICE-NOTES.md`, `../aiMemory/`. `admin/main.css`, tout `src/`,
`admin/generator/AdminTemplateGenerator.php` et le reste de `dclass/` —
lecture seule. **Méthode de preuve héritée du chantier précédent,
appliquée d'emblée** : mesure DOM directe
(`getBoundingClientRect`/`getComputedStyle`/lecture du DOM), pas de
comparaison pixel de captures — établi la dernière fois comme la seule
preuve fiable d'une session à l'autre.

## 28 — Point 0 : la base de comparaison, avant toute modification

**Trois écrans, 1440 px et 390 px**, mesurés **dans le navigateur**
(`getBoundingClientRect` sur chaque `.dv_datatable_container.card`),
pas depuis des fichiers copiés :

| Écran | Largeur | `innerWidth` réel | Carte : largeur / débordement | Hauteur de ligne | Lignes visibles |
|---|---|---|---|---|---|
| `contest/list` | 1440 | 1440 | 1115 / **non** | 77,47 px | 8 |
| `comicbook/list` | 1440 | 1440 | 1115 / **non** | 77,47 px | 10 |
| `contest/detail?id=7`, table 1 (formulaire) | 1440 | 1440 | 447 / **non** | 93,47 px | 4 |
| `contest/detail?id=7`, table 2 (Jury) | 1440 | 1440 | 543 → contenu 712 / **OUI, +169px** | 203,41 px | 10 |
| `contest/detail?id=7`, table 3 (Participant) | 1440 | 1440 | 543 / **non** | 103,53 px | 10 |
| `contest/list` | 390 | **568** (élargi) | 360 → contenu 553 / **OUI, +193px** | 103,53 px | 8 |
| `comicbook/list` | 390 | **985** (élargi) | 360 → contenu 970 / **OUI, +610px** | 77,47 px | 10 |
| `contest/detail?id=7`, table 1 | 390 | **727** (élargi) | 360 / **non** | 93,47 px | 4 |
| `contest/detail?id=7`, table 2 (Jury) | 390 | 727 | 360 → contenu 712 / **OUI, +352px** | 203,41 px | 10 |
| `contest/detail?id=7`, table 3 (Participant) | 390 | 727 | 360 → contenu 445 / **OUI, +85px** | 166,86 px | 10 |

**Trouvaille immédiate, avant tout changement** : à 390 px, `window.
innerWidth` n'est **jamais** 390 — le navigateur l'élargit lui-même
(568, 985, 727) pour accueillir le contenu qui déborde, un symptôme
plus visible encore que le simple `scrollWidth > clientWidth` d'une
carte : **la page entière s'élargit**, pas seulement une carte. C'est
la preuve directe, mesurée avant tout changement, que le débordement
horizontal signalé au chantier précédent touche au minimum 4 des 5
tables testées à 390 px (seule la table 1 du détail — un formulaire à
deux colonnes de dates, pas de tableau de données — n'en souffre pas),
et même **une table du détail à 1440 px** (la table « Jury », 712 px de
contenu dans une carte de 543 px). Répond par avance à la question du
point 2(f) : le débordement n'est pas propre à 390 px, il existe déjà à
1440 px dès qu'une table a assez de colonnes larges (ici une colonne
texte libre).

**Captures, pour montrer, pas pour prouver** (la leçon du chantier
précédent) : `datatable_baseline/01_list_1440_avant.jpg` …
`06_detail_390_avant.jpg`, hors dépôt.

**Aucune ligne de code touchée pour ce point.**

## 29 — Point 2 : `TableTemplateRender.php`, point par point

**(a) `table_class`.** `table-bordered` retiré (`table-striped
table-hover` suffisent). `dataTable`/`no-footer` retirés — **mesuré** :
0 occurrence des deux dans `main.css`/`dv_style.css`/`dv_theme.css`,
classes de la bibliothèque DataTables, jamais chargée ici (confirmé au
chantier précédent). Elles n'habillaient rien.

**(b) Les trois boutons de ligne — décidé, mais pas réalisable dans ce
périmètre d'écriture.** `$btnedit_class`/`$btnview_class` changés vers
`btn-outline-secondary` comme demandé. **Mais mesuré après coup, sur le
HTML réellement rendu** : les boutons de ligne affichés viennent de
`AdminTemplateGenerator::dt_btn_action()` (ligne 40-41 : `$actionclass
= ['edit' => '... btn-warning', 'show' => '... btn-info', 'delete' =>
'... btn-danger']` et ligne 90, le bouton principal, **en dur**, `'btn
btn-warning'`) — cette méthode **ne lit jamais**
`$this->btnedit_class`/`$this->btnview_class`/`$this->btndelete_class`.
Ces trois propriétés sont donc **du code mort**, exactement comme
`.torder` au chantier précédent, découvert cette fois en vérifiant le
rendu plutôt qu'en le supposant. Or `AdminTemplateGenerator.php` est
explicitement en lecture seule pour ce chantier. Options mesurées :
une surcharge CSS ciblée (`.dv_datatable .btn-warning`/`.btn-info`)
aurait pu simuler le changement sans toucher le fichier interdit — mais
**mesuré avant d'agir** : `btn-warning`/`btn-info` apparaissent dans 47
fichiers de `src/` (lecture seule), dont une bonne quinzaine de
`*Table.php` (colonnes personnalisées rendues À L'INTÉRIEUR de
`.dv_datatable`) — une règle aussi large aurait recoloré des éléments
sans rapport avec les boutons de ligne (badges de statut, actions
personnalisées) sur un nombre de modules que je ne peux pas auditer un
par un dans ce chantier. **Je m'arrête là-dessus** : gardé les
propriétés corrigées (correctes, prêtes si `AdminTemplateGenerator.php`
est un jour réaligné sur elles — un autre chantier), **signalé, pas
simulé** par une règle CSS à l'aveugle. Seule la suppression reste
rouge aujourd'hui comme avant — la décision du propriétaire n'est pas
réalisée visuellement, et c'est écrit ici en toutes lettres plutôt que
caché.

**(c) `fa-close` → `fa-times`.** Corrigé, à la fois dans le défaut de
propriété (`TableTemplateRender.php`, pour le cas sans entité) et dans
`Datatable::__construct()` (le cas réel, avec entité — voir (d)).
Vérifié : `fa-edit`, `fa-eye`, `fa-plus` existent bien dans `main.css`
(1 occurrence chacun), ainsi que `fa-arrow-down` (`renderDefaultGroupAction`,
export csv). **Balayage de `admin/generator/`** pour d'autres alias
FA4 morts (`fa-close`, `fa-remove`, `fa-warning`, `fa-send`, autres
noms renommés entre FA4 et FA5) : aucun autre trouvé.

**(d) Labels en anglais → `t()` — zéro changement visible, et c'est
honnête de le dire.** `t($ref,...)` (`lang.php:22`) fait `return $ref;`
**en toute première ligne** — tout le reste de la fonction (recherche
de traduction, enregistrement d'une clé manquante) est du code mort,
jamais atteint. Envelopper `edit`/`show`/`delete`/`create`/`Export
csv`/`All` dans `t()` ne change donc **rien à l'écran aujourd'hui**
(vérifié : HTML identique avant/après). Fait quand même, par cohérence
avec `t("Line to show")` déjà en place, et parce que c'est sans risque
(aucune traduction active pour casser). Le risque de doublon de clé que
le prompt demandait de vérifier est lui aussi nul en pratique : comme
`newdatacollection()` n'est jamais atteint, aucune clé n'est jamais
écrite nulle part, donc aucun doublon n'est possible — mais le patron
suivi (la clé = le texte anglais affiché, comme `t("Line to show")`)
reste le bon pour le jour où `t()` sera réactivé. **Contrainte PHP
rencontrée en écrivant** : un défaut de propriété de classe doit être
une expression constante — `t()` y est interdit (fatal `Constant
expression contains invalid operations`). `$createaction` du
constructeur de `Datatable.php` le savait déjà (il appelait `t('create')`
depuis le constructeur, pas depuis un défaut de propriété) ; le même
patron a été repris pour `$defaultaction` (edit/show/delete), déplacé
du défaut de propriété de `TableTemplateRender.php` vers
`Datatable::__construct()`.

**(e) Option de pagination dupliquée.** `per_page` n'ajoute plus une
option en plus : marquée `selected` sur l'option existante de la boucle
si elle y est déjà, sinon ajoutée une seule fois après. Vérifié par
requête directe : `per_page=20` → un seul `<option value="20"
selected>` (avant : deux) ; `per_page=5` (hors boucle) → une seule
option ajoutée après la boucle, `selected`.

**(f) Débordement horizontal — posé au bon endroit.** `$responsive`
passe de `""` à `"table-responsive"`. **Mesuré avant** (point 0) et
**après**, dans le navigateur : à 390 px sur `comicbook/list`, le pire
cas, `window.innerWidth` était élargi à **985** (la page entière
s'élargissait) — après le correctif, `window.innerWidth` reste
**exactement 390**, `document.documentElement.scrollWidth` aussi, la
carte ne déborde plus (`scrollWidth == clientWidth`), et c'est
désormais le `.table-responsive` lui-même (957 px de contenu) qui porte
le défilement horizontal, contenu à l'intérieur de la carte. Même
vérification sur `contest/list` (390 : 568→390) et sur les trois tables
de `contest/detail` à 1440 **et** 390 px (la table « Jury », qui
débordait de 169 px à 1440 px, ne déborde plus : 543/543). Répond à la
question posée : **oui**, c'était bien la cause (au moins partielle,
voir la carte globale ci-dessous) du débordement relevé au chantier
précédent.

**(g) Grilles Bootstrap mal imbriquées — une des deux citées par le
prompt n'existait pas.** Mesuré avant toute correction : le `.row` que
le prompt disait absent sous `perpagebuilder()` (`col-lg-3`) **existe
bel et bien** — `perpagebuilder()` n'a qu'un seul point d'appel vivant
(`paginationbuilder()` → vue `default.pagination`, ligne 6 :
`class="row"`) ; l'autre point d'appel possible,
`Datatable::tableoption()`, est du code mort (son unique référence est
commentée, `//$html .= $this->tableoption();`). **Seule**
`renderTopOptionAction()` (`col-lg-8`/`col-lg-4` dans un `.d-sm-flex`,
pas un `.row`) avait le défaut. Choix fait : retirer les classes de
grille plutôt qu'ajouter un `.row` imbriqué dans un flex déjà
fonctionnel (`justify-content-between` répartit déjà les deux blocs
sans grille) — évite d'empiler un second système de layout sur le
premier. Mesuré avant/après : le bouton « Action group » était à 20 px
du bord gauche de la carte contre 15 px pour la table juste en dessous
(5 px d'écart dû au padding non compensé de `col-lg-8`) ; après, 20 px
→ mais ce 20 px restant n'est **plus** dû à la grille : c'est le
padding propre du conteneur `.d-sm-flex` (5 px, hérité de `main.css`,
lecture seule) appliqué directement à un enfant direct — **non
corrigible dans ce périmètre, et probablement pas un défaut** (espace
de respiration volontaire du thème). Le véritable gain mesuré : l'écart
dû à la grille mal imbriquée (15 px) a disparu, le reste (5 px) n'est
pas le même défaut.

**Régression, fonctionnelle, essayée pas supposée** : recherche
(`search`), tri de page (`pagination`), changement de lignes par page
(`setperpage`), export CSV (lien et bouton), action de groupe
(`groupdelete`), et les trois actions de ligne (modale d'édition
ouverte et vérifiée à l'écran, structure HTML des boutons `show`/
`delete` inchangée hors classe/icône) — tous essayés sur `contest/list`
après les sept correctifs ci-dessus, tous fonctionnels. Aucune
modification de structure DOM qui romprait les sélecteurs jQuery de
`ddatatable.js` (`.find("#dv_table")`, `.find("tbody")` traversent les
descendants, insensibles à l'ajout du wrapper `.table-responsive`).

## 30 — Point 1 : le tri — établi avant d'écrire, comme demandé

**Mesuré, lecture seule (`dclass/devupsjs/ddatatable.js`,
`dclass/devups/Datatable/Lazyloading.php`), avant tout rendu
conditionnel** :

1. `toggleorder()`/`orderasc()`/`orderdesc()` n'envoient **jamais** les
   paramètres `dsort`/`dsortjoin` que lit explicitement `Lazyloading.php`
   — ils envoient `order=`/`orderjoin=` bruts. C'est
   `extract(Request::$uri_get_param)` (`Lazyloading.php:300`, atteint
   seulement si `next` **et** `per_page` sont tous deux présents — ce
   qui est toujours le cas dans `geturl()`) qui crée les variables
   locales `$order`/`$orderjoin` à partir de ces clés GET. **Mais**
   seule `$order` est ensuite réinjectée dans `orderby($order)` ; `$orderjoin`,
   une fois extrait, n'est plus jamais lu nulle part dans
   `Lazyloading.php`. **Vérifié en direct, pas seulement lu** (`fetch`
   avec les mêmes paramètres que `ddatatable.js` envoie réellement,
   `next=1&per_page=10` inclus — sans ces deux-là, `extract()` ne
   s'exécute même pas) : `order=amount asc/desc` sur
   `comicbook/donate/list` change bien l'ordre des lignes ; `orderjoin=
   wallet.amount asc/desc` sur `devupsfront/user/list` **ne change
   rien** — ordre identique à l'absence de tri, asc et desc confondus.
   **Trouvaille non demandée par le prompt, mesurée en répondant à sa
   propre question** : le tri par colonne jointe est silencieusement
   inopérant, indépendamment de tout ce que ce point peut changer à
   l'affichage. `Lazyloading.php` : lecture seule, signalé, pas corrigé.
   Sur 58 colonnes `order => true` dans tout `src/`, **une seule** est
   jointe (`'wallet.amount'`, `UserTable.php:44`) — la casse est rare,
   mais réelle.

2. `toggleorder()`/`orderasc()`/`orderdesc()` (toujours dans
   `ddatatable.js`) ne remplacent **que** `<tbody>` dans la réponse
   AJAX (`.find("tbody").html(response.datatable.tablebody)`) — le
   `<thead>` n'est **jamais** renvoyé par `getTableRest()`
   (`Datatable.php`, vérifié : seulement `tablebody`/`tablepagination`
   dans son retour) ni consommé par aucun callback JS. Et aucune de ces
   fonctions ne modifie l'URL affichée (pas de `pushState`/
   `location.href`). **Conséquence directe** : même une flèche
   serveur parfaitement exacte au moment du rendu ne se verra **jamais**
   changer après un clic dans l'usage normal — seul un rechargement
   complet d'une URL qui contiendrait déjà `order=`/`orderjoin=`
   (un lien partagé, par exemple) la montrerait juste.

**Décision, au vu de ces deux mesures** : l'état n'est pas « indisponible »
au sens strict du prompt (il est lisible côté serveur, via
`Request::get('order')`/`Request::get('orderjoin')`, à chaque rendu) —
mais il n'est **jamais visible après une interaction réelle**, pour deux
raisons indépendantes et toutes deux hors périmètre d'écriture
(`ddatatable.js` n'est pas dans la liste autorisée). Plutôt que de ne
rien écrire, ou d'inventer un affichage : **une seule icône, calculée
depuis l'état réellement lu à CE rendu** — `fa-sort-up`/`fa-sort-down`
si `Request::get('order')`/`orderjoin` correspond à cette colonne
précise, `fa-sort` (neutre, « triable ») sinon. Vérifié : `?order=note
asc` sur `participant/list` affiche `fa-sort-up` **uniquement** sur la
colonne « Note », `fa-sort` sur « Nb eval » ; `desc` donne
`fa-sort-down`. Honnête parce qu'exact, pas parce que utile au clic —
dit explicitement dans le code et ici : **la mise à jour après clic est
un point JavaScript, un autre chantier** (il faudrait que
`toggleorder()` redemande et réapplique aussi le `<thead>`).

**`align-items` à la place de `align-content`** — `align-content` n'a
aucun effet sur un conteneur flex à une seule ligne (c'est l'axe
transversal sur plusieurs lignes qu'il contrôle) ; `align-items` centre
réellement le libellé et l'icône. Vérifié : `getComputedStyle(div).
alignItems === "center"`.

**`directive` restaurée** sur le `<th>` triable (il ne portait plus
`$valuetd["directive"]`, contrairement à l'en-tête non triable).
**Compte, dans tout `src/`** : 0 colonne de datatable ne déclare
`directive` aujourd'hui (seuls des champs de **formulaire**,
`Form::*`, l'utilisent — 4 fichiers, aucun `*Table.php`) — ce défaut
était donc **théorique**, invisible aujourd'hui, mais corrigé pour la
même raison qu'une faute d'orthographe qu'on ne verra peut-être jamais :
elle reste fausse.

**`.torder` : tranché, retiré.** Établi mort au chantier précédent
(HTML commenté, impossible de comparer visuellement) ; **confirmé
mort à celui-ci** (les deux générateurs HTML commentés qui en
parlaient encore dans `Datatable.php` sont supprimés avec ce chantier,
remplacés par la fleche unique ci-dessus) — plus aucune trace de code
qui pourrait un jour la réactiver. Règle CSS retirée de `dv_style.css`.

**Régression, essayée** : tri ascendant/descendant sur une colonne
simple (`comicbook/donate/list`, `order=amount`) — fonctionne, ordre
des lignes changé, vérifié par les IDs. Recherche, pagination,
changement de lignes par page — inchangés par ce point (aucune ligne
qui les concerne modifiée).

## 31 — Point 3 : l'état vide, un seul endroit

**Chemin de code atteint à `getTableRest()` (ex-ligne 684), établi
avant d'écrire** : c'est l'endpoint AJAX appelé par `ddatatable.js`
pour **recherche**, **pagination** et **changement de lignes par
page** (tous passent par `geturl()`/`$.get`). Reproduit en direct, pas
supposé : recherche réelle sur `contest/list` avec un `id` impossible
(`999999999`) via le vrai bouton « search » → **avant correctif**,
`<tbody></tbody>` strictement vide, aucun texte, aucune trace — la
ligne de filtre reste affichée juste au-dessus (donc pas une « panne
visible », plutôt un silence déroutant : l'utilisateur ne sait pas si
sa recherche n'a rien donné ou si quelque chose s'est cassé.

**Deux implémentations avant ce chantier, maintenant une seule.**
`render()` (page complète) : `$tbody = ""` — rien, même pas un appel à
la vue. `getTableRest()` (AJAX) : appelait `default.table` avec
`['data' => false]` — mais `@if($data)` affichait « la liste est vide »
quand `$data` est **vrai**, et comme `false` est toujours passé, le
code prenait la branche `@else`, **entièrement commentée** (la vraie
table, jamais utilisée en pratique) → résultat : rien non plus. Les
deux chemins produisaient donc un vide silencieux, par deux bugs
différents.

**Un seul endroit, maintenant** : `Datatable::emptyStateRow()`, une
méthode privée appelée par les deux (`render()` et `getTableRest()`),
qui calcule le `colspan` réel (colonnes du modèle + case à cocher de
groupe + colonne Action, si actives) et rend `default.table` —
réparée, pas remplacée : une ligne `<tr><td colspan="...">` insérée
dans le `<tbody>` existant (pas un bloc à part, qui casserait la mise
en page table/carte).

**Deux messages distincts**, comme demandé. Distingué via
`Request::get('dfilters')` — établi en lisant `ddatatable.js` (pas
deviné) : le champ caché `dfilters=on` du formulaire de recherche n'est
envoyé que quand `ddatatable.search()` est réellement déclenché (bouton
« search »), jamais par une pagination ou un changement de lignes par
page. `dfilters == 'on'` → « Aucun résultat pour cette recherche. » ;
sinon → « Aucun élément pour l'instant. ». Vérifié, le cas recherche,
en direct sur `contest/list` (recherche réelle, bouton cliqué, pas
simulée par URL) : **avant**, `<tbody></tbody>` ; **après**, `<tr
class="dv_empty_state"><td colspan="9">Aucun résultat pour cette
recherche.</td></tr>` — confirmé par lecture du texte de page rendue
(`get_page_text`), **pas par capture d'écran** : l'outil de capture de
ce chantier échoue à rendre visuellement ce texte précis (zoomé sur la
zone exacte, deux fois, texte absent de l'image) alors que le DOM
confirme sa présence, sa position, sa couleur (`text-muted`,
`rgb(108,117,125)`, opacité 1, `visibility: visible`) et que
`get_page_text` l'extrait au bon endroit — même défaut d'outil déjà
noté au chantier précédent pour d'autres éléments, pas un bug de ce
correctif. Le cas « liste réellement vide » (`$searched = false`) n'a
pas pu être vérifié en direct sur une vraie table à zéro ligne (aucune
trouvée dans le temps de ce chantier, sans fabriquer de données) — la
logique est un simple ternaire sur un booléen déjà vérifié correct pour
l'autre branche, risque jugé minime.

**Observation en passant** : `pagination.blade.php` affiche déjà, à
part, un bandeau « no item founded » (non traduit, en anglais) quand
`$ll->nb_element` est à zéro — indépendant de ce correctif, pas
touché (pas signalé comme cassé par le prompt), les deux messages
coexistent désormais sans se contredire.

**Régression, essayée** : recherche avec résultats (`contest/list`,
`id=7`) — toujours la bonne ligne, pas de `colspan` parasite ;
pagination et changement de lignes par page sur une liste non vide —
inchangés.

## 32 — Point 4 : la palette, trois substitutions tranchées, contraste vérifié

Trois couleurs en dur, signalées sans être choisies au chantier
précédent — tranchées ici, appliquées depuis les jetons de
`dv_theme.css`, rien d'inventé :

| Valeur en dur | Où | Devient |
|---|---|---|
| `rgba(51,122,183,0.3)` | voile du chargeur de datatable | `rgba(var(--dv-color-primary-rgb), 0.3)` |
| `whitesmoke` (×2) | fonds sticky (`thead`, ligne de filtre) | `var(--dv-color-bg-app)` (#f1f4f6) |
| `#004282` | bordure du chargeur `.dv-autocomplete` | `var(--dv-color-primary)` |

**Un jeton ajouté à `dv_theme.css`, pas une couleur de plus** :
`--dv-color-primary-rgb: 63, 106, 216` — les composantes RGB de
`--dv-color-primary` (#3f6ad8), nécessaires parce que CSS ne permet pas
de dériver un alpha depuis un hex directement dans `rgba()`. Même
source, documentée en regard.

**Contraste vérifié, pas supposé** — c'est le point précis où le
prompt demandait une vérification avant d'agir (texte sur fond sticky) :
calcul du ratio WCAG entre le texte de l'en-tête (`#495057`, déjà le
jeton `--dv-color-text`) et le fond, avant/après :
`whitesmoke` (#f5f5f5) → **7,50:1** ; `--dv-color-bg-app` (#f1f4f6) →
**7,40:1**. Différence de 0,1, imperceptible, les deux loin au-dessus
du seuil AAA (7:1) et a fortiori AA (4.5:1) — aucune dégradation de
lisibilité.

**Les deux autres substitutions vérifiées par valeur calculée** (pas de
contraste à mesurer, ce sont des voiles/bordures, pas du texte) : le
voile du chargeur rend bien `rgba(63, 106, 216, 0.3)`, la bordure de
l'autocomplete rend bien `rgb(63, 106, 216)` sur les bords visibles de
l'animation (transparent sur les deux autres, motif de spinner
inchangé).

**Régression, essayée** : liste chargée, aucune rupture visuelle
(`get_page_text` : les 8 lignes de `contest/list` toujours présentes,
rien de cassé).

## 33 — Point 5 : mesure seule, zéro diff

**(1) Les attributs `style=` en dur, un par un.** 7 cités par le
prompt (numérotés sur l'état du fichier avant ce chantier — les
numéros de ligne ont bougé depuis, identifiés ici par ce qu'ils font) :

| Style | Où | Fait quoi |
|---|---|---|
| `display:none; overflow-y: auto;` | `dialogBox()`, conteneur `#<class>box` | masque la modale de secours par défaut |
| `style=""` | `dialogBox()`, `.swal2-modal` | **vide, ne fait rien** — mort |
| `display: block;` | `dialogBox()`, bouton de fermeture `×` | force l'affichage du bouton |
| `width:100px; display: inline-block` | `perpagebuilder()` (le vivant, `TableTemplateRender.php`), `<select>` | largeur fixe du menu « lignes à afficher » |
| `cursor: pointer;` + `display: flex; align-items: center` | `headerbuilder()`, `<th>` triable | curseur main + alignement de l'icône (ce chantier, point 1) |
| `padding: .3rem;` | `tablebodybuilder()`, `<td>` Action | resserre la cellule des boutons de ligne |
| `position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-131px, 33px, 0px);` | `groupactionbuilder()`, menu « Action group » | **le plus suspect, cité par le prompt** — voir ci-dessous |

**Le plus suspect, expliqué, pas touché.** Ce bloc porte aussi
`x-placement="bottom-end"` — la signature d'un positionnement calculé
par **Popper.js** (la bibliothèque qui pilote les menus déroulants
Bootstrap), normalement recalculé par le JavaScript à chaque ouverture
en fonction de la position réelle du bouton. Ici, il est **figé en dur
dans le PHP** — très probablement une capture, un jour, de la valeur
que Popper.js avait calculée pour un cas précis, collée telle quelle.
En pratique, Bootstrap/Popper **réécrit cet attribut `style` dès que le
menu s'ouvre réellement** (vérifié en ouvrant le menu « Action group »
à l'écran : le menu s'affiche au bon endroit, pas à -131px/33px figé) —
donc probablement inerte aujourd'hui, mais fragile : si Popper.js
venait à ne pas se charger ou à échouer silencieusement, ce menu
s'ouvrirait au mauvais endroit, figé. **Non touché**, comme demandé.

**Code mort trouvé en cherchant** : une **seconde** définition de
`perpagebuilder()` existe, entièrement commentée, à l'intérieur de
`Datatable.php` lui-même (ligne ~564) — jamais exécutée, la vraie vit
dans le trait `TableTemplateRender.php`. Son style `width:100px;
display: inline-block` n'est donc pas « en double » à l'écran, juste
dupliqué dans le code mort.

**(2) `DatatableOverwrite` — implémentée, mais jamais pour la couleur.**
4 fichiers dans `src/` l'implémentent : `ModuleCms/Entity/Cmstext.php`,
`ModuleReporting/Entity/Push_email.php`,
`ModuleReporting/Entity/Reportingmodel.php`,
`ModuleComicbook/Entity/Comicbook.php`. **Lu, pas juste compté** :
dans les 4, `editAction()`/`deleteAction()` sont des coquilles vides
(retournent `null` implicitement — `Datatable::getaction()` les traite
alors comme non surchargées, ne touche ni classe ni couleur).
`showAction()` est la seule réellement personnalisée : vide (`" "`,
Push_email/Reportingmodel — bouton « show » supprimé) ou remplacée par
un lien `btn btn-default` vers une page de détail (Comicbook). **Donc**
: si la décision du point 2(b) finit par s'appliquer un jour (après
réalignement d'`AdminTemplateGenerator.php`, hors de ce chantier), elle
s'appliquerait sans conflit à `edit`/`delete` sur ces 4 entités aussi —
seul `show` y est déjà distinct, et ne serait pas concerné de toute
façon.

**(3) Le poids, après ce chantier.** Mesuré dans le navigateur
(`performance.getEntriesByType`, même méthode que le chantier
précédent) sur `contest/list` : **1 808 344 octets, 17 requêtes** —
contre 1 808 969 octets, 17 requêtes avant. **-625 octets** (le HTML
généré est légèrement plus court : classes retirées du point 2a,
quelques commentaires CSS raccourcis) — aucune requête ajoutée, aucun
fichier de plus chargé, conforme à la règle de ce point.

---

# Chantier « fermeture du moteur de liste » — le jeton manquant, les boutons de ligne, le tri qui se met à jour

Nouveau chantier. Périmètre d'écriture : `admin/assets/css/`,
`admin/generator/AdminTemplateGenerator.php` (rendu des boutons
uniquement), `dclass/devupsjs/ddatatable.js`,
`dclass/devups/Datatable/`, `BACKOFFICE-NOTES.md`, `../aiMemory/`.
`admin/main.css` et tout `src/` : lecture seule.

## 34 — Point 0 : le jeton « manquant » — mesuré présent, pas absent

**La prémisse du point ne tient pas, mesuré avant tout changement, pas
supposé.** `--dv-color-primary-rgb` **est** déclaré dans
`dv_theme.css:29` (`63, 106, 216`), committé au chantier précédent
(`cabeb78`), `git status` propre sur ce fichier avant toute intervention
de ce chantier. Vérifié sur la page réellement chargée, pas seulement
lu dans le fichier :

```
getComputedStyle(document.documentElement).getPropertyValue('--dv-color-primary-rgb')
  → "63, 106, 216"
```

**Et avec le vrai déclencheur, pas un calcul théorique** —
`ddatatable.init('contest'); ddatatable.setloader();` (exactement le
mécanisme cité par le prompt) sur une page réelle : le `.loader` rendu
a `background-color: rgba(63, 106, 216, 0.3)` — **pas** `transparent`.
Le `var()` est résolu, la règle est valide, le voile existe bel et
bien.

**Ce qui est réellement là, en revanche, mesuré au-delà de ce que
demandait le point** : le contraste du texte blanc (`color: white`)
contre ce voile, **tel qu'il apparaît réellement** (le voile est semi-
transparent à 0.3, donc mélangé optiquement au fond blanc des lignes
de tableau en dessous) — calcul WCAG sur la couleur effective
(`rgb(197, 210, 243)`, un bleu très clair) : **1,51:1**. Très en
dessous du seuil AA (4,5:1, ou 3:1 pour du grand texte). **Mais ce
n'est pas une régression de ce chantier ni du précédent** : la même
mesure sur l'ancienne valeur (`rgba(51,122,183,0.3)`, le bleu
Bootstrap 3 d'avant la substitution de palette) donne **1,48:1** —
quasiment identique, un défaut de lisibilité **pré-existant**, jamais
vérifié avant cette mesure, ni introduit ni aggravé par la substitution
de jeton. Signalé, pas corrigé : ce chantier porte sur le jeton
manquant, qui n'existe pas ; un chargeur peu lisible en est un autre,
pas posé par ce prompt, laissé pour signalement au propriétaire.

**Recensement des 11 `var(--…)` de `dv_style.css`, un par un** (compte
confirmé : 11 occurrences, 5 jetons distincts) :

| Ligne | Jeton | Déclaré dans `dv_theme.css` ? |
|---|---|---|
| 26 | `--dv-color-primary-rgb` | **oui**, ligne 29 |
| 43, 44 | `--dv-sidebar-width` | **oui**, ligne 13 |
| 54, 69 | `--dv-header-height` | **oui**, ligne 14 |
| 55, 70 | `--dv-color-bg-app` | **oui**, ligne 19 |
| 93, 96, 100 | `--dv-sidebar-width` (suite) | **oui**, ligne 13 |
| 152 | `--dv-color-primary` | **oui**, ligne 24 |

**Verdict : les 11 occurrences, les 5 jetons, tous déclarés. Aucun
`var()` non résolu dans `dv_style.css` aujourd'hui.**

**Forme choisie pour `--dv-color-primary-rgb` (le prompt demandait de
trancher) : gardée telle quelle — la décomposition en composantes RGB
dans `dv_theme.css`**, plutôt que d'abandonner `rgba()`. Raison : c'est
déjà la forme en place (chantier précédent), elle fonctionne (mesuré
ci-dessus), et abandonner `rgba()` aurait exigé de choisir une couleur
opaque de remplacement pour un voile qui doit rester semi-transparent
par nature (laisser deviner le contenu dessous pendant le chargement)
— un changement de plus large portée que ce que ce point demandait.
**Seul ajout** : le commentaire a été renforcé pour dire explicitement
que les deux jetons (`--dv-color-primary` et `--dv-color-primary-rgb`)
doivent être synchronisés à la main — exactement ce que demandait le
prompt, pour éviter qu'un futur changement de l'un sans l'autre ne
rende le voile silencieusement incohérent (pas invalide : juste une
couleur différente de celle attendue, un défaut bien plus difficile à
repérer qu'un `var()` non résolu).

**Aucune ligne de logique changée pour ce point** — un seul commentaire
étoffé dans `dv_theme.css`.

## 35 — Point 1 : les boutons de ligne, pour de vrai

**Une seule source, désormais.** `Datatable::__construct()` pose
`'class' => $this->btnedit_class` (etc.) directement dans
`$this->defaultaction`, au lieu du mot-clé `'edit'`/`'show'`/`'delete'`
qu'`AdminTemplateGenerator` retraduisait via un tableau `$actionclass`
en dur. **Mesuré avant de changer** : ce mot-clé n'avait aucun autre
usage dans `dclass/` (ni sélecteur CSS, ni JS) — rien d'autre ne
dépendait de sa valeur littérale.

**(a) Le tableau en dur retiré**, dans les deux fonctions
(`dt_btn_action()` et `dt_btn_action_builder()`) — `$el["class"]`
s'utilise tel quel. **Espacement préservé** : `mb-2 mr-2` rajouté au
point de rendu dans les deux branches de `dt_btn_action()` (en ligne,
et dans le menu déroulant — la seconde avait été oubliée dans un
premier jet, corrigée avant de committer). `dt_btn_action_builder()` :
pas de marge ajoutée — elle n'en avait **jamais** eu (le tableau
`$actionclass` y était une variable non définie, `isset()` toujours
faux), donc rien n'est retiré qui existait.

**(b) `$mainaction` n'est plus orange par câblage** —
`$mainaction["class"]` (déjà la bonne classe) remplace le `'btn
btn-warning'` en dur.

**(c) Le `translate3d(-131px, 33px, 0px)` — mesuré, puis retiré.**
Clic réel, pas supposé, aux deux largeurs, ligne du haut **et** ligne
du bas :

| | Avec le style en dur | Sans (attribut retiré avant le clic) |
|---|---|---|
| 1440px, ligne du haut | `transform` mesuré après clic : **`none`** (déjà écrasé), menu `right: 1448.95px` — **déborde de 9px** du viewport (1440px) | `right: 1384.47px` — dans le viewport |
| 1440px, ligne du bas | `transform: none`, pas de débordement | pas de débordement |
| 390px, les deux lignes | identique avec/sans, aucun débordement | identique |

**Verdict : le style en dur ne positionne déjà plus rien** (le
`transform` réellement appliqué après ouverture est toujours `none`,
jamais la valeur figée — le JS du dropdown Bootstrap l'écrase à chaque
ouverture) **et sa présence causait un léger débordement que son
retrait fait disparaître**, sans exception mesurée. Retiré. Popper.js
n'est pas chargé dans ce back-office (confirmé au chantier du socle
visuel : `main.js`, jQuery, notify, sweetalert2, pas Popper) — le
positionnement vient de la classe Bootstrap pure `.dropdown-menu-right`
(`right: 0`), pas d'un calcul JS externe qui aurait pu dépendre du
style en dur. **Même défaut trouvé, non touché** : le menu déroulant
d'`optionImport()` (le bouton « options », import CSV) porte le même
style figé — hors du périmètre explicite de ce point
(`dt_btn_action`/`dt_btn_action_builder`/« le gabarit du menu
déroulant » des boutons d'action, pas celui d'import), signalé pour
mémoire, pas touché.

**(d) `dt_btn_action_builder()` — pas du code mort.** Un appelant trouvé
dans `src/` (lecture seule) :
`src/devupscms/ModuleGallery/Resource/views/front/test.blade.php:13`.
**Mais** : aucun code trouvé dans `src/` qui positionne
`$this->template` vers cette vue précise (recherché par nom de fichier
et par motif `front.test`) — la fonction est référencée, pas
prouvée atteignable en pratique aujourd'hui (le nom même du fichier,
« test », suggère un reliquat d'essai). Corrigée quand même, par la
même occasion que `dt_btn_action()` (c'était le même défaut, une seule
fonction à traiter différemment aurait été arbitraire) : si elle est
un jour atteinte, ses boutons seront désormais colorés correctement au
lieu de porter `class="edit"` nu depuis la genèse.

**La preuve, HTML réel, avant / après** :

```
avant : <button type="button" class="btn btn-warning" ...><i class="fa fa-edit"></i> edit</button>
après : <button type="button" class="btn btn-outline-secondary btn-sm" ...><i class="fa fa-edit"></i> edit</button>

avant (dans le menu) : class="mb-2 mr-2 btn btn-warning"
après (dans le menu) : class="btn btn-outline-secondary btn-sm mb-2 mr-2 btn-block"

avant (delete) : class="mb-2 mr-2 btn btn-danger"
après (delete) : class="btn btn-danger btn-sm mb-2 mr-2 btn-block"
```

Capture envoyée dans la session (`point1_boutons_apres.jpg`) : le
bouton principal de chaque ligne de `contest/list`, à 1440px, gris
neutre au lieu d'orange ; le menu ouvert montre « edit » gris, «
delete » rouge, et « edit content » (une action personnalisée du
module, hors de ce point) toujours orange, inchangée.

**Régression, essayée** : dropdown ouvert et cliquable (lignes du haut
et du bas) ; branche non-déroulante vérifiée sur
`subvention_rule/list` et `subvention_pool/list` (`actionDropdown(false)`,
confirmé actif sur 6 modules) — `class="btn btn-outline-secondary
btn-sm mb-2 mr-2"` / `class="btn btn-danger btn-sm mb-2 mr-2"`,
`onclick` inchangés dans les deux branches.

## 36 — Point 2 : la flèche de tri se met à jour, pour de vrai

**Serveur** : `getTableRest()` (l'endpoint AJAX) renvoie désormais
`tablehead`, construit par le **même** `headerbuilder()` que `render()`
— une seule source de vérité, qui lisait déjà `Request::get('order')`/
`'orderjoin'` depuis le chantier précédent. **Seule la ligne d'en-têtes**
(`$theader['th']`), jamais la ligne de filtre (`$theader['thf']`) —
mesuré avant d'écrire, pas supposé : un remplacement du `<thead>`
entier détruit les `<input>` de la ligne de filtre, donc toute saisie
en cours dans un champ non soumis.

**Client** (`ddatatable.js`, `toggleorder()`) : applique
`response.datatable.tablehead` sur `#dv_table thead tr:not('.th-filter')`
par `.replaceWith()` — jamais sur `tr.th-filter`.

**`orderingup` (drapeau global unique) remplacé par `orderState`**, un
état par entité **et** par colonne (`{param, direction}`). Premier clic
sur une colonne = ascendant ; second clic sur la **même** colonne =
inversé ; clic sur une **autre** colonne = ascendant sur celle-là,
l'ancienne redevenant neutre automatiquement (son icône est recalculée
côté serveur à partir de l'URL réellement envoyée, qui ne porte plus
son paramètre).

**`orderasc()`/`orderdesc()` — cherché, confirmé morts, non touchés.**
Aucun appelant trouvé nulle part dans le dépôt (le seul HTML qui les
invoquait était le générateur de `.torder`, commenté dans
`Datatable.php`, retiré au chantier précédent). Code mort, laissé tel
quel — rien à corriger dans ce qui ne s'exécute jamais.

**Les quatre états, prouvés par clic réel** sur `participant/list`
(deux colonnes triables, « Note » et « Nb eval »), avec une saisie en
cours dans le champ « city » avant chaque clic :

| Action | Note | Nb eval | Champ « city » |
|---|---|---|---|
| (départ) | neutre | neutre | `TAPE_EN_COURS` tapé |
| clic « Note » | `fa-sort-up` | neutre | **préservé** |
| clic « Note » à nouveau | `fa-sort-down` | neutre | **préservé** |
| clic « Nb eval » | neutre | `fa-sort-up` | **préservé** |
| clic « Nb eval » à nouveau | neutre | `fa-sort-down` | **préservé** |

Et l'ordre réel des lignes change bien à chaque clic (IDs non
séquentiels après tri, vérifié).

**Régression, essayée** : recherche (le champ « city » non effacé
EST le test), pagination et lignes par page inchangées (aucune ligne
qui les concerne modifiée dans ce point).

## 37 — Point 3 : le paramètre de tri — la vérité établie par le SQL réel, pas par le raisonnement

**La mesure qui tranche** : journal général MySQL activé temporairement
(diagnostic réversible, aucune donnée ni structure touchée, désactivé
après), clic réel sur une colonne simple (`donate/list`, « Amount »,
`order=amount`) et une colonne jointe (`user/list`, « Balance »,
`orderjoin=wallet.amount`). URL **et** SQL réellement capturés :

| | URL réelle | SQL réel (`ORDER BY`) |
|---|---|---|
| Colonne simple, **avant** correctif | `GET .../donate/datatable?...&order=amount%20asc` | `ORDER BY amount asc` — **présent** |
| Colonne jointe, **avant** correctif | `GET .../user/datatable?...&orderjoin=wallet.amount%20asc` | `ORDER BY `user`.id desc` — **inchangé, `wallet.amount` absent** |

**Laquelle des trois possibilités était la vraie : la troisième,
« autre chose ».** Ni une traduction de noms en route (possibilité 1),
ni le tri simple cassé lui aussi (possibilité 2, directement
contredite par la capture SQL ci-dessus). La vraie explication, plus
fine que les deux hypothèses envisagées : `Lazyloading.php:300` fait
`extract(Request::$uri_get_param)` (seulement si `next` **et**
`per_page` sont tous deux présents — toujours vrai dans `geturl()`) —
un effet de bord qui crée une variable locale `$order` **directement
depuis la clé GET `order`**, jamais relue ni corrigée par le bloc
`dsort`/`dsortjoin` qui suit puisque ces deux-là sont absents de cette
requête. C'est ce canal **implicite et non voulu** qui faisait
fonctionner `order=`, pas une traduction de nom délibérée — et
`orderjoin`, extrait de la même façon en `$orderjoin`, n'était ensuite
**jamais relu nulle part**. Un grep littéral sur `order`/`orderjoin`
ne trouve effectivement aucun consommateur explicite — exact, et
c'est précisément pourquoi ce mécanisme est resté invisible à la
lecture : il n'y a pas de ligne de code qui dise « lire order », il y
a une fonction PHP générique (`extract()`) qui le fait sans le nommer.

**Avant de trancher côté serveur, vérifié comme demandé : `dsort`/
`dsortjoin` ont bien d'autres émetteurs réels**, trouvés par
recherche dans tout le dépôt :
- `admin/views/dashboard.blade.php` — 3 liens (`dsort=id desc`),
  lecture seule, hors périmètre d'écriture ;
- `src/devupscms/ModuleTree/Resource/js/tree_itemManager.js` —
  `dsort=name ASC` (lecture seule, `src/`) ;
- `src/shopping/ModuleCatalog/Controller/ProductFrontController.php` —
  construit `dsort=$dsort` pour la pagination d'une liste front
  (lecture seule, `src/`).

**Donc pas un remplacement — un ajout, les deux noms acceptés.**
`Lazyloading.php` (dans mon périmètre pour ce point précis
uniquement) : `order`/`orderjoin` ajoutés **en repli**, après
`dsort`/`dsortjoin` dans le même `if`/`elseif`, jamais à leur place.
`dsort`/`dsortjoin` gardent la priorité si jamais les deux étaient
présents (cas qui n'arrive pas aujourd'hui, mais la priorité est
explicite plutôt que laissée au hasard).

**Vérifié après correctif, même méthode (journal SQL réel)** :
`orderjoin=wallet.amount asc` sur `user/list` produit maintenant
`ORDER BY wallet.amount asc` — confirmé, plus `user.id desc`. Et
**`dsort=id desc` (le lien du dashboard) vérifié intact** : même
requête, `ORDER BY id desc` toujours présent, aucune régression sur
les émetteurs existants.

**Régression, essayée** : tri simple (`order=amount`, déjà
fonctionnel) inchangé ; tri joint (`orderjoin=wallet.amount`) corrigé ;
lien du dashboard (`dsort=id desc&status:eq=1`) toujours fonctionnel,
même filtre, même ordre.

---

# Chantier « moteur de formulaire » — gabarit, libellés, balise form, type non géré, modale, bouton d'envoi

Nouveau chantier. Périmètre d'écriture : `dclass/devups/Form/`,
`dclass/devupsjs/dform.js`, `admin/views/default/`,
`admin/views/layout/`, `admin/assets/css/`, `BACKOFFICE-NOTES.md`,
`../aiMemory/`. `admin/main.css`, tout `src/`, le reste de `dclass/`
(`Datatable/` compris) : lecture seule.

## 38 — Point 0 : la base de comparaison, avant toute modification

**Deux formulaires mesurés** (modale, clic réel `model._edit(...)`,
`getComputedStyle` — pas `getBoundingClientRect` seul, voir plus bas) :

1. **`ContestForm`** (12 champs, la référence du 26/10) ;
2. **`CollectorForm`** (9 champs, un `FORMTYPE_FILE` et deux champs
   `lang` dont un `FORMTYPE_TEXTAREA` — le candidat « fichier + riche »
   trouvé dans `ModuleComicbook`, confirmé par lecture de
   `CollectorForm.php`).

**Le troisième point (« ouvert en modale, par un clic réel ») n'ajoute
pas un troisième formulaire distinct — établi par la mesure, pas
supposé** : il n'existe dans ce back-office **aucun rendu de page
séparé** pour un formulaire `FormFactory` — `contest/form` renvoie 0
octet en navigation directe (établi au chantier du socle, reconfirmé
ici), et `CollectorForm` n'est, de la même façon, jamais atteint que
par la modale. Les deux formulaires mesurés sont donc déjà, tous les
deux, « le chemin que l'utilisateur emprunte vraiment ».

**Trouvaille méthodologique, avant les chiffres** : SweetAlert2 anime
l'ouverture de `.dv_modal` par un `transform: scale(...)` qui peut
rester bloqué à `0.7` bien au-delà de la durée de la transition CSS
annoncée (`0.5s`) — observé sur le formulaire Contest, pas sur
Collector, même méthode, même attente. `getBoundingClientRect()`
reflète ce facteur d'échelle (toutes les mesures apparaissent à 70%
de leur vraie taille) ; `getComputedStyle()` ne le reflète pas. **Les
chiffres ci-dessous viennent de `getComputedStyle()`**, pas de
`getBoundingClientRect()`, pour cette raison.

| | 1440 px | 390 px |
|---|---|---|
| **Contest** — largeur modale | 800px, aucun débordement | **370px**, aucun débordement |
| Contest — largeur table / libellés / valeurs | 760 / 227 / 533 | 330 / 108 / 222 |
| Contest — hauteur totale | 860px | 860px |
| **Collector** — largeur modale | 800px, aucun débordement | **370px**, aucun débordement |
| Collector — largeur table / libellés / valeurs | 760 / 223,5 / 536,5 | 330 / 153 / 177 |
| Collector — hauteur totale | 809,5px | 809,5px |

**Trouvaille qui répond déjà à une partie du point 5, avant d'y
arriver** : à 390px, la modale **ne déborde pas** — mesuré sur les
deux formulaires, `scrollWidth == clientWidth` à chaque fois, largeur
réelle 370px dans un viewport de 390px. `getComputedStyle(modal).
maxWidth` vaut `100%` : une règle **déjà présente ailleurs** (pas dans
`dv_style.css`, probablement SweetAlert2 ou `main.css`, les deux
lecture seule) plafonne déjà la largeur fixe de 800px. L'hypothèse du
point 5 (« la modale 800px est le candidat le plus probable au
débordement du socle ») **ne se vérifie pas aujourd'hui** — détaillé
au point 5.

**Observation en passant, pertinente pour le point 1** : les libellés
de `CollectorForm` sont des **clés de traduction brutes**
(`collector.content`, `collector.published_at`…) — `t()` étant un
simple passe-plat (établi au chantier du moteur de liste), elles
s'affichent telles quelles, nettement plus longues qu'un libellé
traduit. À 390px, la colonne des libellés de Collector prend 153px sur
330 (46%) contre 108 sur 330 (33%) pour Contest — la moitié de la
largeur utile partie dans des clés techniques, pas un défaut de ce
chantier mais un facteur aggravant pour le gabarit en tableau.

**Captures, pour montrer** : `form_baseline/01_contest_1440_avant.jpg`
… `04_collector_390_avant.jpg`, hors dépôt.

## 39 — Point 2 : les libellés, et une trouvaille plus grave que le style

**`dv_label` stylée** — 0 occurrence avant cette règle (confirmé dans
les trois feuilles chargées). `dv_style.css` : poids 600 (aucun jeton
de poids n'existe, valeur standard, pas une couleur inventée), couleur
`var(--dv-color-text)` (le jeton, explicite plutôt qu'hérité), marge
sous le libellé via `var(--dv-spacer)` (inerte dans le gabarit en
tableau actuel, prendra effet si le point 1 passe en blocs).

**Trouvaille, en vérifiant `FH_REQUIRE` comme demandé — plus grave que
« le marqueur n'a pas de style » : il ne s'affichait quasiment
jamais.** `FormFactory.php` ne vérifiait que `isset($directive
['require'])` — la convention **d'un seul fichier**,
`Dvups_adminForm.php` (3 champs, `"directive" => ["require"=>true]`).
La convention utilisée **partout ailleurs** (plus de 100 occurrences
dans `src/`, `ContestForm.php` compris) est `FH_REQUIRE => true` **au
niveau du champ**, jamais relue à cet endroit. Résultat mesuré sur le
HTML réellement rendu, avant correctif : aucun des 6 champs
`FH_REQUIRE => true` de `ContestForm` (`active`, `admin_status`,
`date_start`, `date_end`, `cover`, `reference`) ne portait d'astérisque
ni d'attribut `required` — l'information d'obligation n'existait nulle
part à l'écran.

**Les deux conventions sont acceptées désormais**, la première trouvée
gagne (même principe que `dsort`/`dsortjoin` + `order`/`orderjoin` au
chantier précédent — un motif qui semble récurrent dans ce dépôt).
`required='required'` posé sur le contrôle (validation navigateur
réelle, pas seulement visuelle) ; l'astérisque devient `<span
class="dv_required" aria-hidden="true">*</span>` — coloré
(`--dv-color-danger`), **pas lu par les lecteurs d'écran**, l'obligation
venant de l'attribut `required` du contrôle. `aria-required` non ajouté
en plus : un `required` HTML5 expose déjà `aria-required="true"` dans
l'arbre d'accessibilité, un ajout aurait été redondant.

**`<label for>` posé, vérifié correspondant — et une limite trouvée en
vérifiant.** `FormFactory::$fieldid` est bien l'`id=` posé par défaut
sur le contrôle pour la plupart des types (confirmé en lisant chaque
`__xxx()` du fichier) — **sauf** `FORMTYPE_RADIO` et
`FORMTYPE_CHECKBOX`. `__radio()` pose `$directive` (donc cet `id=`) sur
le `<label>` de **chaque option**, pas sur l'`<input>` — l'id est donc
**dupliqué** autant de fois qu'il y a d'options. `__checkbox()` ne lit
même pas `$directive`. **Mesuré, pas supposé** : un premier essai posant
`for=` sur tous les types a produit, au clic sur le libellé « Actif »
(radio), un focus sur un **champ complètement différent** du formulaire
(l'id dupliqué désignant un `<label>`, pas un contrôle). Corrigé :
`for=` **omis** pour `RADIO`/`CHECKBOX` plutôt que posé sur une
correspondance fausse — ces deux types gardent l'astérisque et (pour
RADIO) l'attribut `required`, mais celui-ci atterrit sur leurs
`<label>` d'options (inerte, pas d'effet de validation réelle) : **la
case « validation navigateur gratuite » n'est donc tenue que pour les
types de champ autres que radio/checkbox** — signalé, pas corrigé
(changer où `__radio()`/`__checkbox()` posent leurs attributs est plus
large qu'un lien libellé↔champ, un autre chantier).

**La preuve, HTML réel, avant / après** :

```
avant (date_start, FH_REQUIRE=>true) :
<label class='dv_label '>Date start </label>
<input class='form-control' id='contest-date_start' type='date' ... />
  -- aucun astérisque, aucun required, nulle part

après :
<label for='contest-date_start' class='dv_label '>Date start <span class="dv_required" aria-hidden="true">*</span></label>
<input class='form-control' required='required' id='contest-date_start' type='date' ... />
```

**Clic réel, les 10 champs non-radio/checkbox de `ContestForm`** :
chaque libellé cliqué place le focus exactement sur son `id` —
10 sur 10, vérifié par comparaison `document.activeElement.id ===
label.getAttribute('for')`. Contraste de l'astérisque mesuré :
**4,85:1** sur le fond blanc de la modale, au-dessus du seuil AA
(4,5:1).

**Régression, essayée** : formulaire Contest rechargé, les 12 champs
toujours présents et remplis correctement, aucune rupture visuelle.

## 40 — Point 1 : le gabarit en blocs — recensement propre, conversion faite

**Recensement exhaustif, avant toute ligne de gabarit touchée**, dans
`dclass/devupsjs/` **et** tout `src/` (lecture seule) :

- `dform.js` : **0** sélecteur `tr`/`td` de formulaire — confirmé ;
- `model.js:228`, le seul `parents("tr")` cité par le prompt : vise la
  ligne d'un **datatable** lors d'une suppression (`model._delete`),
  pas un formulaire — confirmé ;
- `model._formdata()` (`model.js:290`, le sérialiseur **réellement
  utilisé** à l'envoi, appelé par `dform.js:106`) : lit par
  `form.find('input')`/`find('textarea')`/`find('select')` —
  insensible à la structure parente, `<tr>` ou `<div>` ne change rien ;
- `model._formdatacustom(tbody)` (`model.js:272`), qui lit bien des
  `<tr>` par position : **0 appelant** trouvé nulle part dans le dépôt
  — code mort, sans influence sur la décision ;
- dans `src/` : les seuls `tr`/`td`/`table` trouvés sont (a) le tri par
  glisser-déposer de datatables (`slideCtrl.js`, `statusCtrl.js`,
  `chapterCtrl.js`, `component.js` — tous scopés sur `#dv_table`, sans
  rapport avec un formulaire) et (b) des gabarits HTML de la barre
  d'outils du bloc-éditeur (`cmstextForm.js`/`collectorForm.js`, un
  `<table>` généré pour des boutons, sans rapport avec le gabarit
  `FormFactory`).

**Aucune dépendance trouvée — conversion faite**, pas la version
conservatrice. `<table class='table'>` → `<div class='dv_form_rows'>` ;
chaque `<tr><td>$label</td><td>$valeur</td></tr>` →
`<div class='... dv_field form-group'><div class='dv_field_label'>
$label</div><div class='dv_field_value'>$valeur</div></div>`.
`form-group` atterrit naturellement sur ce conteneur bloc (l'endroit où
Bootstrap l'attend). `$hidden` posé **une seule fois**, en attribut —
retiré de la liste de classes (0 règle `.hidden` dans `main.css`,
confirmé, classe Bootstrap 3 morte).

**CSS** (`dv_style.css`, jetons de `dv_theme.css`) : `.dv_field` en
flex, libellé à 30 % de large à côté de la valeur au-dessus de 576px
(seuil déjà utilisé ailleurs dans ce dépôt, `d-sm-flex`), empilé en
dessous. Bordure de séparation légère (`--dv-color-border`) à la place
des bordures de `.table` retirées.

**Vérifié, aux deux largeurs, sur les deux formulaires** : 1440px,
`flexDirection: row` (côte à côte, confirmé par `getComputedStyle`,
pas seulement par capture — la capture à cette largeur est trompeuse à
l'œil nu dans la vignette réduite de l'outil, zoomée pour confirmer) ;
390px, `flexDirection: column` (empilé), **aucun débordement** sur les
deux formulaires (`modal.scrollWidth == modal.clientWidth`,
`document.documentElement.scrollWidth == 390`).

## 41 — Point 3 : `</form>`, une seule variable pour les deux

**Compte dans `src/` (lecture)** : ~25 fichiers `*Form.php`
surchargent la liaison automatique `formaction`↔`formbutton` de
`FormManager::__construct()` (qui dérive toujours les deux du **même**
`$action`, jamais divergents) avec deux paramètres **indépendants**
(`$action`/`$button`, parfois `$dataform['action']`/`$button`) — un
risque structurel réel, présent dans le code, pas seulement
théorique.

**Échantillonné sur 5 modules** (`Dvups_admin`, `Town`, `Gender`,
`Notificationbroadcasted`, `Message`) jusqu'à leurs points d'entrée
réellement appelés : chaque `render()` généré (même gabarit, même
générateur) appelle systématiquement `__renderForm(getFormData(...),
true)` — `$button` toujours vrai quand `$action` l'est. **Compte de
divergence réellement observée sur les chemins échantillonnés : zéro**
— une bombe non amorcée, désamorcée quand même, comme demandé.

**Corrigé** : `$hasform = (bool)$entitycore->formaction;`, une seule
variable, lue deux fois (ouverture du `<form>`, fermeture dans
`$formbutton`) — plus jamais `$entitycore->formbutton` pour la
fermeture. Si l'un des deux points de lecture doit changer un jour,
l'autre le dira au premier test manuel au lieu de laisser un `<form>`
mal fermé en silence.

## Point 6(a) — Le bouton d'envoi

Fait en même temps que les points 1 et 3 (même bloc de code, même
commit). `btn-success` (vert, confirmation de succès) → `btn-primary`
(`--dv-color-primary` déjà fourni par cette classe dans `main.css`,
rien de nouveau à poser) — l'envoi est une action, pas une
confirmation. Pleine largeur **seulement** sous 576px (modale étroite) ;
en ligne, largeur automatique au-dessus. Pas de bouton d'annulation
ajouté en modale — la croix de fermeture existe déjà.

**La preuve, par le test qui compte le plus (règle 13) : un envoi de
fichier réel, de bout en bout.** Fichier PNG 1×1 valide (67 octets)
attaché par `DataTransfer` à `#contest-cover`, bouton « Save » cliqué
pour de vrai. Réponse serveur :

```json
{"success":true,"contest":{... "src_cover":"http://127.0.0.1:3334/uploads/contest_cover/227fb4a....webp" ...}}
```

Le fichier a été reçu, traité (converti en `.webp`), enregistré.
**Aucun avertissement PHP** — une première tentative avec un faux PNG
(8 octets arbitraires) avait produit trois avertissements dans
`Dfile.php` (hors périmètre, pas touché) à la lecture des dimensions
d'une image invalide — refait avec un PNG réellement valide pour lever
l'ambiguïté, succès propre, sans avertissement.

**Régression, essayée, les deux formulaires** : 12 champs de Contest,
9 champs de Collector (fichier, deux champs `lang`, `select` à 112
options) tous rendus et fonctionnels. Validation navigateur native
vérifiée : champ `date_start` vidé → `form.checkValidity() === false`,
message « Please fill out this field. » — la case « validation
gratuite » tient pour les types non-radio/checkbox (voir point 2).
Moteur de liste vérifié intact après ces changements CSS/gabarit :
8 lignes, recherche, export, action de groupe, boutons de ligne tous
présents sur `contest/list`.

## 42 — Point 4 : `FORMTYPE_INJECT_COLLECTIONFORM`, reconfirmé vestige

**Déjà établi au chantier du 26/10** (`BACKOFFICE-NOTES.md` §21,
antérieur à ce chantier) : zéro occurrence d'`injectioncollection`
dans tout `src/`. **Reconfirmé ici, de zéro** (grep frais, pas une
reprise de l'ancien chiffre) : toujours zéro occurrence, rien n'a
changé depuis. Verdict inchangé : vestige, pas une fonctionnalité
cachée quelque part.

**Non supprimée** (publique, un module hors dépôt pourrait s'y
référer), **non implémentée à l'aveugle** (aucun exemple réel à suivre
— une fonctionnalité, pas un défaut de style, la décision de
conception appartient au propriétaire). **Ce qui manquait, ajouté
maintenant** : la note du 26/10 vivait dans `BACKOFFICE-NOTES.md`,
pas sur le `define()` lui-même, comme ce chantier le demande
explicitement — commentaire posé directement sur
`FormManager.php:33`, pour que le prochain qui tombe sur cette
constante dans le code (pas dans le journal) comprenne en une
lecture.

## 43 — Point 5 : la modale, largeur plafonnée — le lien mesuré, pas supposé

**Le lien mesuré d'abord, comme demandé** : ouverture réelle d'une
modale de formulaire à 390px (déjà fait au point 0, reconfirmé ici) —
**aucun débordement**. `getComputedStyle(modal).maxWidth` valait déjà
`100%` **avant** ce point, par une règle externe non identifiée avec
certitude (pas dans `dv_style.css` — vraisemblablement SweetAlert2 ou
`main.css`, tous deux lecture seule). Le défaut « zone grise à droite »
du chantier du socle **ne se reproduit pas** sur ce qui est mesurable
aujourd'hui.

**Corrigé quand même**, par robustesse plutôt que par correction d'un
bug actif : les deux largeurs fixes (`.dv_modal{width:800px}` et
`.dv_modal .box-container{width:800px}`) remplacées par `width:100%;
max-width:800px` — pour ne plus dépendre d'une règle externe non
identifiée avec certitude, qui pourrait changer ou disparaître sans
que ce fichier le sache.

**Les deux conteneurs de modale, vérifiés tous les deux** :
`#dialog-container` (`layout.blade.php`, dans mon périmètre) **et**
`Datatable::dialogBox()` (`Datatable/`, lecture seule ce chantier-ci).
Établi en lisant son HTML : **aucune largeur en dur** dans
`dialogBox()` elle-même (`style=""` vide sur `.dv_modal`) — elle hérite
entièrement des deux règles CSS ci-dessus, désormais plafonnées sans
y toucher. **Et, mesure en plus, utile pour la suite** :
`Datatable::dialogBox()` n'est **pas** appelée depuis `Datatable.php`
lui-même (sa seule mention dans ce fichier est commentée,
`//. $this->dialogBox()`) mais **deux appelants réels existent dans
`src/`** (lecture seule) : `ModuleComicbook/.../chapter/scanpage.
blade.php` et `ModuleGallery/.../dv_image/index.blade.php` — vivante,
pas morte, juste invoquée ailleurs que supposé en lisant seulement
`Datatable.php`.

**`admin/views/default/dialogform.blade.php`** (le `style="width:
{{$width}}px"` cité) — **vérifié mort avant d'y toucher** : son seul
appelant dans tout le dépôt est un bloc **commenté** de
`FormManager::renderForm()` (`dclass/devups/Form/FormManager.php:142`),
zéro occurrence de `"default.dialogform"` ailleurs, `src/` compris.
Laissé tel quel, avec un commentaire expliquant pourquoi — pas
supprimé, pas corrigé (son `$width` en dur n'est jamais rendu en
pratique, corriger du code mort n'aurait aucun effet mesurable).

**La preuve, clic réel, 390px** : `contest/form` rouvert — largeur
calculée `370px`, `max-width: 800px`, aucun débordement
(`modal.scrollWidth == modal.clientWidth`, page à 390px pile).
Capture envoyée dans la session.

## Point 6(b) — L'éditeur absent de la modale : mesuré, rien à corriger aujourd'hui

**Les deux canaux, établis par lecture puis par un test réel, pas
supposés.**

1. **Canal `FormFactory`-interne** (`$entitycore->addjs`/`addcss`,
   émis à l'intérieur même du `$form` retourné par `__renderForm()`) —
   **grep exhaustif sur tout `src/`** : `$this->addJs(`/`$this->
   addCss(` avec `tinymce` ou `jquery-ui` → **0 occurrence**, dans
   aucun des ~100 fichiers `*Form.php` du dépôt. Ce canal existe, mais
   **aucun module ne s'en sert**, pour rien.
2. **Canal page-complète** (`Controller::$jsfiles`/`$cssfiles` via
   `script()`/`style()` de `layout_module.blade.php`) — rendu
   uniquement dans le `<head>`/`@section('jsimport')` de la page
   complète, jamais dans le fragment AJAX que `databinding.bindmodal()`
   injecte.

**Lequel des deux est réellement en cause ? Aucun — parce qu'aucun
formulaire standard, ouvert en modale, ne demande un éditeur riche
aujourd'hui.** Vérifié en ouvrant `collector/form` (fichier **et**
champ riche, le candidat du point 0) en modale : `typeof tinymce ===
'undefined'`, aucune balise `<script src="...tinymce...">` dans le
DOM — **et c'est attendu**, pas un défaut : `CollectorForm::buildForm()`
ne fait aucun appel `addJs()`. Les vues qui chargent réellement
TinyMCE (`collector_form.blade.php`, `cmstext/form.blade.php`,
`reportingmodel/form.blade.php`, `tree_itemManager.js`,
`postForm.js`) sont toutes des **pages complètes séparées**, jamais
des modales — chacune avec son propre contrôleur JS qui appelle
`tinymce.init()` directement, chargé par le `@section('jsimport')` de
**sa propre page**, jamais affecté par la perte du fragment AJAX
puisqu'il n'y a pas de fragment AJAX dans ce chemin. `/admin/api/
contest/form?1&id=7` (l'URL testée au chantier du socle) a été
reconfirmée ici comme étant bien le formulaire standard à 12 champs de
`ContestForm` — lui non plus ne demande d'éditeur par aucun des deux
canaux.

**Donc : rien à corriger** — ni « changer de canal » ni « faire porter
ses propres actifs au fragment », puisqu'aucun formulaire ne s'appuie
sur l'un ou l'autre pour un éditeur. Mesuré, pas supposé, et le
résultat est qu'il n'y a pas de bug à ce jour sur ce point précis.

**Ce qui reste utile pour la suite — établi par un test direct,
infrastructure seule, aucun module touché** : le canal
`FormFactory`-interne **fonctionnerait** s'il était utilisé. Preuve :
`databinding.bindmodal()` (la fonction qui injecte le HTML d'un
formulaire dans la modale) utilise `.html(data)` de jQuery — testé en
injectant directement un `<script>` dans un appel de `bindmodal()` :
le script **s'exécute** (jQuery réécrit et réinjecte les balises
`<script>` d'un contenu passé à `.html()`, contrairement à un simple
`innerHTML` qui ne les exécute jamais). Le canal page-complète, lui,
ne survivrait pas — confirmé par lecture, sa sortie n'est jamais
renvoyée par l'endpoint AJAX de la modale.

**Le piège d'idempotence, confirmé mécaniquement, pas seulement
redouté** : le même test, `bindmodal()` appelé deux fois avec le même
`<script>`, **exécute le script deux fois** — aucune déduplication,
`.html()` remplace et ré-exécute systématiquement tout ce qui est
injecté. Si un module venait un jour à charger un éditeur par ce
canal, c'est à **son propre script** (pas à `FormFactory`) de se
garder contre une double initialisation à la réouverture (par exemple
vérifier `tinymce.get(id)` avant `tinymce.init()`) — `FormFactory`
elle-même n'a ni état ni mémoire d'un chargement précédent, et ne peut
pas empêcher une ré-exécution côté HTML/JS.
