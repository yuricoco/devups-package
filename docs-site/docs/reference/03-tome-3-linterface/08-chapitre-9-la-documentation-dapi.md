---
title: "Chapitre 9 — La documentation d'API"
sidebar_position: 8
---

:::tip Nouveau
**Nouveau en 7.0.**
:::

## 9.1 Ce qui est généré

La documentation est construite par analyse statique des annotations Devups et des datacores, sans charger aucune classe PHP (`dclass/generator/OpenApiGenerator.php` et `DocGuideGenerator.php`).

| Accès | Contenu | Protection |
|---|---|---|
| `/documentation/` | spécification OpenAPI des routes front (JWT), avec Swagger UI | publique |
| `/documentation/guide.php` | guide développeur interactif, depuis les datacores (`?format=json` pour le JSON brut) | publique |
| `/admin/apidocs` | Swagger UI complet : routes front **et** routes du back-office (session) | `can('read', 'Documentation')` |
| `php devups api:doc` | écrit `openapi.json` à la racine (routes front) | fichier non versionné |
| `php devups doc:guide` | écrit une page HTML par entité sous `documentation/guide/<composant>/<module>/<entité>.html` | fichiers non versionnés |

`Documentation` est une action « virtuelle » : `rights:sync` la crée comme n'importe quelle action d'entité, ce qui permet de donner accès au Swagger du back-office à un rôle autre que `admin`.

:::tip Nouveau
**Corrigé en 7.1.** La description du serveur, dans la spécification OpenAPI, codait en dur `http://127.0.0.1:3333/api` — faux dès que `APP_PORT` différait de `3333` (par exemple `:3031` en développement, §1.4). `dv_build_openapi_spec()` utilise désormais `__env` (`APP_URL` + `APP_PORT`), la même valeur que partout ailleurs dans le framework ; `/documentation/` et le Swagger du back-office (`/admin/apidocs`) en héritent, puisqu'ils partagent le même générateur.
:::

:::note À retenir
**`openapi.json` dans la chaîne d'intégration.** `php devups api:doc` en CI produit un fichier que l'on peut comparer d'une version à l'autre, ou donner à un générateur de client.
:::

## 9.2 Ce qui n'est pas lu

Le générateur déduit chaque route de ses attributs Devups (`#[Api]`, `#[GET]`, `#[POST]`, `#[PUT]`, `#[DELETE]`, `#[Auth]`) et chaque schéma du datacore de l'entité. Les annotations `#[OA\...]` de `zircote/swagger-php`, que l'on trouve sur certains contrôleurs, ne sont **pas** lues par ce générateur : elles ne changent rien à `/documentation/` ni à `/admin/apidocs`. Une route qui n'apparaît pas dans la documentation est d'abord une route dont l'annotation de verbe manque — ce qui, pour un `FrontController`, la rend aussi inatteignable (§2.2).

<p class="end">Fin du Tome III. Le Tome IV traite des modules fournis par le socle : droits, journaux, langues, contenus, arbres, statuts, notifications, courriels, et des dettes connues du framework.</p>
