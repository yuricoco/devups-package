-- ETAPE M.3 (Phase 6, Push) : colonne manquante pour un VRAI envoi Web
-- Push (VAPID, minishlink/web-push) - Push_subscription::getEndpoint()
-- existait deja, mais aucune colonne `endpoint` n'etait declaree : la
-- propriete etait toujours NULL.
--
-- Aucune donnee supprimee.
--
-- A executer MANUELLEMENT sur chaque environnement concerne (jamais par
-- un script automatise) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_08_phase6_push_subscription_endpoint.sql
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui declarent deja cette colonne :
-- ne JAMAIS executer ce fichier dessus.

ALTER TABLE `push_subscription`
    ADD COLUMN `endpoint` TEXT NULL DEFAULT NULL AFTER `status`;
