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

### G.1/G.2/G.3 - plan, renommages et ecran d'edition : FAITS

Le plan (G.1) a ete execute dans cette meme session (pas seulement
ecrit) : la decision initiale de reporter G.2/G.3 a ete revisee une
fois constate que, comme pour toutes les suppressions de table de
cette session (ModuleDatabase, Local_content*, dvups_log...), le code
des renommages peut etre ecrit et teste integralement contre la base
de test (schema recree depuis les entites par Doctrine SchemaTool)
SANS jamais executer de migration sur une base reelle - le risque
"perte de donnees" ne portait que sur l'EXECUTION de la migration, pas
sur l'ecriture du code, exactement comme les migrations DROP deja
ecrites (jamais executees) plus haut.

- Renommage `Cmstext`/`Cmstext_lang` -> `Cmscontent`/`Cmscontent_lang`
  (classes, table `cmstext`/`cmstext_lang` -> `cmscontent`/
  `cmscontent_lang`, routes `services.php`, vues, JS) : fait
  integralement (entite, formulaire, datatable, controleur, front
  controleur, vues `cmscontent/*` et `admin/cmscontent/*`).
- `Imagecms` (table de jointure vers `Dv_image`) reconstruit en
  `Cmsresource`, qui etend `ImageCore` DIRECTEMENT (meme modele que
  `Dv_image`) : absorbe le stockage de fichier au lieu d'une jointure,
  lie a `Cmscontent` par `cmscontent_id`. Plus de dependance a
  `Dv_image` pour les images de contenu CMS.
- `Cmscomment` genere proprement (entite/formulaire/datatable/
  controleur) a partir de `CommentCore.json` dont le `module.name`
  pointait vers un `ModuleComment` inexistant - corrige vers
  `ModuleCms` (ou `Comment` etait deja enregistre dans
  `config/dvups_configurations.php`/`module_configurations.php`,
  maintenant renomme `Cmscomment`). Aucune donnee a migrer : la table
  `comment` n'a jamais existe (aucune entite generee avant cette
  session).
- La page d'edition admin (`/admin/devupscms/ModuleCms/cmscontent/edit?id=1`)
  fonctionne : le Blade casse (`Resource/views/cmscontent/form.blade.php`,
  accolade d'ouverture `{!!` manquante avant un bloc
  `Dv_imageTable::init(...)` mort) est corrige et repointe vers
  `CmsresourceTable` ; la meme vue accedait aussi `$cmscontent->title[$iso]`/
  `content[$iso]` en supposant toujours un tableau par langue (jamais le
  cas pour un contenu cree hors du flux d'edition JS en place, ex. un
  fixture de test) - rendue robuste (`is_array(...) ? ... : ...`) sans
  changer le comportement du flux existant. Couvert par
  `tests/E2E/CmscontentEditPageTest.php`.
- Bug latent decouvert en testant : `Model::create()` sur une entite
  `dvtranslate=true` (comme `Cmscontent`, heritage de `Cmstext`)
  appelait `Dvups_lang::readCache()`, methode retiree a l'ETAPE E.7 de
  cette meme session (remplacee par `cachedActiveDlangs()`, dont le
  format - map iso<->id - ne correspond pas a ce que ce code attend) ;
  jamais exerce avant (aucun test n'appelait `Cmstext::create()`).
  Corrige au point d'appel (`dclass/devups/Tchutte/DBAL.php`) par
  `Dvups_lang::all()`, qui restaure le comportement d'avant l'ETAPE E.7
  pour ce chemin precis - pas un chantier de cache a part, juste la
  reparation d'une regression introduite plus tot dans cette session.
- `Cmsresource`/`Cmscomment` genere aussi des ecrans `list`/`form`
  fonctionnels (verifies par `AdminScreensTest` - voir "Etat des
  tests") : 2 entrees `KNOWN_BROKEN` devenues sans objet retirees
  (`imagecms/form`, `comment/form`).
- Renommages annexes necessaires decouverts en grepant les references
  residuelles a `Cmstext`/`Imagecms` : `Tree_item::getCmstext()` ->
  `getCmscontent()` (methode reelle, mais seul appelant etait du code
  mort apres un `return` anterieur - corrige quand meme) ;
  `Tree_itemController::addcontentAction()` et
  `TreeController::managerView()` (chemins reels, utilises par le
  module Arbre pour creer un contenu CMS depuis un item d'arbre).
- Tests : `tests/Integration/CmsPhase4Test.php` (persistance
  Cmscontent/Cmsresource/Cmscomment et leurs relations) et
  `tests/E2E/CmscontentEditPageTest.php` (page d'edition demandee par
  la consigne, verifiee sans erreur serveur).

**Migration (ecrite, NON executee, comme pour toute migration de cette
session - voir `database/migration/2026_11_08_phase4_cms_rename.sql`)** :
renomme `cmstext`/`cmstext_lang` en `cmscontent`/`cmscontent_lang`, cree
`cmsresource` et `cmscomment` (vides - `cmsresource` doit ensuite etre
peuplee depuis `imagecms JOIN dv_image`, voir le commentaire detaille
dans le fichier ; le script ne fait PAS cette migration de donnees
automatiquement, elle reste a verifier et executer a la main avant de
decommenter le DROP de `imagecms`).

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
- Pourquoi non fait : `cmsresource` existe desormais (G.2/G.3 faits),
  mais la dependance supplementaire de `ModuleTree`/`Tree_item_image` a
  `Dv_image` (non mentionnee dans la demande initiale, decouverte
  pendant la revue - images d'arborescence, pas des images de contenu
  CMS) reste entiere : supprimer `ModuleGallery` casserait cette
  fonctionnalite tant qu'elle n'est pas repointee ou volontairement
  abandonnee, ce qui est une decision produit (perdre la gestion
  d'image des items d'arbre, ou la reconstruire ailleurs) plutot qu'une
  simple execution mecanique - laissee pour une prochaine session.
- Fichiers concernes : `src/devupscms/ModuleGallery/**`,
  `src/devups/ModuleTree/Entity/Tree_item_image.php`,
  `src/devups/ModuleTree/Form/Tree_item_imageForm.php`,
  `src/devups/ModuleTree/Datatable/Tree_item_imageTable.php`,
  `src/devups/ModuleTree/ModuleTree.php`,
  `config/dvups_configurations.php`, `config/module_configurations.php`,
  `tests/E2E/AdminScreensTest.php` (KNOWN_BROKEN).

### G.5 - generateur/datacores/tests e2e

- Phase : G.5
- Fait (en meme temps que G.2/G.3, pas seulement a l'issue de G.4) :
  `CmscontentCore.json`/`CmsresourceCore.json`/`CmscommentCore.json`
  corriges (plus de colonnes fantomes, relations completes, module.name
  de Comment corrige vers ModuleCms) ; `AdminScreensTest::screens()`
  genere deja les bonnes cles depuis `config/dvups_configurations.php`
  mis a jour (`cmscontent`/`cmsresource`/`cmscomment`), aucune cle en
  dur a changer ; 2 entrees `KNOWN_BROKEN` sans objet retirees
  (`imagecms/form`, `comment/form`) ; confirme par grep qu'aucune
  reference residuelle a `Cmstext`/`Imagecms` ne subsiste dans le code
  (`Tree_item`/`Tree_itemController`/`TreeController` corriges au
  passage, voir G.1/G.2/G.3 ci-dessus).
- Ce qui reste : l'entree `KNOWN_BROKEN` `tree-item-image/form`
  ("depend de ModuleGallery") ne pourra etre retiree qu'une fois G.4
  traite.
- Fichiers concernes : `tests/E2E/AdminScreensTest.php`.

---

## Traductions non encore faites (lang:missing, en.php)

`php devups lang:missing` (execute sur le vrai dossier `lang/`, pas sur
le dossier temporaire de test, apres les renommages CMS de l'ETAPE G -
qui ont ajoute `cmscontent.reference`/`entity.cmscontent` et retire
l'ancienne cle orpheline `cmstext.reference`, et traduit au passage
`non`/`oui`) rapporte desormais 271 cles non traduites dans
`lang/en.php` (valeur = cle, comme prevu par la regle : "pour le reste,
laisse valeur = cle"). Liste complete (ordre alphabetique, tel que
rapporte par la commande) :

#, A constante with same key already exist, Action, Activated, Active,
Admin, Alias, All, Auth_token, Callback, Ce numéro de téléphone existe
déjà, Client, Code, Content, Content <br><i>NB: use : to specify a
variable i.e: :username</i>, Contenu text, Country has not been
specified. important for phonenumber, CreatedAt, Date, Description,
Description of the mail, Dvups_action, Dvups_role, Email, Email model
name, Entity, Entity_class, Entityid, Event i.e: new_message, Export
csv, File, First_at, Folder, Fomat de l'adresse email invalide,
Height, Image, Information mis à jour avec succès, Interval, Item
deleted successfully, Label, Last call, Last_at, Le code d'activation
n'est pas valide. Veuillez entrer de nouveau ou alors renvoyer un
autre code, Level, Line, Line to show, Log, Message, Mot de passe mise
à jour avec succès, Name, Notfication type, Notification, Numéro de
téléphone mise a jour, Object of the mail, Occurrences, Parent id,
Payload, Phonenumber, Position, Profile, Ref, Reference, Save,
Session, Size, Status, Timer (minutes), Title of the mail, Type, Type
of reporting, Un nouveau code d'activation vous a ete envoye, Une
erreur interne est survenue., User, User_ext_id, User_id, Username,
Viewedat, Width, _key, _max, _min, _value, action, alias, auth_token,
birthdate, callback, chain, client, cmscontent.reference, code, code
d'activation vous a été envoyé. Utilisez le pour confirmer le
changement de votre numéro., comment, configuration._key,
configuration._type, configuration._value, configuration.comment,
configuration.id, constraint, content, content_type, context,
continent.id, continent.name, continent.status, country not found,
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
entity, entity.cmscontent, entity.dv_image, entity.notification,
entity.tree_item, entity_class, export, file, fingerprint, first_at,
firstname, height, id, image, interval, interval (J), interval._max,
interval._min, interval._value, interval.label, is_activated, label,
lang, last_at, lastname, le numéro doit etre constitué de 9
caractères, level, line, log, log_file, message, name, next_call,
notification.content, notification.creationdate, notification.entity,
notification.entityid, notification.id, notificationbroadcasted.status,
notificationbroadcasted.viewedat, notificationtype,
notificationtype._key, notificationtype.content, notificationtype.id,
oauth_client.client, oauth_client.name, oauth_client.user_ext_id,
oauth_client.user_id, occurrences, password.notconfirm, payload,
phonenumber, position, pseudo, public_key, push_email.date_end,
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
  271 cles restantes listees ci-dessus via `lang:missing` (valeur =
  cle, non reecrites dans le code - liste mise a jour apres les
  renommages CMS de l'ETAPE G, qui ont ajoute/retire quelques cles).
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

**Phase 4 (CMS) - ETAPE G** : plan (G.1), renommages (G.2/G.3) et
nettoyage generateur/tests (G.5) termines et testes.
- `Cmstext`/`Imagecms` renommes et reconstruits en `Cmscontent`/
  `Cmsresource` (ce dernier etend directement `ImageCore`, plus de
  jointure vers `Dv_image`) ; `Cmscomment` genere proprement (datacore
  corrige, jamais d'entite avant cette session) ; page d'edition admin
  du CMS corrigee et fonctionnelle (`cmscontent/edit?id=`) ; bug latent
  de regression (`Dvups_lang::readCache()` retiree a l'ETAPE E.7,
  jamais appele avant cette session) decouvert et corrige en testant.
- Migration ecrite (non executee,
  `database/migration/2026_11_08_phase4_cms_rename.sql`).
- Tests : `tests/Integration/CmsPhase4Test.php`,
  `tests/E2E/CmscontentEditPageTest.php`.
- Reporte (G.4, voir raisons detaillees plus haut) : suppression de
  `ModuleGallery`/`Dv_image` - bloquee par une dependance directe de
  `ModuleTree`/`Tree_item_image` decouverte pendant la revue (images
  d'arborescence, hors perimetre de la demande initiale), dont le
  traitement est une decision produit (repointer ou abandonner cette
  fonctionnalite) plutot qu'une execution mecanique.

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
5. `database/migration/2026_11_08_phase4_cms_rename.sql` (RENAME
   `cmstext`/`cmstext_lang` -> `cmscontent`/`cmscontent_lang`, creation
   `cmsresource`/`cmscomment`, Phase 4/ETAPE G.2/G.3) - **la table
   `cmsresource` reste vide apres ce script** : peupler depuis
   `imagecms JOIN dv_image` et verifier le nombre de lignes migre avant
   de decommenter le `DROP TABLE imagecms` en fin de fichier (voir son
   commentaire detaille). `dv_image`/`ModuleGallery` ne doivent PAS
   etre supprimes (ModuleTree en depend encore, voir G.4).

Sur l'environnement de test (`devups_phpunit`), aucune de ces
migrations n'est necessaire : le schema est recree automatiquement a
chaque run par Doctrine SchemaTool a partir des entites
(`tests/bootstrap.php`).

## Etat des tests

`composer test` verifie vert a chaque commit de cette session
(ETAPE D : commit `d1f25e3` ; E.1-E.6 : `e5a9863` ; E.7-E.8 : `597cdd6` ;
F.1 : `696cbc5` ; F.2 : `92af6d2` ; README : `e55cf7c` ; lang:scan +
plan G : `5f04c6c` ; ETAPE G.1-G.3/G.5 : voir commit suivant) - a la
fin de la session : **183 tests, 0 echec, 13 "incomplete"** documentes
et assumes dans `tests/E2E/AdminScreensTest.php::KNOWN_BROKEN` (2 de
moins qu'avant l'ETAPE G : `imagecms/form` et `comment/form` sont
devenus `cmsresource/form`/`cmscomment/form`, desormais fonctionnels).
