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

## Sécurité

- Les points d'entrée historiques `src/<composant>/<Module>/services.php` exigent une
  session d'administration.
- `.htaccess` refuse l'accès direct à `config/`, `database/`, `cache/`, `vendor/`,
  `tests/`, aux fichiers cachés (`.env`) et au code PHP de `src/` et `dclass/`.
- `cron.php` ne s'exécute qu'en ligne de commande, ou par HTTP avec `?token=<CRON_TOKEN>`.

## Documentation

- API (OpenAPI / Swagger) : `/documentation/`
- Guide développeur généré depuis les datacores : `/documentation/guide.php`
- Site : https://devups.spacekola.com
