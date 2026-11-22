---
title: "Chapitre 1 — Le datacore"
sidebar_position: 0
---

## 1.1 Le principe

Dans Devups, la structure des données est décrite par des fichiers JSON appelés **datacores**. Ils ne sont pas lus à l'exécution pour construire les requêtes : ils servent au générateur, au scan qui établit la liste des composants, modules et entités, au catalogue des droits (`rights:sync`) et à la documentation générée (`api:doc`, `doc:guide`).

La vérité d'exécution, elle, est dans les attributs PHP 8 des classes d'entité, lus par Doctrine (docblock jusqu'à la 7.1, attributs depuis le LOT 1 du chantier v8.0). Le datacore est l'intention ; l'attribut est le contrat.

:::note À retenir
**Corollaire.** Quand les deux divergent — et ils divergent, voir §1.6 —, c'est l'attribut qui gagne, parce que c'est lui que Doctrine lit pour construire la table. Le datacore ne redevient la vérité qu'au moment où l'on régénère l'entité, et il écrase alors tout ce qui a été ajouté à la main.
:::

## 1.2 Le datacore d'un composant

`src/<composant>/<composant>Core.json` :

```json
{
  "name": "monprojet",
  "template": "adminv2",
  "listmodule": []
}
```

| Clé | Rôle |
|---|---|
| `name` | le nom du composant ; doit correspondre au nom du dossier |
| `template` | le gabarit de back-office |
| `listmodule` | ignoré — écrasé par le scan des sous-dossiers |

La présence de ce fichier est la condition d'existence du composant : sans lui, le composant entier disparaît du framework.

## 1.3 Le datacore d'un module

`src/<composant>/<Module>/<module en minuscules>Core.json` :

```json
{
  "name": "ModuleTask",
  "label": "Tâches",
  "icon": "fas fa-fw fa-check-square"
}
```

| Clé | Effet |
|---|---|
| `name` | la clé de `module_configurations.php` |
| `label` | le libellé dans la barre latérale ; par défaut, le nom du module |
| `icon` | la classe d'icône ; par défaut `fas fa-fw fa-cog` |

`{"name":"ModuleXxx"}` suffit à faire exister le module. Les autres clés (`id`, `project`, `nbent`, `listentity`) que l'on trouve dans les modules du socle sont inertes ou écrasées par le scan.

## 1.4 Le datacore d'une entité

`src/<composant>/<Module>/Core/<Entité>Core.json` :

```json
{
  "name": "task",
  "label": "Tâche",
  "lang": ["name", "description"],
  "attribut": {
    "1": { "name": "name", "datatype": "string", "size": "255",
           "nullable": "not", "formtype": "text" }
  },
  "relation": [
    { "entity": "project", "cardinality": "manyToOne",
      "nullable": "default", "ondelete": "cascade" }
  ],
  "actions": [ { "code": "close", "label": "Clôturer" }, "archive" ],
  "methods": []
}
```

Les clés lues sont `name`, `label`, `attribut`, `relation`, `lang`, `actions` et `methods`. Toute autre clé est inerte.

### Le bloc `attribut`

C'est un objet indexé par des chaînes numériques, pas un tableau.

| Clé | Valeurs | Note |
|---|---|---|
| `name` | minuscules, chiffres et `_` | devient le nom de colonne |
| `datatype` | `string`, `text`, `integer`, `float`, `date`, `datetime`, `json`, `boolean` | devient `#[ORM\Column(type: ...)]` |
| `size` | entier en chaîne | pertinent pour `string` |
| `nullable` | `"not"` (NOT NULL) ou `"default"` (nullable) | ignoré pour une relation (§1.6) |
| `formtype` | voir le Tome III | pilote le champ généré |

`id` est implicite et ne doit jamais figurer dans `attribut` : le générateur l'ajoute avec `#[ORM\Id] #[ORM\GeneratedValue]`.

### Le bloc `relation`

| Clé | Valeurs |
|---|---|
| `entity` | le nom en minuscules de l'entité cible |
| `cardinality` | `manyToOne`, `oneToMany`, `manyToMany`, `oneToOne` |
| `nullable` | ignoré par le générateur (§1.6) |
| `ondelete` | `cascade`, `set null`, `restrict` — le seul argument de `#[ORM\JoinColumn]` réellement interpolé |

### Le bloc `lang`

La liste des colonnes traduites. Sa présence fait de l'entité une entité traduite : le générateur produit en plus une classe `<Entité>_lang.php` et une table `<entité>_lang` (chapitre 7).

### Le bloc `actions`

:::tip Nouveau
**Nouveau en 7.0.** Toute entité a six actions implicites : `create`, `read`, `update`, `delete`, `export`, `import`. La clé `actions` déclare les actions métier qui s'y ajoutent. Chaque élément est soit une chaîne (`"archive"`, libellé déduit), soit un objet `{"code": ..., "label": ...}`. `php devups rights:sync` reporte ce catalogue dans la table `dvups_action`, où les rôles peuvent recevoir chaque action (Tome IV, chapitre 2).
:::

## 1.5 Les champs automatiques

Toute entité mappée reçoit `created_at`, `updated_at` et `deleted_at`. Ils ne se déclarent pas dans `attribut`.

`deleted_at` est le support de la suppression douce : `$dvsoftdelete = true` fait que `__delete()` écrit une date au lieu de supprimer la ligne, et `QueryBuilder::handlesoftdelete()` filtre les lignes supprimées de toute lecture.

## 1.6 Les trois choses que le datacore ne sait pas dire

| Besoin | Datacore | Réalité |
|---|---|---|
| contrainte d'unicité | aucune notion | `#[ORM\UniqueConstraint(name: ..., columns: [...])]` à la main |
| relation obligatoire | `"nullable":"not"` déclaré… | …jamais interpolé sur une relation ; la clé étrangère est NULLable dans tous les cas. `nullable: false` à ajouter à la main dans `#[ORM\JoinColumn]` |
| identité composite | aucune notion | `#[ORM\Id] #[ORM\ManyToOne(...)]` à la main, pas de colonne `id`, `getId()` surchargée |

Dans les trois cas, l'entité ne doit plus être régénérée : `core:g:crud` écrase l'attribut (Tome I, §6.7).
