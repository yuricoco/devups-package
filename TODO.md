# TODO

Suivi des points ouverts, decisions a prendre, et blocages rencontres
pendant le chantier (droits / logs / langues). Chaque entree : phase,
ce qui reste, pourquoi, fichiers concernes.

Note : le "document d'etat du chantier" reference dans la demande
n'existe pas dans le depot au moment de cette session ; ce suivi se
base sur l'historique de conversation (Phase 1 entierement committee
avant cette session) et le code tel que trouve sur disque.

(rempli au fur et a mesure)

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
