-- Phase 3 (Langues), C.4/C.5/C.7 : l'ancien editeur de traduction en base
-- (Local_content/Local_content_key/Local_content_lang, et page_mapped qui
-- n'etait utilise par rien d'autre) est retire - les traductions vivent
-- desormais dans des fichiers lang/<iso2>.php (voir lang.php, README.md).
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui ne declarent plus ces tables : ne
-- JAMAIS executer ce fichier dessus (inutile, la table n'y existe plus).
--
-- L'ancien t() de lang.php faisait deja `return $ref;` en toute premiere
-- ligne : ces tables ne sont plus lues par aucun code depuis longtemps.
-- Si des traductions saisies manuellement via l'ancien ecran meritent
-- d'etre reprises dans lang/<iso2>.php avant d'executer ce script, les
-- relire d'abord (ex. SELECT * FROM local_content_lang).

DROP TABLE IF EXISTS `local_content_lang`;
DROP TABLE IF EXISTS `local_content_key`;
DROP TABLE IF EXISTS `local_content`;
DROP TABLE IF EXISTS `page_mapped`;
