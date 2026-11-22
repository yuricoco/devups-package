---
title: "Chapitre 11 — Récapitulatif pour qui reprend un projet"
sidebar_position: 10
---

## 11.1 Les dix premières minutes

1. `composer install`, copier `.env.example` en `.env`, renseigner la base.
2. `php devups migrate:status` : la base est-elle à jour ?
3. `composer test` : le projet tient-il ?
4. Lire `src/requires.php` : la liste des modules réellement chargés.
5. Lire `route/WebService.php` : ce qui est exposé (`$public_access`), ce qui est public (`$publicMethods`).
6. Ouvrir le back-office, écran « Rôles » : qui a le droit de faire quoi.
7. Ouvrir l'écran « Logs » : ce qui casse en ce moment.
8. `php devups lang:missing` : ce qui n'est pas traduit.
9. `php devups schema:update --dump-sql` : le schéma réel correspond-il aux entités ?
10. `/admin/apidocs` : la carte de l'API.

## 11.2 Les dix pièges qui coûtent le plus cher

| # | Piège | Référence |
|---|---|---|
| 1 | `find()`, `first()`, `getInstance()` renvoient une entité vide, jamais `null` | Tome II §4.5 |
| 2 | une relation `manyToOne` ne charge que son `id` sans `with()` | Tome II §4.4 |
| 3 | une ligne traduite sans traduction était invisible à l'ORM (D1, corrigé en 7.1) | Tome II §7.4 |
| 4 | `?<relation>_id=X` ne filtre rien ; la réponse est paginée à 30 | Tome III §2.1 |
| 5 | `gte` / `lte` n'existent pas et deviennent un `LIKE` | Tome III §4.7 |
| 6 | une méthode de `FrontController` sans annotation de verbe est morte | Tome III §2.2 |
| 7 | une méthode `xxxServe()` sans `#[Auth]` est anonyme | Tome III §8.3 |
| 8 | `whereIn()` / `whereBetween()` ne sont pas paramétrés | Tome II §4.2 |
| 9 | un module sans `*Core.json` est invisible, sans erreur | Tome I §7.3 |
| 10 | `Genesis::json_encode()` et `dv_dump()` terminent la requête | Tome III §6.2, Tome I §4.5 |

## 11.3 Les commandes du quotidien

```bash
php devups serve                       # développer
composer test                          # vérifier
php devups migrate:status              # où en est la base ?
php devups migrate --dry-run && php devups migrate
php devups dvups_:update && php devups rights:sync    # après un nouveau module ou une nouvelle entité
php devups lang:scan && php devups lang:missing       # traductions
php devups api:doc                     # instantané OpenAPI
php devups core:g:crud monprojet\ModuleTask\task      # générer
```

## 11.4 Comment contribuer à cette documentation

Chaque projet qui mesure un comportement non documenté, ou trouve une affirmation fausse, l'ajoute ici, avec le fichier qui porte le comportement et, si possible, le test qui le prouve. Une dette soldée passe du tableau 10.2 au tableau 10.1, dans la même version que sa correction.

<p class="end">Fin du Tome IV et de la documentation de Devups 7.0.0.</p>
