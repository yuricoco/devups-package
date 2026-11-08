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
-- ETAPE V : ce grep n'avait pas vu les FK ENTRE ces 5 tables elles-memes
-- (department.region_id -> region, district.town_id -> town,
-- town.department_id -> department) - trouve en executant cette migration
-- sur la base de dev reelle (1451 sur le premier DROP, `department`
-- encore reference par `town`). L'ordre ci-dessous part des tables sans
-- aucune reference entrante restante (district, puis town une fois
-- district supprime, etc.) pour que chaque DROP soit valide au moment ou
-- il s'execute, sans jamais desactiver FOREIGN_KEY_CHECKS.
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
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

DROP TABLE IF EXISTS `district`;
DROP TABLE IF EXISTS `town`;
DROP TABLE IF EXISTS `department`;
DROP TABLE IF EXISTS `region`;
DROP TABLE IF EXISTS `continent`;
