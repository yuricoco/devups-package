# TODO

Suivi des points ouverts, decisions prises, et blocages rencontres
pendant le chantier (droits / logs / langues / cms). Chaque entree :
phase, ce qui reste, pourquoi, fichiers concernes.

Ce fichier remplace entierement la version precedente : les points
Phase 2 (B.2/B.5/B.6/B.7/B.8) et Phase 3 (C.1/C.4/C.5/C.6/C.7) qu'elle
documentait comme ouverts ont ete traites dans cette session (voir
"Resume phase par phase" plus bas). Seul le point Phase 4 (CMS) reste
ouvert, voir ci-dessous.

## Phase 4 (CMS) - ETAPE G

### G.1 - plan (tables, colonnes, migration, routes impactees)

Issu d'une revue du code (`Cmstext`/`Cmstext_lang`, `Imagecms`,
`CommentCore.json`, `ModuleGallery`, `ModuleTree`) - voir le detail des
constats dans l'historique de session. Resume :

**Renommages prevus**

- Table `cmstext` -> `cmscontent`, table `cmstext_lang` ->
  `cmscontent_lang` (colonne FK `cmstext_id` -> `cmscontent_id`) ;
  classes `Cmstext`/`Cmstext_lang`/`CmstextController`/`CmstextForm`/
  `CmstextTable` -> `Cmscontent*`. Le datacore `CmstextCore.json` est
  perime (longueur de `reference` fausse - 25 au lieu de 125 -,
  colonnes `lang`/`creationdate` fantomes qui n'existent pas sur
  l'entite, relation `tree_item` et colonnes `slug`/`active`/
  `position` absentes) : a corriger AVANT toute regeneration, pas a
  suivre tel quel.
- `imagecms` (table de jointure pure `id`/`image`/`cmstext`, sans
  colonne de fichier propre) -> `cmsresource`, reconstruit comme une
  entite qui etend `ImageCore` directement (meme modele que `Dv_image`)
  plutot qu'une table de jointure : absorbe le stockage de fichier et
  le lien vers `cmscontent`. `ImagecmsCore.json` est egalement perime
  (attribut `width_size` fantome, aucune relation listee).
- `comment` -> `cmscomment` : aucune entite n'existe aujourd'hui, seul
  `CommentCore.json` (datacore) existe, avec `module.name` pointant
  vers `ModuleComment` (module inexistant - reference orpheline). Les
  deux fichiers de config (`config/dvups_configurations.php`,
  `config/module_configurations.php`) enregistrent deja `Comment` sous
  `ModuleCms`/`devupscms` : corriger `module.name` dans le datacore
  vers `ModuleCms`, puis generer proprement via
  `php devups core:g:crud Comment` (ou entite/controleur/formulaire/
  table individuellement).

**Migration (ecrite, NON executee - voir regle framework)**

```sql
RENAME TABLE cmstext TO cmscontent;
RENAME TABLE cmstext_lang TO cmscontent_lang;
ALTER TABLE cmscontent_lang CHANGE cmstext_id cmscontent_id INT;
-- cmsresource : creer la nouvelle table (ImageCore + colonne
-- cmscontent_id), recuperer les donnees de imagecms JOIN dv_image,
-- puis seulement alors DROP imagecms (voir G.4 plus bas) - jamais
-- avant la recuperation des donnees.
```

**Routes impactees**

- `services.php` : tous les cas `cmstext.*`/`cmstext/*` -> `cmscontent.*`.
- Route generique admin `/admin/devupscms/ModuleCms/cmscontent/edit?id=1`
  (et equivalent API `admin/api/cmscontent/update`) : doit fonctionner
  une fois la vue corrigee (voir G.3).
- `imagecms.*` -> `cmsresource.*` partout ou reference (services.php,
  vues).

### G.2/G.3 - implementation (renommages, migration, ecran edition) - REPORTEE

- Phase : G.2, G.3
- Ce qui reste : executer les renommages de table/classe ci-dessus,
  ecrire et verifier la migration de donnees `imagecms JOIN dv_image`
  -> `cmsresource` (recuperation AVANT toute suppression), corriger le
  Blade casse de `src/devupscms/ModuleCms/Resource/views/cmstext/
  form.blade.php` (ligne ~105 : accolade d'ouverture `{!!` manquante
  avant `Dv_imageTable::init(...)`, rendant le bloc totalement mort -
  c'est la cause concrete pour laquelle l'edition d'un cmstext est
  aujourd'hui cassee) et le repointer vers `cmsresource` au lieu de
  `Dv_image`/`Dv_imageTable`, puis faire de meme pour
  `admin/cmstext/form.blade.php` (widget `ImagecmsTable` embarque).
- Pourquoi non fait dans cette session : contrairement aux suppressions
  deja executees (ModuleDatabase, Local_content*, Page_mapped - mortes
  des l'origine, aucune donnee reelle a preserver), ce chantier
  renomme des tables qui contiennent potentiellement des donnees
  reelles (contenus CMS existants) ET change leur forme (jointure ->
  entite fichier), ce qui demande une verification manuelle des
  donnees migrees (correspondance 1-a-1 image<->cmstext, fichiers
  physiques toujours presents sur disque) avant d'executer quoi que ce
  soit - une erreur ici est difficile a detecter par les tests seuls et
  risque la perte silencieuse de contenu. Vu le temps restant de la
  session, mieux valait livrer un plan solide et verifiable que
  d'executer des renommages de schema non valides manuellement.
- Fichiers concernes : `src/devupscms/ModuleCms/Entity/Cmstext.php`,
  `Cmstext_lang.php`, `Controller/CmstextController.php`,
  `Form/CmstextForm.php`, `Datatable/CmstextTable.php`,
  `Resource/views/cmstext/form.blade.php`,
  `Resource/views/admin/cmstext/form.blade.php`,
  `src/devupscms/ModuleCms/Entity/Imagecms.php`, `CmstextCore.json`,
  `ImagecmsCore.json`, `services.php`,
  `database/migration/` (migration a ecrire une fois le mapping de
  donnees verifie).

### G.4 - suppression ModuleGallery - REPORTEE (depend de G.2/G.3)

- Phase : G.4
- Avis argumente : `ModuleGallery`/`Dv_image` doit etre supprime une
  fois `cmsresource` operationnel, pas conserve en lecture seule -
  meme logique que les suppressions deja faites cette session (module
  remplace, pas un historique a garder).
- Ce qui reste : recuperer dans `cmsresource` toutes les lignes
  `dv_image` referencees via `imagecms` ET via `tree_item.folder`/
  `tree_item_image` (ModuleTree a aussi une dependance directe a
  `Dv_image`, y compris un asset CSS charge via `Dv_image::classpath()`
  dans `ModuleTree.php`) avant tout DROP ; corriger
  `Tree_item_image` (entite/Form/Datatable) pour ne plus referencer
  `Dv_image` ; ecrire la migration `DROP TABLE imagecms, dv_image`
  (non executee) ; retirer `ModuleGallery` des deux fichiers de config
  et supprimer `src/devupscms/ModuleGallery/` ; retirer de
  `KNOWN_BROKEN` les entrees `imagecms/form` et `tree-item-image/form`
  ("depend de ModuleGallery") une fois corrige.
- Pourquoi non fait : bloque par G.2/G.3 (cmsresource doit exister et
  etre verifie avant de pouvoir y migrer les donnees de dv_image) ; la
  dependance supplementaire de `ModuleTree` (non mentionnee dans la
  demande initiale, decouverte pendant la revue) ajoute un risque de
  perte de donnees (images d'arborescence) si traitee a la hate.
- Fichiers concernes : `src/devupscms/ModuleGallery/**`,
  `src/devups/ModuleTree/Entity/Tree_item_image.php`,
  `src/devups/ModuleTree/Form/Tree_item_imageForm.php`,
  `src/devups/ModuleTree/Datatable/Tree_item_imageTable.php`,
  `src/devups/ModuleTree/ModuleTree.php`,
  `config/dvups_configurations.php`, `config/module_configurations.php`,
  `tests/E2E/AdminScreensTest.php` (KNOWN_BROKEN).

### G.5 - generateur/datacores/tests e2e - REPORTEE (depend de G.1-G.4)

- Phase : G.5
- Ce qui reste : corriger `CmstextCore.json`/`ImagecmsCore.json`/
  `CommentCore.json` (voir G.1) avant toute regeneration ; mettre a
  jour les cles `screens()` de `AdminScreensTest` (`cmstext`/
  `imagecms`/`comment` -> `cmscontent`/`cmsresource`/`cmscomment`) et
  retirer les entrees `KNOWN_BROKEN` devenues sans objet ; verifier
  qu'aucune autre reference a `cmstext`/`imagecms`/`Dv_image`/
  `ModuleGallery` ne subsiste dans la suite de tests.
- Pourquoi non fait : depend entierement de G.2-G.4.
- Fichiers concernes : `dclass/generator/datacores/CmstextCore.json`
  (chemin exact a confirmer), `ImagecmsCore.json`, `CommentCore.json`,
  `tests/E2E/AdminScreensTest.php`.

---

## Traductions non encore faites (lang:missing, en.php)

`php devups lang:missing` (execute sur le vrai dossier `lang/`, pas sur
le dossier temporaire de test) rapporte 274 cles non traduites dans
`lang/en.php` (valeur = cle, comme prevu par la regle : "pour le reste,
laisse valeur = cle"). Liste complete (ordre alphabetique, tel que
rapporte par la commande) :

Notfication type, Notification, Numero, Numéro de téléphone mise a
jour, Object of the mail, Occurrences, Parent id, Payload, Pays,
Phonenumber, Position, Profile, Ref, Reference, Responsable centre,
Save, Session, Size, Status, Statut, Sujet, Timer (minutes), Title of
the mail, Titre, Tous les champs sont important, Type, Type of
reporting, Un nouveau code d'activation vous a ete envoye, Une erreur
interne est survenue., Update Privilege, User, User_ext_id, User_id,
Username, Utilisateur introuvable, Verification du compte, Viewedat,
Width, _key, _max, _min, _value, action, alias, auth_token, birthdate,
callback, chain, client, cmstext.reference, code, code d'activation
vous a été envoyé. Utilisez le pour confirmer le changement de votre
numéro., comment, configuration._key, configuration._type,
configuration._value, configuration.comment, configuration.id,
constraint, content, content_type, context, continent.id,
continent.name, continent.status, country not found,
country.created_at, country.deleted_at, country.id, country.iso,
country.iso3, country.name, country.nicename, country.numcode,
country.phonecode, country.status, country.updated_at, create,
creationdate, date_end, delete, description, dv_image.image,
dvups_action, dvups_action.action, dvups_action.entity,
dvups_action.label, dvups_lang.active, dvups_lang.date_format_full,
dvups_lang.date_format_lite, dvups_lang.id, dvups_lang.iso_code,
dvups_lang.language_code, dvups_lang.locale, dvups_lang.main,
dvups_lang.name, dvups_log.context, dvups_log.entity_class,
dvups_log.file, dvups_log.fingerprint, dvups_log.first_at,
dvups_log.last_at, dvups_log.level, dvups_log.line, dvups_log.log_file,
dvups_log.message, dvups_log.occurrences, dvups_role,
dvups_role.alias, dvups_role.name, edit, email, emailmodel.content,
emailmodel.contenttext, emailmodel.id, emailmodel.object, endpoint,
entity, entity.dv_image, entity.notification, entity.tree_item,
entity_class, export, file, fingerprint, first_at, firstname, height,
id, image, interval, interval (J), interval._max, interval._min,
interval._value, interval.label, is_activated, label, lang, last_at,
lastname, le numéro doit etre constitué de 9 caractères, level, line,
log, log_file, message, name, next_call, notification.content,
notification.creationdate, notification.entity, notification.entityid,
notification.id, notificationbroadcasted.status,
notificationbroadcasted.viewedat, notificationtype,
notificationtype._key, notificationtype.content, notificationtype.id,
oauth_client.client, oauth_client.name, oauth_client.user_ext_id,
oauth_client.user_id, occurrences, password.notconfirm, payload,
phonenumber, position, public_key, push_email.date_end,
push_subscription.auth_token, push_subscription.content_type,
push_subscription.public_key, push_subscription.status,
push_subscription.subscription_type, push_subscription.user_id, queue,
reference, reportingmodel, reset password, resettingpassword, search,
session token unavailable, sexe, show, size, slug, status.color,
status.id, status.key, status.label, status.position,
subscription_id, subscription_type, this field is important!, titre,
tree, tree.branches, tree.description, tree.id, tree.label, tree.name,
tree_item.description, tree_item.hierarchy, tree_item.id,
tree_item.main, tree_item.name, tree_item.parent_id,
tree_item_image.id, tree_item_image.width_size, type, user.email,
user.firstname, user.is_activated, user.lastname, user.password,
user.phonenumber, user.username, user_ext_id, user_id, username,
width, workers.callback, workers.log, workers.payload, workers.queue,
workers.type, you most specify destination (s)

La plupart de ces cles sont des noms de colonnes de datacore
(`tree_item.*`, `dvups_log.*`, `country.*`...) utilises comme libelles
de champ generiques plutot que des phrases a traduire : a trier et
completer manuellement, cle par cle, dans `lang/en.php` - aucune cle
n'a ete reecrite dans le code (regle respectee).

---

## Resume phase par phase

**Phase 1 (Droits)** : entierement terminee et committee AVANT cette
session (6/6 points). Non retouchee ici.

**Phase 2 (Logs) - ETAPE D** : terminee.
- D.1 : le gestionnaire d'erreurs (`set_error_handler`) et la garde
  shutdown (`error_get_last()`) transmettent desormais explicitement
  `file`/`line` a `Log::write()`, qui les utilise au lieu de capturer
  son propre emplacement - teste (`LogHandlerTest`).
- D.2 : `error_reporting() & $level` respecte (erreurs masquees par `@`
  jamais journalisees) ; `LOG_MIN_LEVEL` (defaut `warning`) filtre
  `E_NOTICE`/`E_DEPRECATED` par defaut - teste.
- D.3 : garde anti-recursion (`Log::$writing`) dans `write()` - teste
  par reflexion.
- D.4 : increment atomique (`UPDATE ... SET occurrences = occurrences + 1`)
  au lieu de lire-puis-ecrire ; empreinte en `sha1()`.
- D.5 : `respondWithFatal()` ecrit du texte brut sur `STDERR` en CLI
  (plus de HTML).
- D.6 : detail Log affiche le contenu du fichier de debordement
  (echappe, dans un `<pre>`) ; le JS du module (`dvups_logCtrl.js`/
  `dvups_logForm.js`) etait deja un stub inoffensif, rien a nettoyer.
- B.5/B.7/B.8 (reports de la session precedente, executes cette
  session sous ETAPE F) : voir Phase 2bis ci-dessous.

**Phase 3 (Langues) - ETAPE E** : terminee.
- E.1 : constante `LANG_DIR` (defaut `ROOT.'lang/'`) et `LANG_COLLECT`
  (defaut : actif hors prod) ; `tests/bootstrap.php` cree un dossier
  temporaire dedie par run (jamais les fichiers versionnes) et le
  propage via `putenv()` - le serveur e2e le recoit automatiquement
  (`HttpServer` transmet `getenv()` au process `php -S` enfant).
- E.2 : `LangTest` rendu independant de l'ordre (fixtures creees par le
  test, plus de dependance a l'e2e).
- E.3 : une cle collectee est aussi ajoutee a `$dv_translations` en
  memoire (pas de nouvelle lecture disque pour la meme cle dans la
  requete) - teste.
- E.4 : `lang:scan` reecrit avec `token_get_all()` (plus de regex) ;
  seules les cles litterales simples sont retenues, les appels a cle
  construite (concatenation) sont ignores et signales en sortie -
  teste (`LangScanCommandTest`).
- E.5 : `lang/fr.php`/`lang/en.php` nettoyes des cles parasites issues
  de l'ancienne regex ; traductions evidentes appliquees en anglais ;
  274 cles restantes listees ci-dessus via `lang:missing` (valeur =
  cle, non reecrites dans le code).
- E.6 : `t_lang($cle, $iso, $params=[])` ajoute (lecture directe de
  `lang/<iso>.php`, mise en cache par langue, sans session) ; les 3
  mails (`invitation`/`otp`/`welcome`) migres ; `langt()` retire
  (corps remplace par un commentaire de redirection).
- E.7 : liste des langues actives mise en cache
  (`cache/lang/active.php`, `Dvups_lang::cachedActiveDlangs()`), meme
  modele que le cache des droits, invalidee automatiquement a chaque
  creation/modification/suppression d'une langue (overrides
  `__insert`/`__update`/`__delete` sur `Dvups_lang`) - `header.php` ne
  fait plus `Dvups_lang::all()` a chaque requete.
- E.8 : `Local_content`/`Local_content_key`/`Local_content_lang`/
  `Page_mapped` entierement supprimes (entites, controleurs,
  formulaires, datatables, vues, configurations) ; migration DROP
  ecrite (non executee, `database/migration/2026_11_08_drop_local_content.sql`) ;
  4 entrees `KNOWN_BROKEN` devenues sans objet retirees.
- C.1 (reference iso2) : deja en place avant cette session, confirme
  sans changement.

**Phase 2bis (Logs) - ETAPE F** : terminee.
- F.1 : avis de la session precedente execute - `ModuleDatabase`/
  `Request_history` supprimes entierement (mortaient des l'origine,
  jamais alimentes) ; migration DROP ecrite (non executee,
  `database/migration/2026_11_08_drop_request_history.sql`) ; retire
  des deux fichiers de configuration.
- F.2 : nouveau schema `Emaillog` (destinataire, sujet, modele, statut
  envoye/echec, erreur, dates) ; entite, datatable et ecran lecture
  seule ; `DMail.php` et `Reportingmodel::sendMail` adaptes via
  `Emaillog::logSuccess()`/`logFailure()` ; migration ecrite (non
  executee, `database/migration/2026_11_08_emaillog_schema.sql`) ;
  tests (`EmaillogTest`) couvrant envoi reussi, envoi en echec,
  destinataire chaine simple, et la separation stricte Log::/Emaillog
  dans les deux sens.

**Phase 4 (CMS) - ETAPE G** : plan ecrit (G.1, voir ci-dessus),
implementation reportee (G.2-G.5, voir raisons detaillees plus haut) -
risque de perte de donnees reelles sur des renommages de table non
verifies manuellement, juge trop risque pour le temps restant de la
session. A reprendre en priorite dans une prochaine session, dans
l'ordre G.2/G.3 (renommages + migration + ecran edition) puis G.4
(suppression ModuleGallery, qui depend des deux premiers) puis G.5
(nettoyage generateur/tests).

## Migrations a executer manuellement, dans l'ordre

Aucune de ces migrations n'a ete executee par cette session (regle :
jamais de DROP ni de suppression de donnees hors base de test). A
executer dans cet ordre sur chaque environnement concerne :

1. `database/migration/2026_11_07_dvups_log_schema.sql` (nouveau
   schema `dvups_log`, Phase 2/ETAPE D).
2. `database/migration/2026_11_08_drop_local_content.sql` (DROP
   `local_content`, `local_content_key`, `local_content_lang`,
   `page_mapped`, Phase 3/ETAPE E.8).
3. `database/migration/2026_11_08_drop_request_history.sql` (DROP
   `request_history`, Phase 2/ETAPE F.1).
4. `database/migration/2026_11_08_emaillog_schema.sql` (DROP+CREATE
   `emaillog` avec le nouveau schema - **exporter l'historique existant
   avant execution si besoin**, voir commentaire en tete du fichier,
   Phase 2/ETAPE F.2).
5. (Phase 4/ETAPE G, non ecrite encore - voir G.2/G.3 ci-dessus) :
   `RENAME TABLE cmstext/cmstext_lang`, creation `cmsresource` +
   migration de donnees depuis `imagecms`/`dv_image`, puis
   `DROP TABLE imagecms, dv_image` - a ecrire et verifier manuellement
   avant execution, dans une prochaine session.

Sur l'environnement de test (`devups_phpunit`), aucune de ces
migrations n'est necessaire : le schema est recree automatiquement a
chaque run par Doctrine SchemaTool a partir des entites
(`tests/bootstrap.php`).

## Etat des tests

`composer test` verifie vert a chaque commit de cette session
(ETAPE D : commit `d1f25e3` ; E.1-E.6 : `e5a9863` ; E.7-E.8 : `597cdd6` ;
F.1 : `696cbc5` ; F.2 : `92af6d2` ; README : `e55cf7c`) - 179 tests, 0
echec, 15 "incomplete" documentes et assumes dans
`tests/E2E/AdminScreensTest.php::KNOWN_BROKEN` a la fin de la session
(avant le commit de ce TODO.md - revalider apres ce commit, aucun
changement de code de production ne l'accompagne, seulement
`lang/fr.php`/`lang/en.php`).
