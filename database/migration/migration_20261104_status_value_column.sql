-- migration_20261104_status_value_column.sql
--
-- [status-1] Chantier `status` (../aiMemory/PROTOCOLE_status.md), point 1.
-- Ajoute une colonne `value` a la table `status` : l'entier que chaque
-- `_key` designe quand l'information est mesuree et confirmee. Ne touche
-- JAMAIS les colonnes `status`/`admin_status` d'aucune entite (regle dure
-- #2 du mandat : la table DECRIT, elle ne remplace pas).
--
-- MESURE le 2026-11-04, sur la base locale (agedngyh_app_bd), AVANT ce
-- script (requetes exactes, sans invention) :
--
--   SELECT id, _key, entity, position, color FROM status ORDER BY id;
--     -> 7 lignes (1 process, 2 end, 3 suspended, 4 published, 5 draft,
--        6 in_pending, 7 succeed). `entity` vide sur les 7 (mesure
--        separee, point 0a complet dans le protocole).
--
--   SELECT status_id, iso_code, label FROM status_lang JOIN dvups_lang ...;
--     -> labels reels par langue pour les 7 lignes (table complete dans
--        le protocole, §2).
--
--   SELECT status, COUNT(*) FROM comicbook GROUP BY status;
--     -> 0:64, 1:48
--   SELECT admin_status, COUNT(*) FROM comicbook GROUP BY admin_status;
--     -> NULL:1, 1:111
--   SELECT status, COUNT(*) FROM chapter GROUP BY status;
--     -> 0:266, 1:236
--   SELECT admin_status, COUNT(*) FROM chapter GROUP BY admin_status;
--     -> NULL:1, 1:501
--
--   Seules Comicbook et Chapter etendent ScanCore (grep "extends ScanCore"
--   / "extends \ScanCore" sur backend/src/) - ce sont les deux seules
--   entites avec une colonne entiere `status` dans la famille ScanCore.
--   ScanCore porte aussi `admin_status` (indicateur de moderation/
--   autorisation, "Flag as reported" / "Admin Auth" - dimension distincte,
--   confirmee par les commentaires de code de ScanCore/Post/ComicbookTable,
--   detail dans le protocole §1) : la table `status` decrit `status`
--   UNIQUEMENT, pas `admin_status`.
--
-- CORRESPONDANCE _key -> entier, UNIQUEMENT quand confirmee par une
-- correspondance textuelle/semantique directe entre le label reel de
-- status_lang et le commentaire de code faisant autorite
-- (Chapter::getIconestatus(), lignes 250-275) OU le comportement observe
-- (ScanCore, Comicbook::publish()) :
--
--   draft      (id 5) -> 0 : label "draft"/"draft" = commentaire exact
--                             "// draft" ; seule autre valeur reelle de
--                             comicbook.status/chapter.status.
--   published  (id 4) -> 1 : label "published"/"published" = commentaire
--                             exact "// publish" ; ScanCore "status=1 <=>
--                             public/published" ; Comicbook::publish() pose
--                             status=1.
--   in_pending (id 6) -> 4 : label "pending validation"/"En attente de
--                             validation" ~ commentaire "waiting for
--                             validation"/"En Validation" - correspondance
--                             textuelle forte, pas de collision.
--
--   process (id 1), end (id 2), suspended (id 3), succeed (id 7) -> NULL.
--   Aucune correspondance confirmee. Note explicite (regle dure #5,
--   "pas de correspondance devinee") : `end` et `succeed` partagent le
--   MEME libelle anglais reel ("Completed") - une tentative de deviner par
--   proximite semantique avec le switch de Chapter::getIconestatus()
--   (status=2 "in checking", status=3 "checking validate") a ete
--   explicitement examinee et REJETEE : aucun des deux libelles ne
--   correspond textuellement a "in checking" ou "Validate", et la
--   collision end/succeed montre que les libelles eux-memes ne
--   distinguent pas ces etats de maniere fiable. Une description fausse
--   valant moins que pas de description (instruction du mandat), ces
--   quatre lignes restent NULL.
--
-- Idempotent : ADD COLUMN protege par un test information_schema (MariaDB
-- 10.4.32 ne supporte pas "ADD COLUMN IF NOT EXISTS" via DDL direct dans
-- ce contexte de compatibilite - meme pattern que
-- migration_20261103_tree_identity_constraints.sql). Rejouer ce script ne
-- leve aucune erreur et ne modifie rien la deuxieme fois (verifie
-- manuellement). Les UPDATE finaux sont deja idempotents par nature
-- (memes valeurs assignees aux memes `_key`).
--
-- Sauvegarde complete de la base prise HORS DEPOT avant ce script :
-- C:\tmp\spacekola_backups\agedngyh_app_bd_20261104_184533_pre_status_chantier.sql
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

DELIMITER $$

DROP PROCEDURE IF EXISTS _migration_20261104_status_value_column $$
CREATE PROCEDURE _migration_20261104_status_value_column()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'status' AND COLUMN_NAME = 'value'
    ) THEN
        ALTER TABLE status ADD COLUMN value INT NULL DEFAULT NULL AFTER position;
    END IF;
END $$
CALL _migration_20261104_status_value_column() $$
DROP PROCEDURE _migration_20261104_status_value_column $$

DELIMITER ;

-- Population stricte : seules les 3 correspondances confirmees ci-dessus.
-- process/end/suspended/succeed restent NULL (valeur par defaut de la
-- colonne, aucun UPDATE pour eux).
UPDATE status SET value = 0 WHERE _key = 'draft';
UPDATE status SET value = 1 WHERE _key = 'published';
UPDATE status SET value = 4 WHERE _key = 'in_pending';
