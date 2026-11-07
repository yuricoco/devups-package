# Journal — chantier "harmonisation" (2026-11-04)

Repo `backend`, branche `v3`. Mandat : réparer les 7 écrans de liste en
panne, supprimer `Page_local_content` (obsolète), payer la dette de
vérification mobile. MODE CONTINU — décisions non tranchées écrites ici,
les plus conservatrices.

## Point 0 — rejeu des 7 écrans

Les 7 écrans plantent **toujours**, chacun avec une erreur réelle qui
correspond presque exactement à celle du tableau du mandat (mesuré en
rejouant `listView()`/`datatable()` de chaque Controller, pas en relisant
le code) :

| Écran | Erreur réelle mesurée |
|---|---|
| `page_local_content/list` | `TypeError: Lazyloading::lazyloading(): Argument #2 ($qbcustom) must be of type ?QueryBuilder, int given` |
| `postcomment/list` | `call_user_func(): ... class Postcomment does not have a method "getUsername"` |
| `wallet/list` | `call_user_func(): ... class Wallet does not have a method "getReference"` |
| `analytic/list` | `Error: Call to undefined method Analytic::classpath()` (dans `listView()`, pas `datatable()`) |
| `evaluation/list` | `Error: Call to undefined method Evaluation::classpath()` (idem) |
| `post/list` | `Error: Call to a member function hydrate() on int` |
| `orders/list` | `Error: Class "Order" not found` — à la fois dans `CrudTrait::listView()` (héritée, `self::$classname=ucfirst("order")`) ET dans `OrdersController::datatable()` (`new Order()` littéral) |

Aucun des 7 n'est sorti du chantier. `listView()` seul échoue souvent sur
« View [default.index] not found » — limitation CLI déjà documentée
(pas de contexte HTTP complet), pas la vraie cause ; la vraie cause
mesurée est celle du tableau ci-dessus (via `datatable()` ou, pour
`analytic`/`evaluation`, directement dans `listView()` avant tout rendu).

## Point 1 — `Page_local_content` : suppression

**Références recensées** (grep complet sur `backend/`) : les 5 fichiers du
module, `devups.modulelang.php` (4 lignes `require`), `ModuleLang.php`
(2 `use`, 1 instanciation, 8 `case` du switch `services()`),
`modulelangCore.json` (1 entrée de 167 lignes dans `listentity`,
relations vers `page_mapped` et `local_content`), et les 2 fichiers
générés `config/dvups_configurations.php`/`module_configurations.php`
(2+1 entrées) — ces deux derniers ne sont **pas** dans le périmètre
d'écriture donné explicitement, mais sont des fichiers **générés** (non
édités à la main ailleurs dans ce dépôt, confirmé par l'historique git —
`dvups_:update` est la commande établie pour les régénérer, utilisée dans
plusieurs chantiers précédents documentés dans `BACKOFFICE-NOTES.md`).
**Décision, la plus conservatrice disponible** : plutôt que de les
éditer à la main (risque de les désynchroniser du générateur) ou de les
laisser avec une entrée fantôme (contredit la définition de « fini » du
point 2, qui exige l'absence de `page_local_content` du datacore), j'ai
utilisé l'outil de régénération du dépôt lui-même, avec la sauvegarde
préalable déjà conventionnelle dans ce projet. **Signalé explicitement
ici**, comme demandé, puisque ni `config/` ni la table `dvups_role` ne
figuraient dans le périmètre d'écriture donné.

**Sauvegarde hors dépôt avant toute écriture** :
`C:\tmp\harmonisation_backups\` — `dvups_configurations_before.php`,
`module_configurations_before.php`, `dvups_role_configs_before.json`
(dump des 3 lignes `dvups_role.configs`, 27563 octets).

**`php devups dvups_:update` lancé**, puis **diff réel** (jamais le
message console, documenté menteur par les chantiers précédents) :
- `dvups_configurations.php` : exactement les 2 entrées `Page_local_content`
  retirées (20 lignes), rien d'autre.
- `module_configurations.php` : exactement 1 entrée de 9 lignes retirée
  sous `ModuleLang`, rien d'autre.
- `dvups_role.configs` : rôle id=1 — l'entrée `Page_local_content` retirée,
  `Page_mapped` glisse d'un index (ré-indexation du tableau PHP), aucun
  changement de contenu ailleurs ; rôles id=2 et id=3 — **aucun
  changement** (n'avaient jamais listé `Page_local_content`).

**`composer dump-autoload` lancé** après la suppression des 5 fichiers
(classmap Composer, `vendor/` — hors dépôt git) : nécessaire, sans quoi
`class_exists('Page_local_content')` déclenchait un `include` raté sur un
fichier disparu (warning, pas fatal, mais sale). Avertissements
d'ambiguïté de classe affichés par la commande (`MessageTable`,
`MessageForm`, `PostcommentForm` dupliquées entre modules) :
**préexistants, sans rapport avec ce point** — notés pour le point 2
(`PostcommentForm` existe dans `ModuleComment` ET `ModulePost`).

**`Page_mapped`** : a ses propres `Entity`/`Form`/`Controller`/`Datatable`/
`Core.json` complets, indépendants — **pas** utilisé exclusivement par
`Page_local_content` (la seule référence croisée était la relation
`manyToOne` déclarée côté `page_local_content`, maintenant disparue avec
lui). Conforme à la règle dure : **non touché**.

**Compte de la table `page_local_content`** : **0 ligne**. Table
conservée (règle dure #2), non supprimée.

**Preuve** : `Page_local_content`/`Page_local_contentController`
n'existent plus (`class_exists()` → `false`) ; `Local_content::count()`
== 87 (inchangé) ; `Page_mapped`/`Page_mappedController` existent
toujours. **Limite honnête, découverte en vérifiant, pas causée par ce
point** : `Local_contentController::datatable()`
(`Local_contentTable::...->buildindextable()->getTableRest()`) s'arrête
silencieusement (ni exception, ni erreur fatale visible — un `exit()`
quelque part dans `dclass/devups/Datatable/Datatable.php`, lecture seule)
après une notice sur une propriété `lang` non déclarée sur `Local_content`.
**Vérifié identique avant ce point** (`git stash` sur les changements de
ce point, même script, même résultat) : préexistant, sans rapport avec la
suppression de `Page_local_content`, non corrigé (hors mandat). `Tree::constant()`
intact sur les 16 arbres.

## Point 2 — `postcomment` : `username`

**Ce que la datatable attend réellement** (mesuré dans
`dclass/devups/Datatable/Datatable.php:1255-1261`, lecture seule) : pour
une colonne sans point (`'value' => 'username'`), le moteur appelle
`get` + `ucfirst($value)` directement sur l'entité — ici
`Postcomment::getUsername()`. Ce n'est ni une colonne calculée
(`addColumn()`), ni une valeur venant de l'entité jointe `user` (ce
mécanisme-là existe aussi dans ce moteur, pour les colonnes à point comme
`user.id`, mais n'est pas celui que `username` utilise).

**`username` est une colonne réelle de la table** `postcomment`
(`@Column(name="username", ...)`, propriété `protected $username`,
confirmé dans `Postcomment.php`) — **pas une donnée à dupliquer**, donc
aucune dénormalisation : la colonne existe déjà, nativement, pour
accueillir le pseudonyme des commentateurs invités (non connectés) ;
`Postcomment::profile()` l'utilise déjà ainsi en repli quand
`$this->user->getId()` est vide. Il manquait simplement le getter — même
motif que `getId()` existant juste au-dessus dans la même classe.

**Correction** (`Entity/Postcomment.php`) : ajout de `getUsername()`
(`return $this->username;`). En rejouant l'écran, le plantage avance
immédiatement au colonne suivante du même datatablemodel
(`'value' => 'comment'`) avec la même cause — `getComment()` absent,
alors que `comment` est aussi une colonne réelle (`protected $comment`).
Ajouté par le même motif, dans le même fichier, pour le même point : sans
lui l'écran reste cassé sur la toute prochaine colonne, donc hors de
corriger les deux la mesure de "l'écran s'affiche" (critère de fini) reste
fausse. Les colonnes `user.id`/`Post.id` du même datatablemodel n'ont rien
demandé de plus : `getId()` est déjà fourni par la classe de base
`dclass/devups/model/Model.php:557`, héritée par `User` et `Post`.

**Résultat du rejeu** (`datatable(1,10)`) : plus aucune `TypeError` sur
une méthode manquante. Il reste `InvalidArgumentException: View
[default.pagination] not found` — **même limitation CLI déjà
documentée** au point 0 (`Genesis::getView()` dépend de la variable
globale `$viewdir`, peuplée par le bootstrap HTTP de `admin/`, absente de
ce script de rejeu en ligne de commande) : le fichier
`admin/views/default/pagination.blade.php` existe réellement sur disque,
ce n'est donc pas un gabarit manquant, seulement un chemin de vue non
configuré hors contexte HTTP. Hors périmètre de ce point (framework,
lecture seule), non corrigé, cohérent avec la méthodologie déjà actée au
point 0/1.

**Effet de bord découvert en cours de route, signalé pour mémoire** :
`git status` a révélé une modification non voulue, sans rapport avec ce
chantier, dans `database/migration/migration_20261018.sql` (un "ces"
parasite ajouté à la fin d'une ligne de commentaire, fichier de migration
documentant la conversion des colonnes d'argent en DECIMAL). Corrigée
immédiatement par une édition ciblée (retrait du seul mot parasite,
aucune autre ligne touchée) avant tout commit de ce point — aucune donnée
de migration, aucun calcul, aucun montant n'était concerné (le mot
parasite était dans un commentaire SQL, pas dans une instruction), mais
signalé ici par prudence puisque ce fichier documente une migration
d'argent.

## Point 3 — `wallet` : `reference` — ARRÊT PARTIEL, comme demandé

**Même mécanisme que le point 2** : `WalletTable::buildindextable()`
(`Datatable/WalletTable.php`) déclare `['value' => 'reference']` sans
point — le moteur appelle `get` + `ucfirst($value)` sur l'entité, donc
`Wallet::getReference()`. `reference` est une colonne réelle de la table
`wallet` (`varchar(255)`, confirmé par `SHOW COLUMNS`), pas une donnée
calculée ni dupliquée. **Ajouté** (`Entity/Wallet.php`) : `getReference()`
et, par le même raisonnement que pour `postcomment.comment` au point 2 (la
colonne suivante du même datatablemodel, `amount`, decimal(12,2) réel,
sans getter), `getAmount()` — toutes deux de simples retours de propriété,
aucun calcul, aucune écriture, aucun montant modifié.

**Je m'arrête ici, comme le mandat le demande explicitement pour ce
point**, parce que la colonne suivante (`user.firstname`) expose un
problème structurel, pas un simple getter manquant :

- Pour une colonne à point, le moteur (`Datatable.php:1237`, lecture
  seule) fait `$entity->{strtolower($join[0])}->hydrate()` — il lit
  **directement** la propriété dynamique `user` sur l'entité (pas un
  getter), en attendant un objet de relation déjà chargé. **`Wallet` n'a
  aucune propriété `$user` déclarée, aucune annotation `@ManyToOne`** —
  confirmé par relecture complète de `Entity/Wallet.php`. Rejoué
  (`WalletController::datatable(1,10)`) : `Notice: Propriété non-définie
  via __get() : user de la class Wallet` (`ModelTrait.php:51`), puis le
  script s'arrête silencieusement — **même limitation déjà documentée au
  point 1** (`Local_contentController::datatable()`), pas une nouvelle
  panne provoquée ici.
- **Mesuré en base** (`SHOW COLUMNS FROM wallet` / `SHOW COLUMNS FROM
  user`) : `wallet` n'a **aucune** colonne `user_id` ; c'est `user` qui a
  une colonne `wallet_id`. La relation existe bien, mais **dans le sens
  inverse** de celui que la datatable réclame. Une simple annotation
  `@ManyToOne` sur `Wallet` ne suffirait pas : il faudrait un lookup
  inversé sur mesure (`User::where('wallet_id', $this->id)->first()`),
  une construction de relation nouvelle, pas l'exposition d'une colonne
  existante — ce n'est plus "poser l'attribut", c'est modéliser une
  relation qui n'existe pas encore côté `Wallet`.
- **`shop.shopname` est pire** : recherche complète (`find ... -iname
  "*shop*"` sur `src/` et `dclass/`, puis `SHOW TABLES LIKE '%shop%'` en
  base) — **aucune table, aucune entité `Shop` n'existe nulle part** dans
  ce dépôt ni cette base. `shopping/` est seulement le nom du dossier
  englobant (`ModuleWallet`, `ModulePayment`, `ModuleSticker`,
  `ModuleOrder`) — aucun rapport avec une entité "boutique". Cette colonne
  ne peut pas être réparée sans inventer une table et une entité
  complètes : hors mandat ("répare l'affichage", pas "invente une
  donnée").

**Conséquence** : `wallet/list` plante toujours, maintenant sur
`user.firstname` (plus sur `getReference()`). Je ne touche ni
`WalletTable::buildindextable()` (qui déclare ces deux colonnes), ni
`Wallet.php` au-delà des deux getters déjà ajoutés, ni la base — décision
du propriétaire requise sur : (a) donner à `Wallet` une vraie relation
inversée vers `User`, ou retirer la colonne `user.firstname` de la liste
admin ; (b) retirer `shop.shopname`, qui ne peut correspondre à aucune
donnée existante. Les deux sont des choix de modélisation/produit, pas
des corrections de plomberie, donc non tranchés ici.

## Point 4 — `classpath()` : correction d'une hypothèse du mandat

**Le mandat supposait une seule cause** ("`Evaluation extends
ModelSerializer`, `Analytic` est très probablement dans la même
situation — vérifie-le"). **Mesure faite, c'est faux** : il y a **deux
chaînes d'héritage distinctes**, pas une.

- `Model.php` (racine commune, pas de namespace déclaré) :
  `class Model extends \dclass\devups\model\ModelSerializer`, `use
  ModelTrait;`, et définit lui-même `classpath()`
  (`dclass/devups/model/Model.php:159`, lecture seule).
- `Evaluation` (`src/edition3ag/ModuleContest/Entity/Evaluation.php`)
  **extends `ModelSerializer` directement** (grep confirmé) — hérite de
  tout ce que `ModelSerializer` offre, mais pas de ce que `Model` ajoute
  par-dessus, donc pas `classpath()`.
- `Analytic` (`src/devupsfront/ModuleAnalytic/Entity/Analytic.php`)
  **extends `\stdClass` directement** (pas `ModelSerializer`, contraire à
  l'hypothèse du mandat), et compense en faisant lui-même `use
  ModelTrait;` — donc n'hérite ni de `ModelSerializer::jsonSerialize()`
  (qu'il réécrit lui-même) ni de `classpath()`.

**Recensement complet** (grep sur tout `src/`) : 3 entités extends
`ModelSerializer` directement — `Evaluation`, `Book_stats`,
`Stat_history` (`ModuleComicbook`, hors périmètre d'écriture de ce point,
non touchées) ; 2 entités reproduisent le motif `extends stdClass` + `use
ModelTrait` sans passer par `Model` ni `ModelSerializer` — `Analytic` ET
**`Support_ads`** (même module `ModuleAnalytic`, **pas dans le tableau du
mandat**, découverte en vérifiant la famille comme demandé). Seules
3 de ces 5 entités appellent réellement `classpath()` quelque part
(`Evaluation`, `Analytic`, `Support_ads`, confirmé par grep des
contrôleurs) — `Book_stats`/`Stat_history` ne l'appellent jamais, donc
aucun symptôme visible chez elles aujourd'hui, mais la même lacune existe
si un jour elles l'appellent.

**Chemin A exclu par la mesure** : `Resource/js/evaluationCtrl.js`,
`analyticCtrl.js`, `support_adsCtrl.js` existent réellement sur disque
(fichiers non vides) — `classpath()` déclare un vrai script de formulaire
de liste, pas un appel mort. Retirer l'appel supprimerait une
fonctionnalité JS réelle de l'écran admin : ce n'est pas une réparation,
c'est une régression déguisée.

**Chemin choisi : B pour la famille `ModelSerializer`, variante locale
pour l'autre famille** (le mandat n'anticipait pas deux familles, donc
pas de chemin tout fait pour la seconde) :
- `dclass/devups/model/ModelSerializer.php` (seule exception d'écriture
  dans `dclass/`, **ajout seul**, aucune méthode existante modifiée) :
  copie conforme de `Model::classpath()`. **Affecte les 3 entités qui en
  héritent directement** (`Evaluation`, `Book_stats`, `Stat_history`) —
  aucun effet sur `Model` et sa descendance : `Model` définit déjà SA
  PROPRE `classpath()`, qui prime (résolution de méthode PHP), donc rien
  ne change pour toute entité qui passe par `Model`.
- `Analytic`/`Support_ads` n'héritent pas de `ModelSerializer` : l'ajout
  ci-dessus ne les atteint pas. Comme `ModuleTrait`/`dclass/` restent
  hors périmètre, la méthode a été ajoutée **localement, à l'identique**,
  directement dans `Entity/Analytic.php` et `Entity/Support_ads.php`
  (les deux dans `ModuleAnalytic`, périmètre d'écriture du point 4).

**Ce que le caller voulait** (vérifié, pas supposé) : les trois
JS existent réellement et sont déclarés dans `listView()` avant
`renderListView()` — l'appel n'est pas inutile, contrairement à ce que le
mandat envisageait comme possibilité.

**Résultat mesuré** (rejeu de `listView()`/`datatable()` pour les 3
écrans concernés) : plus aucune `Error: Call to undefined method
classpath()`. Reste `InvalidArgumentException: View [default.index]` /
`[default.pagination] not found` — même limitation CLI déjà documentée
aux points 0/1/2/3, pas une nouvelle panne.

## Point 5 — `post/list` : `hydrate()` sur un entier — cause dans `ModulePost`

**Cause exacte mesurée** (`dclass/devups/Datatable/Datatable.php:1237`,
lecture seule) : pour une colonne à point, le moteur fait
`$entity->{strtolower($join[0])}->hydrate()` — il suppose que la
propriété nommée est déjà une relation chargée. `PostTable::
buildindextable()` déclare `['value' => 'status._key']`. Mais
`Post::$status` (`Entity/Post.php`) est annoté
`@Column(name="status", type="boolean", options={"default":"1"})` — une
**colonne plate**, pas une relation — confirmé en base
(`SHOW COLUMNS FROM post` : `status` = `tinyint(1)`, défaut `1`, **pas**
de colonne `status_id`). Le moteur essaie donc `(int)1 ->hydrate()` : la
cause exacte du message mesuré au point 0. **La cause est dans
`ModulePost`** : la déclaration de colonne de la datatable traite un
booléen comme une entité jointe — ni une coquille du framework, ni un id
mal chargé par une vraie relation (il n'y a pas de relation du tout
ici) : `post.category_id` existe bien et EST une vraie relation
(`$category`, `@ManyToOne(\Tree_item)`), mais `status` n'en a jamais été
une.

**Corrigé à la source** (pas de `if(is_int())` ajouté devant l'appel,
comme le mandat l'interdit) : `Datatable/PostTable.php`, la déclaration
elle-même — `'status._key'` → `'status'` (colonne plate, comme la donnée
réelle qu'elle est). Conséquence mécanique : `Post::getStatus()`
manquant, ajouté (`Entity/Post.php`, retour brut de la propriété, aucune
transformation). En rejouant, le plantage avance à la colonne suivante du
même datatablemodel, `locked` — colonne réelle elle aussi
(`protected $locked`), même cause, même correction : `getLocked()`
ajouté. `createdAt` (colonne suivante) ne plante pas : fourni par la
classe de base (`Model`/`DateTimeTrait`, héritage déjà en place).

**Blocage suivant, cause HORS `ModulePost` — nommé, non corrigé, comme
demandé** : la dernière colonne, `User.username`, plante sur
`User::getUsername()` absent. `User` vit entièrement dans
`src/devupsfront/ModuleUser/Entity/User.php` — **hors du périmètre
d'écriture de ce chantier** (ni `ModulePost`, ni un module explicitement
listé ; "le reste de `backend/src/`" est lecture seule par mandat).
Vérifié avant de nommer : `$this->username` est une vraie propriété,
utilisée directement ailleurs dans `User.php` lui-même (ligne 279/326),
donc le même motif que `postcomment.username`/`wallet.reference`
existe réellement là aussi — mais corriger `User.php` demande une
décision de périmètre que ce point n'accorde pas. `post/list` plante
donc encore aujourd'hui, sur cette seule cause restante, hors de ce que
ce point autorise à toucher.

## Point 6 — `orders/list` : `Order` n'existe pas, l'entité est `Orders`

**Où le nom singulier vient vraiment** (mesuré, pas supposé) : la vraie
convention de ce module est **cohérente au singulier partout** —
`Resource/js/orderCtrl.js` (confirmé sur disque), `OrderForm`,
`OrderTable` existent tous sous ce nom. **Seule l'entité** s'appelle
`Orders` (`Entity/Orders.php extends OrderCore extends \Model`, lecture
seule confirmée dans `dclass/extends/OrderCore.php`) — une exception
isolée à la convention du module, pas une règle de pluralisation
systématique à corriger ailleurs.

`self::$entityname = "order"` (constructeur) est **correct** — il
alimente `self::$classname = ucfirst("order") = "Order"` via
`Controller.php:103` (lecture seule), et c'est CE calcul précis qui est
faux pour cette seule entité : `OrderTable::init()` déclarait
`\Order $order`, `OrdersController::datatable()`/`createAction()`/
`updateAction()`/`detailView()`/`deleteAction()`/`deletegroupAction()`
instanciaient/appelaient tous `Order` (classe inexistante) — mesuré via
`class_exists('Order') === false`.

**Corrigé dans `ModuleOrder`, sans toucher `CrudTrait`** (comme demandé) :
- `OrdersController.php` : ajout d'un `listView()` propre à ce contrôleur
  (même motif que tous ses contrôleurs frères — Postcomment, Wallet,
  Post, Evaluation, Analytic définissent chacun leur propre `listView()`)
  au lieu de laisser `CrudTrait::listView()` dériver `self::$classname`
  automatiquement. Ce contrôleur n'utilisait déjà `CrudTrait` QUE pour
  `listView()` — toutes les autres méthodes étaient déjà réécrites
  localement, seulement avec `Order` au lieu de `Orders` partout. Chaque
  `Order` remplacé par `Orders` dans ces méthodes (6 sites d'appel).
- `OrderTable.php` : `\Order` → `\Orders` dans `init()` ET dans
  `buildusertable()`/`buildfronttable()` (3 fermetures supplémentaires,
  même fichier, même défaut, corrigées pour cohérence puisqu'elles
  auraient fatal de la même façon dès qu'appelées).

**Résultat mesuré** : `class "Order" not found` a disparu des deux
rejeux (`listView()` et `datatable()`). `listView()` ne plante plus que
sur la limitation CLI déjà documentée. `datatable()` avance réellement
dans le rendu des lignes et plante maintenant sur `User::getUsername()`
manquant (colonne `User.username` du même datatablemodel) — exactement
la même lacune que celle nommée au point 5 (`ModuleUser`, hors périmètre
d'écriture de ce chantier) : pas une nouvelle panne, le même défaut
partagé réapparaît ici parce que `ModuleOrder` l'utilise aussi. Non
corrigé, pour la même raison déjà documentée.

**Découverte en recensant "où le nom singulier vient vraiment" dans tout
le module — hors du périmètre strict de ce point ("orders/list"),
signalée sans être touchée** : le même défaut `Order`/`Orders` existe
aussi dans `Form/OrderForm.php` (formulaire admin de création/édition,
4 sites) et **`Controller/OrdersFrontController.php`** (flux de commande
**client**, pas admin — `Order::last()->id + 1` pour générer
`num_facture`, `Order::find()`, `new Order()` via `hydrateWithJson`,
lignes 402/417/425/486). C'est plus étendu que ce que le mandat
caractérisait ("deux pannes" : `CrudTrait` héritée + un appel littéral) —
en réalité la même faute de frappe historique sur le nom de classe
traverse tout le module. Je ne touche ni `OrderForm.php` ni
`OrdersFrontController.php` : le premier est un écran différent
(formulaire, pas liste) et le second est du code de commande **client**
en production, plus risqué — tous deux hors du périmètre explicite de ce
point ("orders/list" seulement) — à trancher par le propriétaire comme
un chantier séparé s'il le souhaite.

**Hors scope, signalé seulement, comme le mandat l'exige — non touché** :
le chantier "status" avait trouvé des appels à des clés de status
inexistantes dans ce module. Vérifié présent, non modifié :
`Status::getStatus("confirm", "order")` —
`Controller/OrdersFrontController.php:309` et `:487` ;
`Status::getStatus('ongoing', self::class)` —
`Entity/Subscription.php:141`. Aucune décision prise sur les données de
status, conformément à la règle dure.

## Point 7 — dette de vérification mobile

**`appv2/.env`** : basculé vers l'environnement de test (dérogation
explicite du mandat, faite en dernier, après tous les points back-office).
**Noms des deux variables changées, jamais leur contenu** :
`EXPO_PUBLIC_API_BASE_URL` (pointait la production, pointe maintenant le
backend local) et `EXPO_PUBLIC_ALLOW_PRODUCTION_WRITES` (remis à `false`
— il avait été retrouvé à `true`, "troisième dérive vers la production"
déjà signalée par `MIGRATION-GENRES-NOTES.md §9`). **Laissé en état test
à la fin**, comme demandé. Aucun code d'`appv2/` modifié.

**Émulateur** : `Pixel_9_Pro_XL` démarré, démarrage confirmé
(`adb wait-for-device` + `sys.boot_completed=1`), tunnel
`adb reverse tcp:3334 tcp:3334` établi vers le backend local.

**Reconstruction native bloquée, mesuré, pas une hypothèse** :
`npx expo run:android` échoue — `Gradle` ne peut pas télécharger
`kotlin-compiler-embeddable` (`Could not GET
'https://repo.maven.apache.org/...'`, `No such host is known
(repo.maven.apache.org)`) : **cet environnement n'a pas d'accès réseau
sortant vers Maven Central**, limitation de l'environnement
d'exécution, pas un défaut d'`appv2/`. Contournement tenté, **sans
modifier aucun fichier d'`appv2/`** : réutilisation du client de
développement déjà installé sur l'émulateur (APK du 13 octobre,
antérieur mais confirmé par `git log` couvrir déjà tout le code du genre
et de la langue), connecté à un Metro démarré aujourd'hui
(`npx expo start --dev-client`, qui ne nécessite aucune compilation
native) pour servir le bundle JS avec le `.env` d'aujourd'hui.

**Résultat du contournement — écran blanc, cause identifiée** : le
bundle JS (React Native 0.88.1, `package.json` actuel) et le client
natif déjà installé (React Native 0.86.3, figé au 13 octobre) sont
incompatibles — `logcat` confirme `React Native version mismatch`
explicite au démarrage. Plantage réel mesuré : `TypeError: undefined is
not a function` dans `react-native-worklets`
(`NativeWorklets.native.ts:459`, `workletsModuleProxy.loadUnpackersWithCode`
absent du binaire natif figé) — empêche le rendu de tout écran. **Cause :
l'écart de version natif/JS causé par l'impossibilité de reconstruire
dans cet environnement, pas un défaut du code livré.** Une tentative de
secours via `expo start --web` (même backend, mêmes routes, aucune
compilation native requise) n'a pas non plus produit de rendu exploitable
dans le temps disponible (le serveur de dev a répondu par le manifeste
JSON brut plutôt que la page web) — complication distincte, non
poursuivie, la preuve API ci-dessous étant déjà solide.

**Vérification de repli, directe et réelle, pas une relecture de code** :
appel direct des **mêmes routes exactes que l'app** (vérifiées dans
`src/features/reading/api/reading.ts` et
`src/features/discovery/hooks/useBooksList.ts`) contre le backend local
réel :
- `GET /comicbook?gender_id=33` : le filtre par genre **fonctionne**,
  résultats réels rendus (cohérent avec la preuve déjà faite par le
  chantier "genres", §Point 4 de `MIGRATION-GENRES-NOTES.md`).
- Sur ce même appel, `genders[].name` sort en **chaîne simple propre**
  ("Action", "Aventure", "Tranche de vie", "Romance") — le correctif
  `setLang(1)` → `Dvups_lang::defaultLang()` fonctionne réellement pour
  les genres venant du pivot.
- **Écart trouvé en mesurant, pas une hypothèse du prompt** : sur un
  échantillon réel de 30 séries (`GET /comicbook`, page par défaut),
  **11 ont un tableau `genders` vide** (pivot non rempli, cf. §Point 1(a)
  du chantier genres : 36 séries sur 112 seulement). Pour ces 11,
  `GenreTags.tsx` retombe sur `book.category`, dont le champ `name`
  **reste l'objet multilingue non résolu** (`{"fr":"Mangas","en":"Mangas"}`
  mesuré tel quel, 3 exemples réels : id 25/26/29) — `Dvups_lang::
  defaultLang()` n'a été appliqué qu'au chemin `genders` (point 3 du
  chantier genres), **pas** au chemin `category`. Le composant mobile ne
  plante pas (`genreLabel()` extrait `.fr` en repli, code déjà lu et
  confirmé par le chantier genres lui-même, `§9`), mais le résultat est
  **toujours le français**, jamais "la langue demandée" — pour ces
  ~37% de séries de l'échantillon, précisément. Signalé, non corrigé
  (hors périmètre d'écriture de ce point — vérification seulement).
- **Plus large que ce seul écart** : aucun mécanisme n'existe nulle part
  dans `appv2/` pour seulement DEMANDER une langue — aucun répertoire
  i18n, aucun paramètre de langue envoyé par le client API
  (`src/shared/api/client.ts`, lu en entier), aucun sélecteur de langue
  fonctionnel dans `app/settings.tsx` (seul un commentaire en mentionne
  l'intention). Donc "les libellés sortent dans la langue demandée" n'est
  aujourd'hui pas une affirmation vérifiable telle quelle : personne,
  app ou utilisateur, ne peut demander autre chose que ce que le backend
  choisit par défaut. Signalé pour un chantier futur, pas corrigé ici
  (hors périmètre, lecture seule sur `appv2/`).

**Franchement, ce qui ne correspond pas** : la preuve mobile "en direct,
sur écran" demandée par le point 7 n'a **pas pu être obtenue au sens
littéral** (écran blanc, cause identifiée et non imputable au code
livré). Ce qui a été obtenu à la place est une vérification réelle,
mesurée, au niveau de l'API que l'app appelle réellement — plus faible
qu'une capture d'écran, mais réelle et honnête, et elle a quand même
révélé un écart concret (`category` non résolu) qu'une capture d'écran
seule, sur UNE série au hasard, aurait pu manquer si cette série avait eu
un `genders` rempli.

---

# Journal — chantier "order-orders-shop" (2026-11-05)

Repo `backend`, branche `v3`. Mandat : réécrire `Order` en `Orders` partout
(avec garde contre un remplacement aveugle de `order`/tri), annuler `shop`,
poser la méthode qui débloque `post/list`/`orders/list`/`wallet/list`, et
corriger un défaut de langue sur `category.name`. MODE CONTINU — décisions
non tranchées écrites ici, les plus conservatrices.

Périmètre d'écriture : `ModuleOrder`, `ModuleWallet`, `ModuleUser`,
`ModuleComicbook`, `database/migration/`, `aiMemory/`. Reste de
`backend/dclass/`, `appv2/`, `creator/`, `admin/` et reste de `backend/src/`
en lecture seule.

## Point 0 — rejeu des 3 écrans, et mesure de `confirmOrderWithWallet()`

Rejoué via `Controller::datatable()` (bootstrap admin réel,
`admin/header.php` — le bootstrap front donne un faux "View [default.
pagination] not found" par `$viewdir` mal peuplé, artefact CLI déjà
documenté, pas un bug) :

| Écran | Erreur réelle mesurée |
|---|---|
| `post/list` | `TypeError: ... class User does not have a method "getUsername"` |
| `orders/list` | `TypeError: ... class User does not have a method "getUsername"` (même cause que post/list) |
| `wallet/list` | **PAS** `getUsername()` — `die()` dans `ModelTrait::__get()` : `Propriété non-définie : user de la class Wallet`. Correction au mandat : `wallet/list` meurt une étape **avant** d'atteindre un quelconque accesseur `User`, sur l'absence de la propriété `$user` elle-même sur `Wallet`. |

`confirmOrderWithWallet()` — mesuré en simulant exactement
`Request::FrontController()` (reflection + `hasAnnotation()`) contre la
vraie classe, pas en lisant : la méthode ne porte **aucune** annotation
(`@GET`/`@POST`/`@PUT`/`@DELETE`/`@Api`/`@Auth`) — contrairement à
`index()`/`purchase()`/`create()`/`check()` dans le même fichier qui en
portent toutes une. `niceFunction('confirm-order-with-wallet')` résout bien
vers `confirmOrderWithWallet` (donc `method_exists()` est vrai, le routeur
NE tombe PAS sur le chemin REST générique), mais `mapFunction()`
exige ensuite une annotation HTTP ou `@Api` sur la méthode résolue — absente
ici → **405 "Request method X not allowed for this route" pour GET et POST,
mesuré pour les deux**. **`confirmOrderWithWallet()` est MORT : aucun
paiement par portefeuille n'échoue aujourd'hui sur ce chemin, parce qu'il
ne s'exécute jamais, par aucune route.** `subscribeAction()` (même fichier)
mesuré avec la même méthode : **également mort**, même cause exacte.

## Point 1 — `Order` → `Orders`

Recensement complet (`grep` ciblé sur le jeton de classe, pas sur le mot
`order`) : 8 sites, tous dans `ModuleOrder` :
- `OrdersFrontController.php:402,417,425,486` (`confirmOrderWithWallet()`,
  `subscribeAction()` ×3)
- `Form/OrderForm.php:9,127,139,148`

Tous corrigés à l'identique (`Order::` → `Orders::`, `new Order()` →
`new Orders()`) — **aucun fichier renommé, aucune classe satellite
touchée** (`OrderForm`, `OrderTable`, `OrderController`, `orderCtrl.js`
restent). Guard-list relue avant chaque modification : `order_by`,
`orderby()`, `$order_by`, `order=`/`orderjoin=`, `dsort`/`dsortjoin`,
`order_id`, `Orders_item`, `$order` (variable) — **aucun touché**.

Faux positif écarté, mesuré avant d'écarter : `Order_productTable.php`
(`'Order._date'`) — ce n'est PAS le jeton de classe, c'est un nom de
propriété/relation en minuscule par la convention de résolution par point
de `Datatable.php` (`strtolower($join[0])`). Mesuré en plus : cette classe
entière est **du code mort** — aucune entité `Order_product` n'existe dans
tout le dépôt, et aucun appelant ne référence `Order_productTable` nulle
part (confirmé par `grep`). `services.php` l'instancie pourtant
(`order_product.*`) via une classe `Order_productForm` qui n'existe pas non
plus — une fonctionnalité "order_product" entièrement orpheline,
pré-existante, signalée et non touchée (hors périmètre de ce point).

**Preuve, réellement exercée, sans écriture d'argent :**
- `OrderForm::getFormData()` (les deux branches, avec et sans id) : appelé
  réellement — lecture seule, aucune écriture — `new Orders()`/
  `Orders::find()` résolvent correctement après correction.
- `confirmOrderWithWallet()` : appelé réellement dans une transaction PDO
  ouverte puis **annulée** (`Model::beginTransaction()`/`rollBack()`, déjà
  présents dans le cadre, F-02). Le bug de classe est bien corrigé
  (`Orders::find()` résout), mais révèle un **second défaut
  pré-existant, distinct, et HORS périmètre de ce point** : à la ligne
  403, `Orders_item::where("order_id", $order->id)->get()` génère
  `WHERE 1 AND `order_id` IS NULL` — la vraie colonne sur `orders_item`
  est **`orders_id`** (confirmée par le SQL généré, qui SELECT bien
  `orders_item.orders_id`), pas `order_id`. **Ceci contredit directement
  la garde du mandat lui-même**, qui affirmait `order_id` comme "colonne
  réelle en base" à ne pas toucher — mesuré faux, mais la garde explicite
  du mandat prime : non corrigé, nommé ici. Même si la routabilité de
  cette méthode n'avait pas déjà été morte par le point 0, cet appel
  aurait échoué de toute façon.

## Point 2 — `shop` : annulation

Recensement complet de `shop` (insensible à la casse) dans `backend/src/` —
17 fichiers trouvés, tous classés :
- **Dans le périmètre, retiré** : `ModuleWallet/Datatable/WalletTable.php`
  — colonne `'shop.shopname'` dans `buildindextable()`, retirée (aucune
  relation de remplacement : pas de table `Shop`, reconfirmé ici — aucun
  `Shop.php`, aucun `ModuleShop`, aucune migration `CREATE TABLE shop`
  nulle part dans le dépôt).
- **Dans le périmètre, rien à faire** : `ModuleOrder` — aucune référence
  réelle, seulement des faux positifs (`require 'shopping/...'`, icônes
  FontAwesome `fa-shopping-cart`/`fa-shopping-bag`).
- **Hors périmètre, listé, non touché** :
  - `ModuleUser` : `has_shop` (indicateur booléen sur `user`,
    `Core.json` + `UserTable.php:152`) — pas une relation vers une entité
    `Shop`, un champ legacy mal nommé (probablement "a une boutique").
  - `ModuleCatalog` : `ProductTable.php:58`, `'Market.shopname'` —
    relation vers une entité `Market` existante, pas vers `Shop`.
- **Faux positifs écartés partout, par mot composé** : `shopping` (nom de
  projet datacore, `src/shopping/`), `shop_ref` (colonne réelle de
  `Collector`, `ModuleComicbook` — chaîne simple, aucune relation vers
  `Shop`), classe CSS `left-content-shoping` (faute de frappe, sans
  rapport).

**Aucune table supprimée** — il n'y en avait aucune à supprimer (0 trouvée,
compté).

## Point 3 — `User::getUsername()`, et la relation inversée `Wallet` → `User`

Vérifié avant d'écrire : `username`/`firstname` sont des colonnes réelles
sur `UserCore` (lecture seule), mais **aucun accesseur nulle part**
(`UserCore.php` ni `User.php`) — pas même sous une autre forme qui les
masquerait. Ajoutés sur `User.php` (l'enfant, jamais `UserCore` lecture
seule) : `getUsername()`, `getFirstname()`.

Famille auditée sur tout `backend/src/` (grep de tous les `'User.xxx'`/
`'user.xxx'` en colonne de datatable) : seuls `.username`, `.firstname`,
`.id` (déjà couvert par `getId()`) et `.email` (un seul site,
`NotificationbroadcastedTable`, `ModuleNotification`, **hors périmètre**)
sont utilisés. `.email` et `.lastname` **listés, non ajoutés** : ni l'un ni
l'autre ne débloquent l'un des 3 écrans nommés par ce point, et la règle 7
(aucune donnée personnelle nouvellement exposée) méritait une décision
explicite plutôt qu'un ajout silencieux — `email` circule déjà par ailleurs
via `jsonSerialize()` (pas une nouvelle exposition), `lastname` n'a
aujourd'hui aucun appelant mesuré.

**Relation inversée `Wallet` → `User`**, mesurée avant construite : la FK
est sur `user.wallet_id` (pas `wallet.user_id`). Le mécanisme déclaratif de
`Datatable`/`QueryBuilder` (`with()`/`hydrate()` par point,
`Datatable.php:1237`, `QueryBuilder.php:1544`) ne sait joindre QUE le sens
PROPRIÉTAIRE (FK sur l'entité courante) — `Wallet` n'a d'ailleurs AUCUNE
propriété `$user` déclarée du tout. `Model::__hasone()` (`dclass`, déjà
présent, lecture seule, `Model.php:455`) sait déjà faire cette lecture
inversée (`User::where("this.wallet_id", $this->getId())`) — exactement le
bon sens. Vérifié en direct contre une ligne réelle (user #1 / wallet #1)
avant d'écrire : correct. Utilisé via une colonne fermeture dans
`WalletTable::buildindextable()` — aucune migration, aucun `dclass/`
touché.

**Rejeu réel (bootstrap admin), après ces deux correctifs :**
- `post/list` : **RÉPARÉ** — rendu complet confirmé (`<a class='User'
  href='...'>edition 3ag</a>`).
- `wallet/list` : **RÉPARÉ** — rendu complet confirmé (`Atemkeng Azankang`
  affiché via la relation inversée), tri (`order=amount`) testé intact.
- `orders/list` : la cause `User::getUsername()` est levée, mais révèle
  un **AUTRE blocage immédiat, mesuré, pas anticipé par ce point** :
  `Orders`/`OrderCore` (`dclass`, lecture seule) ne porte **aucun**
  accesseur sur ses propres colonnes réelles. Ajoutés sur `Orders.php`
  (l'enfant, jamais `OrderCore`) — même principe que les accesseurs
  `User` : `getTotalamount()`, `getNettopay()`, `getNum_facture()`. Non
  demandé littéralement par ce point (qui ne cite que
  `User::getUsername()`), mais mesuré comme le vrai blocage suivant —
  décision conservatrice, mode continu.
  **Reste ENCORE bloqué** après ce correctif : `paymentref`, référencé par
  `OrderTable.php:33` (colonne de table sans `'value'`, donc accès direct
  `$entity->paymentref`) ET par le code mort de `subscribeAction()` —
  **colonne INEXISTANTE** (aucune `@Column` nulle part sur `Orders`/
  `OrderCore`), confirmée par un `die()` dans `ModelTrait::__get()`.
  Contrairement aux autres, ce n'est pas un simple accesseur manquant :
  nécessite soit une migration, soit une décision produit (peut-être
  `payment.reference`, via la relation `$payment` déjà existante sur
  `Orders`) — non tranché ici, décrit pour le propriétaire. Tri
  (`order=createdAt` sur post/list) vérifié intact par ailleurs.

## Point 4 — `category.name` : hypothèse réfutée, vrai défaut trouvé ailleurs

**Hypothèse du mandat (collision d'alias `l`) : mesurée FAUSSE.** Lu le
code réellement exécuté, pas la version commentée : `QueryBuilder::
initSelect()` route `'l.name'` (dans `with('category', ['l.name'])`) vers
`$_with_lang['category']`, **jamais** vers un JOIN alisé `l` — c'est
`DBAL::getLangValues()` qui la résout ensuite, via une requête **séparée**,
avec l'alias `dl`, pas `l`. Le bloc de `QueryBuilder.php:101-122` que le
mandat semble citer est **entièrement commenté**, donc mort ; son
équivalent vivant (`:1582-1600`) ne construit un join alisé `l` QUE pour la
traduction de l'entité PRINCIPALE (`comicbook`), jamais pour une relation
`with()`. Aucune collision possible entre les deux.

**Prémisse mesurée du mandat (`category.name` toujours en français quand
`genders` est vide, 11/30) : NE SE REPRODUIT PAS.** Mesuré contre le vrai
backend local tournant (`curl` direct sur `127.0.0.1:3334`, pas une
hypothèse) : **0/30** cas de chaîne française forcée sur `GET /comicbook`
(liste) — `category.name` y est **toujours** l'objet bilingue `{fr,en}`,
que `genders` soit vide ou non, déjà toléré par `appv2` (`GenreTags.tsx`).

**Cause réelle de l'apparence "pareil dans les deux langues", vérifiée en
base, pas dans le code** : 89 lignes de `tree_item_lang` sur 95 ont une
valeur `fr`/`en` **identique** (mots d'emprunt : Mangas, Comics, Webtoon,
BDs, Light Novel) ; les 6 qui diffèrent réellement (ex. `id=34` : "Aventure"
/ "Adventure") **se résolvent correctement** dans les deux langues, mesuré.
Le mécanisme fonctionne ; c'est une question de contenu/traduction, pas de
code — **aucune correction de mécanisme n'est nécessaire**.

**Vrai défaut trouvé en comparant les DEUX points d'entrée (liste vs
détail), pas en relisant un seul** : `GET /comicbook/:id` ne portait PAS
`category.name` du tout (absent du JSON, pas même en français) —
`ComicbookFrontController::detail()` n'appelait jamais
`with('category', ['l.name'])`, à la différence d'`index()` dans le même
fichier. **Corrigé en une ligne**, même convention qu'`index()`.

**Preuve** : les 30 mêmes séries re-interrogées sur `GET /comicbook/:id`
après correctif — **29/30** renvoient désormais l'objet bilingue complet ;
**1/30** (`id=191`) reste sans `category.name`, mais `category_id` est
réellement `NULL` en base pour cette série (vérifié) : aucune série
n'a de catégorie non résolue qui ne le soit pas légitimement.

Aucune migration, aucun `dclass/` touché.

## Commits

| Commit | Contenu |
|---|---|
| `a10e578` | Point 1 : `Order` → `Orders`, 8 sites, `confirmOrderWithWallet()`/`subscribeAction()` morts (mesuré) |
| `ba058eb` | Point 2 : `shop` annulé dans le périmètre, aucune table à supprimer |
| `401dd68` | Point 3 : `User::getUsername()`/`getFirstname()`, relation inversée `Wallet`→`User`, `Orders::getTotalamount()`/`getNettopay()`/`getNum_facture()` |
| `57c947a` | Point 4 : hypothèse réfutée, `category.name` absent sur `detail()` corrigé |

Aucun `git push`. `php -l` propre sur chaque fichier écrit.

## Ce qui reste cassé, nommé, pas corrigé

- `orders/list` : bloqué sur `paymentref`, colonne inexistante — décision
  produit/migration requise (point 3).
- `Order_productTable.php`/`Order_productForm` (`services.php`) :
  fonctionnalité orpheline entière, aucune entité, aucun appelant réel —
  signalée, hors périmètre de ce chantier.
- `has_shop` (`ModuleUser`) et `Market.shopname` (`ModuleCatalog`) : hors
  périmètre d'écriture, non audités plus avant.
