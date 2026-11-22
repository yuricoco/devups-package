---
title: "Chapitre 4 — Lire"
sidebar_position: 3
---

## 4.1 Les points d'entrée statiques

Toute entité hérite de `ModelTrait`, qui expose ces méthodes directement sur le nom de la classe.

| Méthode | Rôle | Sur zéro résultat |
|---|---|---|
| `Entity::find($id, $id_lang = null)` | lecture par id (ou tableau d'ids, ou tableau de conditions) | entité **vide**, jamais `null` |
| `Entity::findOrNull($id, $id_lang = null)` | idem | `null` |
| `Entity::where($col, $op, $val, $id_lang = null)` | fabrique un `QueryBuilder` déjà filtré | (chaîner) |
| `Entity::whereId($id)` | `this.id = $id`, ou `IN` si tableau | — |
| `Entity::whereIn($col, $values)` | `IN (...)` | — |
| `Entity::first($id_lang)` / `::firstOrNull()` | le premier enregistrement | entité vide / `null` |
| `Entity::last()` / `::lastrow()` | le dernier | entité vide |
| `Entity::firstOrCreate($constraint, $data)` | lit, ou crée si absent | crée |
| `Entity::firstOrNew($constraint, $data)` | lit, ou instancie sans insérer | — |
| `Entity::count($col = null, $val = null)` | `COUNT(*)`, filtré ou non | `0` |
| `Entity::sum()`, `::avg()`, `::max()` | agrégats | — |
| `Entity::all($sort = 'id', $order = 'asc', $id_lang)` | tout | `[]` |
| `Entity::allrows(...)` | tout, en lignes brutes | `[]` |
| `Entity::trashed(...)` | les lignes en suppression douce | `[]` |
| `Entity::getbyattribut($attribut, $valeur)` | une entité, par n'importe quelle colonne | entité vide |
| `Entity::select($columns = '*', ...)` | un `QueryBuilder` avec projection | — |
| `Entity::initQb($defaultjoin = true)` | un `QueryBuilder` vierge | — |
| `Entity::with($relation, $colonnes)` | chargement de relation | — |
| `Entity::orderBy($col, $sort)`, `::distinct($cols)` | tri, distinct | — |
| `Entity::lazyloading($order = "")` | listing paginé de l'API (Tome III) | — |
| `Entity::truncate()` | vide la table | — |
| `Entity::dclone($id, $update)` | duplique une ligne | — |

`Entity::where(...)` est le point d'entrée du quotidien : il équivaut à `Entity::initQb()->where(...)`.

`$defaultjoin = false` désactive les `LEFT JOIN` automatiques des relations `manyToOne` — utile quand la requête n'a besoin d'aucune relation.

## 4.2 La clause `WHERE`

### Comparaisons

```php
->where('this.status', '=', 'active')           // opérateur explicite
->where('this.id', $id)                          // deux arguments = égalité
->where('this.parent_id', null)                  // deux arguments, null = IS NULL
->where(['status' => 'active', 'type' => 'X'])   // tableau = égalités en AND
->andwhere($col, $op, $val)
->orWhere($col, $op, $val)
->whereId($id)
->where($unProject)                              // project.id = :project_id
```

Le préfixe `this.` désigne l'entité de base. Sans préfixe, la colonne pouvait devenir ambiguë dès qu'une jointure était en jeu (§4.8).

:::tip Nouveau
**Corrigé en 7.1 (D2), pour le cas de la jointure de traduction.** `where()`/`whereIn()`/`whereInReverse()`/`whereBetween()` qualifient désormais automatiquement (avec `this.`) une colonne non préfixée dès que l'entité est traduite (`dvtranslate`) — la jointure vers `<table>_lang` (toujours présente) et vers `dvups_lang` (en mode bilingue, sans `id_lang` forcé) exposent toutes les deux des colonnes `id`/`name` qui entraient en collision avec celles de l'entité de base. Préfixer en `this.` reste la bonne pratique pour toute AUTRE jointure (`->join()` explicite, §4.8) : seul le cas de la jointure de traduction est couvert automatiquement.
:::

### Les opérateurs reconnus

```
=  <  >  <=  >=  <>  !=  <=>
like  'like binary'  'not like'  ilike  'not ilike'  between  is
&  |  ^  <<  >>   rlike  regexp  'not regexp'
~  ~*  !~  !~*  ~~*  !~~*   'similar to'  'not similar to'
```

:::tip Nouveau
**Corrigé en 7.0.** La forme à deux arguments est normalisée : `where('col', $valeur)` est une égalité, `where('col', null)` produit `IS NULL`. Auparavant, une valeur qui n'était pas un opérateur connu pouvait être liée comme opérateur et donner un résultat faux sans erreur.
:::

### Recherche « contient »

```php
->whereLike('label', $q)         // LIKE '%q%', en AND
->whereLike('label', $q, 'l')    // LIKE 'q%' (préfixe)
->whereLike('label', $q, 'r')    // LIKE '%q' (suffixe)
->orWhereLike('city', $q)        // en OR
->where('col')->like($q)         // forme chaînée
```

Ces méthodes sont paramétrées : la valeur n'entre jamais dans le texte SQL.

### Groupe de conditions en OR

```php
Project::where('this.status', '=', 'active')
    ->whereGroup(function ($qb) use ($q) {
        $qb->whereLike('name', $q)
           ->orWhereLike('code', $q)
           ->orWhereLike('description', $q);
    })
    ->orderBy('this.name')
    ->get();
// WHERE ... AND status = :x AND (name LIKE :a OR code LIKE :b OR description LIKE :c)
```

Un second argument `$outerLink` (`'AND'` par défaut) rattache le groupe entier en OU au reste.

À ne pas confondre avec `whereRaw($constraint, $link)` et `where_str($constraint, $link)` — deux méthodes au corps identique — qui prennent une chaîne SQL brute, jamais paramétrée. Dernier recours, jamais avec une valeur venant d'un utilisateur.

### Listes, nulls, dates, intervalles

```php
->whereIn('this.id', [1, 2, 3])
->whereIn('this.status', ['a', 'b'], 'NOT')
->where('col')->in([1, 2, 3])        ->where('col')->notIn($values)
->whereNull('col')                   ->whereNotNull('col')
->whereBetween('col', [$min, $max])  ->where('col')->between($v1, $v2)
->whereDate('col', $v)  ->whereDay(...)  ->whereMonth(...)  ->whereYear(...)  ->whereHour(...)
```

:::warning Piège
**Non corrigé : `whereIn()` et `whereBetween()` ne sont pas paramétrés.** `whereIn()` entoure ses chaînes de quotes par `qb_sanitize()` sans les échapper, et `whereBetween()` interpole ses bornes directement. Toute valeur venant d'une requête HTTP doit être castée (`(int)`) ou validée (liste blanche) avant d'y entrer — ou passer par `whereGroup()` et des `where('col', '=', $v)` répétés.
:::

## 4.3 Tri, regroupement, limite

```php
->orderBy('this.name')                 // ASC implicite
->orderBy('this.name', 'DESC')
->orderBy('col1')->orderBy('col2')     // cumulables
->rand()                               // ORDER BY RAND()
->groupBy('this.project_id')
->limit(10)                            // LIMIT 10
->limit(0, 10)                         // LIMIT 0, 10
->limit(-5)                            // les 5 DERNIERS
->distinct('col')
```

`orderBy('project.position')` — une colonne d'une relation jointe — fonctionne si la relation est jointe par défaut.

## 4.4 Les relations

Chaque `#[ORM\ManyToOne]` de l'entité de base est automatiquement jointe en `LEFT JOIN`, avec pour alias le nom de la table cible :

```sql
LEFT JOIN `project` ON `project`.id = task.project_id
```

:::warning Piège
**Ce `LEFT JOIN` ne charge que l'`id`.** Accéder à `$task->project->name` sans rien de plus renvoie une chaîne vide — pas une erreur, rien de visible.
:::

```php
// SANS with() : $row->project existe, ses colonnes hors id sont vides
$rows = Task::where('this.done', '=', 0)->get();

// AVEC with() : les colonnes demandées sont réellement hydratées
$rows = Task::where('this.done', '=', 0)
    ->with('project', ['name', 'description'])
    ->get();
echo $rows[0]->project->name;
```

`with(string $relation, array $columns = [])` prend le nom de la **propriété** de relation (`public $project;` → `'project'`).

| Méthode | Effet |
|---|---|
| `with($relation, $columns)` | hydrate réellement les colonnes de la relation |
| `without(...$relations)` | exclut des relations du `LEFT JOIN` par défaut |
| `initQb(false)` | désactive tous les `LEFT JOIN` par défaut |
| `join($classname, $on)` | jointure explicite |
| `leftjoin(...)`, `leftJoinOn($classname, $alias, $constraint)` | jointures à gauche |
| `innerjoin(...)`, `innerJoinOn(...)` | jointures internes |
| `join_str($classname, $on)` | jointure en SQL brut |

`with()` est aussi pilotable depuis la requête HTTP : l'en-tête `Columns` portant `{"project":["name","description"]}` est converti en appels `with()`.

## 4.5 Les méthodes terminales, et le piège `null` contre entité vide

C'est le piège le plus coûteux de cette couche.

| Méthode | Zéro résultat |
|---|---|
| `->get($column = "*")` | `[]` |
| `->getInstance()` / `->first()` | entité **vide** (`new X`), jamais `null` |
| `->firstOrNull()` / `->lastOrNull()` | `null` |
| `Entity::find($id)` | entité vide |
| `Entity::findOrNull($id)` | `null` |
| `->getRows($column, $callback)` | tableau de lignes brutes, sans hydratation |
| `->row()` | une ligne brute |
| `->getValue()` / `->getValues()` | valeur(s) scalaire(s) |
| `->count()`, `->sum()`, `->avg()`, `->max()`, `->min()` | scalaire |
| `->cursor($callback)` | parcours ligne à ligne |

Après un `find()`, `getInstance()` ou `first()` sans le suffixe `OrNull`, ne jamais tester `if ($entity)` seul : un objet PHP est toujours vrai.

```php
// FAUX — ne détecte jamais une entité vide
$existing = Project::where('this.code', '=', $code)->getInstance();
if ($existing) { /* toujours vrai */ }

// CORRECT
$existing = Project::where('this.code', '=', $code)->firstOrNull();
if ($existing && $existing->getId()) { /* vraiment trouvé */ }
```

## 4.6 Prévisualiser une requête sans l'exécuter

```php
QueryBuilder::$debug = true;
$result = Project::where('this.status', '=', 'active')->get();
// $result vaut ['query' => ..., 'sql' => ..., 'parameters' => [...]]
QueryBuilder::$debug = false;   // à ne pas oublier : la propriété est statique
```

`->getSqlQuery()` donne le SQL d'une requête sans passer par le mode global. Utile pour prouver qu'une valeur est bien liée et non interpolée : une valeur contenant une apostrophe reste dans `parameters`, jamais dans le texte SQL.

## 4.7 Exemples prêts à reprendre

**Lecture par id avec « non trouvé » explicite**

```php
$project = Project::findOrNull($id);
if (!$project) {
    return ['success' => false, 'detail' => t('Projet introuvable')];
}
```

**Jointure avec chargement réel des colonnes**

```php
$tasks = Task::where('this.project_id', '=', $id)
    ->where('this.done', '=', 0)
    ->with('project', ['name', 'code'])
    ->orderBy('this.created_at', 'DESC')
    ->get();
```

**Recherche multi-champs en OU, réponse au contrat figé**

```php
$qb = Project::where('this.status', '=', 'active');
if (($q = trim($q)) !== '') {
    $qb->whereGroup(fn ($sub) => $sub->whereLike('name', $q)->orWhereLike('code', $q));
}
$projects = $qb->orderBy('this.name')->get();

// Mise en forme explicite, jamais un jsonSerialize() qui exposerait toutes les colonnes
return ['success' => true, 'projects' => array_map(fn ($p) => [
    'id' => $p->getId(), 'code' => $p->code, 'name' => $p->name,
], $projects)];
```

**Vérifier l'existence avant d'écrire**

```php
$existing = Project::where('this.code', '=', $code)->firstOrNull();
if ($existing && $existing->getId()) {
    return ['success' => false, 'detail' => t('Code déjà utilisé')];
}
```

## 4.8 Qualifier les colonnes

Quand une entité est traduite, la jointure de traduction ramène la table `dvups_lang` sous l'alias `l`, qui a sa propre colonne `name`. Sur une entité traduite qui a aussi une colonne `name`, un `WHERE name = ...` non qualifié devient :

```
SQLSTATE[23000] ... Column 'name' in where clause is ambiguous
```

:::note À retenir
**Règle de travail.** Toujours qualifier : `this.<colonne>`. Sur une entité non traduite aussi, par habitude : cela ne coûte rien et prévient le jour où l'entité devient traduite.
:::
