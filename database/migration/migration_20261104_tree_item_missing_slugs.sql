-- migration_20261104_tree_item_missing_slugs.sql
--
-- [moduletree-6/point1] Pose un slug sur les 12 items `main=1` qui n'en
-- avaient pas (protocole ../aiMemory/PROTOCOLE_moduletree.md §6, U5).
--
-- RAISONNEMENT verifie avant d'ecrire : ajouter un slug la ou il n'y en a
-- pas est additif et ne peut rien casser, puisqu'aucun code ne peut
-- aujourd'hui reference un slug NULL par definition (le slug est la cle
-- machine lue par Tree::constant()/getmainmenu()/childrenOf() - rien ne
-- cherche NULL). Aucun contre-exemple trouve.
--
-- MESURE le 2026-11-04, sur la base locale (agedngyh_app_bd), AVANT ce
-- script :
--
--   SELECT id, tree_id, slug FROM tree_item WHERE main = 1 AND slug IS NULL;
--     -> exactement les 12 lignes ci-dessous, aucune autre.
--
-- CORRECTION d'une affirmation du protocole precedent (§6/§9) : les 12
-- items ne sont PAS repartis sur les arbres gender/device/faq uniquement -
-- l'item id=83 appartient a l'arbre `category` (id 3), un quatrieme arbre
-- non cite jusqu'ici. Verifie par la requete ci-dessus.
--
-- Table de correspondance id -> libelle (langue par defaut) -> slug :
--
--   id | arbre (id)     | libelle            | slug pose
--   ---|----------------|--------------------|-----------------
--   34 | gender (2)     | Adventure          | adventure
--   35 | gender (2)     | Mystère            | mystere
--   36 | gender (2)     | Seinen             | seinen
--   39 | device (10)    | web                | web
--   40 | device (10)    | mobile             | mobile
--   52 | faq (9)        | CONTENT            | content
--   53 | faq (9)        | COMMENT            | comment
--   54 | faq (9)        | OTHER              | other
--   76 | gender (2)     | Shonen             | shonen
--   77 | gender (2)     | African Fantasy    | african_fantasy
--   79 | gender (2)     | Enquète            | enquete
--   83 | category (3)   | Mini Chapitre      | mini_chapitre
--
-- Regle de derivation (deterministe) : minuscules, accents retires, tout
-- ce qui n'est pas [a-z0-9] remplace par "_", doublons de "_" reduits.
--
-- UNICITE (U3, deja posee) verifiee par requete AVANT d'ecrire, pour
-- chacun des 12 candidats, dans son propre tree_id :
--   SELECT id, slug FROM tree_item WHERE tree_id = <tree_id> AND slug = '<candidat>';
--     -> 0 ligne pour les 12 - aucune collision, donc aucun suffixe
--        numerique necessaire.
--
-- Idempotent : chaque UPDATE porte sur un id precis ET `slug IS NULL` -
-- rejoue sans effet si le slug a deja ete pose.
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

UPDATE tree_item SET slug = 'adventure'        WHERE id = 34 AND slug IS NULL;
UPDATE tree_item SET slug = 'mystere'          WHERE id = 35 AND slug IS NULL;
UPDATE tree_item SET slug = 'seinen'           WHERE id = 36 AND slug IS NULL;
UPDATE tree_item SET slug = 'web'              WHERE id = 39 AND slug IS NULL;
UPDATE tree_item SET slug = 'mobile'           WHERE id = 40 AND slug IS NULL;
UPDATE tree_item SET slug = 'content'          WHERE id = 52 AND slug IS NULL;
UPDATE tree_item SET slug = 'comment'          WHERE id = 53 AND slug IS NULL;
UPDATE tree_item SET slug = 'other'            WHERE id = 54 AND slug IS NULL;
UPDATE tree_item SET slug = 'shonen'           WHERE id = 76 AND slug IS NULL;
UPDATE tree_item SET slug = 'african_fantasy'  WHERE id = 77 AND slug IS NULL;
UPDATE tree_item SET slug = 'enquete'          WHERE id = 79 AND slug IS NULL;
UPDATE tree_item SET slug = 'mini_chapitre'    WHERE id = 83 AND slug IS NULL;
