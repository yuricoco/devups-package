---
title: "Chapitre 6 — Le protocole de cache, les statuts, les pays"
sidebar_position: 5
---

## 6.1 Pourquoi un protocole

Compiler des données de référence en fichiers PHP est une idée simple qui peut mal tourner de sept façons. Les sept règles qui suivent ont été établies en construisant le cache de `Tree`, puis revérifiées en le réimplémentant pour `Status`. Elles s'appliquent à tout cache de données de référence.

`Tree::buildCacheData($name, $id_lang)` produit, par langue active, deux fichiers :

| Fichier | Forme | Usage |
|---|---|---|
| `cache/trees/<name>.<iso>.php` | liste ordonnée | rendu d'une liste déroulante, d'un menu |
| `cache/trees/<name>_references.<iso>.php` | carte indexée par slug | recherche en temps constant |

## 6.2 Les sept règles

**C1 — La clé de fichier.** Le nom du fichier dérive d'une clé unique et immuable, et il est assaini à l'écriture. `Util::arrayToPhpFile()` n'accepte que `^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$` : les séparateurs `<name>.<iso>` passent, `../` non.

**C2 — La langue.** Un fichier par langue active, et la langue est propagée à chaque niveau de la descente — sinon les enfants restent dans la langue par défaut.

**C3 — L'invalidation à l'écriture.** Toute écriture (`Tree_item::__insert()`, `__update()`, `__delete()`, `Tree::__delete()`) appelle `Tree::markDirty($name)`. Une seule fonction d'arrêt par processus reconstruit, en fin de requête, chaque arbre marqué : une reconstruction par arbre et par requête, depuis l'état final en base. Conséquence pour les tests : le rebuild différé est invisible dans le processus qui écrit, il se vérifie dans un second processus.

**C4 — L'atomicité.** Fichier temporaire dans le même dossier, puis `rename()`, exception si l'une des deux étapes échoue. Sans cela, un lecteur concurrent peut inclure un fichier à moitié écrit, donc du PHP invalide.

**C5 — La lecture honnête.** Un cache absent doit se voir. `Tree::constant()` lit le fichier de la langue demandée, retombe sur la langue par défaut, puis reconstruit depuis la base ; dans ce dernier cas, il journalise toujours, et lève une exception hors production. La lecture renvoie `null` (et non `[]`) quand le fichier est absent : un cache manquant et un arbre vide restent distinguables. Et elle signale par le journal, jamais par `dv_dump()`, qui tuerait la page.

**C6 — La profondeur.** `maxDepth = min(count(branches), Tree::MAX_CACHE_DEPTH)`, avec `MAX_CACHE_DEPTH = 10`. Un garde anti-cycle (`parent_id` A → B → A) arrête la descente au nœud déjà visité, en journalisant, au lieu de boucler.

**C7 — Ce qui n'est pas mis en cache.** Le dire explicitement. Pour `Tree` : les éléments sans `slug` et ceux dont `main != 1` sont exclus. Un élément sans `slug` n'apparaît donc jamais dans le cache, silencieusement — une exclusion silencieuse ressemble en tout point à une donnée absente.

| Règle | Verdict de la seconde implémentation (`Status`) |
|---|---|
| C1 — clé de fichier | adaptée : la clé est `entity`, partagée par nature |
| C2, C3, C4, C5 | inchangées |
| C6 — profondeur | sans objet : les statuts sont plats |
| C7 — exclusions | adaptée : un statut sans `entity` n'est jamais mis en cache |

## 6.3 `Status` — décrire, ne pas remplacer

Beaucoup d'entités ont une colonne `status` entière. `Status` est une table de **description** : une clé machine (`draft`, `published`…), l'entier qu'elle décrit (`value`), une couleur, une position, un libellé traduit, et une portée (`entity`). Une contrainte `UNIQUE(entity, _key)`, à poser par migration, empêche deux lignes de porter la même clé pour la même entité.

:::note À retenir
**Pourquoi la table ne devient pas la source de vérité.** Le code métier compare la colonne `status` à des entiers (`status = 1`), jamais par une jointure vers `status`. Faire dépendre une règle de visibilité d'une table éditable par un administrateur — où l'on peut ajouter, renommer ou supprimer une clé — serait fragile. La table dit ce que les entiers veulent dire pour un humain ; elle ne décide jamais ce qui est visible. Quand on ne sait pas quel entier une clé décrit, laisser `value` à `NULL` : c'est une information ; deviner est une erreur silencieuse.
:::

```php
Status::constant('task');               // tous les statuts d'une entité, depuis le cache
Status::constant('task', 'draft');      // un statut précis
Status::constant('task', null, $idLang);
$status->is('draft');                   // test
$status->getColortab();                 // pastille HTML colorée (texte contrasté par luminance)
$status->getValue();                    // l'entier décrit
```

Une clé inexistante ne lève pas : elle renvoie une entité vide (Tome II §4.5), et un filtre construit dessus devient silencieusement faux. Vérifier `getId()`.

## 6.4 `Interval` et `Interval_group`

Des intervalles et des barèmes, rattachés à des éléments d'arbre (`Interval_group` est le pivot `interval` ↔ `tree_item`). Usage typique : des tranches (d'âge, de montant, de durée) éditables au back-office.

## 6.5 `Country`

`ModuleLocation` ne garde que `Country` : code ISO à deux et trois lettres, indicatif téléphonique, nom traduit. Les données de référence (environ 195 pays) sont chargées, de façon idempotente, par :

```bash
php devups location:seed
```

La source est `database/seed/countries.php`. Un projet qui a besoin de régions ou de villes les modélise dans son propre composant, ou dans un arbre (`ModuleTree`) si ce sont des listes de référence.
