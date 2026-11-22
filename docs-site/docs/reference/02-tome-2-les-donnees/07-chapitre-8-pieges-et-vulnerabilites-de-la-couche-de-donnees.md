---
title: "Chapitre 8 — Pièges et vulnérabilités de la couche de données"
sidebar_position: 7
---

Récapitulatif, avec l'état réel de chacun en 7.1.

| # | Objet | État | Détail |
|---|---|---|---|
| 1 | `whereLike()`, `like()` et variantes | corrigé | paramétrés |
| 2 | `whereIn()`/`in()`/`notIn()`/`whereInReverse()` sur des chaînes | **corrigé en 7.1** | valeurs liées en paramètre ; forme chaîne brute dépréciée (entiers uniquement) |
| 3 | `whereBetween()`/`between()` | **corrigé en 7.1** | bornes liées en paramètre |
| 4 | forme à deux arguments de `where()` | corrigé | égalité, ou `IS NULL` |
| 5 | relations `manyToOne` | documenté | seul l'`id` est chargé ; `with()` au-delà |
| 6 | `getInstance()`, `first()`, `find()` sur zéro résultat | documenté | entité vide, jamais `null` ; `if ($x && $x->getId())` |
| 7 | `whereRaw()` / `where_str()` | documenté | SQL brut, jamais avec une donnée utilisateur |
| 8 | connexion PDO | corrigé | une seule connexion par processus |
| 9 | `$dvold` par référence | corrigé en 7.0 | `hydrate()` pose un vrai clone |
| 10 | erreur SQL fatale | corrigé en 7.0 | `DBALException`, rattrapable |
| 11 | D1 — `INNER JOIN dvups_lang` | **corrigé en 7.1** | `LEFT JOIN` ; ligne sans traduction reste visible (§7.4) |
| 12 | `get($col, $id_lang)` | **corrigé en 7.1** | `setLang()` posée avant `initSelect()` (§7.6) |
| 13 | D2 — colonne ambiguë sur une entité traduite | **corrigé en 7.1** | qualifiée automatiquement (`this.`) quand l'entité est traduite (§4.2) |
| 14 | `__get()` sur un attribut non déclaré | **comportement changé en 7.1** | lève `UndefinedPropertyException` (`\LogicException`), journalisée par le gestionnaire global — plus un `null` silencieux |
| 15 | `StatusTrait` | **supprimé en 7.1** | aucun usage, référençait une entité qui n'existait plus |

Les points 2 et 3 restent une bonne pratique de validation/typage en amont (toute valeur venant d'une requête HTTP qui entre dans un `whereIn()`/`whereBetween()` gagne à être validée ou castée tôt), mais ce n'est plus une nécessité de sécurité depuis 7.1 : le `QueryBuilder` lie désormais lui-même ces valeurs en paramètre, comme il le fait déjà pour `where()`, `whereLike()` et `whereGroup()`.

<p class="end">Fin du Tome II. Le Tome III traite de l'interface : les routes, les contrôleurs, les styles d'API, le moteur de listes, le moteur de formulaires, les vues et les langues, la sécurité et la documentation d'API.</p>
