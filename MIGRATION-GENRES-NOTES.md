# Migration des genres : chaîne `gender_names` → pivot `comicbook_gender`

**Date** : 2026-11-03
**Périmètre** : `backend/src/edition3ag/ModuleComicbook/`, `backend/dclass/extends/ScanCore.php`,
`backend/database/migration/`, `creator/src/`
**État final** : livré — points 0 à 5 faits, vérifiés en direct, commités (aucun `git push`)
**Base** : locale, `agedngyh_app_bd` (localhost, MariaDB 10.4.32)

Sauvegarde complète de la base prise avant tout écrit, **hors des deux dépôts** :
`db-backups/agedngyh_app_bd_20261103_054841_pre_genres_migration.sql` (13,6 Mo).

---

## Point 0 — la corruption

### Reproduite avant correction

Ligne (vraie, pas celle du prompt — voir §8) `creator/src/pages/forms/Comicbook.vue:563` :
`comicbook.value.gender_names = (response.comicbook.genders)`. `axios.js:79` retourne
`response` sans le déballer (`response.data` porte la charge utile), donc
`response.comicbook` vaut `undefined` et cette ligne levait une `TypeError`, avalée par le
`.catch()` de la chaîne — confirmé en console, en direct, sur `forms/comicbook?id=12` (token
JWT local, `userId=348`, propriétaire de cette série) :

```
TypeError: Cannot read properties of undefined (reading 'genders')
    at .../Comicbook.vue:183:58
```

La ligne 556 (`comicbook.value = response.data.comicbook`) avait déjà posé `gender_names` à la
**chaîne brute** de la base (`"33,34,76"`). Sans correctif, à l'enregistrement suivant
(`save()`), `for (let item of comicbook.value.gender_names)` itère les **caractères** de cette
chaîne (8 caractères), chaque `item.id` vaut `undefined`, `.join()` produit `",,,,,,,,"`.

**Reproduit en direct** : chargement de la fiche `id=12` + clic "Enregistrer en brouillon"
**sans toucher aux genres** → `gender_names` passé de `33,34,76` à `,,,,,,,,` en base. Restauré
immédiatement après la preuve.

### Comptage des données déjà abîmées

```sql
SELECT COUNT(*) FROM comicbook
WHERE gender_names IS NOT NULL AND gender_names != ''
  AND gender_names REGEXP '^[,]+$';
```
→ **6 séries** (id 28, 30, 31, 34, 35, 36), toutes réduites à une chaîne de virgules pures.
`gender_names` ne porte pas de date de modification propre ; ce comptage est la seule mesure
possible de "depuis quand" — il ne dit pas la date, il dit le nombre déjà touché.

### Correctifs

1. **`creator/src/pages/forms/Comicbook.vue`** (commit `genres-0`, dépôt `creator`) :
   - ligne 556+ : `comicbook.value.gender_names = response.data.comicbook.genders` (bonne
     source, **confirmée** être déjà la forme `{id, name}` attendue par le `v-combobox` — voir
     `Tree_item::jsonSerialize()` / `Comicbook::jsonSerialize()`) ;
   - `save()` rendu défensif : `gender_names` n'est envoyé dans le payload **que** si
     `Array.isArray(comicbook.value.gender_names)` est vrai ; sinon la clé est **omise**
     (jamais une chaîne de virgules vide). Vérifié compatible avec le backend :
     `hydrateWithJson()` (`dclass/devups/Controller/Controller.php:529`) ne touche QUE les
     champs présents dans le JSON soumis — omettre la clé laisse `gender_names` intact côté
     serveur, pas écrasé à vide.

2. **`backend/database/migration/migration_20261103_comicbook_gender_names_repair.sql`**
   (commit `genres-0`, dépôt `backend`) : `UPDATE ... SET gender_names = NULL WHERE ...REGEXP
   '^[,]+$'`. Idempotent (vérifié : 6 lignes puis 0 au rejeu). Ne touche PAS le pivot (rien
   d'exploitable dans une chaîne de virgules pures).

### Preuve finale

Après correctif : chargement `id=12` (3 genres) + "Enregistrer" **sans toucher aux genres** →
`gender_names` reste `33,34,76`, vérifié en base. Reproduit une seconde fois avec le correctif
en place pour confirmer la non-régression.

---

## Point 1 — ce que le cadre sait faire

### (a) Le pivot est-il peuplé ?

Mesures AVANT tout écrit (après le correctif du point 0, donc sur les 6 lignes déjà NULL) :

| Mesure | Valeur |
|---|---|
| `comicbook_gender` (lignes) | 21 |
| `comicbook_gender` (comicbook_id distincts) | 8 |
| `comicbook.gender_names` non vide, non virgules pures | 37 |
| `comicbook.gender_names` réduit à des virgules pures | 0 (réparé par le point 0) |
| `comicbook` (total) | 112 |

**Conclusion** : ni un remplissage pur (le pivot a déjà 8 séries), ni une fusion complète (37
séries ont une chaîne exploitable, le pivot n'en couvre que 8) — c'est une **fusion partielle**.
Pour 2 de ces 8 séries (id 12 et 14), le pivot contenait même des identifiants **absents** de la
chaîne (35 et 38 respectivement) : le pivot est par endroits une *source distincte*, pas un
simple sous-ensemble de la chaîne. `INSERT IGNORE` (point 2) préserve ces entrées sans les
écraser, par construction.

### (b) Le cadre sait-il filtrer à travers un pivot ?

**Non — établi en lisant le code, pas supposé.**

`Lazyloading::filter()` (`dclass/devups/Datatable/Lazyloading.php:163-200`) résout un filtre
`<préfixe>.<champ>:<op>` en SQL brut `<préfixe>.<champ> <op> <valeur>` (via `filterswicher()`),
en supposant qu'un alias SQL nommé `<préfixe>` existe déjà dans la requête. Cet alias n'est créé
automatiquement que par `QueryBuilder::initdefaultjoin()`
(`dclass/devups/Tchutte/QueryBuilder.php:576-593`), qui lit `$this->entity_link_list` — une
liste peuplée EXCLUSIVEMENT depuis `$metadata->associationMappings` (Doctrine), c'est-à-dire les
annotations `@ManyToOne` déclarées sur l'entité. Le join généré est TOUJOURS de la forme
`LEFT JOIN <table> <alias> ON <alias>.id = this.<alias>_id` : **une seule colonne de clé
étrangère scalaire sur la table courante**, jamais une table de jointure plusieurs-à-plusieurs.

Recherche exhaustive (`grep -rn "ManyToMany"`) : **aucune annotation `@ManyToMany` n'existe nulle
part dans ce dépôt.** Le pivot `comicbook_gender` est modélisé comme une entité à clé composite
(`@Id @ManyToOne` des deux côtés), jamais comme une relation déclarée sur `Comicbook`.

**Conséquence** : déclarer une relation (quelle que soit l'annotation choisie) n'aurait pas fait
marcher `genders.id:eq` — le mécanisme de jointure automatique de ce framework ne sait tout
simplement pas générer une jointure plusieurs-à-plusieurs. Le commentaire
`appv2/src/features/discovery/hooks/useBooksList.ts:17` ("`genders.id:eq` échoue") reste exact
et n'a pas besoin d'être signalé comme faux.

→ Point 4 prend donc la branche **paramètre explicite**.

### (c) Balayage `Tree_item` complet

29 modules (`find src -maxdepth 2 -type d -name "Module*"`). **20 à zéro référence** :
`ModuleAdmin`, `ModuleConfig`, `ModuleDatabase`, `ModuleLang` (devups/) ;
`ModuleLocation`, `ModulePushNotification`, `ModuleReporting` (devupscms/) ;
`ModuleAnalytic`, `ModuleMessage`, `ModuleUser` (devupsfront/) ;
`ModuleSubvention` (edition3ag/) ;
`ModuleCatalog`, `ModuleOrder`, `ModulePayment`, `ModuleSticker`, `ModuleWallet` (shopping/) ;
`ModuleComment`, `ModuleKola`, `ModuleMessenger`, `ModuleRelation` (spacekola/).

`ModuleNotification` (devupscms/) : 1 grep positif, mais **mort** —
`src/devupscms/ModuleNotification/ModuleNotification.php:16-18`, trois `use` commentés.

**Relations déclarées (`@ManyToOne(Tree_item)`)** — le tableau demandé :

| Fichier | Propriété | `onDelete` |
|---|---|---|
| `dclass/extends/ScanCore.php:44` | `$category` (hérité par `Comicbook` ET `Chapter`) | `set null` |
| `edition3ag/ModuleComicbook/Entity/Comicbook_gender.php:18` | `$gender` (pivot, côté genre) | `cascade` |
| `devupscms/ModuleGallery/Entity/Dv_image.php:17` | `$folder` | `set null` |
| `devupscms/ModuleGallery/Entity/Dv_image.php:23` | `$position` (nommée ainsi, cible bien `Tree_item`) | `set null` |
| `devupscms/ModuleTree/Entity/Tree_item_lang.php:21` | traduction (`@Id` composite) | `cascade` |
| `devupscms/ModuleTree/Entity/Tree_item_image.php:22` | `$tree_item` | **non déclaré** (pas de `@JoinColumn`) |
| `devupscms/ModuleData/Entity/Interval_group.php:25` | `$interval` (`@Id` composite) | `cascade` |
| `spacekola/ModulePost/Entity/Post.php:93` | `$category` | `set null` |
| `devupscms/ModuleCms/Entity/Cmstext.php:43` | `$tree_item` | **non déclaré** |

**Usages en requête directe** (pas une relation déclarée, pas de `onDelete` applicable) :

| Fichier | Usage |
|---|---|
| `edition3ag/ModuleContest/Controller/ContestController.php:128` | `Tree_item::getmainmenu('criteria')` |
| `edition3ag/ModuleContest/Controller/ContestController.php:225` | `leftJoinOn('tree_item','folder','folder.id = image.folder_id')` |
| `edition3ag/ModuleContest/Entity/Evaluation.php:58` | `Tree_item::getmainmenu('criteria')` |
| `devupsfront/ModuleSlide/Entity/Slide.php:180` | `Tree_item::mainmenu("folder")->andwhere(...)` |

Usages **morts** (commentés, non comptés comme des références réelles) :
`ChapterController.php:112`, `ChapterFrontController.php:523`, `ComicbookController.php:231`
(tous `//$cmstext->setCategory(Tree_item::getbyattribut(...))`),
`ContestFrontController.php:169`, `ParticipantFrontController.php:89`.

**Lecture** : le genre n'est pas un cas isolé, mais le SEUL `@ManyToOne(Tree_item)` à être
`onDelete=cascade` sur une relation PLUSIEURS-à-un ambiguë (côté genre du pivot) — les deux
autres `cascade` (`Tree_item_lang`, `Interval_group`) sont des lignes de traduction/configuration
dont la suppression en cascade est sans ambiguïté. Les relations "à un seul `Tree_item`"
(`category`, `folder`, `position`, `tree_item` de `Cmstext`) sont toutes `set null` ou non
déclarées — jamais `cascade`. Le pivot genre est donc un patron VALIDE pour sa forme (supprimer
un genre retire l'association, ne détruit pas le livre), pas une anomalie isolée — mais c'est
aussi la SEULE relation `Tree_item` d'une liste à choix multiples trouvée dans tout le dépôt.

---

## Point 2 — remplissage du pivot

**`backend/database/migration/migration_20261103_comicbook_gender_pivot_fill.sql`** — CTE
récursif MariaDB (`WITH RECURSIVE`, supporté en 10.4), `SUBSTRING_INDEX` pour éclater
`gender_names`, `INSERT IGNORE` sur la clé composite `(comicbook_id, gender_id)`.

**Avant / après** :

| | Avant | Après |
|---|---|---|
| Lignes du pivot | 21 | 101 |
| `comicbook_id` distincts | 8 | 36 |

80 lignes insérées. Idempotent : rejoué immédiatement après, **0 ligne insérée** la seconde
fois.

**Orphelins numériques** (identifiant qui n'existe plus dans `tree_item`) : **0** — mesuré par
une jointure externe dédiée (lecture seule, en commentaire dans le script), jamais exécutée
comme partie de l'`INSERT`.

**Jetons non numériques — une troisième forme de corruption, trouvée en mesurant ce point, pas
une hypothèse du prompt** : 5 séries (id 26, 32, 33, 37, 38) ont `gender_names` rempli de NOMS de
genres en toutes lettres au lieu d'identifiants (ex. `"African Fantasy,Shonen,Aventure"`). La
clause `REGEXP '^[0-9]+$'` les exclut de l'`INSERT` (un `CAST` implicite les aurait tous
silencieusement rabattus sur `0`). Aucune tentative de résolution nom→identifiant : au moins
deux de ces chaînes portent un encodage déjà corrompu (`"EnquÃªte"` pour `"Enquête"` — un bug de
double encodage UTF-8/Latin1 ANTÉRIEUR et DISTINCT, hors périmètre, affectant aussi au moins un
nom de série — `"L'Ã‰lu du Monde ParallÃ¨le"`, id 28 — signalé ici, non corrigé, pour un chantier
suivant). Ces 5 séries restent sans pivot tant qu'un créateur ne rouvre pas leur fiche et ne
re-sélectionne pas leurs genres (le formulaire est désormais correct, point 0).

**Preuve (trois séries, chaîne vs pivot)** :

| id | chaîne | pivot | |
|---|---|---|---|
| 16 | `33,38,76` | `33,38,76` | identique |
| 20 | `33,76,78` | `33,76,78` | identique |
| 23 | `33,34,77` | `33,34,77` | identique |

Sur les 32 séries à `gender_names` numérique propre, **30 identiques, 2 différentes** (id 12 et
14 — le pivot y porte un identifiant de PLUS que la chaîne, hérité des 8 séries déjà peuplées
avant ce script, §1(a) ci-dessus — comportement voulu, pas une erreur).

---

## Point 3 — bascule de lecture

**`Comicbook::getGenders($idlang)`** (`src/edition3ag/ModuleComicbook/Entity/Comicbook.php`) :
délègue à `Comicbook_gender::getGenders($this->id, $idlang)` (déjà écrite, jamais appelée avant
ce chantier — seule une ligne commentée l'invoquait). Signature et forme du retour inchangées :
toujours un tableau de `Tree_item` sérialisés.

**`setLang(1)` en dur supprimé.** Mais écart trouvé EN VÉRIFIANT (pas une hypothèse du prompt) :
`$this->dvid_lang`, passé par `jsonSerialize()`, n'est **jamais** peuplé par un `GET /comicbook`
(liste, via `Lazyloading`) ni un `GET /comicbook/:id` (détail) — mesuré en appelant les deux
endpoints en direct et en lisant `dclass/devups/Tchutte/DBAL.php::hydrateData()` /
`::dbrow()` : `dvid_lang` n'y est posé QUE sur les entités liées en `@ManyToOne` (`category`,
`author`, `user`), jamais sur l'entité principale elle-même. Un simple retrait du `setLang(1)`
aurait donc fait sortir `name` sous sa forme multilingue fusionnée (`{fr, en}`) au lieu d'une
chaîne, TOUJOURS (pas seulement quand `dlang` est absent) :

- toléré côté `appv2/` : `CategorySchema.name` accepte déjà `z.union([{fr,en}, string])` (lu
  AVANT d'écrire ce correctif — pas une coïncidence, un dev précédent avait déjà observé cette
  ambiguïté sur `category`) ;
- **PAS toléré côté `creator/`** : son `v-combobox` affiche `{{ item.name }}` en supposant une
  chaîne — vérifié EN DIRECT sur `forms/comicbook?id=12`, les 4 genres s'affichaient
  `[object Object]`. Régression visible, contraire à la règle 9.

**Correctif** : repli sur `Dvups_lang::defaultLang()` (même résolution que le reste de
l'application : `Request::get('lang')`, sinon la langue `main=1`, sinon la première) quand
`$idlang` est faux. Revérifié en direct : les 4 genres s'affichent de nouveau en texte simple
("Action", "Aventure", "Mystère", "Shonen").

**Charge de requêtes** (mesurée via le journal général MariaDB, `GET /comicbook` par page de 20,
en comptant les requêtes `tree_item ... WHERE tree_item.id IN (...)`) :

| | Avant (chaîne) | Après (pivot) |
|---|---|---|
| Requêtes de résolution genres, page de 20 | 20 (1 par livre) | 20 (1 par livre) |

**Aucune régression proportionnelle à ce changement** : c'était déjà un N+1 par livre (chaque
`Comicbook::jsonSerialize()` appelle `getGenders()` individuellement), le pivot ne fait que
remplacer le filtre SQL interne (`IN (select gender_id from comicbook_gender where
comicbook_id = X)` au lieu d'un `IN (33,34,76)` littéral) — même nombre de requêtes, pas de
requête supplémentaire par livre. Le total de requêtes de la page a augmenté (62 → 77) parce que
le pivot est mieux rempli que la chaîne pour les livres de cette page (plus de genres résolus →
plus d'appels à `Tree_item::jsonSerialize()`, qui fait lui-même un `COUNT(*)` de ses propres
enfants — un N+1 préexistant et distinct, dans `Tree_item`, hors périmètre). **Signalé, non
corrigé** : un chargement groupé par liste de `comicbook_id` (une seule requête pour toute la
page) serait l'amélioration naturelle d'un chantier suivant, si ce N+1 devient un problème réel.

**Mobile (point 3 "preuve")** : n'a PAS pu être vérifié sur l'app en direct — voir §9.

---

## Point 4 — filtre par genre

Réponse du point 1(b) : la relation ne peut pas être déclarée pour faire marcher
`genders.id:eq`. Branche retenue : **paramètre explicite** `gender_id` (un ou plusieurs,
séparés par une virgule — même convention que l'opérateur générique `in` de
`Lazyloading::filterswicher()`).

**`ComicbookFrontController::index()`** : deux requêtes, **jamais de concaténation** :
1. requête PRÉPARÉE (paramètres liés) sur `comicbook_gender`, qui rend les `comicbook_id`
   concernés ;
2. `QueryBuilder::in()` sur ce tableau de PHP `int` (jamais la valeur brute de la requête),
   qui assainit chaque élément via `qb_sanitize` — même patron que
   `dclass/devups/model/ModelTrait.php:526`.

Repli `[0]` (aucun `comicbook.id` ne vaut 0) si aucune série ne correspond — un `IN ()` vide
serait invalide en SQL, et "pas de résultat" doit rester "pas de résultat", pas "filtre
ignoré".

**Filtre cassé supprimé** : `$gender_name = Request::get('gender_name'); ... where_str($gender_name
. ' IN (this.gender_names) > 0')` — injection directe (valeur brute dans du SQL), et logique
fausse (`X IN ('12,7,3')` compare à une liste d'UN SEUL élément).

**Preuve** : `gender_id=33` → 19 séries rendues par l'API, **identiques** (même liste de 19 id)
à un comptage SQL direct sur `comicbook_gender` filtré ensuite par la visibilité
(`ScanCore::seriesVisibleSql()` — 23 séries ont le genre 33, 4 sont des brouillons d'autres
auteurs, invisibles à l'utilisateur de test, 19 restent).

**Valeur hostile** :
- `gender_id=1 OR 1=1` → `intval()` par segment extrait `1` (un entier normal) ; 0 résultat,
  aucune erreur SQL.
- `gender_id=33' OR '1'='1` → `intval()` extrait `33` ; résultat NORMAL pour le genre 33 (pas
  élargi), aucune erreur SQL.

---

## Point 5 — écriture, synchronisation, sort de `gender_names`

Transition en deux temps, **décidée par le prompt** (pas une hypothèse) : le creator continue
d'envoyer `gender_names` (chaîne), inchangée ; le backend devient le seul écrivain du pivot.

**`Comicbook::__insert()` / `Comicbook::__update()`** (nouveau — `Comicbook` n'avait jusqu'ici
aucun `__update()` propre, hérité tel quel de `Model::__update()`) : enveloppent
`parent::__insert()`/`parent::__update()` et `syncGenderPivot()` dans **une seule transaction
PDO explicite** (`\Model::pdo()->beginTransaction()`/`commit()`/`rollBack()`). `\Model::pdo()`
est la connexion PDO UNIQUE partagée par toute la requête (`DBAL.php`, commentaire "F-01") :
la transaction couvre donc réellement les deux écritures, l'une annule l'autre en cas d'échec.

`dbtransaction` (constante `config/constante.php`) n'est PAS une transaction SQL — vérifié en
lisant `DBAL::executeDbal()` : c'est un flag de journalisation (`DB_dumper::transaction()`),
sans rapport avec l'atomicité. Une vraie transaction PDO était donc nécessaire, le framework n'en
fournit aucune par défaut.

**`syncGenderPivot()`** : éclate `gender_names` (mêmes garde-fous que le point 2 — jetons non
numériques et identifiants orphelins ignorés), puis `DELETE ... WHERE comicbook_id = ? AND
gender_id NOT IN (...)` (retire ce qui n'est plus demandé) suivi d'`INSERT IGNORE` (ajoute ce qui
manque, sans dupliquer). Une vraie synchronisation, pas un simple ajout qui accumulerait.

**Preuve, en direct, sur le formulaire creator réel (`id=12`)** :
1. retrait de 2 genres (Mystère, Shonen) → chaîne ET pivot passent tous les deux de 4 à **2**
   entrées identiques (`33,34`) ;
2. ajout d'un genre (Seinen) → chaîne ET pivot remontent tous les deux à **3** entrées
   identiques (`33,34,36`).

Les deux sens de la synchronisation prouvés. Autres champs du livre (nom, catégorie, synopsis,
statut) vérifiés intacts après ces deux sauvegardes — **aucune régression du reste du formulaire**
(règle 9).

### `onDelete`

Le pivot est `cascade` (`Comicbook_gender.php:18`), `category` est `set null`
(`ScanCore.php:44`) — les deux corrects pour leur forme (supprimer un genre retire
l'association sans toucher au livre). **L'administrateur ne voit aucun compte avant de
cliquer** sur la suppression d'un `tree_item` : un "utilisé par N séries" devant cette action
est le correctif qui manque — **non touché ici** (le back-office n'est pas dans le périmètre
d'écriture), pour un chantier suivant.

### Trouvé en vérifiant ce point, hors hypothèse du prompt — risque latent, signalé, non corrigé

`src/edition3ag/ModuleComicbook/Controller/ComicbookController.php:193-213`
(`addGender()`/`deleteGender()`, routes admin `comicbook.add-gender`/`comicbook.delete-gender`)
écrit **directement** dans `comicbook_gender` via `DBAL::_createDbal()`/`_deleteDbal()`, en
contournant entièrement `gender_names`. Le gabarit admin qui les appelle
(`Resource/views/admin/comicbook/detail.blade.php:132-156`, fonction JS `addMember()`) n'est
rattaché à AUCUN élément visible dans ce même gabarit (pas de `toggleCollection(..., 'gender')`
trouvé) — semble être une fonctionnalité orpheline, jamais confirmée câblée à un contrôle réel.
**Si elle l'est** (ailleurs, non vérifié), le prochain `Comicbook::__update()` de la même série
(par n'importe quel chemin) écraserait cet ajout/retrait direct avec l'état de `gender_names` au
moment de cette sauvegarde, puisque `syncGenderPivot()` traite la chaîne comme source de vérité.
Pré-existant (la chaîne et le pivot divergeaient déjà avant ce chantier), mais désormais plus
visible puisque le pivot est lu (point 3). Hors périmètre d'écriture pertinent pour CE chantier
(fonctionnalité admin, pas le flux creator/mobile) — signalé, non corrigé.

---

## Diff par commit

Dépôt `creator` (branche `v3`) :
- `3a9b210` `[genres-0] stoppe la corruption de gender_names a chaque sauvegarde`

Dépôt `backend` (branche `v3`) :
- `adab944` `[genres-0] repare les gender_names deja reduits a des virgules pures`
- `c16a298` `[genres-2] remplit comicbook_gender depuis gender_names, idempotent`
- `6276ca4` `[genres-3] getGenders() lit le pivot, setLang(1) en dur supprime`
- `eeff004` `[genres-4] filtre gender_id, remplace le gender_name casse et injectable`
- `d3b8255` `[genres-5] synchronise le pivot a l'ecriture ; corrige point 3 (langue)`

Aucun `git push`. Point 1 : pas de commit (mesure seule, par construction du prompt).

---

## 8. Ce que le prompt a écrit de faux, nommément

- **Numéros de ligne de `Comicbook.vue`** : le prompt annonçait 557/563, les vraies lignes
  étaient 556/563 — assez proches pour ne pas égarer, mais vérifiées plutôt que reprises
  (consigne explicite du prompt lui-même).
- **"Reprends le patron de `category`, qui passe par `with(..., ['l.name'])`"** (point 3) :
  inapplicable tel quel — `with()` résout une relation À UN SEUL `Tree_item` (colonne scalaire
  intégrée à la requête principale), `genders` est une relation À PLUSIEURS, le mécanisme ne
  peut pas être réutilisé directement. Résolu autrement (`Dvups_lang::defaultLang()`).
- **Tableau `Tree_item` du prompt** : les deux premières lignes citaient
  `@ManyToOne(Tree_item)` sans l'argument `targetEntity=` — en réalité, TOUTES les déclarations
  de ce dépôt utilisent la forme longue `@ManyToOne(targetEntity="\Tree_item")`. Détail de
  syntaxe, sans conséquence sur le fond du tableau.
- **Rien d'autre d'identifié comme faux** dans les affirmations vérifiables du prompt — les
  hypothèses explicitement données comme non vérifiées (ex. "40 fichiers sur 29 modules") se
  sont révélées cohérentes avec la mesure complète (44 fichiers listés dans le grep large,
  dont 1 module purement mort).

## 9. Ce qui n'a pas pu être fait

- **La preuve mobile du point 3** (trois écrans, accueil/détail/catalogue, sur appareil ou
  émulateur) **n'a pas pu être réalisée en direct**. `appv2/.env` pointe actuellement
  `EXPO_PUBLIC_API_BASE_URL` vers la **PRODUCTION**
  (`https://backend.3ag-edition.com/v3/api`), avec `EXPO_PUBLIC_ALLOW_PRODUCTION_WRITES=true` —
  le commentaire du fichier lui-même qualifie ceci de "troisième dérive vers la production".
  Trouvé en ouvrant `.env` (lecture seule, jamais modifié — règle dure), APRÈS avoir démarré
  Expo Web par réflexe : le serveur a été arrêté IMMÉDIATEMENT, avant toute navigation ou
  interaction dans l'app, dès la lecture du fichier. Aucune donnée de production n'a été
  touchée. Vérifié à la place, par le code et par une capture API réelle : `CategorySchema`
  d'`appv2/` tolère déjà les deux formes de `name` (`{fr,en}` et chaîne simple — lu dans
  `common.ts`), et `GenreTags.tsx:18-21` (le composant qui affiche les genres sur la fiche
  œuvre) extrait déjà `.fr` si `name` n'est pas une chaîne — donc pas de "[object Object]"
  visible côté mobile, à l'inverse de ce qui a été observé puis corrigé côté creator. Ce
  `.env` n'a pas été touché (règle dure) ; **signalé pour un chantier suivant**, pas corrigé.
- **Le câblage réel de `ComicbookController::addGender()`/`deleteGender()`** côté admin
  (§Point 5) — semble orphelin (aucun élément du gabarit ne l'appelle), mais pas vérifié de
  façon exhaustive (pourrait être appelé depuis un autre gabarit non lu).
- **Un "utilisé par N séries" avant suppression d'un `tree_item`** côté back-office (demandé
  explicitement par le prompt comme "à noter, pas à faire").
- **La résolution automatique des 5 séries à genres nommés** (id 26, 32, 33, 37, 38, point 2) :
  nécessite qu'un créateur rouvre sa fiche et re-sélectionne ses genres dans le formulaire
  désormais réparé — pas une migration automatisable sans risque de faux rapprochement.
