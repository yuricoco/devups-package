-- Phase 7 (Mots de passe), ETAPE K : migration transparente sha1() ->
-- password_hash() au prochain login reussi (voir dv_verify_password() dans
-- dclass/dvups_functions.php, Dvups_admin::verifyPassword(),
-- UserCore::verifyPassword()) - AUCUNE donnee a transformer ici, les
-- anciens hash sha1() restent lisibles et sont remplaces un par un, par le
-- code applicatif, a la prochaine connexion reussie de chaque compte.
--
-- Colonnes du "remember me" (K.3) : le cookie ne contient plus le hash du
-- mot de passe mais un token aleatoire dont seul le hash (sha256) est
-- stocke ici, avec une expiration - jamais le mot de passe ni son hash.
--
-- `password` reste VARCHAR(255) pour dvups_admin et user : suffisant pour
-- un hash password_hash() (bcrypt ~60 caracteres, argon2id jusqu'a ~97) -
-- aucun agrandissement de colonne necessaire.
--
-- A executer MANUELLEMENT sur chaque environnement concerne (jamais par un
-- script automatise) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_08_phase7_password_hash.sql
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui declarent deja ces colonnes : ne
-- JAMAIS executer ce fichier dessus (inutile, les colonnes y existent
-- deja).

ALTER TABLE `dvups_admin`
    ADD COLUMN `remember_token_hash` VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN `remember_token_expires_at` DATETIME NULL DEFAULT NULL;
