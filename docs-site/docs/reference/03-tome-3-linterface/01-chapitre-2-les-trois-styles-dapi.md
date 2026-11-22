---
title: "Chapitre 2 — Les trois styles d'API"
sidebar_position: 1
---

C'est la distinction à ne pas confondre. Les trois styles vivent sur les mêmes entités et ne se comportent pas pareil.

## 2.1 Style générique — `<action>/<entité>`

Piloté par `WebService::systemServe()` et les méthodes `createCore()`, `updateCore()`, `deleteCore()`, `detailCore()`, `uploadCore()`, `ll()`, `dcollection()` de `FrontController.php`.

L'entité doit figurer dans `$public_access` de `route/WebService.php` **et** dans `config/dvups_configurations.php`, sinon :

```
entity <x> has no public access or has not been found
```

| Action | Route | Méthode du socle |
|---|---|---|
| Créer | `POST /api/create/<entité>` | `createCore()` |
| Détail | `GET /api/detail/<entité>?id=X` | `detailCore($id)` |
| Lister | `GET /api/lazyloading/<entité>?dfilters=on&...` | `ll()` |
| Mettre à jour | `/api/update/<entité>?id=X` | `updateCore($id)` |
| Supprimer | `/api/delete/<entité>?id=X` | `deleteCore($id)` |
| Envoyer un fichier | `/api/upload/<entité>?id=X` | `uploadCore($id)` |
| Lectures groupées | `POST /api/dcollection` | `dcollection()` |

:::note À retenir
**Sur les verbes HTTP de ce style.** Le dispatcher ne contrôle pas le verbe : il teste seulement le premier segment contre la liste des sept actions. Écrire le verbe correct reste la bonne pratique ; une version future peut resserrer.
:::

### La charge utile

```json
{ "task": { "name": "valeur", "project.id": 3 } }
```

Le point référence une relation `manyToOne`. Pour une création en masse :

```json
{ "task_bulk": [ { ... }, { ... } ] }
```

`createCore()` traite alors chaque élément séparément et renvoie un tableau `datacollection` avec un `success` par ligne.

### L'envoi de fichiers

Un attribut de type fichier ne passe pas en JSON : il faut du `multipart/form-data`, avec des clés `<entité>_form[champ]` :

```
cmsresource_form[name]
cmsresource_form[image]      (le fichier)
```

`createCore()` détecte `$_FILES[<entité>_form]` et enchaîne un `upload<Champ>()` après la création.

### Deux pièges du listing

**1. `?<relation>_id=X` ne filtre rien.** Le bon filtre relationnel est :

```
?dfilters=on&project.id:eq=1
```

La première forme répond 200, `success: true`, avec des résultats — ceux de toute la table.

**2. La réponse est paginée, silencieusement.** Par défaut `per_page = 30`. La réponse porte `nb_element` (le total réel du filtre), `per_page`, `current_page`, `next`, `remain` (1 tant qu'il reste des pages). Les paramètres de pagination sont `?next=<page>&per_page=<taille>` — ni `?page=`, ni `?p=`.

```js
let all = [], page = 1, remain = true
while (remain) {
  const res = await api.get(`/lazyloading/task?dfilters=on&project.id:eq=${id}&next=${page}&per_page=100`)
  all = all.concat(res.data.listEntity || [])
  remain = !!res.data.remain
  page = res.data.next
}
```

## 2.2 Style REST dédié — un `FrontController` par entité

Généré par `php devups core:g:frontcontroller`. La classe porte `#[Api(name: '/xxx')]`, les méthodes un attribut de verbe.

```php
#[Api(name: '/task')]
class TaskFrontController extends FrontController
{
    #[GET]
    public function index()
    {
        return Task::initQb()->with('project', ['name'])->lazyloading();
    }

    #[GET(path: '/:id')]
    public function detail($id) { ... }

    #[Auth(authorized: 1)]
    #[POST]
    public function create() { ... }

    #[Auth(authorized: 1)]
    #[PUT(path: '/:id')]
    public function update($id) { ... }

    #[Auth(authorized: 1)]
    #[DELETE(path: '/:id')]
    public function delete($id) { ... }

    #[Auth(authorized: 1)]
    #[POST(path: '/:id/close')]
    public function close($id) { ... }
}
```

| Méthode PHP | Attribut | Route |
|---|---|---|
| `index()` | `#[GET]` | `GET /api/task` |
| `create()` | `#[POST]` | `POST /api/task` |
| `detail($id)` | `#[GET(path: '/:id')]` | `GET /api/task/42` |
| `update($id)` | `#[PUT(path: '/:id')]` | `PUT /api/task/42` |
| `delete($id)` | `#[DELETE(path: '/:id')]` | `DELETE /api/task/42` |
| `close($id)` | `#[POST(path: '/:id/close')]` | `POST /api/task/42/close` |

Ce style est strict sur le verbe : une méthode portant `#[GET]` appelée en `POST` répond 405.

### Le dispatch

`Request::FrontController()` :

1. convertit le segment d'action en nom de méthode (`close-task` → `closeTask`) ;
2. vérifie que l'entité demandée correspond au `name` de l'attribut `#[Api]` de la classe (sinon 500) ;
3. si la méthode n'existe pas, balaie les méthodes publiques qui portent un attribut du verbe courant avec un `path`, et apparie le motif complet à l'URI — c'est le mécanisme des sous-ressources ;
4. sinon, résout par nombre de segments (`/task` → `index`/`create`/…, `/task/42` → `detail`/`update`/`delete`) ;
5. contrôle `Auth::$restrictions` sur le nom de méthode réellement résolu (§8.4) ;
6. contrôle `#[Auth]` ;
7. **verrou d'attribut** : si la méthode n'a ni attribut du verbe courant, ni `#[Api]`, répond 405 ;
8. appelle la méthode, paramètres liés par nom.

:::warning Piège
**Conséquence du verrou (étape 7).** Une méthode publique de `FrontController` sans attribut de verbe n'est atteignable par aucune route, quel que soit son nom. Vérifier l'attribut avant de conclure qu'une méthode est appelée.
:::

## 2.3 Style libre — `<action>Serve()`

Pour tout ce qui n'est ni une entité ni un CRUD : une méthode `<Action>Serve()` directement sur `route/WebService.php`.

```php
// GET /api/hello → helloServe()
public function helloServe() { ... }

// POST /api/accept-invitation?id=5 → acceptInvitationServe()
public function acceptInvitationServe() { ... }
```

Les paramètres passent en chaîne de requête. Le routeur n'exige pas d'attribut ici : `#[Auth]` est à poser explicitement, ou le contrôle à écrire à la main :

```php
if (!Auth::$user_id) {
    http_response_code(401);
    Genesis::json_encode(['success' => false, 'detail' => 'non authentifié']);
}
```

`Auth::$user_id` est déjà peuplé à ce stade : `runServe()` appelle `Auth::execute()` en tête.

## 2.4 Lequel choisir

| Besoin | Style |
|---|---|
| CRUD immédiat d'une entité, sans écrire de contrôleur | générique |
| API consommée par une application mobile ou un front séparé, avec un contrat stable | REST dédié |
| sous-ressource (`/task/:id/close`) | REST dédié, avec `#[POST(path: '/:id/close')]` |
| action métier qui n'est pas un CRUD | libre (`xxxServe`) |

Le style générique est pratique et dangereux : il expose les actions d'écriture de toute entité enregistrée. Le chapitre 8 décrit les garde-fous.
