---
title: "Chapitre 4 — Configuration"
sidebar_position: 4
---

## 4.1 Les fichiers

| Fichier | Rôle | Suivi par git ? |
|---|---|---|
| `.env` | toutes les valeurs propres à une installation : base, URLs, secrets | **non** (ignoré) |
| `.env.example` | le modèle commenté de `.env` | oui |
| `config/constante.example.php` | le modèle de `constante.php` : `define()` alimentés par `dv_env()` | oui |
| `config/constante.php` | la copie locale du modèle | non (ignoré) |
| `config/env.php` | `dv_env_load()` et `dv_env()` | oui |
| `config/load_constants.php` | charge `constante.php` (ou le fichier désigné par `DEVUPS_CONSTANTS`) | oui |
| `config/dependanceInjection.php` | charge les constantes, Composer, tout `dclass/`, puis l'`EntityManager` | oui |
| `config/Database.php` | ouvre la connexion PDO | oui |
| `config/dvups_configurations.php`, `config/module_configurations.php` | générés (§2.5) | oui |

:::tip Nouveau
**Nouveau en 7.0 — plus aucun secret versionné.** Les versions 4.x versionnaient un `constante.prod.php` qui contenait des valeurs réelles. Désormais, un secret ne vit que dans `.env` ou dans les variables d'environnement du serveur, qui sont prioritaires sur le fichier. Le dépôt ne contient que des valeurs fictives (`change-me`, `example.com`).
:::

## 4.2 `dv_env()`

```php
define('dbname', dv_env('DB_NAME', 'devups'));
define('__prod', dv_env('APP_PROD', false));
```

`dv_env($clé, $défaut)` lit d'abord la variable d'environnement du processus, puis `.env`, puis renvoie le défaut. Les chaînes `true`, `false` et `null` sont converties en vraies valeurs PHP. Pour la suite de tests, la variable `DEVUPS_CONSTANTS` désigne un autre fichier de constantes : c'est ainsi que les tests pointent vers leur base dédiée sans toucher à la configuration locale.

## 4.3 Les variables, par famille

Les valeurs ci-dessous sont des exemples de forme.

**Application**

| Variable `.env` | Constante | Rôle |
|---|---|---|
| `APP_NAME` | `PROJECT_NAME` | nom affiché |
| `APP_URL` | `__server` | hôte |
| `APP_PORT` | `__env_port` | `:3031` en développement, vide en production |
| — | `__env` | `__server . __env_port . '/'`, la racine de toute URL construite |
| `APP_PROD` | `__prod` | bascule le comportement d'erreur (§4.5) |
| `FRONT_URL` | `__env_front` | l'application front séparée, si elle existe |
| `CORS_ORIGINS` | `__csrf_origin` | origines autorisées en CORS, séparées par des virgules |
| `DEFAULT_LANG` | `__default_lang`, `__lang` | langue par défaut (code ISO à deux lettres) |
| `PROJECT_ID` | `__project_id` | préfixe des clés de session (concaténé au port) |
| `ROUTE_CACHE` | `__route_cache` | cache de routes (inactif en pratique, §5.8) |

**Base de données**

| Variable | Constante | Rôle |
|---|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | `dbhost`, `dbname`, `dbuser`, `dbpassword` | connexion |
| `DB_DUMPER` | `dbdumper` | si vrai, un dump est pris à chaque requête — à ne jamais activer en production |
| `DB_TRANSACTION` | `dbtransaction` | journal de transactions |

**Courriel, jetons, services**

| Famille | Variables |
|---|---|
| SMTP | `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM`, `MAIL_FROM_NAME` |
| JWT | `JWT_SECRET`, `JWT_EXPIRE` (`false` = jetons sans expiration, ou une durée comme `+72 hours`) |
| Tâches planifiées | `CRON_TOKEN` |
| Notifications FCM | `FCM_PAIR_KEY`, `FCM_USER_ID`, `FCM_PROJECT_ID`, `FCM_AUTH_FILE` |
| Web Push | `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_ID_WEB`, `GOOGLE_CLIENT_ID_ANDROID`, `GOOGLE_CLIENT_ID_IOS`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URL` |
| Géolocalisation IP | `IPGEOLOCATION_API_KEY` (facultatif) |

**Journal, sécurité, langues** (nouvelles en 7.0)

| Variable | Défaut | Rôle |
|---|---|---|
| `LOG_MIN_LEVEL` | `warning` | niveau minimal enregistré par le gestionnaire global d'erreurs |
| `LOG_RETENTION_DAYS` | `90` | durée de conservation pour `Log::purge()` |
| `ACTIVATION_CODE_TTL_MINUTES` | `15` | validité d'un code d'activation à 6 chiffres |
| `RESET_PASSWORD_TOKEN_TTL_MINUTES` | `30` | validité d'un lien de réinitialisation de mot de passe |
| `RATE_LIMIT_LOGIN_MAX_ATTEMPTS` | `5` | échecs de connexion tolérés par compte… |
| `RATE_LIMIT_LOGIN_MAX_ATTEMPTS_IP` | `20` | … et par adresse IP |
| `RATE_LIMIT_LOGIN_WINDOW_SECONDS` | `900` | fenêtre de comptage des échecs |
| `RATE_LIMIT_CODE_MAX_ATTEMPTS` | `5` | essais d'un code avant invalidation |
| `RATE_LIMIT_RESEND_MAX_ATTEMPTS`, `RATE_LIMIT_RESEND_WINDOW_SECONDS` | `3`, `3600` | renvois de code |
| `TRUSTED_PROXIES` | vide | IP de reverse proxy de confiance ; sans elle, `X-Forwarded-For` est ignoré |
| `LANG_DIR` | `lang` | dossier des fichiers de traduction |
| `LANG_COLLECT` | vrai hors production | collecte automatique des clés de traduction manquantes |

**Constantes dérivées** (non configurables) : `ROOT`, `UPLOAD_DIR`, `admin_dir`, `web_dir`, `CLASSJS`, `__admin`, `__front`, et les clés de session `ADMIN` (`__project_id . '_devups'`), `CSRFTOKEN`, `dv_role_navigation`, `dv_role_permission`. Le préfixe `__project_id` permet à deux projets Devups servis depuis le même hôte de ne pas partager leurs sessions.

## 4.4 L'ordre de chargement

`config/dependanceInjection.php` est le seul point commun à tous les points d'entrée (site, back-office, CLI, tests). Il fait, dans cet ordre :

1. `require config/load_constants.php` — donc `constante.php`, donc `.env` ;
2. `require vendor/autoload.php` ;
3. `require config/Database.php` ;
4. `require`, un par un, les fichiers de `dclass/` — l'ordre est significatif (`ModelSerializer` avant `ModelTrait` avant `Model`, `QueryBuilder` avant `Controller`) ;
5. `$em = DBAL::getEntityManager()` — l'`EntityManager` Doctrine, global.

Puis `header.php` (application publique) ajoute :

```php
session_start();
require __DIR__ . '/config/dependanceInjection.php';
require __DIR__ . '/lang.php';           // traductions de la langue courante
require 'src/requires.php';              // tous les modules du projet
Log::installGlobalHandler();             // gestionnaire global d'erreurs
require 'route/WebService.php';
require 'route/App.php';
$dlangs = Dvups_lang::cachedActiveDlangs();   // langues actives, depuis le cache
$global_config = require ROOT . 'config/dvups_configurations.php';
```

`admin/header.php` fait l'équivalent pour le back-office, puis restaure la session d'administration et applique la garde de session des points d'entrée de module (§2.4).

:::note À retenir
**`src/requires.php` est la liste des modules du projet.** Un nouveau module doit y être ajouté à la main, sinon : `Fatal error: Class XController not found`.
:::

:::note À retenir
**Le gestionnaire global d'erreurs n'est installé que par les deux `header.php`**, jamais par `dependanceInjection.php`. La CLI et PHPUnit gardent donc leur propre gestion d'erreurs.
:::

## 4.5 Le mode production

`__prod` change le comportement sur quatre points :

| Situation | `APP_PROD=false` | `APP_PROD=true` |
|---|---|---|
| erreur non rattrapée | détail affiché (page, JSON ou texte selon le contexte) | réponse générique, détail seulement dans le journal |
| route introuvable (page) | message de développement | `Genesis::render('404')` |
| route introuvable (API) | message de développement en JSON | `{"success":false,"message":"404 : ... service note found"}` |
| collecte des clés de traduction | active | inactive |

:::warning Piège
**`dv_dump()` n'est pas un journal.** C'est `dump($args); die(1);`, en développement comme en production : la requête s'arrête. Pour signaler un problème sans tuer la requête, utiliser `Log::warning()` ou `Log::error()` (Tome IV, chapitre 3).
:::
