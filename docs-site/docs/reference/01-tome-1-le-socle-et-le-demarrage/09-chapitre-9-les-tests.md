---
title: "Chapitre 9 — Les tests"
sidebar_position: 9
---

## 9.1 Les trois suites

Le framework est livré avec une suite PHPUnit 11, configurée par `phpunit.xml.dist` :

| Suite | Commande | Contenu |
|---|---|---|
| unitaire | `composer test:unit` | sans base de données : chargement de `.env`, codes d'activation, propriété, neutralité du dépôt, langues |
| intégration | `composer test:integration` | contre une base dédiée, recréée à chaque exécution : CRUD du modèle, `QueryBuilder`, droits, migrations, journal, courriels |
| bout en bout | `composer test:e2e` | un serveur `php -S` démarré automatiquement : API, connexion, sécurité, chaque écran liste et formulaire du back-office |
| toutes | `composer test` | les trois |

L'intégration continue (`.github/workflows/tests.yml`) exécute `composer test` sur PHP 8.3, 8.4 et 8.5 avec un service MySQL, à chaque push et chaque pull request.

## 9.2 La base de test

La suite utilise une base **dédiée** (`DB_NAME=devups_phpunit` par défaut), supprimée et recréée à chaque exécution par `tests/bootstrap.php`. Par sécurité, le bootstrap refuse toute base dont le nom ne contient pas `test` ou `phpunit` : impossible de vider une base de développement par erreur.

Pour changer les accès, définir `DB_USER`, `DB_PASSWORD`… dans l'environnement, ou copier `phpunit.xml.dist` en `phpunit.xml` (non versionné). Les fichiers de traduction utilisés par les tests vivent dans un dossier temporaire (`LANG_DIR`) : un test ne modifie jamais `lang/fr.php` ni `lang/en.php`.

## 9.3 Les classes d'appui

| Classe | Rôle |
|---|---|
| `Tests\Support\DatabaseTestCase` | une transaction par test, annulée à la fin |
| `Tests\Support\HttpServer` | démarre et arrête `php -S` sur le port de test (`APP_PORT`, `:8765` par défaut) |
| `Tests\Support\HttpTestCase` | requêtes HTTP, session d'administration, jeton JWT |

## 9.4 L'écran par écran

`tests/E2E/AdminScreensTest.php` ouvre chaque écran liste et chaque formulaire de chaque entité déclarée dans `dvups_configurations.php`. Un écran connu comme cassé est inscrit dans `KNOWN_BROKEN` et signalé comme incomplet plutôt qu'en échec. En 7.0, il n'y reste que les deux écrans de journal, dont le formulaire n'existe pas par conception : ces écrans sont en lecture seule.

:::note À retenir
**Règle de travail.** Une correction de `dclass/` s'accompagne d'un test qui échoue avant et passe après. Un écran qu'on ajoute au back-office est couvert automatiquement par `AdminScreensTest` dès qu'il figure dans `dvups_configurations.php`.
:::

<p class="end">Fin du Tome I. Le Tome II traite des données : le datacore en détail, les entités, le QueryBuilder, l'écriture, les transactions, les migrations et la traduction des données.</p>
