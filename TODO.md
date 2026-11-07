# TODO

Suivi des points ouverts, decisions a prendre, et blocages rencontres
pendant le chantier (droits / logs / langues). Chaque entree : phase,
ce qui reste, pourquoi, fichiers concernes.

Note : le "document d'etat du chantier" reference dans la demande
n'existe pas dans le depot au moment de cette session ; ce suivi se
base sur l'historique de conversation (Phase 1 entierement committee
avant cette session) et le code tel que trouve sur disque.

## Phase 2 (Logs)

### B.2 - migration schema dvups_log (a executer manuellement)

- Phase : B.2
- Ce qui reste : executer `database/migration/2026_11_07_dvups_log_schema.sql`
  sur chaque environnement (dev/staging/prod) qui possede encore
  l'ancienne table `dvups_log` (colonnes `object`/`log`, module Config).
  Commande : `mysql -u <user> -p <dbname> < database/migration/2026_11_07_dvups_log_schema.sql`
- Pourquoi : changement de schema reel (nouvelle structure avec
  empreinte/compteur/dates) ; la consigne interdit toute suppression de
  table/colonne/donnee hors base de test. Le fichier est ecrit, non
  execute.
- Fichiers : `database/migration/2026_11_07_dvups_log_schema.sql`,
  `src/devups/ModuleLog/Entity/Dvups_log.php`.

### B.7 - avis sur request_history : a supprimer

- Phase : B.7
- Avis argumente : **supprimer** `ModuleDatabase`/`Request_history`,
  pas le garder en lecture seule. Constats :
  - aucun code n'ecrit jamais dans `request_history` (`grep -rn
    "Request_history::create"` ne trouve rien) : la table n'est
    alimentee par aucun mecanisme (pas de middleware, pas de hook) ;
  - le module n'a pas d'`index.php` et n'est PAS present dans
    `src/requires.php`, contrairement a tous les autres modules : il
    n'est meme pas charge, donc inaccessible en HTTP ;
  - les seules references hors du module lui-meme sont son
    enregistrement dans `config/dvups_configurations.php` et
    `config/module_configurations.php` (metadonnees d'entite), pas des
    usages reels.
  C'est donc un module mort des l'origine (scaffolding genere jamais
  branche), pas un historique a conserver pour audit. Le garder en
  lecture seule donnerait l'illusion d'un historique des requetes qui
  n'a jamais existe.
- Ce qui reste (non fait dans cette session, faute de temps/portee) :
  - ecrire la migration `DROP TABLE request_history` dans
    `database/migration/` (non executee, comme pour toute suppression) ;
  - retirer `ModuleDatabase` de `config/dvups_configurations.php` et
    `config/module_configurations.php` ;
  - supprimer `src/devups/ModuleDatabase/` entierement.
- Fichiers concernes : `src/devups/ModuleDatabase/**`,
  `config/dvups_configurations.php`, `config/module_configurations.php`.

### B.5 - schema Emaillog a refondre (destinataire/sujet/modele/statut/erreur)

- Phase : B.5
- Fait : tous les usages de `Emaillog::create()` qui servaient en
  realite a journaliser des erreurs/evenements NON lies a un email
  reellement envoye (push notifications, SMS, avertissement front sans
  token) ont ete retires et rediriges vers `Log::`. Voir le commit
  "Log: route vers Log:: les usages non-email de Emaillog::create()".
- Ce qui reste : le schema actuel d'`Emaillog` (`reference`, `object`,
  `log`) ne correspond pas a la forme demandee (destinataire, sujet,
  modele, statut, erreur). Les deux usages legitimes restants
  (`dclass/lib/DMail.php`, `src/devupscms/ModuleReporting/Entity/
  Reportingmodel.php::sendMail`) continuent donc a ecrire dans
  l'ancien format.
- Pourquoi bloquant/non fait : refondre le schema demande (1) de
  changer l'entite + le formulaire/datatable/controleur back-office
  d'Emaillog, (2) d'adapter DMail.php et Reportingmodel.php pour
  remplir les nouveaux champs (destinataire = recepteurs reels,
  sujet = objet du mail, modele = template utilise, statut =
  envoye/echec, erreur = message d'exception le cas echeant), et (3)
  d'ecrire la migration correspondante (comme pour dvups_log, non
  executee hors base de test). C'est une portee plus large que le
  reroutage mecanique deja fait, et le temps restant de la session a
  ete priorise sur les autres points de la Phase 2 et la Phase 3.
- Fichiers concernes : `src/devupscms/ModuleReporting/Entity/
  Emaillog.php`, `src/devupscms/ModuleReporting/Controller/
  EmaillogController.php`, `src/devupscms/ModuleReporting/Datatable/
  EmaillogTable.php`, `src/devupscms/ModuleReporting/Form/
  EmaillogForm.php`, `dclass/lib/DMail.php`,
  `src/devupscms/ModuleReporting/Entity/Reportingmodel.php`,
  `database/migration/` (nouvelle migration a ecrire).

### B.6 - ecran Log : JS/vue a finaliser

- Phase : B.6
- Ce qui reste : `Resource/js/dvups_logCtrl.js` et
  `Resource/js/dvups_logForm.js` du module Log sont restes tels que
  generes par `core:g:crud`/`core:g:moduleendless` : ils referencent
  encore des actions create/update qui n'existent plus dans
  `Dvups_logController` (lecture seule par design). La vue detail ne
  rend pas encore le contenu du fichier de debordement
  (`cache/log/<AAAA-MM>/<empreinte>.log`) de maniere formatee dans le
  back-office (seule la lecture brute via `file_exists`/
  `file_get_contents` est codee cote controleur).
- Pourquoi : priorise le coeur fonctionnel (regroupement, debordement
  fichier, gardes globales, tests) plutot que le polish JS/vue dans le
  temps disponible ; l'ecran liste/datatable fonctionne et est couvert
  par `AdminScreensTest` (le formulaire est volontairement absent, cf.
  entree KNOWN_BROKEN ajoutee).
- Fichiers : `src/devups/ModuleLog/Resource/js/dvups_logCtrl.js`,
  `src/devups/ModuleLog/Resource/js/dvups_logForm.js`,
  `src/devups/ModuleLog/Controller/Dvups_logController.php`.

### B.8 - test emaillog manquant

- Phase : B.8
- Ce qui reste : un test verifiant qu'`Emaillog` ne contient QUE des
  emails reellement envoyes (et plus aucun usage push/SMS/evenement)
  n'a pas ete ecrit.
- Pourquoi : depend de la refonte du schema Emaillog (entree B.5
  ci-dessus) pour avoir des champs stables a asserter (destinataire,
  sujet, statut...) ; ecrire un test sur l'ancien schema (`object`/
  `log`) serait a refaire integralement une fois B.5 traite.
- Fichiers : `tests/Integration/` (fichier a creer, ex.
  `EmaillogTest.php`), `src/devupscms/ModuleReporting/Entity/
  Emaillog.php`.

## Phase 3 (Langues)

### C.4/C.5/C.7 - suppression Local_content/Local_content_key/Page_mapped reportee

- Phase : C.4, C.5, C.7
- Avis argumente : ces entites (ancien editeur de traduction en base,
  colonnes `local_content`/`local_content_key`/`local_content_lang`,
  plus `page_mapped` jamais utilise ailleurs) sont mortes au sens
  fonctionnel - l'ancien `t()` de `lang.php` faisait deja `return $ref;`
  en toute premiere ligne, rendant tout le code de requete
  `Local_contentController::getdata()`/`Local_content_key` qui suivait
  injoignable. Elles sont donc candidates a la suppression plutot qu'a
  la conservation en lecture seule.
- Ce qui reste (non fait dans cette session, faute de temps) :
  contrairement a `dvups_log` (Phase 2) ou `request_history`, ces
  entites sont profondement cablees dans `ModuleLang.php` lui-meme (son
  routeur `manage()`/`manageServe()` a base de `switch`, ~20 references
  : instanciation des controleurs, cases de route
  `local-content-key/*`, `local-content/*`, `page-mapped/*`, rendu de
  formulaires) et dans plusieurs vues (`Resource/views/admin/
  overview.blade.php`, `Resource/views/front/setting.blade.php`,
  `web/views/_setting.blade.php`, `Resource/views/admin/
  exportlang.blade.php` referencent `Local_content::classpath()`/
  `generatecacheAction()`), ainsi que dans `Dvups_lang::__delete()`
  (`Local_content::where("lang", $this->iso_code)->delete();`). Une
  suppression correcte demande de reecrire une bonne partie de
  `ModuleLang.php` et ces vues, pas seulement retirer des fichiers -
  trop risque pour le temps restant de cette session.
  A faire dans une session dediee : (1) retirer toutes les references
  listees ci-dessus, (2) retirer l'enregistrement dans
  `config/dvups_configurations.php`/`config/module_configurations.php`,
  (3) ecrire la migration `DROP TABLE local_content, local_content_key,
  local_content_lang, page_mapped` dans `database/migration/` (non
  executee), (4) retirer de `KNOWN_BROKEN`
  (`tests/E2E/AdminScreensTest.php`) les 4 entrees
  `devups/ModuleLang/local-content*` et `devups/ModuleLang/page-mapped/*`
  devenues sans objet (les ecrans n'existeront plus du tout, donc plus
  besoin d'entree).
  C.5 (UI d'edition des traductions) : vu la decision de supprimer
  Local_content, la reponse est "non, plus besoin d'UI d'edition en
  base" - les traductions se modifient desormais directement dans les
  fichiers `lang/<iso2>.php` (ou via `php devups lang:scan`/
  `lang:missing`, voir C.6).
- Fichiers concernes : `src/devups/ModuleLang/ModuleLang.php`,
  `src/devups/ModuleLang/Controller/Local_content*.php`,
  `src/devups/ModuleLang/Controller/Page_mappedController.php`,
  `src/devups/ModuleLang/Entity/Local_content*.php`,
  `src/devups/ModuleLang/Entity/Page_mapped.php`,
  `src/devups/ModuleLang/Entity/Dvups_lang.php` (methode `__delete`),
  `src/devups/ModuleLang/Resource/views/**`, `web/views/_setting.blade.php`,
  `config/dvups_configurations.php`, `config/module_configurations.php`,
  `tests/E2E/AdminScreensTest.php` (KNOWN_BROKEN).

### C.4 - langt() non retire : mails a langue explicite, incompatible avec t()

- Phase : C.4
- Ce qui reste : `langt($fr, $en, $local, $matcher)`
  (`dclass/dvups_functions.php`) n'a pas ete retire ; il est encore
  utilise dans `web/views/mails/invitation.blade.php`,
  `web/views/mails/otp.blade.php`, `web/views/mails/welcome.blade.php`.
- Pourquoi bloquant : ces templates recoivent un `$lang` explicite par
  email (la langue du DESTINATAIRE, qui peut differer de la langue de
  la requete/session en cours - un admin en session "en" peut declencher
  l'envoi d'un email a un utilisateur en "fr"). Le nouveau `t($cle,
  $params=[])` traduit toujours dans la langue COURANTE (`local()`,
  session/URL), pas dans une langue passee explicitement : le remplacer
  tel quel changerait silencieusement la langue des emails envoyes.
  Il manque une variante (ex. `t_lang($cle, $iso, $params=[])` lisant
  directement `lang/<iso>.php` sans dependre de la session) avant de
  pouvoir migrer ces 3 templates et supprimer `langt()`.
- Fichiers : `dclass/dvups_functions.php` (fonction `langt`),
  `web/views/mails/invitation.blade.php`, `web/views/mails/otp.blade.php`,
  `web/views/mails/welcome.blade.php`, `lang.php` (si `t_lang()` est
  ajoute).

### C.6 - lang:scan et lang:missing : fait, limite connue

- Phase : C.6
- Fait : `php devups lang:scan` (trouve les `t('...')` a cle litterale
  et complete `lang/*.php`) et `php devups lang:missing` (liste par
  langue les cles non traduites), ajoutes dans
  `dclass/generator/builder.php` (`dv_lang_scan_keys()` +
  les cases `lang:scan`/`lang:missing`). Teste par
  `tests/Integration/LangScanCommandTest.php`.
- Limite connue : le reperage des cles est une regex simple
  (`\bt\(\s*(['"])...\1`), pas un vrai parseur PHP. Sur un appel ou la
  cle est construite par concatenation (`t("prefix" . $var . "suffix")`),
  elle capture un FRAGMENT de la premiere chaine litterale plutot que de
  l'ignorer proprement (quelques entrees bizarres comme `" . $attribut
  ->name . "` apparaissent dans `lang/*.php` apres un `lang:scan`). Pas
  bloquant pour l'usage normal (cles simples, l'immense majorite du
  code), mais a nettoyer/ameliorer (tokenizer PHP reel) si le besoin
  s'en fait sentir.
- Fichiers : `dclass/generator/builder.php`.

### C.1 - reference iso2 : deja en place, rien a faire

- Phase : C.1
- Constat : `local()` (`dclass/dvups_functions.php`) retourne deja le
  code iso2 courant (session `$_SESSION[LANG]`, ou `Request::get('lang')`
  pour l'URL), et `dvups_lang.iso_code` est deja la colonne de reference
  en base. Les fichiers `lang/<iso2>.php` (nouveaux cette session)
  suivent la meme convention. Rien a changer : la demande etait deja
  satisfaite par le code existant.

---

## Resume phase par phase

**Phase 1 (Droits)** : entierement terminee et committee AVANT cette
session (6/6 points). Non retouchee ici (rien de nouveau a signaler).

**Phase 2 (Logs)** : coeur fonctionnel complet et teste.
- Fait : nouveau module `ModuleLog` (`dvups_log`, regroupement par
  empreinte, debordement fichier) ; API `Log::error/warning/info` ;
  gardes globales (exception/erreur fatale) installees dans
  `header.php`/`admin/header.php` ; ecran back-office lecture seule ;
  migration de schema ecrite (non executee) ; ancien `dvups_log` de
  ModuleConfig retire ; usages non-email de `Emaillog::create()`
  rediriges vers `Log::` ; section "Logs" du README ; tests
  (regroupement, debordement, purge, gardes simulees en sous-processus).
- Reporte (voir TODO ci-dessus, entrees B.5/B.6/B.7/B.8) : refonte du
  schema `Emaillog` (destinataire/sujet/modele/statut/erreur) et le
  test correspondant ; polish JS/vue de l'ecran Log ; suppression de
  `request_history` (avis donne : a supprimer, non execute).

**Phase 3 (Langues)** : socle fonctionnel complet et teste, nettoyage
de l'ancien systeme reporte.
- Fait : reference iso2 deja en place (rien a changer) ; traductions en
  fichiers `lang/<iso2>.php` ; `t($cle, $params=[])` sans aucune requete
  SQL, repli sur la cle, collecte automatique des cles manquantes en
  dev uniquement (ecriture surete, jamais en prod) ; `tt()` simplifie ;
  commandes CLI `lang:scan`/`lang:missing` ; section "Langues" du
  README ; tests unitaires et d'integration couvrant tous les sous-
  points demandes (traduction connue, parametres, repli, non-crash sur
  ancien appel, collecte dev/jamais-prod, absence de SQL, lang:scan).
- Reporte (voir TODO ci-dessus, entrees C.4/C.5/C.7) : suppression de
  l'ancien systeme de traduction en base (`Local_content`,
  `Local_content_key`, `Local_content_lang`, `Page_mapped`) - avis
  donne (a supprimer), non execute car profondement cable dans le
  routeur de `ModuleLang.php` et plusieurs vues ; retrait des entrees
  `KNOWN_BROKEN` correspondantes, conditionne a cette suppression ;
  retrait de `langt()` (templates d'email, bloque par un besoin de
  langue explicite par appel que `t()` ne couvre pas encore).

**Etat des tests** : `composer test` vert a chaque commit de cette
session (169 tests, 0 echec, 18 "incomplete" assumes et documentes
dans `AdminScreensTest::KNOWN_BROKEN`).

**A reprendre en priorite dans une prochaine session** : B.5 (refonte
Emaillog) et C.4/C.5/C.7 (suppression Local_content/Page_mapped) sont
les deux chantiers les plus consequents laisses ouverts ; tout le
reste de cette liste est soit fait, soit une decision/avis deja pris
qui n'attend plus qu'une execution mecanique.
