---
title: "Chapitre 6 — La ligne de commande"
sidebar_position: 6
---

## 6.1 Le binaire

```php
<?php
// monprojet/devups
include('dclass/generator/builder.php');
```

Tout se passe dans `dclass/generator/builder.php`, qui analyse `$argv`.

```bash
php devups <commande> [cible] [options]
```

Sans argument reconnu, l'aide est affichée.

## 6.2 Cycle de vie

| Commande | Effet |
|---|---|
| `install` | crée la base, le schéma, le compte maître, marque les migrations comme jouées (§3.2) |
| `database:create` | crée la base et le schéma seuls |
| `init:caches` | crée `cache/` et `database/` avec leurs sous-dossiers |
| `serve` | lance le serveur PHP de développement |
| `build` | copie `src/` et `dclass/` dans `build/` et produit une archive zip |
| `deploy` | décompresse cette archive à la racine |

Pour les tests, utiliser `composer test` (chapitre 9) plutôt que l'ancienne commande `php devups tests`.

## 6.3 Base de données et migrations

| Commande | Effet |
|---|---|
| `migrate` | joue les migrations en attente de `database/migration/` |
| `migrate --dry-run` | affiche le SQL sans l'exécuter |
| `migrate --step` | un lot distinct par migration |
| `migrate --force` | saute la confirmation d'une migration destructive |
| `migrate:status` | liste les migrations jouées et en attente |
| `schema:update --dump-sql` | affiche le SQL que Doctrine appliquerait pour aligner le schéma sur les entités |
| `schema:update --force` | l'applique |
| `transaction:rollback <id>` | rejoue un dump de `database/dump/` et annule une transaction journalisée |
| `location:seed` | charge la liste des pays (idempotent) |

:::note À retenir
**`migrate` ou `schema:update` ?** `migrate` est la voie normale pour faire évoluer une base existante, sur tous les environnements : les migrations sont versionnées, jouées dans l'ordre et tracées. `schema:update --dump-sql` reste l'outil de diagnostic qui dit si le schéma réel correspond aux entités. Le Tome II, chapitre 6, détaille la différence.
:::

:::warning Piège
**Piège Doctrine.** `schema:update --force` peut répondre « updated successfully » sans rien appliquer sur un renommage d'index provoqué par l'ajout d'une relation à une table déjà en base. `--dump-sql` propose alors la même séquence en boucle. Contournement : écrire ce SQL dans une migration et la jouer avec `migrate`.
:::

## 6.4 Datacore, droits, langues, documentation

| Commande | Effet |
|---|---|
| `dvups_:update` | rescanne `src/`, réécrit les deux fichiers de configuration (§2.5) et met à jour les composants, modules et entités du rôle maître |
| `dvups_:admin` | rejoue `config_data.sql` |
| `rights:sync` | complète le catalogue des actions (`dvups_action`) depuis les datacores et rafraîchit le menu de chaque rôle (Tome IV) |
| `lang:scan` | parcourt le code, repère les appels `t('...')` à clé littérale et complète les fichiers `lang/*.php` |
| `lang:missing` | liste, par langue, les clés encore non traduites |
| `api:doc` | écrit un instantané `openapi.json` à la racine (routes front) |
| `doc:guide` | écrit une page HTML par module et entité sous `documentation/guide/` |
| `entity:g:core <cible> [--sync]` | (re)génère le `<Entité>Core.json` d'une entité existante |
| `entity:g:postmandoc <cible>` | génère une collection Postman |

`dvups_:update` est à lancer après toute création ou suppression de module ou d'entité, puis `rights:sync` pour que les nouvelles actions soient attribuables aux rôles.

## 6.5 La génération

La cible s'écrit `<composant>\<Module>\<entité>`, avec des barres obliques inversées :

```bash
php devups core:g:crud monprojet\ModuleTask\task
```

| Commande | Génère |
|---|---|
| `core:g:component <composant>` | tout un composant, depuis son `<composant>Core.json` |
| `core:g:module <composant>\<Module>` | la coquille d'un module |
| `core:g:moduleendless <composant>\<Module>` | toutes les pièces d'un module : CRUD de chaque entité, `index.php`, `services.php`, chargeur, ressources |
| `core:g:crud <cible>` | entité + formulaire + liste + contrôleur d'administration |
| `core:g:entity <cible> [--lang]` | `Entity/<Entité>.php` (ou la classe `_lang`) |
| `core:g:form <cible>` | `Form/<Entité>Form.php` |
| `core:g:table <cible>` | `Datatable/<Entité>Table.php` |
| `core:g:controller <cible>` | `Controller/<Entité>Controller.php` |
| `core:g:frontcontroller <cible>` | `Controller/<Entité>FrontController.php` |
| `core:g:views <cible>` | les vues Blade |
| `core:g:formwidget`, `core:g:viewswidget`, `core:g:genesis`, `core:g:dependencies` | pièces spécialisées |
| `core:g:moduledependencies`, `core:g:moduleindex`, `core:g:moduleservices`, `core:g:moduleressources` | les pièces d'un module, séparément |

`core:g:crud` ne génère pas de `FrontController`. Il faut `core:g:frontcontroller`, puis décommenter sa ligne `require` dans `<composant>.module<nom>.php` : elle est générée commentée.

## 6.6 Le garde de régénération

`GeneratorFileGuard` (`dclass/generator/GeneratorFileGuard.php`) protège le travail manuel : avant d'écrire un fichier qui existe déjà, il le renomme en une version horodatée, et ne génère que si le renommage a réussi. À la fin, un récapitulatif liste ce qui a été renommé ou n'a pas pu l'être. L'option `--force`, cherchée n'importe où dans la ligne de commande, désactive le renommage et écrase.

## 6.7 Ce qu'il faut savoir avant de régénérer

**1. Régénérer une entité existante pouvait dupliquer ses `require`.** `core:g:crud` sur une entité déjà générée pouvait ajouter un second bloc de `require` à la fin de `<composant>.module<nom>.php`. Ce sont des `require` et non des `require_once` : le résultat était un `Cannot redeclare class X` qui dépend de l'ordre de chargement.

:::tip Nouveau
**Corrigé en 7.1 (D7).** `__Generator::dependencies()` vérifie désormais si le `require` de l'entité est déjà présent avant d'ajouter un bloc en mode "append" — régénérer la même entité deux fois de suite n'écrit plus rien de nouveau si son bloc existe déjà. Relire quand même le fichier de module après une régénération manuelle reste une bonne pratique pour toute autre modification faite à la main.
:::

**2. Une entité éditée à la main pour une contrainte sort de la régénération.** Un `#[ORM\UniqueConstraint]`, une identité composite : tout cela est écrasé par `core:g:crud`. L'entité passe alors en édition manuelle permanente.

**3. Le `nullable` d'une relation était ignoré par le générateur.** `BackendGenerator.php` interpolait `nullable` pour une colonne simple, mais pour une relation ne produisait que `#[ORM\JoinColumn(onDelete: "...")]` — la clé étrangère restait toujours NULLable, quoi que dise le datacore.

:::tip Nouveau
**Corrigé en 7.1 (D6).** Une relation déclarée `"not"` nullable dans le datacore produit désormais `#[ORM\JoinColumn(onDelete: "...", nullable: false)]` (attribut PHP 8 depuis le LOT 1 du chantier v8.0 - docblock `@JoinColumn(onDelete="...", nullable=false)` avant). Une relation `"default"` (le cas courant) reste inchangée.
:::

:::tip Nouveau
**Corrigé en 7.0 — le `update()` généré écrit vraiment.** Dans les versions 4.x, `Model::hydrate()` posait `$this->dvold = $this`, une référence et non une copie. La mise à jour ne détectait donc jamais de changement et répondait `Empty parameters` avec un 200. `hydrate()` pose désormais un vrai clone : le gabarit `hydrateWithJson(new X($id), ...)` et l'`updateAction()` du back-office fonctionnent sans retouche. Des tests de régression le couvrent.
:::
