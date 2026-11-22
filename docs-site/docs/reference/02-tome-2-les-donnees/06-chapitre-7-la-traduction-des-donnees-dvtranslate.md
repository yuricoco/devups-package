---
title: "Chapitre 7 — La traduction des données (`dvtranslate`)"
sidebar_position: 6
---

Ce chapitre traite des **données** traduites, stockées en base (le libellé d'une catégorie en français et en anglais). La traduction de l'**interface** (`t()`, fichiers `lang/*.php`) est au Tome III, chapitre 7.

## 7.1 Le modèle

Une entité traduite déclare :

```php
public $dvtranslate = true;
public $dvtranslated_columns = ["label"];
```

Son schéma se dédouble :

| Table | Contenu |
|---|---|
| `<entité>` | les colonnes non traduites, dont la clé machine |
| `<entité>_lang` | une ligne par langue : les colonnes traduites et `lang_id` |
| `dvups_lang` | les langues (`id`, `iso_code`, `active`, `name`…) |

Entités traduites du socle en 7.0 : `Tree`, `Tree_item`, `Status`, `Cmscontent`, `Notificationtype`, `Reportingmodel`, `Country`, `Dvups_entity`.

## 7.2 La bonne séparation

Le code se réfère à une **clé machine** non traduite, jamais au libellé : `tree.name` (et non `tree_lang.label`), `tree_item.slug` (et non `tree_item_lang.name`), `status._key`. Un libellé change quand un traducteur le change ; une clé machine ne doit jamais changer.

## 7.3 Forcer une langue

| Appel | Effet |
|---|---|
| `->setLang($id_lang)` | fixe la langue du builder |
| `Entity::find($id, $id_lang)` | |
| `->firstOrNull($id_lang)` / `->first($id_lang)` | |
| en-tête HTTP `Dlang` / paramètre `?dlang=fr` | force la langue d'une réponse d'API |

Sans langue forcée, les colonnes traduites reviennent concaténées sur toutes les langues par un `GROUP_CONCAT`, et le consommateur les sépare — c'est ce qui produit l'objet `{fr: ..., en: ...}` qu'une application cliente reçoit.

## 7.4 Le défaut D1 — les lignes invisibles (corrigé en 7.1)

C'était le défaut le plus sérieux de cette couche, et il touchait toute entité traduite.

`QueryBuilder::initSelect()` — appelé par toute lecture ORM — construisait, quand aucune langue n'était forcée :

```sql
left join <table>_lang on (<table>_lang.<table>_id = `<table>`.id)
join dvups_lang l on (<table>_lang.lang_id = l.id)     -- INNER JOIN, pas LEFT
```

Si la ligne de base n'avait **aucune** ligne `<table>_lang`, le `LEFT JOIN` produisait des colonnes `NULL`, et le `JOIN` suivant sur `NULL = l.id` éliminait la ligne entière — y compris pour un `WHERE this.id = X`. La ligne existait en base, n'était pas perdue, et devenait invisible à l'ORM, silencieusement.

Avec une langue forcée, la branche était différente : le filtre de langue est dans la clause `ON` du `LEFT JOIN`, sans `JOIN dvups_lang`. La ligne restait déjà visible dans ce cas ; la colonne traduite revenait `NULL`.

:::tip Nouveau
**Corrigé en 7.1.** Le `JOIN` vers `dvups_lang` est désormais un `LEFT JOIN` : une ligne sans aucune ligne `<table>_lang` reste visible dans les deux modes, ses colonnes traduites revenant simplement `NULL`/vides — jamais une ligne perdue ni une erreur. Forcer la langue n'est plus nécessaire pour cette seule raison ; cela reste utile pour obtenir une chaîne simple plutôt que le format bilingue (§7.3).
:::

**Côté écriture**, mettre à jour une entité traduite sans ligne `_lang` produirait une erreur SQL illisible. `Model::__update()` refuse donc explicitement, avec un motif clair, avant d'y arriver — inchangé par ce correctif.

### L'outil de diagnostic

`DvtranslateInspector` (`dclass/devups/model/DvtranslateInspector.php`, lecture seule) :

| Méthode | Rôle |
|---|---|
| `invisibleRows($entityClass)` | compte et liste les lignes invisibles d'une entité |
| `isInvisible($entityClass, $id)` | cet id existe-t-il en base tout en étant invisible à l'ORM ? |
| `diagnose($entityClass, $id)` | distingue `no_translation`, `orphan_lang`, `visible`, `not_found`, `not_translated`, `schema_missing` |

:::note À retenir
**La règle qui évite le défaut.** Toute création d'une entité traduite doit créer sa ligne de traduction dans chaque langue. `__insert()` le fait (une ligne `_lang` par langue de `dvups_lang`) ; une insertion SQL directe — script, migration, import — doit le faire elle-même.
:::

## 7.5 Lire une colonne non traduite d'une entité traduite

Si le besoin est de lire une clé machine ou un drapeau d'une entité traduite, et que la ligne peut ne pas avoir de traduction, passer par une requête préparée :

```php
$stmt = Model::pdo()->prepare("SELECT branches FROM tree WHERE name = ?");
$stmt->execute([$treeName]);
$branches = $stmt->fetchColumn();
```

C'est ce que fait `Tree::buildCacheData()` (Tome IV).

## 7.6 Un second défaut, dans la même famille (corrigé en 7.1)

`QueryBuilder::get($colonne, $id_lang)` appelait `initSelect()` **avant** de poser la langue. Passer `$id_lang` en second argument était donc sans effet : le libellé revenait concaténé sur toutes les langues. La forme de contournement, encore valide (`setLang()` est idempotente) :

```php
$rows = Status::where('this.entity', '=', 'task')
    ->setLang($id_lang)
    ->get();
```

:::tip Nouveau
**Corrigé en 7.1.** `get()` pose désormais la langue avant `initSelect()` : `get($colonne, $id_lang)` respecte l'`$id_lang` passé en second argument directement, sans détour par `setLang()`.
:::
