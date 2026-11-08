-- ETAPE P.1 (Corrections) : jeton dedie a la reinitialisation de mot de
-- passe (random_bytes(32) encode base64url, seul son hash sha256 est
-- stocke), distinct du code court d'activation (colonne `activationcode`,
-- inchangee - toujours utilisee pour l'activation de compte et le SMS).
--
-- Additif uniquement (ADD COLUMN, nullable) : aucune donnee supprimee ni
-- modifiee.
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php, cf. dclass/extends/UserCore.php), qui
-- declarent deja ces colonnes : ne JAMAIS executer ce fichier dessus.

ALTER TABLE `user`
    ADD COLUMN `reset_token_hash` VARCHAR(255) NULL DEFAULT NULL AFTER `activationcode_expired_at`,
    ADD COLUMN `reset_token_expired_at` DATETIME NULL DEFAULT NULL AFTER `reset_token_hash`;
