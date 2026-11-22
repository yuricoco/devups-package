---
title: "Chapitre 8 — Premier module, de bout en bout"
sidebar_position: 8
---

Ce chapitre assemble les précédents sur un exemple complet : un module `ModuleTask` dans un composant `monprojet`, avec une entité `task` liée à une entité `project` déjà existante.

## 8.1 Le datacore du composant

`src/monprojet/monprojetCore.json` :

```json
{
  "name": "monprojet",
  "template": "adminv2",
  "listmodule": []
}
```

## 8.2 Le datacore du module

`src/monprojet/ModuleTask/moduletaskCore.json` :

```json
{
  "name": "ModuleTask",
  "label": "Tâches",
  "icon": "fas fa-fw fa-check-square"
}
```

`label` et `icon` alimentent la barre latérale du back-office. Sans eux, le nom du module et une icône d'engrenage sont utilisés.

## 8.3 Le datacore de l'entité

`src/monprojet/ModuleTask/Core/taskCore.json` :

```json
{
  "name": "task",
  "label": "Tâche",
  "attribut": {
    "1": { "name": "name", "datatype": "string", "size": "255",
           "nullable": "not", "formtype": "text" },
    "2": { "name": "description", "datatype": "text",
           "nullable": "default", "formtype": "textarea" },
    "3": { "name": "done", "datatype": "integer",
           "nullable": "default", "formtype": "checkbox" }
  },
  "relation": [
    { "entity": "project", "cardinality": "manyToOne",
      "nullable": "default", "ondelete": "cascade" }
  ],
  "actions": [ { "code": "close", "label": "Clôturer" } ]
}
```

Trois détails comptent : `attribut` est un objet indexé (`"1"`, `"2"`…), pas un tableau ; `id` est implicite et ne se déclare jamais ; `actions` déclare les actions métier qui s'ajoutent aux six actions implicites (Tome IV, chapitre 2).

## 8.4 Générer

```bash
php devups core:g:moduleendless monprojet\ModuleTask
php devups core:g:frontcontroller monprojet\ModuleTask\task
```

| Fichier | Rôle |
|---|---|
| `Entity/Task.php` | l'entité, avec ses attributs Doctrine |
| `Form/TaskForm.php` | le formulaire déclaratif |
| `Datatable/TaskTable.php` | la liste déclarative |
| `Controller/TaskController.php` | le CRUD de back-office |
| `Controller/TaskFrontController.php` | l'API REST dédiée |
| `monprojet.moduletask.php` | les `require` |
| `index.php`, `services.php`, `.htaccess`, `ModuleTask.php`, `Resource/` | les pièces du module |

## 8.5 Les trois gestes manuels

1. Décommenter le `require` du `FrontController` dans `monprojet.moduletask.php`.
2. Ajouter le module à `src/requires.php` :

```php
require 'monprojet/ModuleTask/monprojet.moduletask.php';
```

3. Exposer l'entité à l'API, dans `route/WebService.php` — et décider ce qui est public :

```php
public $public_access = [
    // ...
    'task' => \Task::class,
];

public static $publicMethods = [
    // ...
    'task' => ['index', 'detail'],   // lectures anonymes ; tout le reste exige un jeton
];
```

Sans l'entrée dans `$public_access`, `GET /api/task` répond comme une entité inconnue. Sans entrée dans `$publicMethods`, toutes ses méthodes exigent un jeton (Tome III, chapitre 8).

## 8.6 Créer la table

Écrire une migration, par exemple `database/migration/2026_11_20_create_task.sql`, en partant du SQL proposé par Doctrine :

```bash
php devups schema:update --dump-sql      # copier le SQL de la nouvelle table
php devups migrate --dry-run             # relire
php devups migrate                       # jouer
php devups dvups_:update                 # l'écran apparaît au back-office
php devups rights:sync                   # ses actions sont attribuables aux rôles
```

## 8.7 Vérifier

| Chemin | Attendu |
|---|---|
| `/admin/monprojet/ModuleTask/task/list` | la liste, avec ses colonnes |
| `GET /api/task` | la liste paginée, `{"success":true,...}` |
| `GET /api/task/1` | le détail |
| `POST /api/task` avec `{"task":{"name":"x","project.id":3}}` et un jeton | la création |
| `php devups migrate:status` | aucune migration en attente |
| `php devups schema:update --dump-sql` | rien à faire |

Noter la forme `"project.id": 3` dans la charge utile : le point référence une relation `manyToOne`.

## 8.8 Si quelque chose ne marche pas

| Symptôme | Cause la plus probable |
|---|---|
| ni `dvups_:update` ni `schema:update` ne voient le module | le `<module>Core.json` manque (§7.3) |
| `Fatal error: Class TaskController not found` | `src/requires.php` n'a pas été mis à jour |
| `Cannot redeclare class Task` | double bloc de `require` après une régénération (§6.7) |
| `Class 'Task' does not exist` alors que le fichier est là | classmap à régénérer : `composer dump-autoload` |
| l'entité n'apparaît pas au back-office | `php devups dvups_:update` n'a pas été lancé |
| l'écran répond 403 pour un administrateur qui n'est pas maître | `rights:sync` non lancé, ou privilège non attribué au rôle |
| `GET /api/task` répond comme une entité inconnue | `$public_access` (§8.5) |
| `GET /api/task` répond 401 sans jeton | `index` absent de `$publicMethods` — c'est le comportement voulu par défaut |
| la clé étrangère accepte NULL malgré `"nullable":"not"` | le générateur ignore `nullable` sur une relation (§6.7) |
