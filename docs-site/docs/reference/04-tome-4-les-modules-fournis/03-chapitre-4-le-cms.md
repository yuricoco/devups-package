---
title: "Chapitre 4 — Le CMS"
sidebar_position: 3
---

:::tip Nouveau
**Renommé en 7.0.** `Cmstext` devient `Cmscontent`, `Imagecms` devient `Cmsresource`, et `Cmscomment` est généré pour la première fois. La migration `2026_11_08_phase4_cms_rename.sql` renomme les tables en conservant les données, puis `…_phase4_drop_old_images.php` ne supprime les anciennes tables d'images que si la reprise est vérifiée complète.
:::

## 4.1 Le modèle

| Entité | Rôle | Colonnes principales |
|---|---|---|
| `Cmscontent` | un contenu éditable, traduit, rattaché à un élément d'arbre | `reference`, `slug`, `active`, `position` ; traduites : titre, contenu |
| `Cmsresource` | une image ou un fichier d'un contenu ; étend `ImageCore` et porte elle-même son fichier | colonnes d'`ImageCore`, relation vers `Cmscontent` |
| `Cmscomment` | un commentaire sur un contenu, lié à un `User` | `pseudo`, `email`, `comment`, `seen` |

`Cmscontent` est rattaché à un `Tree_item` : la page ou la rubrique à laquelle il appartient. C'est ce qui permet de construire un menu (un arbre) et d'y accrocher des contenus sans table supplémentaire.

## 4.2 L'édition

La page `/admin/devupscms/ModuleCms/cmscontent/edit?id=<id>` édite un contenu dans toutes ses langues et liste ses ressources (`CmsresourceTable`). Le téléversement passe par `Cmsresource::uploadImage()`, qui s'appuie sur `Dfile`.

## 4.3 Lire un contenu

```php
$about = Cmscontent::where('this.slug', '=', 'a-propos')
    ->setLang($idLang)
    ->firstOrNull();
```

Lire par `slug` (clé machine), jamais par titre (libellé traduit) ; forcer la langue reste utile pour obtenir une chaîne simple plutôt que le format bilingue, même si l'ancien défaut D1 (Tome II §7.4) ne rend plus la ligne invisible depuis 7.1.
