# Changelog

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Aucune version n'a encore été taguée sur ce dépôt : cette entrée résume
l'ensemble du chantier de durcissement/mise à niveau mené en plusieurs
sessions, avant la première publication.

## [Non publié] - chantier de durcissement (ÉTAPES D à R)

### Ajouté

- **Migrations** : `php devups migrate` / `migrate:status` (table de
  suivi `dvups_migration`) remplacent l'exécution manuelle
  `mysql -u ... < fichier` ; détection automatique des migrations
  destructives (`DROP`/`TRUNCATE`/`DELETE`), confirmation requise
  (`--force` ou `oui` à l'invite), refusées en production sans `--force` ;
  `--dry-run`, `--step` ; formats `.sql` (inchangé) et `.php`
  (`up(PDO $pdo)`).
- **Journalisation applicative** (`dclass/devups/Log.php`, table
  `dvups_log`) : regroupement par empreinte, compteur d'occurrences,
  niveau minimal configurable (`LOG_MIN_LEVEL`), rétention
  (`LOG_RETENTION_DAYS`).
- **Rate limiting générique** (`dclass/devups/RateLimiter.php`) sur le
  login admin, le login front et la vérification/le renvoi de code
  d'activation ; IP cliente déterminée de façon fiable
  (`Util::get_IP_address()`, `TRUSTED_PROXIES`).
- **Réinitialisation de mot de passe** : jeton dédié à usage unique
  (`random_bytes(32)` base64url, seul son `sha256` est stocké,
  `RESET_PASSWORD_TOKEN_TTL_MINUTES`), distinct du code court
  d'activation ; réponse identique que le compte existe ou non (pas
  d'énumération de comptes).
- **"Se souvenir de moi"** sans mot de passe (ni son hash) dans le
  cookie : token aléatoire dont seul le hash est stocké, avec
  expiration, rotation à chaque usage et invalidation à la déconnexion
  ou au changement de mot de passe.
- **Mots de passe** : `password_hash()`/`password_verify()`
  (`Dvups_admin`, `User`) ; migration transparente d'un ancien hash
  `sha1()` au prochain login réussi, sans réinitialisation forcée.
- **Country/Localisation** : données de référence (~195 pays) via
  `php devups location:seed` (idempotent).
- **Web Push réel** (`minishlink/web-push`, clés VAPID dans `.env`) pour
  `Push_subscription::sendWebPush()`, avec suppression automatique d'un
  abonnement expiré (HTTP 410).
- **Documentation** : `php devups api:doc` (instantané OpenAPI),
  `php devups doc:guide` (pages HTML statiques par module/entité),
  Swagger UI back-office (`/admin/apidocs`) distinguant les routes
  front (JWT) et admin (session), protégée par `can('read', 'Documentation')`
  - action "virtuelle" synchronisée par `rights:sync` comme n'importe
  quelle action d'entité, donc attribuable à un rôle non maître.
- **Langues** : `t_lang($cle, $iso, $params = [])` (lecture directe
  hors session) ; `lang:scan`/`lang:missing` basés sur le tokenizer PHP
  (`token_get_all()`, gère correctement les apostrophes échappées) au
  lieu d'une regex.
- `phpstan` (niveau 0, `composer phpstan`) ajouté en dépendance de
  développement.

### Modifié

- **Rupture d'API** : la route de réinitialisation de mot de passe
  passe de `/resetpassword/:id` à `/resetpassword` (le lien e-mail ne
  contient plus `u_id` ni le code court, uniquement le jeton dédié).
- `Util::get_IP_address()` durci en place (même nom/signature) : ne
  fait confiance à `X-Forwarded-For` (et assimilés) que si
  `REMOTE_ADDR` figure dans `TRUSTED_PROXIES`.
- `Notification::on()`/`braodcast()` (faute de frappe historique)
  deviennent des alias dépréciés (`@deprecated` + un avertissement au
  premier appel) de `toUser()`/`toAdmin()`/`toRole()` et `broadcast()`
  - `on()` n'a pas été migré (couvre des cas, entité arbitraire/SMS/
  session, que la nouvelle API ne couvre pas).
- `TableTemplateRender::templateTableRowDetail()` : bug latent corrigé
  (double-indexation de son argument) qui cassait l'écran "détail" de
  **toute** entité admin (`_show`), jamais exercé par un test avant
  cette session.
- Schéma `activationcode_expired_at` (`user`) : `DATE` → `DATETIME`
  (précision à la minute, nécessaire à `ACTIVATION_CODE_TTL_MINUTES`).
- Schéma `emaillog`/`dvups_log` : reconstruits avec un schéma structuré
  (destinataire/sujet/statut/erreur ; empreinte/compteur) au lieu de
  l'ancien format générique `object`/`log`.

### Supprimé (ruptures de compatibilité)

- `ModuleGallery`/`Dv_image` (plus aucune dépendance dans le code :
  `Cmsresource` et `Tree_item_image` héritent désormais directement de
  `ImageCore`).
- `Local_content`/`Local_content_key`/`Local_content_lang`/
  `Page_mapped` (éditeur de traduction en base - les traductions vivent
  dans `lang/<iso2>.php`) ; `langt()` (remplacée par `t_lang()`/`t()`).
- `Continent`/`Department`/`District`/`Region`/`Town`
  (`ModuleLocation`) - seule l'entité `Country` est conservée.
- `Notificationbroadcasted` (entité fantôme jamais générée).
- `ModuleDatabase`/`Request_history` (module mort depuis l'origine,
  jamais alimenté).
- Code mort de push : `pushNotificaiton()`, `cloudMessagingPush()`,
  `fcmPushNotification()`, `testPushNotif()`, colonne
  `Push_subscription::subscription_id`.
- `Cmstext`/`Imagecms` renommés et reconstruits en `Cmscontent`/
  `Cmsresource` (ce dernier porte directement son fichier, comme
  `ImageCore`, au lieu d'une table de jointure).

### Corrigé

- Appels à des méthodes statiques inexistantes trouvés par analyse du
  dépôt (`Util::getIp()` → `Util::get_IP_address()` dans `Country`,
  `*Form::render()` → `*Controller::formView()`/`detailView()` dans
  plusieurs modules legacy).
- `Dvups_role` sans `getAlias()` (cassait l'écran détail du rôle).
- `DBAL::_createDbal()` utilisait `Dvups_lang::all()` (toutes les
  langues) au lieu du cache des langues actives.
- `Dvups_admin::updatePassword()` manquante.
- Détection d'un ancien hash sha1() (`password_get_info()['algo']` vaut
  `null`, jamais `0`) qui empêchait toute migration transparente de mot
  de passe.
- Clé de traduction tronquée (`Aucun élément pour l\'`) retirée de
  `lang/en.php`/`lang/fr.php`.

## Mise à jour depuis la version précédente

Checklist pour un environnement existant (hors base de test, qui est
toujours recréée automatiquement par `tests/bootstrap.php`) :

1. **Migrations** : exécuter `php devups migrate --dry-run` pour
   revoir le SQL, puis `php devups migrate` (confirmer les migrations
   destructives une par une - voir la liste ordonnée et annotée dans
   `TODO.md` section "Migrations"). Les 10 migrations existantes à ce
   jour couvrent, dans l'ordre : nouveau schéma `dvups_log`, suppression
   de l'éditeur de traduction en base, suppression de
   `request_history`, nouveau schéma `emaillog`, renommage CMS (avec
   reprise de données), suppression des tables de localisation
   inutilisées, ajout de colonnes "remember me", passage de
   `activationcode_expired_at` en `DATETIME`, ajout de la colonne
   `push_subscription.endpoint`, ajout des colonnes du jeton de
   réinitialisation de mot de passe.
2. **Nouvelles constantes `.env`** (toutes optionnelles, valeur par
   défaut documentée dans `.env.example` et `config/constante.example.php`) :
   `TRUSTED_PROXIES`, `RESET_PASSWORD_TOKEN_TTL_MINUTES`,
   `ACTIVATION_CODE_TTL_MINUTES`, `RATE_LIMIT_*` (6 variables),
   `LOG_MIN_LEVEL`, `LOG_RETENTION_DAYS`, `VAPID_PUBLIC_KEY`/
   `VAPID_PRIVATE_KEY`/`VAPID_SUBJECT`, `LANG_COLLECT`. En particulier,
   définir `TRUSTED_PROXIES` si l'application est derrière un reverse
   proxy/load balancer (sinon l'IP cliente calculée ignorera
   systématiquement `X-Forwarded-For`, ce qui est le choix sûr par
   défaut mais peut casser un rate limiting basé sur l'IP réelle du
   client).
3. **Ruptures de compatibilité** à vérifier sur du code appelant
   personnalisé (hors coeur du dépôt) : `/resetpassword/:id` →
   `/resetpassword` ; `langt()` supprimée (utiliser `t()`/`t_lang()`) ;
   `ModuleGallery`/`Dv_image`, `Continent`/`Department`/`District`/
   `Region`/`Town`, `Local_content*`/`Page_mapped`,
   `Notificationbroadcasted`, `Request_history` supprimés ;
   `Cmstext`/`Imagecms` renommés en `Cmscontent`/`Cmsresource` ;
   `Notification::on()`/`braodcast()` dépréciées (toujours
   fonctionnelles, mais à migrer vers `toUser()`/`toAdmin()`/
   `toRole()`/`broadcast()` quand possible).
4. Si l'instance tourne derrière un rôle non maître pour la
   documentation (Swagger UI), relancer `php devups rights:sync` pour
   créer l'action `Documentation`/`read` et l'attribuer au(x) rôle(s)
   concerné(s).
