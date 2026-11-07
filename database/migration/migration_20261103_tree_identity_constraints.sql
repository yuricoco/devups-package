-- migration_20261103_tree_identity_constraints.sql
--
-- [moduletree-1] Pose U1, U3 (contraintes d'identite du protocole
-- ../aiMemory/PROTOCOLE_moduletree.md, point 1). U2, U4, U5, S1 ne se
-- posent pas en SQL (voir MIGRATION-GENRES-NOTES equivalent pour ce
-- chantier : ../aiMemory/PROTOCOLE_moduletree.md §6).
--
-- MESURE le 2026-11-03, sur la base locale (agedngyh_app_bd), AVANT ce
-- script (requetes exactes, sans invention) :
--
--   SELECT name, COUNT(*) c FROM tree GROUP BY name HAVING c>1;
--     -> 0 ligne. Aucun doublon de `tree.name`.
--
--   SELECT id, name FROM tree WHERE name IS NULL OR name NOT REGEXP '^[a-z0-9_]+$';
--     -> 0 ligne. Les 16 arbres existants respectent deja [a-z0-9_]+.
--
--   SELECT COUNT(*) FROM tree WHERE name IS NULL OR name = '';
--     -> 0. `tree.name` est par ailleurs deja NOT NULL au niveau colonne
--        (SHOW CREATE TABLE tree : `name varchar(255) NOT NULL`) - ce
--        script n'a donc rien a faire pour la partie "non nul" de U1.
--
--   SELECT tree_id, slug, COUNT(*) c FROM tree_item
--   WHERE slug IS NOT NULL AND slug != '' GROUP BY tree_id, slug HAVING c>1;
--     -> 0 ligne. Aucun doublon (tree_id, slug).
--
-- CONCLUSION : U1 et U3 peuvent etre posees SANS lister de non-conformite
-- ni attendre de decision du proprietaire - il n'y en a aucune a ce jour.
-- Si ce script est rejoue plus tard sur une base qui EN a (une ligne
-- importee entre-temps, par exemple), les ALTER ci-dessous echoueront
-- avec une erreur MySQL/MariaDB explicite (doublon de cle) plutot que de
-- corrompre ou de choisir a la place du proprietaire - c'est le
-- comportement voulu, pas un defaut de ce script.
--
-- DECOUVERT EN MESURANT, HORS PERIMETRE DE CE SCRIPT (documente dans le
-- protocole, §6 et §9, PAS corrige ici - ce ne sont pas des doublons de
-- (tree_id, slug), donc U3 n'a rien a y redire) :
--   - le slug 'gallery' existe a la fois dans l'arbre 4 (folder) et
--     l'arbre 5 (menu) ;
--   - le slug 'news' existe a la fois dans l'arbre 3 (category) et
--     l'arbre 11 (device_page) ;
--   - CORRECTION d'une affirmation fausse du protocole (§6) : celui-ci
--     citait 'image' comme exemple de slug partage entre deux arbres.
--     Verifie par requete (SELECT id, tree_id, slug FROM tree_item WHERE
--     slug='image') : 0 ligne. 'image' n'existe nulle part comme slug.
--     L'exemple reel est 'gallery'/'news' ci-dessus.
--   - 12 items `main=1` ont un slug NULL (ids 34,35,36,39,40,52,53,54,
--     76,77,79,83 - trees 'gender'(2), 'device'(10), 'faq'(9)) : U5,
--     comptes et listes, non corriges (le proprietaire choisit le slug,
--     pas ce script). Dont 6 des 15 genres du chantier precedent
--     (tree 'gender', id=2) - signale au rapport, § dedie.
--
-- Idempotent : chaque contrainte est ajoutee par une procedure qui
-- verifie d'abord sa presence dans information_schema (MariaDB 10.4.32
-- ne supporte pas "ADD INDEX IF NOT EXISTS" ni "ADD CONSTRAINT IF NOT
-- EXISTS" - verifie par `SELECT VERSION()`). Rejouer ce script ne leve
-- aucune erreur et ne modifie rien la deuxieme fois (verifie
-- manuellement).
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

DELIMITER $$

DROP PROCEDURE IF EXISTS _migration_20261103_tree_u1 $$
CREATE PROCEDURE _migration_20261103_tree_u1()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tree' AND INDEX_NAME = 'UNIQ_tree_name'
    ) THEN
        ALTER TABLE tree ADD CONSTRAINT UNIQ_tree_name UNIQUE (name);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.CHECK_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tree' AND CONSTRAINT_NAME = 'CHK_tree_name_format'
    ) THEN
        ALTER TABLE tree ADD CONSTRAINT CHK_tree_name_format CHECK (name REGEXP '^[a-z0-9_]+$');
    END IF;
END $$
CALL _migration_20261103_tree_u1() $$
DROP PROCEDURE _migration_20261103_tree_u1 $$

DROP PROCEDURE IF EXISTS _migration_20261103_tree_item_u3 $$
CREATE PROCEDURE _migration_20261103_tree_item_u3()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tree_item' AND INDEX_NAME = 'UNIQ_tree_item_tree_slug'
    ) THEN
        -- InnoDB : une cle UNIQUE multi-colonnes autorise plusieurs NULL sur
        -- `slug` (NULL != NULL pour l'unicite) - necessaire puisque U5
        -- documente 12 items main=1 avec slug NULL, legitimes tant qu'ils
        -- ne sont pas destines au cache (C7).
        ALTER TABLE tree_item ADD CONSTRAINT UNIQ_tree_item_tree_slug UNIQUE (tree_id, slug);
    END IF;
END $$
CALL _migration_20261103_tree_item_u3() $$
DROP PROCEDURE _migration_20261103_tree_item_u3 $$

DELIMITER ;
