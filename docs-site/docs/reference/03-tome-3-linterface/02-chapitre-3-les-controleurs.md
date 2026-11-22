---
title: "Chapitre 3 — Les contrôleurs"
sidebar_position: 2
---

## 3.1 Les quatre familles

| Classe | Rôle | Dispatch |
|---|---|---|
| `<Entité>Controller` | le CRUD du back-office | `Request::Controller()` |
| `<Entité>FrontController` | l'API REST dédiée | `Request::FrontController()` |
| `Module<Nom>` (extends `ModuleController`) | les écrans d'un module | `Request::Module()` |
| `route/App`, `WebService`, `Admin`, `AdminService` | les quatre portes | `Router` |

## 3.2 Le contrôleur d'administration

`Request::Controller()` convertit le dernier segment de l'URL en nom de méthode :

| URL | Méthode | Action contrôlée par `can()` |
|---|---|---|
| `/admin/mp/ModuleTask/task/list` | `listView()` | `read` |
| `.../task/datatable` | `datatable()` | `read` |
| `.../task/detail?id=4` | `detailView(4)` | `read` |
| `.../task/form?id=4` | `formView(4)` | `read` |
| `.../task/create` | `createAction()` | `create` |
| `.../task/update?id=4` | `updateAction(4)` | `update` |
| `.../task/delete?id=4`, `deletegroup` | `deleteAction()`, `deletegroupAction()` | `delete` |
| `.../task/export`, `import` | `exportAction()`, `importAction()` | `export`, `import` |

:::tip Nouveau
**Nouveau en 7.0.** Avant d'appeler la méthode, `Request::Controller()` vérifie `can($action, $entité)`. Refus : 403, `{"success":false,"detail":"forbidden"}`. Cela vaut pour tout contrôleur d'entité, qu'il utilise `CrudTrait` ou ses propres méthodes. Une action métier (`closeAction()`) ne figure pas dans cette table : son contrôleur appelle `can('close', Task::class)` lui-même.
:::

## 3.3 `CrudTrait` — le CRUD en six méthodes

Un `<Entité>Controller` généré utilise `CrudTrait`, qui fournit :

| Méthode | Rôle |
|---|---|
| `listView($next, $per_page)` | construit la liste par `<Entité>Table::init(...)->buildindextable()` et rend la vue |
| `datatable($next, $per_page)` | renvoie la liste en JSON, pour le rechargement sans rechargement de page |
| `formView($id = null)` | construit le formulaire, avec pour action `admin/api/<entité>/create` ou `update?id=` |
| `createAction()` / `updateAction($id)` | remplissent l'entité par `form_fillingentity()`, renvoient les erreurs ou la ligne mise à jour |
| `detailView($id)` | rend la fiche de détail |
| `deleteAction($id)` / `deletegroupAction($ids)` | suppression simple et en masse |

Le contrôleur généré se contente de déclarer quatre noms :

```php
class TaskController extends Controller
{
    use CrudTrait;
    public static $classname = 'Task';
    public static $entityname = 'task';
    public static $tablename = 'TaskTable';
    public static $formname  = 'TaskForm';
}
```

## 3.4 Le remplissage d'une entité depuis une requête

| Méthode | Entrée | Usage |
|---|---|---|
| `form_fillingentity($object, $data)` | `$_POST[<entité>_form]` | les formulaires du back-office |
| `hydrateWithJson($object, $jsonform, $deeper = false)` | un tableau décodé depuis le corps JSON | l'API |
| `form_generat($object, $data, $deeper)` | | |

Les deux premières alimentent `$this->error`, un tableau indexé par nom de champ. Un contrôleur vérifie `if ($this->error)` avant de persister. Le cœur du remplissage appelle, pour chaque champ, le `set<Champ>()` de l'entité s'il existe, et traite sa valeur de retour comme un message d'erreur (Tome II, §5.4).

## 3.5 Les écouteurs d'événements

```php
Controller::addEventListener($position, $action, $classname);
Controller::addEventListenerAfterCreate($classname, $method);
```

`createCore()` s'en sert pour enchaîner un `upload<Champ>()` après la création, quand des fichiers accompagnent la requête.

## 3.6 Les contrôleurs de module

Un `Module<Nom>.php` hérite de `ModuleController`, donc de `Router`. `Request::Module()` convertit le segment en `<segment>View`.

- Le constructeur d'un `Module<X>` attend un `$default_url` (une chaîne : `'layout'`), jamais l'objet `Router`.
- La barre latérale génère un lien `.../dashboard` pour chaque module. `ModuleController::dashboardView()` est un repli qui appelle `layoutView()` si elle existe. Un module qui définit sa propre `dashboardView()` l'emporte.

## 3.7 La dégradation gracieuse

Un écran dont le code n'a jamais été généré ne doit pas produire une erreur fatale nue. Si la classe de contrôleur n'existe pas, ou si l'erreur levée est une « Class … not found », `Request::Controller()` et `Request::Module()` rendent `dclass/devups/Resource/views/not_monitored.blade.php` — un écran « contenu non monitoré ». Seule une classe introuvable est dégradée : un vrai bug continue de planter, sinon le message mentirait.
