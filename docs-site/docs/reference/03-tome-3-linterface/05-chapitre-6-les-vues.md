---
title: "Chapitre 6 — Les vues"
sidebar_position: 5
---

## 6.1 Blade

Les vues sont des gabarits Blade (`*.blade.php`) compilés dans `cache/views`. Les chemins de recherche sont dans le tableau global `$viewdir` :

| Contexte | Chemins |
|---|---|
| application publique | `web/views` |
| back-office | `admin/views` |
| écran d'entité ou de module | plus `src/<composant>/<Module>/Resource/views` |

## 6.2 `Genesis` — le rendu

| Méthode | Rôle |
|---|---|
| `Genesis::render($view, $data)` | rend une vue |
| `Genesis::renderView($view, $data)` | rend une vue avec `$viewdir` ; ajoute `moduledata` |
| `Genesis::getView($view, $data)` | retourne la chaîne au lieu de l'émettre |
| `Genesis::json_encode($value)` | émet du JSON **et termine la requête** |
| `Genesis::json_encode2(...)` | la même chose sans terminer |
| `Genesis::top_action($action)` | la barre d'actions d'une liste, selon les droits |

:::warning Piège
**`Genesis::json_encode()` termine la requête.** Tout ce qui doit s'exécuter doit l'être avant l'appel. Inversement, un refus émis avec `json_encode2()` laisse l'exécution continuer : après un refus, toujours s'assurer que la suite ne s'exécute pas.
:::

## 6.3 Les fonctions globales utiles

Déclarées dans `dclass/dvups_functions.php` :

| Fonction | Rôle |
|---|---|
| `route($path, $id, $title)` / `d_url(...)` | une URL avec le préfixe de langue (sauf en administration) |
| `path($src)`, `d_assets($src)` | `__env . $src`, `__env . 'web/assets/' . $src` |
| `services($service)` | `__env . 'admin/api/' . $service` |
| `redirect($url)` | redirection puis arrêt |
| `getadmin()` / `setadmin($id)` | l'administrateur en session |
| `can($action, $entité)` | le droit de l'administrateur courant (Tome IV, chapitre 2) |
| `local()` / `setlang($iso)` | la langue courante (chapitre 7) |
| `dv_hash_password()` / `dv_verify_password()` | hachage des mots de passe (§8.7) |
| `url_format()`, `remove_accents()`, `clean()`, `get_string_between()` | chaînes |
| `qb_sanitize($value)` | entoure de quotes **sans échapper** — voir Tome II §4.2 |
| `dv_dump(...$args)` | `dump()` puis `die(1)` — un arrêt, pas un journal |

## 6.4 Le JavaScript du socle

`dclass/devupsjs/`, servi par la constante `CLASSJS` :

| Fichier | Rôle |
|---|---|
| `devups.js` | le socle |
| `model.js` | le modèle côté client : création, édition, soumission |
| `ddatatable.js`, `DdatatableClass.js` | la liste : rechargement, tri, filtres, pagination |
| `dform.js` | la soumission d'un formulaire sans rechargement |
| `dialogbox.js` | les fenêtres modales |
| `Request.js`, `rest.js` | les appels HTTP (stockage local sous la clé `devups_store`) |

Une liste demande `devups.js`, `model.js` et `ddatatable.js` ; un formulaire demande en plus `dform.js` — c'est ce que fait `addDformjs()`.
