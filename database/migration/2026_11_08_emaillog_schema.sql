-- Phase 2 (Logs), B.5/B.8 : remplace l'ancien schema `emaillog`
-- (reference/object/log, generique et non structure) par le schema
-- demande (destinataire/sujet/modele/statut/erreur) - voir
-- src/devupscms/ModuleReporting/Entity/Emaillog.php.
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir de
-- l'entite (tests/bootstrap.php) : ne JAMAIS executer ce fichier dessus.
--
-- La table existante contient un historique d'envois dans un format non
-- structure (object/log) : ce script la remplace plutot que de tenter une
-- conversion colonne a colonne (le format precedent ne distingue pas
-- clairement destinataire/sujet/statut/erreur dans le texte libre). Si cet
-- historique doit etre conserve, l'exporter avant d'executer ce script :
--   SELECT * FROM emaillog;

DROP TABLE IF EXISTS `emaillog`;

CREATE TABLE `emaillog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinataire` varchar(500) NOT NULL,
  `sujet` varchar(500) NOT NULL,
  `modele` varchar(255) DEFAULT NULL,
  `statut` varchar(20) NOT NULL,
  `erreur` longtext,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emaillog_statut` (`statut`),
  KEY `idx_emaillog_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
