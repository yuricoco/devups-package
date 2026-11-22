---
title: "Chapitre 5 — `ModuleTree` : les données de référence"
sidebar_position: 4
---

## 5.1 Le problème qu'il résout

Toute application a des données meubles : des valeurs qui remplissent des listes déroulantes et changent rarement. Des catégories, des types, des critères, des liens de réseaux sociaux, un menu. Chacune, prise seule, appelle une table : un `id`, un libellé, parfois un parent, une position. Toujours les mêmes colonnes.

`ModuleTree` les remplace par une structure générique en arbre, et compile chaque arbre en fichiers PHP pour un accès de type constante clé-valeur. Le gain n'est pas d'économiser des tables : c'est d'avoir un seul écran d'administration, un seul mécanisme de traduction et un seul cache pour toutes les énumérations du projet.

## 5.2 Le modèle

**`tree` — un arbre**

| Colonne | Note |
|---|---|
| `name` | la clé machine — unique, et **immuable** une fois utilisée dans le code |
| `branches` | les noms de paliers, séparés par des virgules — pilote la profondeur (§5.3) |
| `description` | |

**`tree_item` — un élément**

| Colonne | Note |
|---|---|
| `tree_id` | l'arbre |
| `slug` | la clé machine — unique par arbre |
| `position` | l'ordre d'affichage |
| `parent_id` | auto-adjacence |
| `main` | 1 = élément de premier niveau |
| `status` | |
| `chain` | libre — chaque usage définit son format (§5.7) |

Plus `tree_lang` (`label`) et `tree_item_lang` (`name`) pour les libellés, et `Tree_item_image` (qui étend `ImageCore`) pour les images d'un élément.

Le code se réfère toujours à la clé machine (`tree.name`, `tree_item.slug`), jamais au libellé. C'est ce qui rend le cache clé-valeur possible.

## 5.3 `branches` — le schéma de profondeur

`tree.branches` est la liste, du plus haut au plus bas, des noms de paliers de l'arbre. Une hiérarchie anonyme — « un élément, ses enfants, leurs enfants » — ne dit rien de ce qu'est chaque niveau ; nommer les niveaux leur donne une identité. Un arbre `catalogue` peut déclarer `famille,sous-famille,gamme`.

- Le gestionnaire d'arbre refuse de créer un sous-élément au-delà de la profondeur déclarée.
- Le cache descend sur `count(branches)` niveaux.
- Chaque entrée du cache porte son `level` (1 = racine) et le nom de son palier (`branch`).
- Un arbre sans `branches` garde un unique niveau.

`Tree::setBranches()` retire les espaces de bordure et refuse explicitement tout segment vide (virgule en double ou en bordure), en retournant un message d'erreur comme tout setter de validation.

## 5.4 Les quatre modes de liaison

Une entité qui consomme une donnée de référence peut s'y rattacher de quatre façons.

| Mode | Forme | Usage |
|---|---|---|
| **Relation** | `#[ORM\ManyToOne(targetEntity: "\Tree_item")]`, `onDelete: "set null"` | une valeur. Filtrable, intègre, jointe automatiquement |
| **Table pivot** | `<entité>_<référence>`, deux clés étrangères | plusieurs valeurs. Filtrable, intègre |
| **Instantané par slug** | la valeur figée dans la ligne à sa création, souvent en JSON | enregistrements historiques qui doivent garder la valeur d'origine même si elle est renommée ensuite ; impose l'immuabilité du slug |
| **Chaîne dénormalisée** | `"3,7,12"` dans une colonne texte | **proscrit** : aucune intégrité, aucun filtrage SQL possible |

:::note À retenir
**La règle.** Tout nouveau consommateur utilise une relation (une valeur) ou une table pivot (plusieurs valeurs). L'instantané est réservé aux enregistrements historiques. La chaîne dénormalisée ne se révèle comme dette que le jour où il faut filtrer — et ce jour-là, `?categories.id:eq=3` échoue.
:::

## 5.5 Le contrat de lecture

```php
Tree::constant('category');                     // la liste ordonnée, pour une liste déroulante
Tree::constant('category', 'electronique');      // un élément précis, par son slug
Tree::constant('category', null, $idLang);       // dans une langue donnée
```

`Tree::constant(string $name, ?string $slug = null, ?int $id_lang = null)` est le point d'entrée unique de lecture. `Tree::readCache($name)` lui délègue, conservé pour compatibilité.

## 5.6 Les contraintes du modèle

| # | Contrainte | Garantie par |
|---|---|---|
| U1 | `tree.name` unique, non nul, de la forme `[a-z0-9_]+` | à poser en base par migration (le schéma livré ne la déclare pas) |
| U2 | `tree.name` immuable | `Tree::__update()`, qui compare à la valeur en base et lève une exception |
| U3 | `tree_item.slug` unique par arbre | à poser en base : index unique `(tree_id, slug)` |
| U4 | un `slug` référencé par un instantané ne doit plus changer | discipline du projet |
| U5 | tout élément destiné au cache a un `slug` non nul | discipline du projet ; sinon l'élément est exclu du cache (C7) |

U2 fait fonctionner tout le reste : le cache est indexé par `tree.name`. Si le nom pouvait changer, le fichier de cache deviendrait orphelin sans que rien ne le signale.

## 5.7 `chain` est libre

`chain` stockait autrefois la liste des parents d'un élément ; `branches` l'a remplacée. La colonne est désormais polyvalente : chaque usage définit son format.

:::note À retenir
**La leçon est transférable.** Une colonne volontairement polyvalente ne doit pas avoir d'accesseur typé : l'accesseur crée une promesse de format que la colonne ne tient pas.
:::
