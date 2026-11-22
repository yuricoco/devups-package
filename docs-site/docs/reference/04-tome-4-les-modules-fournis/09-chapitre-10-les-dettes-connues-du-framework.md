---
title: "Chapitre 10 — Les dettes connues du framework"
sidebar_position: 9
---

Un défaut connu et nommé coûte une heure ; le même défaut redécouvert coûte une journée.

## 10.1 Ce que la 7.0 et la 7.1 ont soldé

| Dette des versions 4.x | Correction |
|---|---|
| `$dvold` posé par référence : les mises à jour n'écrivaient rien | vrai clone dans `hydrate()` |
| erreur SQL = affichage puis arrêt | `DBALException`, rattrapable |
| `install` : `config_data.sql` échouait en silence, pas d'administrateur | fichier réduit aux `INSERT`, séquence testée |
| `services.php` des modules acceptaient des écritures anonymes | session d'administration exigée |
| profil utilisateur public exposant e-mail et téléphone | `restrictForRequester()` |
| mots de passe en `sha1`, cookie « se souvenir » contenant le hash | `password_hash`, jeton aléatoire |
| aucune limitation des tentatives | `RateLimiter` |
| inclusion de fichier par `?lang=` | `dv_valid_lang()` + `realpath()` |
| traductions de l'interface en base, une requête par libellé | fichiers `lang/*.php`, aucune requête |
| `Dvups_right` sans vérification réelle | `can()`, catalogue, privilèges, cache |
| secrets dans un fichier versionné | `.env` |
| schéma modifié à la main par environnement | `php devups migrate` |
| `Datatable` : 500 après toute création ou mise à jour par certains contrôleurs | `catch (\Throwable)` dans le rendu de ligne |
| D1 : `INNER JOIN dvups_lang` après le `LEFT JOIN <entité>_lang` — une ligne traduite sans traduction disparaissait de toute lecture ORM | `LEFT JOIN` vers `dvups_lang` (7.1) |
| D2 : colonne ambiguë sur une entité traduite (`l.name`) | qualification automatique `this.<colonne>` quand l'entité est traduite (7.1) |
| D3 : `get($col, $id_lang)` — second argument sans effet sur la langue | langue posée avant `initSelect()` (7.1) |
| D4 : `whereIn()`/`in()`/`notIn()`/`whereInReverse()` quotaient sans échapper réellement | valeurs liées en paramètre PDO (7.1) |
| D5 : `whereBetween()`/`between()` interpolaient leurs bornes | bornes liées en paramètre PDO (7.1) |
| D6 : `nullable` d'une relation obligatoire ignoré par le générateur | `nullable=false` sur `@JoinColumn` quand la relation est `'not'` (7.1) |
| D7 : `core:g:crud` pouvait dupliquer le bloc de `require` du module | `__Generator::dependencies()` vérifie l'idempotence avant d'écrire (7.1) |
| D10 : contrôle de session vide en tête de `manageServe()` | vrai refus 401 JSON, mêmes exceptions que `manage()` (7.1) |
| D12 : une clé de colonne `Datatable` qui ne nomme aucune propriété arrêtait l'écran (`\TypeError` de `call_user_func()`) | `method_exists()` vérifié avant l'appel : cellule vide + `Log::warning()` (7.1) |
| D13 (partie `StatusTrait`) : `StatusTrait` référençait une entité disparue | supprimée, aucun usage réel (7.1) — `Dvups_config_item`, déclarée dans la même dette, s'est révélée toujours utilisée (héritée par `Dvups_entity`) : retirée de la liste, ce n'était pas du code mort |
| D15 : téléversement d'une image de 1×1 pixel — erreur de redimensionnement GD plantant toute la requête | `Dfile` conserve l'original déjà enregistré et renonce proprement à la seule variante redimensionnée en cas d'échec GD (7.1) |

## 10.2 Les dettes ouvertes

| # | Dette | Emplacement | État |
|---|---|---|---|
| D8 | `schema:update --force` peut répondre « success » sans appliquer un renommage d'index | Doctrine | passer par une migration |
| D9 | `Router::cacheRoute()` commence par `return null;` | `Router.php` | confirmé en 7.1 (audit explicite, ÉTAPE E.4) : code mort par construction, documenté en commentaire plutôt que supprimé ; laisser `ROUTE_CACHE=false` |
| D11 | `FORMTYPE_INJECT_COLLECTIONFORM` déclaré, sans rendu | `FormManager`, `FormFactory` | marqué `@deprecated` en 7.1 ; à ne pas utiliser |
| D14 | `whereRaw()` et `where_str()` ont un corps identique | `QueryBuilder` | laissé tel quel ; jamais avec une donnée utilisateur |

## 10.3 Les leçons de méthode

- **Mesurer avant de corriger.** Une affirmation sur un comportement se vérifie par un test qui l'exécute, pas par la lecture seule.
- **Corriger à la racine quand elle est dans le projet, la nommer quand elle est dans le socle.** Une correction de `dclass/` change le comportement de tous les projets : elle se fait dans le dépôt du framework, avec un test de non-régression.
- **Un refus doit terminer la requête.** Et ne rien révéler de la ressource demandée.
- **Une exclusion silencieuse ressemble à une donnée absente.** Toute règle qui écarte des lignes (cache, filtre, droit) le dit.
- **Du code mort qui ressemble à de la sécurité est pire que pas de code.** Il fait croire à une protection qui n'existe pas.
