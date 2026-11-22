---
title: "Chapitre 5 — Le cycle de vie d'une requête"
sidebar_position: 5
---

C'est le chapitre à lire avant tous les autres : la plupart des surprises de ce framework viennent de ce que quatre dispatchers cohabitent, et qu'ils ne suivent pas exactement les mêmes règles.

## 5.1 Les deux points d'entrée

| Fichier | Appelle |
|---|---|
| `index.php` | `Router::start()` — l'application publique et son API |
| `admin/index.php` | `Router::startAdmin()` — le back-office et son API |

`Router::getPathInfo()` établit le chemin en essayant, dans l'ordre : `$_SERVER['PATH_INFO']`, puis `REQUEST_URI` moins le répertoire de base, puis `$_GET['url']`, puis la chaîne de requête. La partie après `?` est retirée.

## 5.2 Les quatre portes

```
            index.php                              admin/index.php
         Router::start()                        Router::startAdmin()
                │                                       │
      ┌─────────┴──────────┐                 ┌──────────┴──────────┐
  /api/* ?             sinon           /admin/api/* ?          sinon
      │                    │                 │                     │
      ▼                    ▼                 ▼                     ▼
  WebService              App           AdminService             Admin
  ->runServe()          ->run()       ->manageServe('')        ->manage()
 (API publique)     (pages publiques)  (API back-office)   (pages back-office)
```

Les quatre classes vivent dans `route/` et héritent de `Router`.

## 5.3 Porte 1 — `App::run()`, les pages publiques

1. **Langue dans l'URL.** Si le premier segment est le code ISO d'une langue active (`$dlangs`), il est retiré du chemin et la langue est positionnée. `/fr/contact` et `/contact` mènent au même endroit, dans deux langues.
2. **Nom de méthode.** Le segment suivant est découpé sur `-` et `_`, chaque morceau prend une capitale initiale, et `View` est ajouté : `/mon-compte` → `MonCompteView()`, `/` → `HelloView()`.
3. Si la méthode n'existe pas sur `App`, le tableau `$append_routes` est consulté — le seul mécanisme de route nommée :

```php
// route/App.php
public $append_routes = [
    '/imprimer-facture' => 'Invoice@printInvoice',
];
```

4. Sinon : 404 en production, message de développement sinon.
5. `mapFunction('App', $function)` (§5.7).

## 5.4 Porte 2 — `WebService::runServe()`, l'API publique

**a. CORS.** L'origine de la requête est comparée à `__csrf_origin`. Si elle y figure (ou vaut `null`), les en-têtes `Access-Control-Allow-Origin` et `-Credentials` sont émis. Les en-têtes autorisés sont codés en dur dans `Router.php` : `Authorization, Auth, X-Customer-Auth, Columns, Dlang, common, Content-Type, X-Requested-With`. Une requête `OPTIONS` répond 200 et s'arrête.

:::warning Piège
**Piège de développement.** Si le serveur de développement du front choisit un port absent de `CORS_ORIGINS`, le navigateur n'affiche qu'un « Failed to fetch ». Ajouter l'origine à `CORS_ORIGINS`.
:::

**b. Deux en-têtes lus comme des paramètres.** `Columns` (un JSON `{"relation":["col1","col2"]}` qui déclenche un `with()`, Tome II) et `Dlang` (force la langue de la réponse).

**c. L'authentification, inconditionnellement.** `(new Auth())->execute()` est appelé avant tout dispatch. S'il réussit, `Auth::$user_id` est peuplé. S'il échoue, rien ne se passe : l'absence de jeton n'est pas une erreur à ce stade ; elle le devient plus loin si la méthode visée l'exige.

**d. Le dispatch.** Le chemin, privé de `/api/`, est découpé sur `/` :

| Condition | Dispatch | Style |
|---|---|---|
| ≥ 2 segments et segment 0 ∈ `create`, `upload`, `detail`, `read`, `update`, `delete`, `lazyloading` | `systemServe()` | générique |
| segment 0 est une clé de `$public_access` | `FrontController::frontServe()` | REST dédié |
| sinon | `<Segment>Serve()` sur `WebService` | libre |

```
POST /api/create/task          → systemServe()     générique
GET  /api/task/42              → TaskFrontController::detail(42)
GET  /api/hello                → helloServe()      libre
POST /api/accept-invitation    → acceptInvitationServe()
```

**e.** Si la méthode n'existe pas : `$append_routes`, puis 404.

## 5.5 Porte 3 — `Admin::manage()`, les pages du back-office

`Router::manage()` positionne `Router::$admin_route = true` — un drapeau qui a des conséquences de sécurité réelles (Tome III) — puis :

1. **Session obligatoire.** Si `$_SESSION[ADMIN]` est absent et que le chemin n'est pas `connexion-action`, redirection vers la page de connexion.
2. Le chemin est découpé ; `admin` est ajouté en tête s'il manque.
3. Le nombre de segments décide :

| Segments | Interprétation | Conséquence |
|---|---|---|
| ≥ 5 | `/admin/<composant>/<module>/<entité>/<action>` | `Controller::views()` — le CRUD générique, après vérification des droits |
| ≥ 3 | `/admin/<composant>/<module>` | `Controller::viewsModule()` — l'écran du module |
| sinon | `/admin/<page>` | méthode `<Page>View()` sur `Admin` |

```
/admin/                                     → DashboardView()
/admin/monprojet/ModuleTask                 → écran du module
/admin/monprojet/ModuleTask/task/list       → TaskController::listView()
```

C'est ce mécanisme qui donne à une entité générée un écran de liste, de détail et de formulaire sans qu'une ligne de contrôleur d'écran soit écrite — à condition que l'entité figure dans `dvups_configurations.php`.

## 5.6 Porte 4 — `AdminService::manageServe()`, l'API du back-office

| Forme | Dispatch |
|---|---|
| 2 segments, segment 0 ∈ les sept verbes génériques | `systemServe()` |
| 2 segments, `<entité>/<action>` (`/admin/api/task/create`) | `Controller::serve()` → `Request::Controller()` |
| sinon | `<Path>Service()` sur `AdminService` |

Noter la différence de suffixe : `View` pour une page, `Serve` pour l'API publique, `Service` pour l'API du back-office.

:::tip Nouveau
**Nouveau en 7.0 — la garde des droits.** `Request::Controller()` est le point de passage unique de toute action CRUD d'entité, qu'elle arrive par une page (§5.5) ou par l'API du back-office. Avant d'appeler la méthode, il traduit son nom en action (`createAction` → `create`, `listView` et `formView` → `read`…) et appelle `can($action, $entité)`. Refus : 403. Le Tome IV, chapitre 2, décrit le système de droits.
:::

:::tip Nouveau
**Corrigé en 7.1 — le garde de session de `manageServe()`.** Le contrôle de session en tête de `manageServe()` était jusqu'ici un bloc entièrement commenté (aucun refus réel) : `Admin`, `ModuleConfig`, `ModuleLog`, `ModuleUser` (et tout module généré sur le même patron) n'avaient donc aucun garde qui leur soit propre à ce point d'entrée partagé — protégés en pratique seulement par `admin/header.php` (par nom de script/chemin) pour les points d'entrée historiques, ou par le constructeur d'`AdminService`. `manageServe()` refuse désormais lui-même (401 JSON) toute requête sans session d'administration, avec les mêmes exceptions que `manage()` (`connexion-action`, `public_access`) — une méthode `xxxService()` écrite à la main sur n'importe quelle classe héritant de ce point d'entrée est donc protégée par construction, plus seulement par le script qui l'inclut.
:::

## 5.7 `mapFunction()` — le dernier kilomètre

`Router::mapFunction($classname, $function)` est appelée par trois des quatre portes. Elle :

1. réfléchit la méthode cible (`ReflectionAnnotatedClass`) ;
2. lie les paramètres par nom : une méthode `detailView($id)` reçoit automatiquement `?id=...` ;
3. contrôle `@Auth` : si la méthode porte l'annotation, `Auth::$user_id` doit être peuplé, sinon exception ;
4. cherche une annotation du verbe HTTP courant (`@GET`, `@POST`…), sinon `@Api`, pour extraire les paramètres de chemin ;
5. appelle la méthode.

:::warning Piège
**Divergence entre dispatchers.** Si aucune annotation de verbe ni `@Api` n'est trouvée, `mapFunction()` continue quand même : une méthode `xxxServe()` ou `xxxView()` sans annotation est appelable. Ce n'est pas vrai du style REST dédié : `Request::FrontController()` répond **405** à une méthode de `FrontController` sans annotation de verbe. Une méthode publique de `FrontController` sans annotation n'est donc atteignable par aucune route.
:::

## 5.8 Le cache de routes

Si `__route_cache` est vrai, `runCache()` consulte `cache/routes.php` avant le dispatch normal. Mais `Router::cacheRoute()`, qui devrait écrire ce fichier, commence par `return null;`. Le mécanisme est complet côté lecture et mort côté écriture : laisser `ROUTE_CACHE=false`.

:::tip Nouveau
**Confirmé en 7.1.** Audit explicite (ÉTAPE E.4) : `cacheRoute()` a son propre `return null;` précoce et **inconditionnel** (atteint même si `__route_cache` est vrai), et `runCache()` ne dépasse jamais son `if (!__route_cache) return null;` puisque cette constante vaut toujours `false` par défaut. Les deux méthodes sont désormais commentées en ce sens dans le code plutôt que supprimées (plusieurs sites d'appel dans `Request.php`, jamais exercés par aucun test) — rien n'a changé fonctionnellement, le constat ci-dessus reste exact.
:::

## 5.9 Résumé en un tableau

| Porte | Classe | Convention de méthode | 405 sur annotation absente | Contrôle d'accès |
|---|---|---|---|---|
| Pages publiques | `App` | `<Path>View()` | non | `@Auth` si posé |
| API publique | `WebService` | `<Path>Serve()`, générique, ou `FrontController` | oui, pour `FrontController` seulement | JWT, `Auth::$restrictions`, `@Auth` |
| Pages back-office | `Admin` | `<Path>View()`, ou CRUD générique par nombre de segments | non | session + `can()` |
| API back-office | `AdminService` | `<Path>Service()`, ou générique | non | `can()` pour les entités ; à la main pour un `xxxService()` |
