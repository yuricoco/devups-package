-- migration_20261103_comicbook_gender_pivot_fill.sql
--
-- [genres-2] Remplit `comicbook_gender` depuis la chaine `comicbook.gender_names`,
-- sans l'ecraser ni la supprimer (transition en deux temps du point 5 : le
-- creator continue de l'envoyer, ./MIGRATION-GENRES-NOTES.md en dit plus).
--
-- MESURE le 2026-11-03, sur la base locale (agedngyh_app_bd), AVANT ce
-- script :
--   - comicbook_gender : 21 lignes, 8 comicbook_id distincts (deja rempli
--     partiellement par un autre chemin - ce script FUSIONNE, il ne part pas
--     d'un pivot vide) ;
--   - comicbook.gender_names non vide et non reduit a des virgules pures :
--     37 series (6 autres etaient reduites a des virgules pures - reparees
--     par migration_20261103_comicbook_gender_names_repair.sql, A JOUER AVANT
--     CELUI-CI) ;
--   - le plus grand nombre de jetons separes par virgule observe dans
--     gender_names : 6 (la borne `n <= 20` ci-dessous est une marge, pas une
--     mesure).
--
-- Idempotent : `INSERT IGNORE` sur la cle composite (comicbook_id, gender_id)
-- - rejouer ce script n'insere plus aucune ligne la seconde fois (verifie
-- manuellement, voir MIGRATION-GENRES-NOTES.md §2).
--
-- ORPHELINS (identifiants numeriques qui n'existent plus dans tree_item) :
-- la jointure interne sur tree_item les exclut deja de l'INSERT. La requete
-- de COMPTAGE/LISTE separee plus bas (lecture seule, jamais executee comme
-- partie de la migration elle-meme) les denombre pour le rapport.
--
-- JETONS NON NUMERIQUES : 5 series (id 26, 32, 33, 37, 38) ont gender_names
-- rempli de NOMS de genres en toutes lettres ("Action", "Aventure", ...) au
-- lieu d'identifiants - une TROISIEME forme de corruption, distincte du bug
-- du point 0, decouverte en mesurant ce point. La clause REGEXP '^[0-9]+$'
-- les exclut de l'INSERT (un CAST implicite en entier les aurait tous
-- silencieusement rabattus sur 0) ; ce script ne tente AUCUNE resolution
-- nom -> identifiant (risque de faux rapprochement sur une colonne
-- traduite, et au moins deux de ces chaines portent deja un encodage
-- corrompu - "EnquÃªte" pour "Enquête" - signe d'un bug d'encodage ANTERIEUR
-- et distinct, hors perimetre de ce chantier). Ces 5 series restent donc
-- SANS pivot tant qu'un createur ne rouvre pas leur fiche et ne re-selectionne
-- pas leurs genres dans le formulaire desormais corrige (point 0) - la
-- meme reparation manuelle que pour les 6 series a virgules pures.
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

INSERT IGNORE INTO comicbook_gender (comicbook_id, gender_id)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 20
),
split AS (
    SELECT
        cb.id AS comicbook_id,
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cb.gender_names, ',', seq.n), ',', -1)) AS token
    FROM comicbook cb
    JOIN seq ON seq.n <= 1 + LENGTH(cb.gender_names) - LENGTH(REPLACE(cb.gender_names, ',', ''))
    WHERE cb.gender_names IS NOT NULL AND cb.gender_names != ''
)
SELECT split.comicbook_id, CAST(split.token AS UNSIGNED)
FROM split
JOIN tree_item ti ON ti.id = CAST(split.token AS UNSIGNED)
WHERE split.token REGEXP '^[0-9]+$';

-- Lecture seule, pour le rapport : jetons numeriques orphelins (ne pointent
-- plus vers un tree_item existant). A executer separement, jamais comme
-- partie de l'INSERT ci-dessus (qui les exclut deja par sa jointure).
--
-- WITH RECURSIVE seq AS (
--     SELECT 1 AS n UNION ALL SELECT n + 1 FROM seq WHERE n < 20
-- ),
-- split AS (
--     SELECT cb.id AS comicbook_id,
--            TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(cb.gender_names, ',', seq.n), ',', -1)) AS token
--     FROM comicbook cb
--     JOIN seq ON seq.n <= 1 + LENGTH(cb.gender_names) - LENGTH(REPLACE(cb.gender_names, ',', ''))
--     WHERE cb.gender_names IS NOT NULL AND cb.gender_names != ''
-- )
-- SELECT split.comicbook_id, split.token
-- FROM split
-- LEFT JOIN tree_item ti ON ti.id = CAST(split.token AS UNSIGNED)
-- WHERE split.token REGEXP '^[0-9]+$' AND ti.id IS NULL;
