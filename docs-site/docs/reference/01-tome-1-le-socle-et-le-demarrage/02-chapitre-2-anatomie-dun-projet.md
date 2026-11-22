---
title: "Chapitre 2 — Anatomie d'un projet"
sidebar_position: 2
---

## 2.1 L'arborescence racine

```
monprojet/
├── admin/             le back-office (point d'entrée, vues, assets)
├── bin/               binaire Doctrine
├── cache/             vues compilées, droits, langues, arbres, journaux longs
├── config/            constantes, chargement du noyau, configurations générées
├── database/          migrations, seeds, dumps
├── dclass/            LE FRAMEWORK — ne se modifie pas par projet
├── documentation/     documentation d'API et guide développeur servis par l'application
├── lang/              un fichier de traductions par langue (fr.php, en.php…)
├── route/             les quatre portes d'entrée du routage
├── src/               LE PROJET — composants, modules, entités
├── tests/             suites PHPUnit unit / integration / e2e
├── uploads/           fichiers déposés par les utilisateurs
├── vendor/            dépendances Composer
├── web/               le site public (vues, assets, gabarits de courriels)
├── .env.example       modèle de configuration locale
├── composer.json
├── cron.php           tâches planifiées
├── devups             le binaire de ligne de commande
├── header.php         amorçage de l'application publique
├── index.php          point d'entrée public
├── lang.php           les fonctions de traduction t(), tt(), t_lang()
└── phpunit.xml.dist   configuration des tests
```

## 2.2 La frontière qui compte : `dclass/` contre `src/`

C'est la distinction la plus importante du framework, et la plus facile à enfreindre.

**`dclass/` est le framework.** Il arrive identique dans tout projet Devups. Y toucher répare un défaut pour un projet et le réintroduit pour tous les autres — ou corrige un comportement sur lequel un autre projet s'appuie. Une correction de `dclass/` se fait dans le dépôt du framework, avec un test, et se diffuse par une nouvelle version.

**`src/` est le projet.** C'est là que vit tout ce qui est propre à l'application.

:::note À retenir
**Règle de travail.** Quand une entité a besoin d'un accesseur que sa classe parente de `dclass/extends/` ne fournit pas, l'accesseur s'ajoute sur l'enfant, dans `src/`, jamais sur le parent.
:::

## 2.3 L'intérieur de `dclass/`

```
dclass/
├── devups/
│   ├── Core.php                 le scan du datacore
│   ├── Genesis.php              le rendu des vues (Blade) et des réponses JSON
│   ├── Log.php                  le journal applicatif (Log::error/warning/info)
│   ├── RateLimiter.php          limitation de fréquence (connexions, codes)
│   ├── Dfile.php                fichiers et images
│   ├── Api.php, ScanDir.php, DvupsTranslation.php
│   ├── Controller/
│   │   ├── Controller.php       contrôleur d'administration (CRUD générique)
│   │   ├── FrontController.php  contrôleur d'API (CRUD générique, propriété)
│   │   ├── CrudTrait.php        le CRUD d'administration en six méthodes
│   │   └── ModuleController.php
│   ├── Datatable/               le moteur de listes (Datatable, Lazyloading…)
│   ├── Form/                    le moteur de formulaires (FormManager, FormFactory, Form)
│   ├── Http/                    Router, Request, Route, Response
│   ├── Interfaces/              NotificationSerialize, WorkerCallback
│   ├── Migration/
│   │   └── MigrationRunner.php  le moteur de `php devups migrate`
│   ├── model/                   Model, ModelTrait, ModelSerializer, EntityCollection…
│   ├── Resource/views/          vues internes (écran « non monitoré »)
│   └── Tchutte/                 QueryBuilder, DBAL, DBALException, DB_dumper, Column
├── devupsjs/                    le JavaScript du back-office
├── extends/                     les classes de base d'entités réutilisables
├── generator/                   le générateur de code, la CLI, OpenAPI, guide
├── lib/
│   ├── annotation/              GET, POST, PUT, DELETE, Api, Auth, MethodView, DDoc (attributs PHP 8 natifs)
│   ├── ReflectionAnnotated.php  ReflectionAnnotatedClass/Method (hasAnnotation()/getAnnotation() via getAttributes())
│   └── Util.php, DMail.php, DPdf.php, HZip.php, SimpleXLSX.php
└── dvups_functions.php          fonctions globales (can(), local(), d_url()…)
```

:::tip Nouveau
**Nouveau en 7.0.** `Log.php`, `RateLimiter.php`, `Migration/`, `Tchutte/DBALException.php`, `generator/OpenApiGenerator.php` et `generator/DocGuideGenerator.php`. Ont disparu : `UploadFile.php` (remplacé par `Dfile`), `System_prod.php`, et dans `extends/` les classes propres à un ancien projet (`OrderCore`, `ScanCore`, `CurrencyTrait`, `RoleGuardTrait`, `Item`).
:::

## 2.4 L'intérieur d'un module

C'est la forme que le générateur produit, et celle qu'un module écrit à la main doit respecter.

```
src/<composant>/<Module>/
├── <module en minuscules>Core.json   LE FICHIER QUI FAIT EXISTER LE MODULE
├── Module<Nom>.php                   la classe du module (écrans de module)
├── <composant>.module<nom>.php       les require des classes du module
├── index.php                         point d'entrée direct du module (généré)
├── services.php                      routes de service historiques du back-office
├── .htaccess                         n'autorise que index.php et services.php
├── Core/<Entité>Core.json            le datacore de chaque entité
├── Entity/<Entité>.php               (+ <Entité>_lang.php si traduite)
├── Controller/<Entité>Controller.php       back-office
├── Controller/<Entité>FrontController.php  API REST dédiée
├── Datatable/<Entité>Table.php
├── Form/<Entité>Form.php
└── Resource/{js,css,views/admin}/
```

Les fichiers de référence sont ceux que produit `php devups core:g:moduleendless <composant>\<Module>` : `index.php`, `services.php`, `.htaccess`, le chargeur `<composant>.module<nom>.php`, les vues `layout` et `overview`. Le générateur fait foi : un module dont ces fichiers ont été modifiés à la main peut être régénéré pour revenir à la forme de référence.

:::tip Nouveau
**Nouveau en 7.0 — le `.htaccess` d'un module.** Il refuse l'exécution directe de tout fichier PHP du module, à l'exception de `index.php` et `services.php`. Et `admin/header.php`, inclus par ces deux points d'entrée, exige une session d'administration pour tout `services.php`, ainsi que pour l'`index.php` des composants `devups` et `devupscms`. L'`index.php` de `devupsfront` reste public : ses routes sont protégées par JWT et `@Auth`.
:::

## 2.5 Les deux fichiers de configuration générés

`Core::updateDvupsTable()` (dans `Core.php`) écrit deux fichiers PHP à partir du scan du disque :

| Fichier | Indexé par | Lu par |
|---|---|---|
| `config/dvups_configurations.php` | nom d'entité, et nom pleinement qualifié | le routage d'un écran d'entité, les formulaires, `rights:sync`, la documentation d'API |
| `config/module_configurations.php` | nom de module | le routage d'un écran de module, la barre latérale du back-office |

Chaque entrée d'entité porte `path`, `module`, `name`, `label`, `namespace`, `namespace_name`, `component`. Chaque entrée de module porte `name`, `label`, `icon`, `entities`.

Ces deux fichiers sont générés : la commande `php devups dvups_:update` les réécrit depuis le disque. Ils sont versionnés, parce que l'application les lit à chaque requête. Ils ne se maintiennent pas à la main, sauf pour retirer une entrée orpheline que le scan ne voit plus.
