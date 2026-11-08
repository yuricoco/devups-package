# Devups

Framework PHP orienté back-office : à partir des *datacores* (fichiers JSON qui
décrivent les entités), Devups génère entités Doctrine, formulaires, tables et
contrôleurs, et fournit un back-office de suivi prêt à l'emploi (rôles, configuration,
CMS, arbres, notifications, mails...).

## Prérequis

- PHP 8.1 ou plus (extensions `pdo_mysql`, `mbstring`, `xml`, `gd`, `zip`, `curl`, `intl`)
- MySQL / MariaDB
- Composer

## Installation

```bash
composer create-project spacekola/devups mon-projet
cd mon-projet

cp config/constante.example.php config/constante.php   # versionné : aucun secret dedans
cp .env.example .env                                   # jamais versionné : renseigner ici

php devups install          # base, schéma, compte admin (dv_admin / admin)
php devups serve            # http://127.0.0.1:<APP_PORT>/  et  /admin/
```

Toute la configuration propre à une installation (base, URLs, SMTP, JWT, FCM,
Google...) vit dans `.env`. `config/constante.example.php` lit ces valeurs avec
`dv_env()` ; les variables d'environnement du serveur sont prioritaires sur le fichier.

## Premier module

Un module est décrit entièrement par des fichiers *datacore* (JSON) avant
toute génération de code ; le CLI `devups` ne fait que lire ces fichiers
et produire entités/contrôleurs/formulaires/tables à partir d'eux - il ne
les crée pas. Exemple minimal, un module `ModuleExemple` avec une entité
`Produit` dans le composant `devups` :

```bash
mkdir -p src/devups/ModuleExemple/Core
```

`src/devups/ModuleExemple/moduleexempleCore.json` (métadonnées du module) :

```json
{
  "id": 90,
  "name": "ModuleExemple",
  "label": "Exemple",
  "icon": "fas fa-box",
  "project": "admin",
  "nbent": 1,
  "listentity": []
}
```

`src/devups/ModuleExemple/Core/ProduitCore.json` (une entité, un champ
`nom` obligatoire) - voir `src/devups/ModuleLog/Core/Dvups_logCore.json`
pour un exemple avec plusieurs types de champs (`string`, `integer`,
`datetime`, `text`) et `nullable` (`"not"` / `"default"`) :

```json
{
  "id": 9090,
  "name": "produit",
  "module": { "id": 90 },
  "ref": null,
  "attribut": {
    "1": { "name": "nom", "visibility": "", "datatype": "string", "size": "255", "nullable": "not", "formtype": "text" }
  },
  "relation": []
}
```

Puis génération du code et branchement du module (CRUD, index, services,
dépendances, ressources) :

```bash
php devups core:g:module devups\ModuleExemple
php devups core:g:moduleendless devups\ModuleExemple
php devups rights:sync          # ajoute les actions create/read/update/delete/export/import de Produit à dvups_action
```

Déclarer ensuite le module dans `config/module_configurations.php` et
`config/dvups_configurations.php` (même structure que les entrées
`ModuleLog`/`Dvups_log` déjà présentes) pour qu'il apparaisse dans le
menu du back-office et dans les écrans générés (`admin/devups/ModuleExemple/produit/list`).
`php devups dvups_:update` donne à l'admin maître les droits sur le
nouveau module.

## Organisation

| Dossier | Rôle |
|---|---|
| `dclass/` | noyau : ORM Tchutte (`Tchutte/`), modèles, routeur, formulaires, datatables, générateur |
| `src/` | composants livrés : `devups` (admin, config, base, langues), `devupscms` (cms, arbres, données, notifications, mails...), `devupsfront` (utilisateurs) |
| `route/` | routes de l'application : `App` (front), `WebService` (API), `Admin`, `AdminService` |
| `admin/`, `web/` | vues et ressources du back-office et du front |
| `config/` | constantes, chargement du noyau |
| `tests/` | tests unitaires, d'intégration et de bout en bout |

## Tests

```bash
composer install            # installe aussi PHPUnit (require-dev)
composer test               # toutes les suites
composer test:unit          # sans base de données
composer test:integration   # base de test recréée à chaque exécution
composer test:e2e           # serveur HTTP local démarré automatiquement
```

La suite utilise une base **dédiée** (`DB_NAME=devups_phpunit` par défaut, voir
`phpunit.xml.dist`), supprimée et recréée à chaque exécution ; le bootstrap refuse
une base dont le nom ne contient pas `test` ou `phpunit`. Pour surcharger les accès
(utilisateur, mot de passe), définir les variables d'environnement `DB_USER`,
`DB_PASSWORD`... ou copier `phpunit.xml.dist` en `phpunit.xml` (non versionné).

La suite e2e ouvre chaque écran liste et chaque formulaire du back-office pour
toutes les entités déclarées : les écrans connus comme cassés sont listés dans
`tests/E2E/AdminScreensTest.php` (`KNOWN_BROKEN`) et signalés comme incomplets.

## Droits

Chaque entité declare ses actions (`create`, `read`, `update`, `delete`, `export`,
`import` par defaut, plus celles propres a son metier dans son datacore,
cle `actions` du `Core.json`). `php devups rights:sync` synchronise ce
catalogue en base (`dvups_action`) depuis `config/dvups_configurations.php` ;
jamais destructif, une action retiree du datacore reste en base avec ses
eventuels privileges.

Un role recoit des privileges (`dvups_privilege`, couple role/action) via
l'ecran back-office "Roles" (grille de cases a cocher par entite/action,
sur le meme formulaire que l'edition du role). Compiles une seule fois par
role dans `cache/rights/<id>.php` (invalide a chaque modification des
privileges du role).

API unique pour verifier un droit : `can('action', Entite::class)`. Branchee :
- dans `Request::Controller()` (point de passage de toute action CRUD
  generee, que le controleur utilise `CrudTrait` ou ses propres methodes) ;
- a la main dans `Dvups_roleController`/`Dvups_adminController`, atteints
  aussi par les routes historiques `services.php?path=...` qui ne passent
  pas par `Request::Controller()` ;
- dans les boutons des datatables (`Datatable::actionListView()`).

Le role nomme `admin` est le role maitre : `can()` le laisse toujours
passer, quels que soient les privileges attribues. Seul ce role peut
modifier les privileges d'un role, ou le role d'un admin - meme si un
autre role a recu `update` sur `Dvups_role`/`Dvups_admin` pour autre chose
(renommer un role, modifier un profil).

`can()` relit l'admin courant en base (1 requete par processus, mise en
cache) plutot que de se fier a `$_SESSION[ADMIN]` (snapshot serialise a la
connexion) : un changement de role est pris en compte des la requete
suivante, sans reconnexion.

## Langues

Reference unique = le code iso2 (`fr`, `en`, ...), partout : session
(`local()`), URL (`?lang=`), base (`dvups_lang.iso_code`) et fichiers.
`header.php` compile une seule fois le mapping iso_code<->id des langues
ACTIVES dans `cache/lang/active.php` (`Dvups_lang::cachedActiveDlangs()`,
meme modele que le cache des droits), invalide automatiquement a chaque
creation/modification/suppression d'une langue par le back-office.

Les traductions vivent dans `lang/<iso2>.php` (constante `LANG_DIR`,
defaut `lang/` - la suite de tests pointe vers un dossier temporaire,
jamais les fichiers versionnes), qui retourne un tableau
`cle => traduction`. `lang.php` (racine) charge le fichier de la langue
courante une seule fois par requete - `t()` ne fait jamais de requete SQL.

`t($cle, $params = [])` retourne la traduction de `$cle` dans la langue
COURANTE, ou `$cle` elle-meme si elle est absente (avec substitution
`:param` dans les deux cas : `t('bonjour :nom', ['nom' => 'Jean'])`).
Quand `LANG_COLLECT` est actif (defaut : developpement uniquement, jamais
en production), une cle absente est automatiquement ajoutee a tous les
fichiers `lang/*.php` (valeur = la cle) et a `$dv_translations` en
memoire (pas de nouvelle ecriture pour la meme cle dans la meme requete),
via une ecriture surete (verrou sur un fichier dedie + fichier temporaire
+ renommage) : le traducteur n'a plus qu'a remplir les valeurs.

`t_lang($cle, $iso, $params = [])` traduit dans une langue EXPLICITE,
independamment de la session/l'URL courante (mise en cache par langue,
un seul `require` par iso et par requete, jamais de collecte automatique)
- utilisee par les emails envoyes a un destinataire dont la langue peut
differer de celle de l'expediteur (`web/views/mails/*.blade.php`).

`php devups lang:scan` parcourt le code (via `token_get_all()`, pas une
regex) a la recherche des appels `t('...')`/`tt('...')` a cle LITTERALE
et complete les fichiers de langue en une fois ; une cle construite
(concatenation, variable) est ignoree et seulement signalee en sortie.
`php devups lang:missing` liste, par langue, les cles encore non
traduites (valeur egale a la cle).

`tt($cle)` est un alias de `t($cle)` utilise dans les vues.

## Logs

API unique, utilisable partout (noyau, front, admin) : `Log::error($messageOuThrowable, $context = [])`,
`Log::warning(...)`, `Log::info(...)`. N'interrompt jamais l'appelant : une
erreur d'ecriture du journal (base ou fichier) est elle-meme avalee.

Les occurrences identiques sont regroupees sous une seule ligne
`dvups_log` par empreinte (hash de la classe + fichier + ligne + message
normalise - les nombres variables comme un id sont fusionnes). La ligne
garde un compteur d'occurrences et les dates de premiere/derniere
occurrence. Un message ou une trace trop longs (> `Log::INLINE_MAX_LENGTH`,
1000 caracteres) debordent vers `cache/log/<AAAA-MM>/<empreinte>.log` (non
versionne, ecrase a chaque nouvelle occurrence) ; la base ne garde que le
chemin et un apercu tronque. `Log::purge()` supprime les lignes (et leurs
fichiers) plus anciennes que `LOG_RETENTION_DAYS` (defaut 90) - a appeler
depuis une tache planifiee, jamais automatiquement.

Les gardes PHP globales (`set_exception_handler`, `set_error_handler`,
`register_shutdown_function` pour les erreurs fatales) sont installees une
seule fois dans `header.php` (front) et `admin/header.php` (admin), jamais
dans `config/dependanceInjection.php` (partage par les tests et la CLI, ou
installer ces gardes perturberait la gestion d'erreurs de PHPUnit). En
production, la reponse est generique (page, JSON selon le contexte, ou
texte brut sur STDERR en CLI) ; en developpement, le detail de l'erreur
est affiche. `error_reporting()` est toujours respecte (une erreur
masquee par `@` n'est jamais journalisee), et seuls les niveaux >=
`LOG_MIN_LEVEL` (defaut `warning`) sont enregistres - `E_NOTICE`/
`E_DEPRECATED` sont donc ignores par defaut, sans empecher PHP de les
traiter normalement par ailleurs (journal natif, `display_errors`...).

Ecran back-office "Logs" en lecture seule (liste, filtres niveau/date,
detail - y compris le contenu complet d'un fichier de debordement),
protege par `can('read', Dvups_log::class)`.

`Emaillog` reste reserve aux emails REELLEMENT envoyes (voir
`dclass/lib/DMail.php`, `Reportingmodel::sendMail`) : destinataire, sujet,
modele, statut (`envoye`/`echec`) et erreur eventuelle -
`Emaillog::logSuccess()`/`logFailure()` centralisent l'ecriture. Ecran
back-office en lecture seule, comme le module Log. Ne jamais s'en servir
pour journaliser des erreurs ou d'autres evenements : utiliser `Log::` a
la place.

## CMS (Phase 4)

Le contenu CMS (anciennement `cmstext`) vit dans `Cmscontent`
(`src/devupscms/ModuleCms/`), toujours relié à un `Tree_item` et
traduit comme avant (`dvtranslated_columns`). Les images d'un contenu
sont des `Cmsresource` : contrairement à l'ancien `Imagecms` (simple
table de jointure vers `Dv_image`/`ModuleGallery`), `Cmsresource`
étend directement `ImageCore` (même modèle que l'ancien `Dv_image`) et
porte elle-même le fichier - plus de dépendance à `ModuleGallery` pour
le contenu CMS. Les commentaires (`Cmscomment`, jamais générés avant
cette session) sont liés à un `Cmscontent` et à un `User`.

La page d'édition admin d'un contenu
(`/admin/devupscms/ModuleCms/cmscontent/edit?id=`) fonctionne et liste
ses ressources via `CmsresourceTable`.

`ModuleGallery`/`Dv_image` a été supprimé entièrement : `ModuleTree`
(`Tree_item_image`, pour les images d'arborescence) étend lui aussi
désormais `ImageCore` directement, sur le même modèle que
`Cmsresource` - plus aucune dépendance à `ModuleGallery` dans tout le
code. La migration de données (renommage CMS, reprise des données
`imagecms`→`cmsresource` et des colonnes d'image de `tree_item_image`
- avec comptage avant/après en commentaire -, puis DROP de
`imagecms`/`dv_image`, ces deux derniers commentés en attendant
vérification manuelle) reste à exécuter sur les environnements réels
(`database/migration/2026_11_08_phase4_cms_rename.sql`, via
`php devups migrate` - voir section "Migrations" ci-dessous), voir
`TODO.md` sections "Phase 4 (CMS) - ÉTAPE G" et "ÉTAPE I".

## Localisation (Phase 5)

Seule l'entité `Country` (`src/devupscms/ModuleLocation/`) est
conservée - `Continent`, `Department`, `District`, `Region` et `Town`
ont été retirés entièrement (aucun autre module n'en dépendait). Le nom
de `Country` est traduit (`dvtranslate`, comme `Cmscontent`).

Donnée de référence (iso2, iso3, nom, indicatif téléphonique, ~195
pays) via `database/seed/countries.php`, chargée de façon idempotente
par :

```
php devups location:seed
```

La migration qui `DROP` les tables retirées
(`database/migration/2026_11_08_phase5_location_drop.sql`) reste à
exécuter sur les environnements réels via `php devups migrate` (c'est
une migration destructive : confirmation requise, voir section
"Migrations" ci-dessous) - voir `TODO.md` section
"Phase 5 (Localisation) - ÉTAPE J".

## Sécurité

- Les points d'entrée historiques `src/<composant>/<Module>/services.php` exigent une
  session d'administration.
- `.htaccess` refuse l'accès direct à `config/`, `database/`, `cache/`, `vendor/`,
  `tests/`, aux fichiers cachés (`.env`) et au code PHP de `src/` et `dclass/`.
- `cron.php` ne s'exécute qu'en ligne de commande, ou par HTTP avec `?token=<CRON_TOKEN>`.
- Langue (`?lang=`) : validée par `dv_valid_lang()` (format `^[a-z]{2}$`
  ET langue active) avant toute utilisation dans un chemin de fichier
  (`local()`, `setlang()`, `lang.php`, `t_lang()`) ; une valeur invalide
  retombe silencieusement sur `__lang`, jamais une erreur 500 ni une
  inclusion de fichier hors de `lang/`. Voir `TODO.md` section
  "Sécurité langue - ÉTAPE H".
- Mots de passe (`Dvups_admin`, `User`) : stockés avec `password_hash()`
  (jamais sha1()/md5()) ; un ancien hash sha1() encore en base est
  remplacé automatiquement au prochain login réussi, sans
  réinitialisation forcée. "Se souvenir de moi" ne place plus jamais le
  mot de passe (ni son hash) dans un cookie : un token aléatoire dont
  seul le hash est stocké en base, avec expiration, rotation à chaque
  usage et invalidation à la déconnexion ou au changement de mot de
  passe. Voir `TODO.md` section "Phase 7 (Mots de passe) - ÉTAPE K".

## Migrations (ÉTAPE Q)

`database/migration/*.sql` (ou `.php`, voir plus bas) ne s'exécute plus
à la main (`mysql -u ... -p ... < fichier`) : `php devups migrate` rejoue,
dans l'ordre du nom de fichier, les migrations non encore jouées sur la
base courante, et les enregistre (nom, lot, date) dans la table
`dvups_migration`.

```
php devups migrate              # joue les migrations en attente
php devups migrate --dry-run    # affiche le SQL sans l'executer
php devups migrate --step       # un lot distinct par migration (au lieu d'un seul lot pour tout le batch)
php devups migrate --force      # saute la confirmation d'une migration destructive
php devups migrate:status       # liste les migrations jouees et en attente
```

Une migration contenant `DROP`, `TRUNCATE` ou `DELETE` est marquée
**destructive** : elle demande une confirmation explicite (taper `oui`
à l'invite, ou relancer avec `--force`) avant d'être jouée. En
production (`APP_PROD=true`), seul `--force` passe - jamais la
confirmation interactive.

Format des fichiers : SQL brut (comme avant), ou PHP si plus pratique -
le fichier doit `return` un objet avec une méthode `up(PDO $pdo)` :

```php
<?php
return new class {
    public function up(\PDO $pdo) {
        $pdo->exec("ALTER TABLE ...");
    }
};
```

Une installation neuve (`php devups install`) construit déjà le schéma
complet depuis les entités actuelles (Doctrine `orm:schema:create`) :
les migrations existantes au moment de l'installation sont donc
marquées jouées sans être ré-exécutées (elles seraient redondantes avec
le schéma déjà créé). Seule une base **existante**, antérieure à cette
fonctionnalité ou à une migration donnée, doit vraiment la jouer via
`php devups migrate`.

Logique dans `dclass/devups/Migration/MigrationRunner.php` (testée dans
`tests/Integration/MigrationRunnerTest.php` : ordre, idempotence,
dry-run, refus d'une migration destructive, `--force`, `--step`,
migration PHP, `migrate:status`, marquage de base de départ).

## Documentation

- API (OpenAPI / Swagger), routes front (JWT) uniquement, sans authentification : `/documentation/`
- Guide développeur interactif généré depuis les datacores : `/documentation/guide.php`
  (`?format=json` pour le JSON brut)
- `php devups api:doc` écrit un instantané `openapi.json` à la racine du
  projet (routes front uniquement, même contenu que `/documentation/`) -
  utile pour un outillage externe (CI, client généré) ; non versionné
  (`.gitignore`), à régénérer à la demande.
- `php devups doc:guide` écrit une page HTML statique par module/entité
  sous `documentation/guide/<composant>/<module>/<entité>.html` ; non
  versionné, à régénérer à la demande.
- Swagger UI back-office (`/admin/apidocs`), protégée par
  `can('read', 'Documentation')` (role `admin` uniquement, tant qu'aucun
  droit dédié n'est créé) : inclut EN PLUS les routes admin (session,
  tag "Admin – session") à côté des routes front (JWT), générées depuis
  `config/dvups_configurations.php` sur le même calcul de chemin que
  `tests/E2E/AdminScreensTest.php::screens()`.
- Logique de génération partagée (parsing statique des annotations
  Devups et des datacores, aucune classe PHP chargée) :
  `dclass/generator/OpenApiGenerator.php` et
  `dclass/generator/DocGuideGenerator.php`.
- Site : https://devups.spacekola.com
