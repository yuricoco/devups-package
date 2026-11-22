---
title: "Chapitre 1 — Qu'est-ce que Devups"
sidebar_position: 1
---

## 1.1 En une phrase

Devups est un framework PHP dans lequel l'arborescence des dossiers est le modèle de données, et où un générateur produit, à partir de fichiers JSON de description, l'entité, le formulaire, la liste et les contrôleurs d'un écran d'administration complet — avec un back-office de suivi (rôles et droits, journaux, langues, contenus, notifications) prêt à l'emploi.

## 1.2 Les quatre idées qui le caractérisent

**1. Le datacore.** Un dossier n'existe pour le framework que s'il contient un fichier `*Core.json` qui le déclare. Le disque est scanné, et c'est ce scan — pas une table de configuration, pas un fichier de routes — qui établit la liste des composants, des modules et des entités du projet. Un module sans `<module>Core.json` est invisible, silencieusement.

**2. Le contrat déclaratif.** Une liste de back-office ne s'écrit pas en HTML : elle se déclare en PHP sous forme de tableau (`$datatablemodel`), et un moteur unique (`Datatable.php`) rend toutes les listes. Un formulaire se déclare de la même façon (`$this->fields`), et un moteur unique (`FormFactory.php`) rend tous les formulaires. Changer le moteur change toutes les listes et tous les formulaires d'un coup — c'est la grande force et le grand risque du framework.

**3. Trois styles d'API qui cohabitent.** Un style générique (`POST /api/create/task`) sert n'importe quelle entité enregistrée sans écrire de contrôleur. Un style REST dédié (`POST /api/task`) passe par un `FrontController` par entité, piloté par annotations. Un style libre (`GET /api/hello` → `helloServe()`) sert les actions qui ne sont pas un CRUD. Les trois vivent sur les mêmes entités et ne se comportent pas pareil : le Tome III les sépare.

**4. Doctrine n'est là que pour les métadonnées.** `doctrine/orm` lit les attributs PHP 8 `#[ORM\Column]` / `#[ORM\ManyToOne]` et sait créer ou mettre à jour le schéma. Mais aucune requête métier ne passe par lui : toute lecture et toute écriture passent par la couche maison `DBAL` / `QueryBuilder`, dite « Tchutte » (du surnom de l'auteur du socle).

## 1.3 Les deux niveaux : composant et module

Un projet Devups est découpé en **composants**, eux-mêmes découpés en **modules**.

- Un composant est un domaine. Le framework en livre trois : `devups` (le noyau d'administration), `devupscms` (contenus, arbres, notifications, courriels…) et `devupsfront` (les utilisateurs du site ou de l'application).
- Un module est une fonctionnalité à l'intérieur d'un composant : `ModuleAdmin`, `ModuleTree`, `ModuleUser`…

Un projet ajoute ses propres composants à côté des trois du socle — par exemple `src/monprojet/`. Les composants préfixés `devups*` sont le socle réutilisable : ils sont censés arriver identiques dans tout nouveau projet.

:::note À retenir
**Convention, pas contrainte technique.** Rien n'empêche de ranger un module dans un composant auquel il n'appartient pas logiquement. C'est une règle d'hygiène : un module métier va dans le composant du projet, jamais dans un composant `devups*`.
:::

## 1.4 Prérequis

| Élément | Valeur |
|---|---|
| PHP | **8.3 ou plus** (`composer.json` : `"php": ">=8.3"`), testé en intégration continue sur 8.3, 8.4 et 8.5 |
| Extensions | `pdo_mysql`, `mbstring`, `xml`, `gd`, `zip`, `curl`, `intl` |
| Base de données | MySQL ou MariaDB |
| Composer | requis |

Dépendances principales (`composer.json`) :

| Paquet | Rôle |
|---|---|
| `doctrine/orm ^3.7` | métadonnées d'attribut PHP 8 et création du schéma (`doctrine/dbal` suit en `^4`) |
| `illuminate/view ^11.0` (+ `illuminate/events ^11.0`) | moteur de vues Blade, assemblé directement (sans `philo/laravel-blade`, retiré) |
| `firebase/php-jwt ^6.9` | jetons d'authentification de l'API |
| `phpmailer/phpmailer ^6.6` | envoi de courriels |
| `mpdf/mpdf ^8.0` | génération de PDF |
| `guzzlehttp/guzzle ^7.5` | appels HTTP sortants |
| `symfony/cache ^7.4` | cache des métadonnées Doctrine (remplace `doctrine/cache`, retiré) |
| `ifsnop/mysqldump-php ^2.9` | sauvegardes de base |
| `zircote/swagger-php ^4.11` | annotations OpenAPI |
| `google/apiclient ^2.18` | authentification Google |
| `minishlink/web-push ^10.0` | notifications Web Push (VAPID) |
| `barcode-bakery/barcode-1d ^7.0` | codes-barres |
| `psr/container ^2.0`, `psr/log ^3.0`, `psr/http-message ^2.0`, `psr/http-server-handler ^1.0`, `psr/http-server-middleware ^1.0` | interfaces PSR-11/PSR-3/PSR-7/PSR-15 (`Devups\Container`, `Devups\Logger`, `Devups\Http\MiddlewareStack` - Tome III) |

Les routes se lisent désormais par des attributs PHP 8 natifs (`#[GET]`, `#[POST]`...) - `maslosoft/addendum` et la bibliothèque vendorée `dclass/lib/addendum/` qui servaient à simuler des annotations par lecture de docblock sont retirées (chapitre 5, §5.7).

En développement s'ajoutent `phpunit/phpunit ^11.5`, `phpstan/phpstan ^2.3` et `symfony/var-dumper`/`symfony/var-exporter`.

L'autochargement est un classmap sur `dclass/` et `src/`. Conséquence pratique : Composer résout une classe par son nom, pas par le composant qui la déclare. Après avoir ajouté, renommé ou déplacé une classe, lancer `composer dump-autoload`.

:::warning Piège
**Piège.** Deux projets Devups qui partagent un dossier `vendor/` hérité peuvent voir une entité d'un projet intercepter celle de l'autre. Le symptôme est un `Class 'X' does not exist` qui pointe vers un chemin d'un autre projet. Correctif : `composer dump-autoload` depuis la racine du projet.
:::
