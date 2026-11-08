-- Phase 5 (Localisation), ETAPE J : seul Country est conserve.
-- Continent/Department/District/Region/Town sont retires du code (entites,
-- formulaires, routes, config) - ce script retire les tables correspondantes.
--
-- Recherche effectuee (grep sur tout le depot) : aucune colonne de cle
-- etrangere department_id/district_id/region_id/town_id/continent_id n'a
-- ete trouvee dans un autre module (user, address, etc.) - ces tables
-- n'etaient referencees nulle part ailleurs. Aucune colonne a retirer
-- en dehors de ces 5 tables elles-memes.
--
-- A executer MANUELLEMENT sur chaque environnement concerne (jamais par un
-- script automatise) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_08_phase5_location_drop.sql
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui ne declarent plus ces tables : ne
-- JAMAIS executer ce fichier dessus (inutile, les tables n'y existent plus).
--
-- ATTENTION : destructeur de donnees. Si ces tables contiennent encore des
-- donnees utiles sur un environnement donne, les exporter avant execution
-- (ex. mysqldump --no-create-info <dbname> department district region town
-- continent > backup_location.sql).

DROP TABLE IF EXISTS `department`;
DROP TABLE IF EXISTS `district`;
DROP TABLE IF EXISTS `region`;
DROP TABLE IF EXISTS `town`;
DROP TABLE IF EXISTS `continent`;
