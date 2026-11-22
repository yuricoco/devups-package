---
title: "Chapitre 4 — Le moteur de listes"
sidebar_position: 3
---

## 4.1 Le contrat déclaratif

Une liste se déclare dans une classe `<Entité>Table` qui hérite de `Datatable`, et un moteur unique (`Datatable.php`) rend toutes les listes.

```php
class TaskTable extends Datatable
{
    public $entity = "task";

    public static function init(\Task $task = null)
    {
        $dt = new TaskTable($task);
        $dt->entity = $task;
        return $dt;
    }

    public function buildindextable()
    {
        $this->order_by = "this.id desc";
        $this->datatablemodel = [
            ['header' => '#', 'value' => 'id'],
            ['header' => t('Nom'), 'value' => 'name'],
            ['header' => t('Projet'), 'value' => 'Project.name'],
            ['header' => t('Fait'), 'value' => function (\Task $t) {
                return $t->done ? '<span class="badge badge-success">' . t('oui') . '</span>' : '';
            }],
        ];
        return $this;
    }

    public function builddetailtable()
    {
        $this->datatablemodel = [
            ['label' => t('Nom'), 'value' => 'name'],
            ['label' => t('Description'), 'value' => 'description'],
        ];
        return $this;
    }
}
```

:::warning Piège
**Toujours passer l'entité au constructeur.** `new TaskTable($task)`, pas `new TaskTable()` suivi de `$dt->entity = $task`. Le constructeur calcule la classe de l'entité, dont `can()` a besoin pour filtrer les boutons d'action ; sans elle, l'écran lève une `TypeError`.
:::

## 4.2 Les quatre formes d'une colonne

`Datatable` parcourt `$this->datatablemodel` ; quatre formes sont reconnues, dans cet ordre.

**Forme 1 — `'get'` appelable**, appelé avec l'entité et `'param'` en second argument s'il est présent.

**Forme 2 — pas de `'value'` : la clé est la colonne.**

```php
'name'       => ['header' => t('Nom')],      // → $entity->name
'project.id' => ['header' => t('Projet')],   // → $entity->project->id
```

C'est la forme que le générateur produit.

:::warning Piège
**La clé doit nommer une propriété réelle.** Sinon l'accès échoue dans `ModelTrait::__get()`, qui depuis 7.1 lève `UndefinedPropertyException` sur un attribut non déclaré (voir Tome II, chapitre 8, point 14) : l'écran entier ne s'affiche toujours pas, mais l'erreur est désormais explicite et journaliée plutôt qu'un `null` silencieux.
:::

**Forme 3 — `'value'` appelable**, qui reçoit l'entité hydratée. La forme à utiliser pour tout calcul, formatage HTML ou lecture indirecte.

**Forme 4 — `'value'` chaîne.** `'name'` → `$entity->name` ; une chaîne pointée (`'Project.name'`) est résolue **par accesseurs** (`getProject()` puis `getName()`). D'où la nécessité que les accesseurs existent — c'est la cause la plus fréquente des listes cassées : `class X does not have a method "getY"`.

:::tip Nouveau
**Corrigé en 7.1.** Une colonne sans valeur jointe (`'.'` absente de `value`) dont le getter résolu (`get` + `ucfirst($value)`) n'existe pas sur l'entité levait une `\TypeError` fatale qui plantait tout l'écran. `Datatable::getTableEntityBody()` vérifie désormais `method_exists()` avant l'appel : une colonne sans getter rend une cellule vide et journalise un avertissement (`Log::warning()`), le reste de l'écran continue de s'afficher.
:::

Deux préfixes spéciaux : `src:<champ>` appelle `show<Champ>()` (fichier ou image), `<Relation>::<collection>` parcourt une collection.

:::tip Nouveau
**Corrigé en 7.0.** Une colonne traduite affichée sans `value` explicite (forme 2) affichait « Array » et levait un avertissement. Elle est désormais résolue dans la langue courante, comme ailleurs dans le moteur.
:::

## 4.3 Les autres clés d'une entrée

| Clé | Rôle |
|---|---|
| `header` | l'en-tête, pour une liste |
| `label` | l'en-tête, pour une fiche de détail |
| `value`, `get`, `param` | la colonne (§4.2) |
| `search` | active ou désactive le filtre de cette colonne |
| `order` | contrôle le tri sur cette colonne |
| `sql` | une expression SQL ajoutée à la projection, aliasée sur la clé |
| `directive` | des attributs HTML supplémentaires |

```php
'total' => [
    'header' => t('Total'),
    'sql' => '(SELECT COUNT(*) FROM task t2 WHERE t2.project_id = this.id)',
],
```

## 4.4 Les propriétés de comportement

| Propriété | Défaut | Rôle |
|---|---|---|
| `$order_by` | `""` | le tri par défaut, ex. `"this.id desc"` |
| `$per_page` | `10` | la taille de page |
| `$enablepagination` | `true` | |
| `$searchaction` | `true` | la ligne de filtres |
| `$groupaction` | `true` | sélection multiple |
| `$isRadio` | `false` | sélection unique |
| `$enabletopaction` / `$defaulttopaction` | `true` | la barre d'actions du haut, le bouton « ajouter » |
| `$enablecolumnaction` / `$actionDropdown` | `true` | la colonne d'actions, en menu déroulant |
| `$mainrowaction` | `"edit"` | l'action principale d'une ligne |
| `$customactions`, `$topactions`, `$groupactions` | `[]` | actions supplémentaires |
| `$template`, `$viewdata` | | un gabarit de rendu personnalisé |
| `$trattribut_callback` | `null` | pose des attributs HTML sur une ligne |
| `$qbcustom` | `null` | un `QueryBuilder` personnalisé |

Les boutons d'action par défaut (voir, modifier, supprimer) ne sont affichés que si l'administrateur a l'action correspondante (`can()`).

## 4.5 Les méthodes utiles

| Méthode | Rôle |
|---|---|
| `buildindextable()` / `builddetailtable()` | à implémenter |
| `build<X>table()` + `router()` | un modèle alternatif, sélectionné par `?tablemodel=X` |
| `render()` | la page complète |
| `getTableRest()` | la liste en JSON |
| `getSingleRowRest($entity)` | une seule ligne, après création ou mise à jour |
| `renderentitydata($entity)` | une fiche de détail |
| `Qb($callback)` | injecte un `QueryBuilder` personnalisé |
| `addFilterParam($param, $value)` | un filtre figé |
| `addcustomaction()`, `addgroupaction()`, `setDefaultAction()`, `crud_url()` | actions |
| `disablepagination()`, `setperpage($n)`, `enablefilter($b)`, `disableColumnAction()` | comportement |
| `exportCsv()` | export, déclenché par `?dexport=1` |

## 4.6 Le vocabulaire de la requête

| Paramètre | Rôle |
|---|---|
| `dfilters=on` | active le filtrage dynamique |
| `order=<colonne>`, `orderjoin=<relation>`, `dsort`, `dsortjoin` | tri |
| `next=<page>`, `per_page=<n>` | pagination |
| `tablemodel=<X>` | sélectionne `build<X>table()` |
| `dexport=1` | export CSV |
| `dlang=<iso>` | la langue |
| `drand`, `dcount`, `dsum` | ordre aléatoire, agrégats |
| en-tête `Columns` | `with()` sans toucher au code |

## 4.7 Les opérateurs de filtre

Syntaxe : `?dfilters=on&<colonne>:<op>=<valeur>`.

| Opérateur | SQL |
|---|---|
| `eq` / `neq` / `oreq` | `=` / `!=` / `=` en OR |
| `gt` / `lt` | `>` / `<` |
| `get` / `let` | `>=` / `<=` |
| `lk` / `lkr` / `lkl` | `LIKE '%v%'` / `'v%'` / `'%v'` |
| `in` / `notin` | `IN (...)` / `NOT IN (...)`, valeurs séparées par des virgules |
| `rein` | `<valeur> IN (<colonne>)` |
| `empty` | `= ''` si 1, `!= ''` sinon |
| `isNull` / `notNull` | `IS NULL` / `IS NOT NULL` |
| `btw` | `BETWEEN a AND b`, bornes jointes par `_` : `created_at:btw=2026-07-01_2026-07-31` |

:::warning Piège
**`gte` et `lte` n'existent pas.** Un opérateur non reconnu tombe dans le cas par défaut, qui est un `LIKE` : `created_at:gte=X` génère `created_at LIKE '%X%'`, sans avertissement. Toujours `get`, `let` ou `btw`.
:::

Une colonne pointée est résolue comme une relation : `project.id:eq=1`. Le filtrage dynamique ne combine qu'en ET ; pour un OU, `whereGroup()` côté PHP.

## 4.8 La réponse de `lazyloading`

| Clé | Contenu |
|---|---|
| `success`, `listEntity` | les entités de la page |
| `nb_element` | le total réel du filtre |
| `per_page`, `current_page`, `next`, `previous` | pagination |
| `remain` | 1 tant qu'il reste des pages |
| `aggregations` | si `dcount` / `dsum` ont été demandés |
| `append_values` | ce que `append($key, $value)` a ajouté |
