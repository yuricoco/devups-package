-- Phase 2 (Logs) : remplace l'ancienne table `dvups_log` (ModuleConfig,
-- colonnes object/log, generique et non structuree) par le schema du
-- nouveau module Log dedie (regroupement par empreinte, cf.
-- dclass/devups/Log.php et src/devups/ModuleLog/).
--
-- A executer MANUELLEMENT sur chaque environnement concerne (jamais par un
-- script automatise) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_07_dvups_log_schema.sql
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir de
-- l'entite (tests/bootstrap.php) : ne JAMAIS executer ce fichier dessus.
--
-- La table existante contient des erreurs journalisees, pas des donnees
-- metier : ce script la remplace plutot que de tenter une conversion
-- colonne a colonne depuis l'ancien format (object/log) qui ne porte pas
-- l'information necessaire (empreinte, compteur, dates) au nouveau schema.

DROP TABLE IF EXISTS `dvups_log`;

CREATE TABLE `dvups_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(64) NOT NULL,
  `level` varchar(20) NOT NULL,
  `entity_class` varchar(255) DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `message` varchar(1000) DEFAULT NULL,
  `occurrences` int(11) NOT NULL DEFAULT 1,
  `first_at` datetime DEFAULT NULL,
  `last_at` datetime DEFAULT NULL,
  `context` longtext,
  `log_file` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dvups_log_fingerprint` (`fingerprint`),
  KEY `idx_dvups_log_last_at` (`last_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
