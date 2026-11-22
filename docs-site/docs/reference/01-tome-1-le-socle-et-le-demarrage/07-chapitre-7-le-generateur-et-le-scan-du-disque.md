---
title: "Chapitre 7 — Le générateur et le scan du disque"
sidebar_position: 7
---

## 7.1 Les trois niveaux de datacore

| Niveau | Fichier | Emplacement |
|---|---|---|
| Composant | `<composant>Core.json` | racine du composant |
| Module | `<module en minuscules>Core.json` | racine du module |
| Entité | `<Entité>Core.json` | dans `Core/` du module |

Le Tome II décrit le contenu de ces fichiers. Ce chapitre décrit comment ils sont lus.

## 7.2 Le scan

`Core::buildOriginCore()` parcourt `src/` et, pour chaque dossier, appelle `Core::findprojectcore()` :

- `findprojectcore($dir, $file)` : si `$dir/<file en minuscules>Core.json` n'existe pas, retourne `[]` — le composant n'existe pas pour le framework. Sinon, lit ce fichier, scanne les sous-dossiers par `findmodulecore()` et **écrase** `listmodule` par le résultat.
- `findmodulecore($dir, $file)` : même principe pour un module ; appelle `findentitycore($dir . "/Core")` et pose le résultat dans `listentity`.
- `findentitycore($dir)` : décode en JSON chaque fichier de `Core/`.

## 7.3 Les trois conséquences à retenir

1. **`listmodule` dans un `<composant>Core.json` est ignoré.** Il est écrasé par le scan des sous-dossiers.
2. **Un module sans son `*Core.json` est invisible, sans erreur.** Ni `dvups_:update`, ni `schema:update`, ni la documentation ne le voient.
3. **Les `<Entité>Core.json` ne pilotent pas Doctrine.** Une fois le module trouvé, tout fichier portant l'attribut `#[ORM\Entity]` dans son dossier `Entity/` est pris en compte par Doctrine, qu'il ait un datacore ou non. Les datacores servent à la génération, à `rights:sync` et à la documentation.

## 7.4 Créer un module à la main

1. `src/<composant>/<Module>/<module>Core.json` → le module existe.
2. Les entités dans `Entity/`, portant l'attribut `#[ORM\Entity]`.
3. Une migration qui crée les tables (Tome II, chapitre 6), puis `php devups migrate`.
4. `require '<composant>/<Module>/<composant>.module<nom>.php';` dans `src/requires.php` → les classes sont chargées.
5. `php devups dvups_:update` → l'entité apparaît dans le back-office.
6. `php devups rights:sync` → ses actions sont attribuables aux rôles.
7. Pour l'API REST dédiée : enregistrer l'entité dans `$public_access` de `route/WebService.php` (Tome III).

## 7.5 Ce que `dvups_:update` écrit

`Core::updateDvupsTable()` construit les deux tableaux de configuration (§2.5), les écrit par `Util::arrayToPhpFile()`, puis met à jour le rôle maître (composants, modules, entités, menu).

`Util::arrayToPhpFile()` écrit de façon atomique et assainie : le nom de fichier doit correspondre à `^[A-Za-z0-9_\-]+(\.[A-Za-z0-9_\-]+)*$`, ce qui interdit `../`, et l'écriture passe par un fichier temporaire suivi d'un `rename()`. Aucun lecteur ne voit jamais un fichier à moitié écrit. C'est la même fonction qui écrit les caches d'arbres et de statuts (Tome IV).

## 7.6 Les trois choses que le datacore ne sait pas exprimer

| Besoin | État | Solution |
|---|---|---|
| contrainte d'unicité | aucune notion dans le format | `#[ORM\UniqueConstraint(name: ..., columns: [...])]` à la main |
| relation obligatoire | `"nullable":"not"` ignoré sur une relation (§6.7) | `nullable: false` à la main dans `#[ORM\JoinColumn]` |
| identité composite | aucune notion | `#[ORM\Id] #[ORM\ManyToOne(...)]` à la main, pas de colonne `id` |

Dans les trois cas, l'entité passe en édition manuelle permanente.

:::note À retenir
**Sur la contrainte d'unicité.** Nommer la contrainte à l'identique de l'index physique déjà en base. Doctrine le reconnaît alors comme sien et ne propose plus de `DROP INDEX` dans `--dump-sql`. À l'inverse, `#[ORM\Column(unique: true)]` sans nom fait générer par Doctrine son propre nom (`UNIQ_...`), différent de l'existant.
:::

:::tip Nouveau
**Disparu en 8.0 — le piège du docblock intercalé.** Jusqu'à la 7.1, Doctrine lisait le mapping dans des annotations de docblock, et son lecteur n'acceptait que le docblock immédiatement adjacent à `class Xxx` : un docblock de prose intercalé entre l'annotation et la classe faisait échouer toute lecture de métadonnées (« is not a valid entity or mapped super class »). Depuis le passage aux attributs PHP 8 (`#[ORM\Entity]`), ce piège n'existe plus : un attribut est une construction syntaxique indépendante du docblock (lu par réflexion, `getAttributes()`), peu importe qu'un commentaire s'intercale entre lui et la classe.
:::
