-- ETAPE L.1 (Durcissement) : l'expiration des codes a 6 chiffres
-- (activation, changement de telephone, reinitialisation de mot de passe)
-- a besoin d'une precision a la minute (defaut 15 min,
-- ACTIVATION_CODE_TTL_MINUTES) - la colonne etait declaree en DATE (sans
-- heure), ce qui rendait toute expiration courte impossible a representer
-- (et, de toute facon, cette colonne n'etait jamais lue nulle part avant
-- cette etape - voir UserFrontController::checkActivationCode()).
--
-- Aucune donnee supprimee : les valeurs existantes (minuit, faute d'heure)
-- sont converties en DATETIME telles quelles par MySQL.
--
-- A executer MANUELLEMENT sur chaque environnement concerne (jamais par un
-- script automatise) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_08_phase8_rate_limit_activation_expiry.sql
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui declarent deja cette colonne en
-- DATETIME : ne JAMAIS executer ce fichier dessus.

ALTER TABLE `user`
    MODIFY COLUMN `activationcode_expired_at` DATETIME NULL DEFAULT NULL;
