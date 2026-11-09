# Changelog

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [7.1.0] - 2026-11-09 - finitions et dettes du cœur (ÉTAPES A à F)

**PHP 8.2 minimum (8.1 en fin de vie).** La 7.0.0 annonçait 8.1 mais ne
s'installait pas réellement à cause de `minishlink/web-push` v10 (qui exige
déjà PHP ≥ 8.2) - `composer.json` l'affiche maintenant correctement.
Intégration continue sur PHP 8.2, 8.3 et 8.4.

### Sécurité

- `QueryBuilder::whereIn()`/`in()`/`notIn()`/`whereInReverse()`/
  `between()`/`whereBetween()` : les valeurs utilisateur sont désormais
  liées en paramètre PDO plutôt qu'interpolées via `qb_sanitize()`
  (guillemets simples sans échappement) - une valeur contenant une
  apostrophe pouvait injecter ou casser la requête, y compris dans la
  forme réputée "sûre" (`whereIn()`).
- Opérateurs Lazyloading `rein`/`notin` (`Datatable/Lazyloading.php::filterswicher()`) :
  mêmes correctifs côté appelant (`whereInReverse()` paramétrée,
  `notin` désormais éclaté par virgule comme `in`).
- `qb_sanitize()` (`dclass/dvups_functions.php`) : dépréciée, plus
  aucun appelant interne ; échappe désormais via `PDO::quote()` au lieu
  de concaténer des guillemets.
- `Router::manageServe()` (point d'entrée partagé par `Admin`,
  `AdminService`, `ModuleConfig`, `ModuleLog`, `ModuleUser` - et tout
  module généré) : le garde de session était un bloc entièrement
  commenté (aucun refus réel). Remplacé par un vrai refus 401 JSON,
  mêmes exceptions que `Router::manage()` (`connexion-action`,
  `$this->public_access`).

### Comportements modifiés

- **D1** : une ligne d'une entité traduite du socle (`Tree`,
  `Tree_item`, `Status`, `Cmscontent`, `Notificationtype`,
  `Reportingmodel`, `Country`, `Dvups_entity`) sans aucune ligne
  `<table>_lang` était jusqu'ici invisible pour `find()`/`findOrNull()`/
  `where()->get()`/`first()` (INNER JOIN éliminant la ligne de base
  entière plutôt que ses seules colonnes traduites) - elle apparaît
  désormais normalement, avec ses colonnes traduites à `null`/vides.
- **D3** : `QueryBuilder::get($colonne, $id_lang)` ignorait
  systématiquement l'`$id_lang` explicitement demandé (toujours
  construit en mode bilingue) - respecte désormais la langue demandée.
- **D2** : une colonne non qualifiée (`where('name', ...)`) sur une
  entité traduite pouvait lever "Column ... is ambiguous" (1052) dès
  qu'une jointure de traduction était présente - qualifiée
  automatiquement désormais.
- `ModelTrait::__get()` sur un attribut vraiment inconnu lève
  désormais `UndefinedPropertyException` (`\LogicException`, journalisée
  et transformée en réponse 500 par le gestionnaire global) au lieu de
  rendre silencieusement `null` (`trigger_error()` en dev seulement,
  rien du tout en production).
- `Datatable` : une clé de colonne sans getter correspondant sur
  l'entité rend désormais une cellule vide (+ `Log::warning()`) au lieu
  de faire planter tout l'écran (`\TypeError` fatale).
- `JWT_EXPIRE` vaut désormais `+72 hours` par défaut dans `.env.example`
  (était `false`, jeton sans expiration) - un déploiement existant qui
  ne définit pas explicitement `JWT_EXPIRE` dans son propre `.env`
  n'est pas affecté (la constante effective reste celle de ce fichier,
  jamais celle de l'exemple).

### Corrigé

- Swagger : le menu/la description du serveur codait en dur le port
  `3333` au lieu du port réellement configuré (`__env`).
- Un échec GD (fichier source illisible, PNG/JPEG corrompu, image 1×1
  limite) au redimensionnement d'une image uploadée faisait planter
  toute la requête (500) au lieu de conserver l'original déjà enregistré
  et de renoncer proprement à la seule variante redimensionnée.
- Création d'un `Reportingmodel` : chemin de gabarits inexistant dans
  `ReportingmodelForm::buildForm()` (champ "type" sans aucune option) et
  `Reportingmodel::setObject()` qui renvoyait `$this` (traité comme une
  erreur de champ par `Controller::formWithPost()`, affiché
  "[object Object]" côté client) - même anti-pattern corrigé par
  précaution sur `Notification::setEmittedBy()`/`setRedirect()` et
  `Push_subscription::setEndpoint()`.
- Contraintes d'unicité manquantes (`tree.name`, `tree_item`, `status`).
- **D6** : une relation déclarée `"not"` nullable dans le datacore ne se
  répercutait jamais dans l'annotation Doctrine générée
  (`@JoinColumn` sans `nullable=false`).
- **D7** : régénérer la même entité deux fois de suite (`core:g:crud`)
  dupliquait son bloc `require` (Entity/Form/Datatable/Controller) dans
  le fichier de dépendances du module.
- **Compatibilité MySQL 8 en mode strict** (`STRICT_TRANS_TABLES`, le
  défaut de MySQL 8 - absent par défaut de MariaDB, utilisé en local par
  la plupart des développeurs) : la CI GitHub (MySQL 8) échouait depuis
  la 7.0.0, invisible en local. `DBAL::createDbal()`/`_createDbal()`
  omettaient entièrement une colonne traduite jamais fournie par
  l'appelant (`Tree::create(['name' => ...])` sans `label`) de l'INSERT
  de la ligne `_lang` - chaque colonne traduite reçoit désormais
  systématiquement une valeur (celle fournie pour cette langue, sinon
  celle de la langue par défaut, sinon une chaîne vide). `Model::create()`
  (`DBAL::_createDbal()`) ne transmettait non plus que les colonnes
  explicitement fournies par l'appelant à l'INSERT principal - toute
  colonne `NOT NULL` sans défaut SQL mais avec un défaut PHP non nul sur
  l'entité (ex. `Country::$iso3 = ''`) et omise par l'appelant est
  désormais complétée par ce défaut (les colonnes nullable restent
  inchangées : `NULL` si omises, comme avant).
- **Caches créés automatiquement** : `cache/` est entièrement ignoré par
  git - un clone neuf (celui de la CI) n'a donc aucun de ses
  sous-dossiers. `cache/rights`, `cache/lang`, `cache/trees`,
  `cache/log` et `cache/ratelimit` se recréaient déjà seuls à l'usage ;
  `cache/views` (compilation Blade) ne le faisait pas
  (`file_put_contents(.../cache/views/xxx.php): Failed to open stream`
  au premier rendu de vue) - `BladeD` (point d'entrée unique de toute
  instanciation de Blade dans ce framework) le crée désormais elle-même
  si besoin, sans dépendre de `php devups install`.
- **Valeur vide sur une colonne numérique/booléenne/temporelle** : un
  formulaire HTML soumet toujours des chaînes - un champ laissé vide
  (ex. `phonecode` sur `CountryForm`) arrivait tel quel (`''`) jusqu'à
  l'INSERT/UPDATE SQL. MySQL non strict (MariaDB en local) convertissait
  silencieusement en `0`/`false` ; `sql_mode` strict (MySQL 8, celui de
  la CI) le refusait avec une erreur SQL illisible (1366 "Incorrect
  integer value"). `DBAL` résout désormais `''` en amont, pour les
  colonnes Doctrine `integer`/`smallint`/`bigint`/`float`/`decimal`/
  `boolean`/`date`/`datetime`/`time` : `NULL` si la colonne est
  nullable, sinon le défaut PHP déclaré sur l'entité, sinon (numérique/
  booléen) `0`/`false`, sinon (date/datetime/time, sans défaut sûr) une
  erreur de validation lisible plutôt qu'un plantage SQL.
- **Perte silencieuse de données sur une mise à jour partielle (risque
  réel en 7.0.0 sur MySQL non strict)** : toute mise à jour passant par
  `CrudTrait::updateAction()`/`Dvups_adminController::updateAction()`
  (le chemin utilisé par tous les écrans d'administration) construit
  l'entité par id seul (`new Entite($id)`), jamais hydratée avant
  application du formulaire. `ModelTrait::entityKeyUpdate()` traitait
  alors CHAQUE colonne actuellement non modifiée par le formulaire comme
  "modifiée à sa valeur PHP par défaut (souvent `NULL`)" et l'écrivait
  en base - par exemple, changer uniquement le rôle d'un administrateur
  effaçait silencieusement son `login`, son mot de passe et son email.
  Sous MySQL non strict (7.0.0, MariaDB en local), ceci passait
  inaperçu (colonne `NOT NULL` réécrite avec une valeur par défaut
  implicite, avec un simple warning) ; sous `sql_mode` strict (MySQL 8,
  la CI) l'erreur 1048 ("Column 'login' cannot be null") le révélait.
  `Controller::form_fillingentity()` hydrate désormais l'entité depuis
  la base avant d'y appliquer le formulaire (`formWithPost()` ne cible
  que les clés soumises, jamais une remise à zéro) : une mise à jour
  n'écrit plus que les colonnes réellement soumises par le formulaire.
  `entityKeyUpdate()` ignore en complément, par sécurité, toute colonne
  encore à `NULL` quand aucun état hydraté n'est disponible.
- `composer phpstan` ne trouvait aucune des constantes définies via
  `define('x', dv_env(...))` dans `config/constante.example.php`
  (`dbname`, `__prod`, `GOOGLE_CLIENT_ID`...) dans un clone neuf (`config/
  constante.php`, qui les déclare en local via `const`, est ignoré par
  git) - la CI n'avait donc jamais pu exécuter cette étape avec succès.
  `phpstan.neon.dist` charge désormais `config/constante.example.php` en
  `bootstrapFiles` ; un `composer install`/`update` crée en complément
  `config/constante.php` depuis l'exemple s'il est absent.

### Migrations v7.1

Une seule migration ajoutée par ce chantier, dans l'ordre d'exécution :

1. `2026_11_09_v71_a5_unique_constraints.php` - contraintes d'unicité
   `tree.name`, `tree_item`, `status` (ÉTAPE A.5).

### Dépréciée

- `qb_sanitize()` (`dclass/dvups_functions.php`) - plus aucun appelant
  interne, gardée pour compatibilité d'un éventuel appelant externe.
- `QueryBuilder::whereIn()`/`in()`/`notIn()` forme chaîne brute
  (ex. `"1,2,3"`) - n'accepte plus qu'une liste d'entiers séparés par
  des virgules (`InvalidArgumentException` sinon) ; aucun appelant du
  dépôt n'utilisait cette forme.
- `FORMTYPE_INJECT_COLLECTIONFORM` (`dclass/devups/Form/FormManager.php`) -
  jamais implémentée, jamais référencée ailleurs dans le dépôt.
- `__route_cache`/`ROUTE_CACHE` - `Router::runCache()`/`cacheRoute()`
  sont du code mort par construction (retour précoce inconditionnel),
  quelle que soit cette valeur.

## Mise à jour depuis la version 7.0

1. **Migration** : `php devups migrate --dry-run` puis `php devups migrate`
   (une seule migration, non destructive : ajout de contraintes
   d'unicité - voir "Migrations v7.1" ci-dessus).
2. **PHPStan** : `composer phpstan` (niveau 1 désormais, était niveau 0)
   est ajouté à la CI - sans effet sur une installation existante, les
   836 erreurs préexistantes sont gelées dans `phpstan-baseline.neon`.
3. **Points d'attention** (comportements changés, voir "Comportements
   modifiés" ci-dessus) :
   - une ligne d'une entité traduite sans ligne `_lang` (D1) qui était
     jusqu'ici invisible va réapparaître dans les listes/recherches -
     vérifier qu'aucun code appelant ne comptait implicitement sur son
     absence ;
   - tout code appelant qui accédait délibérément à une propriété
     inexistante d'une entité (en s'appuyant sur le `null` silencieux
     rendu auparavant) lèvera désormais `UndefinedPropertyException` -
     chercher les accès `->` sur un nom de colonne incertain ;
   - définir explicitement `JWT_EXPIRE` dans son propre `.env` si le
     comportement "jeton sans expiration" doit être conservé
     (`JWT_EXPIRE=` vide, ou ne pas copier la valeur de `.env.example`).
4. Aucune rupture de compatibilité d'API dans ce chantier (uniquement
   des correctifs et deux dépréciations de formes d'appel déjà inutilisées
   dans ce dépôt).

## [7.0.0] - 2026-11-08 - chantier de durcissement (ÉTAPES D à R)

### Ajouté

- **Migrations** : `php devups migrate` / `migrate:status` (table de
  suivi `dvups_migration`) remplacent l'exécution manuelle
  `mysql -u ... < fichier` ; détection automatique des migrations
  destructives (`DROP`/`TRUNCATE`/`DELETE`), confirmation requise
  (`--force` ou `oui` à l'invite), refusées en production sans `--force` ;
  `--dry-run`, `--step` ; formats `.sql` (inchangé) et `.php`
  (`up(PDO $pdo)`).
- **Journalisation applicative** (`dclass/devups/Log.php`, table
  `dvups_log`) : regroupement par empreinte, compteur d'occurrences,
  niveau minimal configurable (`LOG_MIN_LEVEL`), rétention
  (`LOG_RETENTION_DAYS`).
- **Rate limiting générique** (`dclass/devups/RateLimiter.php`) sur le
  login admin, le login front et la vérification/le renvoi de code
  d'activation ; IP cliente déterminée de façon fiable
  (`Util::get_IP_address()`, `TRUSTED_PROXIES`).
- **Réinitialisation de mot de passe** : jeton dédié à usage unique
  (`random_bytes(32)` base64url, seul son `sha256` est stocké,
  `RESET_PASSWORD_TOKEN_TTL_MINUTES`), distinct du code court
  d'activation ; réponse identique que le compte existe ou non (pas
  d'énumération de comptes).
- **"Se souvenir de moi"** sans mot de passe (ni son hash) dans le
  cookie : token aléatoire dont seul le hash est stocké, avec
  expiration, rotation à chaque usage et invalidation à la déconnexion
  ou au changement de mot de passe.
- **Mots de passe** : `password_hash()`/`password_verify()`
  (`Dvups_admin`, `User`) ; migration transparente d'un ancien hash
  `sha1()` au prochain login réussi, sans réinitialisation forcée.
- **Country/Localisation** : données de référence (~195 pays) via
  `php devups location:seed` (idempotent).
- **Web Push réel** (`minishlink/web-push`, clés VAPID dans `.env`) pour
  `Push_subscription::sendWebPush()`, avec suppression automatique d'un
  abonnement expiré (HTTP 410).
- **Documentation** : `php devups api:doc` (instantané OpenAPI),
  `php devups doc:guide` (pages HTML statiques par module/entité),
  Swagger UI back-office (`/admin/apidocs`) distinguant les routes
  front (JWT) et admin (session), protégée par `can('read', 'Documentation')`
  - action "virtuelle" synchronisée par `rights:sync` comme n'importe
  quelle action d'entité, donc attribuable à un rôle non maître.
- **Langues** : `t_lang($cle, $iso, $params = [])` (lecture directe
  hors session) ; `lang:scan`/`lang:missing` basés sur le tokenizer PHP
  (`token_get_all()`, gère correctement les apostrophes échappées) au
  lieu d'une regex.
- `phpstan` (niveau 0, `composer phpstan`) ajouté en dépendance de
  développement.

### Modifié

- **Rupture d'API** : la route de réinitialisation de mot de passe
  passe de `/resetpassword/:id` à `/resetpassword` (le lien e-mail ne
  contient plus `u_id` ni le code court, uniquement le jeton dédié).
- `Util::get_IP_address()` durci en place (même nom/signature) : ne
  fait confiance à `X-Forwarded-For` (et assimilés) que si
  `REMOTE_ADDR` figure dans `TRUSTED_PROXIES`.
- `Notification::on()`/`braodcast()` (faute de frappe historique)
  deviennent des alias dépréciés (`@deprecated` + un avertissement au
  premier appel) de `toUser()`/`toAdmin()`/`toRole()` et `broadcast()`
  - `on()` n'a pas été migré (couvre des cas, entité arbitraire/SMS/
  session, que la nouvelle API ne couvre pas).
- `TableTemplateRender::templateTableRowDetail()` : bug latent corrigé
  (double-indexation de son argument) qui cassait l'écran "détail" de
  **toute** entité admin (`_show`), jamais exercé par un test avant
  cette session.
- Schéma `activationcode_expired_at` (`user`) : `DATE` → `DATETIME`
  (précision à la minute, nécessaire à `ACTIVATION_CODE_TTL_MINUTES`).
- Schéma `emaillog`/`dvups_log` : reconstruits avec un schéma structuré
  (destinataire/sujet/statut/erreur ; empreinte/compteur) au lieu de
  l'ancien format générique `object`/`log`.

### Supprimé (ruptures de compatibilité)

- `ModuleGallery`/`Dv_image` (plus aucune dépendance dans le code :
  `Cmsresource` et `Tree_item_image` héritent désormais directement de
  `ImageCore`).
- `Local_content`/`Local_content_key`/`Local_content_lang`/
  `Page_mapped` (éditeur de traduction en base - les traductions vivent
  dans `lang/<iso2>.php`) ; `langt()` (remplacée par `t_lang()`/`t()`).
- `Continent`/`Department`/`District`/`Region`/`Town`
  (`ModuleLocation`) - seule l'entité `Country` est conservée.
- `Notificationbroadcasted` (entité fantôme jamais générée).
- `ModuleDatabase`/`Request_history` (module mort depuis l'origine,
  jamais alimenté).
- Code mort de push : `pushNotificaiton()`, `cloudMessagingPush()`,
  `fcmPushNotification()`, `testPushNotif()`, colonne
  `Push_subscription::subscription_id`.
- `Cmstext`/`Imagecms` renommés et reconstruits en `Cmscontent`/
  `Cmsresource` (ce dernier porte directement son fichier, comme
  `ImageCore`, au lieu d'une table de jointure).

### Corrigé

- Appels à des méthodes statiques inexistantes trouvés par analyse du
  dépôt (`Util::getIp()` → `Util::get_IP_address()` dans `Country`,
  `*Form::render()` → `*Controller::formView()`/`detailView()` dans
  plusieurs modules legacy).
- `Dvups_role` sans `getAlias()` (cassait l'écran détail du rôle).
- `DBAL::_createDbal()` utilisait `Dvups_lang::all()` (toutes les
  langues) au lieu du cache des langues actives.
- `Dvups_admin::updatePassword()` manquante.
- Détection d'un ancien hash sha1() (`password_get_info()['algo']` vaut
  `null`, jamais `0`) qui empêchait toute migration transparente de mot
  de passe.
- Clé de traduction tronquée (`Aucun élément pour l\'`) retirée de
  `lang/en.php`/`lang/fr.php`.

## Mise à jour depuis la version précédente

Mise à jour depuis la v6.x. Checklist pour un environnement existant
(hors base de test, qui est toujours recréée automatiquement par
`tests/bootstrap.php`) :

1. **Migrations** : exécuter `php devups migrate --dry-run` pour
   revoir le SQL, puis `php devups migrate` (confirmer les migrations
   destructives une par une - voir la liste ordonnée et annotée dans
   `TODO.md` section "Migrations"). Les 10 migrations existantes à ce
   jour couvrent, dans l'ordre : nouveau schéma `dvups_log`, suppression
   de l'éditeur de traduction en base, suppression de
   `request_history`, nouveau schéma `emaillog`, renommage CMS (avec
   reprise de données), suppression des tables de localisation
   inutilisées, ajout de colonnes "remember me", passage de
   `activationcode_expired_at` en `DATETIME`, ajout de la colonne
   `push_subscription.endpoint`, ajout des colonnes du jeton de
   réinitialisation de mot de passe.
2. **Nouvelles constantes `.env`** (toutes optionnelles, valeur par
   défaut documentée dans `.env.example` et `config/constante.example.php`) :
   `TRUSTED_PROXIES`, `RESET_PASSWORD_TOKEN_TTL_MINUTES`,
   `ACTIVATION_CODE_TTL_MINUTES`, `RATE_LIMIT_*` (6 variables),
   `LOG_MIN_LEVEL`, `LOG_RETENTION_DAYS`, `VAPID_PUBLIC_KEY`/
   `VAPID_PRIVATE_KEY`/`VAPID_SUBJECT`, `LANG_COLLECT`. En particulier,
   définir `TRUSTED_PROXIES` si l'application est derrière un reverse
   proxy/load balancer (sinon l'IP cliente calculée ignorera
   systématiquement `X-Forwarded-For`, ce qui est le choix sûr par
   défaut mais peut casser un rate limiting basé sur l'IP réelle du
   client).
3. **Ruptures de compatibilité** à vérifier sur du code appelant
   personnalisé (hors coeur du dépôt) : `/resetpassword/:id` →
   `/resetpassword` ; `langt()` supprimée (utiliser `t()`/`t_lang()`) ;
   `ModuleGallery`/`Dv_image`, `Continent`/`Department`/`District`/
   `Region`/`Town`, `Local_content*`/`Page_mapped`,
   `Notificationbroadcasted`, `Request_history` supprimés ;
   `Cmstext`/`Imagecms` renommés en `Cmscontent`/`Cmsresource` ;
   `Notification::on()`/`braodcast()` dépréciées (toujours
   fonctionnelles, mais à migrer vers `toUser()`/`toAdmin()`/
   `toRole()`/`broadcast()` quand possible).
4. Si l'instance tourne derrière un rôle non maître pour la
   documentation (Swagger UI), relancer `php devups rights:sync` pour
   créer l'action `Documentation`/`read` et l'attribuer au(x) rôle(s)
   concerné(s).
