# Stickers — rendre l'upload atteignable, écran de proposition créateur

Journal des décisions prises en continu (même règle que les chantiers
précédents : option la plus conservatrice, notée ici, on continue).
Périmètre d'écriture pour ce chantier côté `backend/` : **uniquement**
`src/shopping/ModuleSticker/Controller/StickerFrontController.php`, ce
fichier, et `../aiMemory/`. Aucune autre ligne de `backend/` n'a été
touchée — `Sticker.php` (entité), `Dfile.php`, `Router.php`,
`WebService.php`, `Request.php` restent strictement en lecture.

---

## 0 — Étape 0 : les trois constats mesurés avant d'écrire une ligne

Backend local, `127.0.0.1:3334`, branche `v3`, jamais la production.
Les quatre mesures demandées, verbatim :

**Mesure 1** — `POST /api/sticker` multipart, `sticker_form[image]` = un
vrai fichier, `sticker_form[unit_price]=0.20` (un prix VALIDE) :
```
400 {"success": false, "detail": "unit_price must be one of: 0.20, 0.60, 1.80, 6.00, 18.00"}
```
Confirme le **constat (b)** : `Request::raw()` lit `php://input`, déjà
vidé par PHP sur une requête multipart — la validation du prix échoue
**avant** d'atteindre la branche d'upload, même avec un prix par
ailleurs correct. `create()` n'a pas été touché pour autant (consigne
explicite) ; c'est exactement le trou que la nouvelle route d'étape 1
doit combler sans passer par `Request::raw()`.

**Mesure 2** — `POST /api/upload/sticker` (chemin générique) :
```
403 {"success": false, "detail": "this path does not serve this entity"}
```
Confirme le **constat (a)** : `WebService::$genericPathWriteBlockedEntities`
bloque bien `sticker` sur le chemin générique.

**Mesure 3** — `PUT /admin/api/sticker/validate/1`, avec une **session
admin réelle** (compte jetable `scheduled_test_admin`, id=7, déjà créé
en base locale par un chantier précédent — mot de passe local
réinitialisé pour ce tour, connexion refaite par le vrai formulaire
`admin/login.php` → `connexion-action`, 302 vers `/admin/dashboard`) :
```
HTTP 200, corps = une page de debug (dv_dump), pas du JSON
```
Ce n'est pas un succès : `Router::startAdmin()` réécrit
`/admin/api/sticker/validate/1` en `/api/sticker/validate/1` puis
appelle `AdminService::manageServe()`. Comme le chemin a 3 segments
(`sticker/validate/1`), `manageServe()` ne passe jamais par le switch de
`systemServe()` (réservé aux chemins à 2 segments et aux actions
génériques `create/upload/detail/read/update/delete/lazyloading`) : il
cherche une méthode `StickerValidate1Service` sur `AdminService`
elle-même, qui n'existe pas, et retombe (hors prod) sur `dv_dump(...)`
puis `die`. **Confirme le "routing failure" attendu** : `validate()`/
`reject()` ne sont jamais atteintes par ce chemin, quelle que soit la
session.

Complément mesuré (pas dans la liste des 4, mais nécessaire pour savoir
*où* `validate()`/`reject()` sont réellement atteignables) : `PUT
/api/sticker/validate/1` par le chemin JWT normal, avec un utilisateur
non-admin authentifié :
```
403 {"success":false,"detail":"sticker validation is reserved to the administration"}
```
`denyUnlessAdmin()` lit `Router::$admin_route`, qui n'est mis à `true`
que dans `manage()` et `manageServe()` (univers admin) — **jamais** sur
le chemin `/api/...` JWT qui héberge les méthodes annotées de
`StickerFrontController`. Autrement dit `validate()`/`reject()` sont
aujourd'hui **structurellement inatteignables par quiconque, y compris
un vrai administrateur** : ce n'est pas une faille de sécurité (rien ne
fuite), c'est une fonctionnalité d'administration morte. Hors périmètre
de ce chantier (`create()`/`update()`/`retire()` ne sont pas concernés,
eux passent bien par le JWT normal) — signalé ici et au §4 (diagnostic),
pas corrigé : le prompt ne demande pas de réparer le circuit admin.

**Mesure 4** — type réel de la colonne (`SHOW COLUMNS` passe par
`information_schema.COLUMNS`, le seul helper de lecture disponible
n'acceptant que des `SELECT`) :
```
unit_price : double, NOT NULL, pas de défaut
```
Correspond à l'annotation `@Column(type="float")` de l'entité — aucun
écart flottant/décimal à corriger ici ; `isValidUnitPrice()` compare déjà
en `bccomp()` sur des chaînes, donc insensible au type de stockage réel.

**Aucun hard-stop** : les trois constats se confirment tous. Étape 1
peut s'écrire.

## 1 — Étape 1a : la clé de champ pour l'upload

Résolu en lisant `Dfile::__construct()` (`dclass/devups/Dfile.php:177-247`,
lecture seule) : quand `Model::Dfile($fileparam)` est appelé depuis une
entité (`Sticker::uploadImage($file='image')` → `self::Dfile($file)`),
`$entity` vaut le nom de classe (`'Sticker'`), et le constructeur résout
`$entityname = strtolower($global_config['Sticker']['name'])` =
`'sticker'` (`config/dvups_configurations.php:1551`, `'name' =>
'Sticker'`). Il cherche alors `$_FILES['sticker_form']['error'][$file]`
— c'est à dire le sous-champ `$file` (`'image'` par défaut) **à
l'intérieur** du groupe `sticker_form`, exactement ce que produit un
champ posté sous la forme `sticker_form[image]`.

**Il n'y a donc aucune incohérence entre `create()` (qui teste
`isset($_FILES['sticker_form'])`) et `uploadImage(\$file='image')`** :
les deux attendent le même nommage imbriqué. C'est précisément la
convention déjà utilisée par `ImageUploadController` côté créateur pour
les illustrations (`keyform='illustration_form[image]'`) — la nouvelle
route réutilise ce même patron avec `keyform='sticker_form[image]'`,
sans rien inventer.

**Décision** : la nouvelle méthode `upload()` lit `$_FILES` directement
(jamais `Request::raw()`), construit un `new Sticker()` non persisté,
appelle `uploadImage('image')` (le défaut, cohérent avec la clé
imbriquée ci-dessus), et répond sans jamais faire `__insert()`.

## 2 — Étape 1a : ce qui rejette vraiment un non-image, mesuré

Lu `Dfile::settype()`/`validation()`/`moveto()` en entier (lecture
seule). Fait établi, pas supposé : **`Dfile` ne bloque par défaut AUCUN
type de fichier**. `settype()` classe seulement l'extension dans
`image/audio/video/document/archive/text` (liste `$EXTENSION_IMAGE =
['jpg','jpeg','png','gif','webp']`, par extension du nom de fichier,
pas par lecture du contenu) ; `validation()` ne rejette que si
`setConstraintfiletype()` a été appelé au préalable (jamais le cas dans
`Sticker::uploadImage()`) ou si une erreur d'upload PHP est déjà
présente. Sans appeler `setConstraintfiletype('image')`, **un fichier
`.pdf` ou `.exe` renommé passerait `moveto()` sans erreur** et finirait
dans `uploads/stickers/`.

**Décision (conservatrice, dans le périmètre `StickerFrontController.php`
uniquement — `Sticker.php`/`Dfile.php` restent hors-limite)** : la
nouvelle route `upload()` vérifie elle-même l'extension du fichier posté
AVANT d'appeler `uploadImage()`, en réutilisant directement la liste déjà
existante `Dfile::$EXTENSION_IMAGE` (publique, statique — pas recopiée,
pas réinventée). Rejet en 400 si l'extension n'y figure pas.

**Mesuré en écriture réelle, pas supposé** : un contrôle par extension
seul ne suffit pas. Un fichier texte renommé `not_an_image.png` passe le
contrôle d'extension (le nom dit `.png`), puis traverse `Dfile` sans
erreur propre — `moveto()`/la conversion webp plantent plus loin avec des
warnings PHP (`Dfile.php:552/554/556`, `getimagesize()` sur un fichier
qui n'est pas une image) qui **fuitent un chemin serveur dans la réponse
JSON** (pas de ligne insérée pour autant : `upload()` ne fait jamais
`__insert()`). Corrigé en ajoutant un second contrôle, réel, sur le
contenu : `getimagesize($tmp_name) !== false` — lit les en-têtes réels du
fichier, pas son nom. Rejet propre en 400 (`'file is not a valid
image'`), vérifié par un rejeu de la même requête après correction (le
PNG réel passe toujours, le faux `.png` est maintenant refusé proprement,
plus aucun warning). Aucune dépendance ajoutée : `getimagesize()` est une
fonction PHP native, déjà utilisée par `Dfile` lui-même.

## 3 — Étape 1b : où exposer `Sticker::UNIT_PRICES`

**Décision** : un nouveau `@GET(path='/prices')`, public (même règle
d'auth que `index()`), qui renvoie `{"success": true, "prices":
Sticker::UNIT_PRICES}`. Pas fondu dans `mine()` : `mine()` répond une
liste paginée de stickers, mélanger une liste de prix dedans aurait
changé la forme de sa réponse existante consommée ailleurs (potentiel,
non vérifié, donc évité par prudence) ; un endpoint séparé n'a aucun
effet de bord et donne à l'écran créateur une source serveur unique pour
son `v-select`, sans dupliquer la liste côté Vue.

## 4 — Vérification

- `php -l` sur `StickerFrontController.php` : voir rapport final.
- Aucune dépendance ajoutée, aucun `.gitignore` touché.
- Commit prévu : `[stickers-6] route de depot d'image et liste de prix servie`.
