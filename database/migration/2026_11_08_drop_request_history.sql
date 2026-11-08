-- Phase 2 (Logs), B.7 : ModuleDatabase/Request_history retire - module
-- mort depuis l'origine (aucun code n'ecrit jamais dans cette table,
-- jamais charge par src/requires.php, pas de index.php). Voir TODO.md
-- (avis argumente : a supprimer, pas a garder en lecture seule).
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui ne declarent plus cette table : ne
-- JAMAIS executer ce fichier dessus.

DROP TABLE IF EXISTS `request_history`;
