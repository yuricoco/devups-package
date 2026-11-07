-- migration_20261104_status_entity_key_unique.sql
--
-- [status-2] Chantier `status` (../aiMemory/PROTOCOLE_status.md), point 2.
-- Pose une contrainte UNIQUE (entity, _key) sur `status`, pour empecher a
-- l'avenir le defaut deja rencontre sur `tree.name` (chantier ModuleTree,
-- U1/U3) : deux lignes portant le meme `_key` pour la meme `entity`, qui
-- rendraient `Status::get($key)`/`getStatus($key)` ambigus pour cette
-- entite.
--
-- MESURE le 2026-11-04, sur la base locale (agedngyh_app_bd), AVANT ce
-- script :
--
--   SELECT entity, _key, COUNT(*) c FROM status GROUP BY entity, _key
--   HAVING c>1;
--     -> 0 ligne. Aucun doublon (entity, _key) aujourd'hui (les 7 lignes
--        ont `entity` vide et 7 `_key` tous distincts).
--
-- CONCLUSION : la contrainte peut etre posee sans lister de non-
-- conformite ni attendre de decision du proprietaire - il n'y en a
-- aucune a ce jour. Si ce script est rejoue plus tard sur une base qui EN
-- a, l'ALTER ci-dessous echouera avec une erreur MySQL/MariaDB explicite
-- (doublon de cle) plutot que de corrompre ou de choisir a la place du
-- proprietaire - comportement voulu (meme principe que
-- migration_20261103_tree_identity_constraints.sql).
--
-- NOTE (ne se pose pas en SQL, documentee ici et dans le protocole) :
-- avec `entity` vide sur les 7 lignes actuelles, cette contrainte
-- n'empeche PAS un futur doublon de `_key` seul entre deux lignes qui
-- resteraient toutes deux sans entite (entity='' , entity='') puisque
-- ('', 'draft') et ('', 'draft') SONT un doublon de (entity,_key) - donc
-- en realite la contrainte protege deja ce cas precis. Ce qu'elle ne
-- couvre pas : le choix delibere de laisser `entity` vide pour une ligne
-- "generique" et une autre specifique au meme `_key` - situation non
-- rencontree aujourd'hui, non traitee plus avant (hors mesure).
--
-- Idempotent : meme pattern que les migrations precedentes
-- (information_schema.STATISTICS avant ALTER). Rejouer ce script ne leve
-- aucune erreur et ne modifie rien la deuxieme fois (verifie
-- manuellement).
--
-- Sauvegarde complete de la base prise HORS DEPOT avant le point 1 de ce
-- chantier (toujours valable, aucune donnee supprimee depuis) :
-- C:\tmp\spacekola_backups\agedngyh_app_bd_20261104_184533_pre_status_chantier.sql
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

DELIMITER $$

DROP PROCEDURE IF EXISTS _migration_20261104_status_entity_key_unique $$
CREATE PROCEDURE _migration_20261104_status_entity_key_unique()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'status' AND INDEX_NAME = 'UNIQ_status_entity_key'
    ) THEN
        ALTER TABLE status ADD CONSTRAINT UNIQ_status_entity_key UNIQUE (entity, _key);
    END IF;
END $$
CALL _migration_20261104_status_entity_key_unique() $$
DROP PROCEDURE _migration_20261104_status_entity_key_unique $$

DELIMITER ;
