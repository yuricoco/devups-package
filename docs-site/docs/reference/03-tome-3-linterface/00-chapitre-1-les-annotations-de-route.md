---
title: "Chapitre 1 — Les annotations de route"
sidebar_position: 0
---

## 1.1 Le lecteur d'annotations

:::tip Nouveau
**Converti au LOT 3 du chantier v8.0.** Jusqu'à la 7.1, Devups lisait ses annotations de route en docblock avec `maslosoft/addendum` et une bibliothèque vendorée (`dclass/lib/addendum/`), via `ReflectionAnnotatedClass` et `$method->hasAnnotation('X')` / `getAnnotation('X')`. Les deux sont retirées (plus maintenues, inutiles face aux attributs natifs disponibles depuis PHP 8.0). Les mêmes annotations existent désormais comme attributs PHP 8 natifs (`#[GET(...)]`, `#[Auth(...)]`...), et `hasAnnotation()`/`getAnnotation()` restent utilisables à l'identique : `ReflectionAnnotatedClass`/`ReflectionAnnotatedMethod` sont redéfinies dans une nouvelle classe, `dclass/lib/ReflectionAnnotated.php`, qui s'appuie sur `getAttributes()->newInstance()` plutôt que sur un parseur de docblock.
:::

Devups lit ses annotations de route sous forme d'attributs PHP 8, via `ReflectionAnnotatedClass` et `$method->hasAnnotation('X')` / `getAnnotation('X')` :

```php
#[Auth(authorized: 1)]
#[GET(path: '/:id/comments')]
public function comments($id) { ... }
```

Les classes d'annotation (désormais des classes d'attribut `#[\Attribute(...)]`) vivent dans `dclass/lib/annotation/`.

:::warning Piège
**Attribut global, classe namespacée.** Les classes d'attribut de `dclass/lib/annotation/` sont dans l'espace de noms global. Une classe qui déclare elle-même un `namespace` (ex. `route/Admin.php`, `namespace route;`) doit référencer l'attribut par son nom pleinement qualifié (`#[\MethodView(...)]`), sans quoi PHP cherche `route\MethodView` et non `\MethodView`. Les `FrontController` restent dans l'espace de noms global : `#[GET]`/`#[POST]`/... s'y résolvent sans préfixe.
:::

## 1.2 Les annotations disponibles

| Annotation (attribut PHP 8) | Portée | Rôle |
|---|---|---|
| `#[Api(name: '/xxx')]` | classe | le préfixe d'URL d'un `FrontController` |
| `#[GET(path: '...')]` | méthode | route en GET |
| `#[POST(path: '...')]` | méthode | route en POST |
| `#[PUT(path: '...')]` | méthode | route en PUT ; `path` vaut `'/:id'` par défaut |
| `#[DELETE(path: '...')]` | méthode | route en DELETE ; `path` vaut `'/:id'` par défaut |
| `#[Api]` (sur une méthode) | méthode | route tous verbes |
| `#[Auth(authorized: 1)]` | méthode | exige un jeton JWT valide |
| `#[MethodView(...)]` | méthode | l'ancienne forme, utilisée dans `route/Admin.php` |
| `#[DDoc(...)]` | méthode | documentation |
| `#[OA\...]` | classe / méthode | annotations OpenAPI (`zircote/swagger-php`), documentation seulement |

`GET`, `POST`, `PUT` et `DELETE` héritent de `Api`. Chacun apparie l'URL réelle au motif `<name de la classe>` + `<path de la méthode>` et verse les paramètres extraits dans `Request::$uri_get_param`. Si l'appariement échoue : 404.

## 1.3 Les segments de chemin

Un segment préfixé de `:` est un paramètre nommé :

```php
#[GET(path: '/:id')]
public function detail($id) { ... }

#[GET(path: '/export/:format/:id')]
public function export($format = null, $id = null) { ... }
```

Le nom du paramètre d'URL doit correspondre au nom du paramètre PHP : c'est par ce nom que la valeur est liée. Un `path` peut porter plusieurs motifs séparés par des virgules ; le premier qui a le bon nombre de segments est retenu.

## 1.4 La barre oblique initiale

Le motif complet est construit par `rtrim($ctrlApi->name, '/') . '/' . ltrim($ann->path, '/')`. La barre oblique initiale du `path` reste la forme attendue : `#[GET(path: '/export/:format/:id')]`, jamais `#[GET(path: 'export/:format/:id')]`.
