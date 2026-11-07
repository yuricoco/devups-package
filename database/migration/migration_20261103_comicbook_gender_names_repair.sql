-- migration_20261103_comicbook_gender_names_repair.sql
--
-- [genres-0] Repare les donnees deja abimees par le bug reproduit et corrige
-- le 2026-11-03 dans `creator/src/pages/forms/Comicbook.vue` : l'intercepteur
-- de reponse axios (`creator/src/plugins/axios.js`) ne deballe pas la reponse
-- (il retourne `response` tel quel, pas `response.data`), donc
-- `response.comicbook.genders` levait une TypeError sur `undefined` a chaque
-- chargement du formulaire. L'exception, avalee par le `.catch()` de la
-- chaine, laissait `comicbook.value.gender_names` a la valeur posee par la
-- ligne precedente : la chaine BRUTE deja en base (ex. "33,34,76"), pas un
-- tableau d'objets {id, name}.
--
-- A l'enregistrement suivant, `for (let item of comicbook.value.gender_names)`
-- iterait alors les CARACTERES de cette chaine (JS `for...of` sur un string),
-- chaque `item.id` valant `undefined`, et `.join()` produisait une sequence de
-- virgules nues - un "," par caractere d'origine. Reproduit en direct le
-- 2026-11-03 sur comicbook.id=12 (gender_names "33,34,76", 8 caracteres) :
-- apres un simple chargement + clic "Enregistrer" sans toucher aux genres,
-- gender_names valait bien ",,,,,,,," (8 virgules) - preuve dans
-- aiMemory/memory_2026-11-03_v1.md §1 et ./MIGRATION-GENRES-NOTES.md §0.
--
-- MESURE le 2026-11-03, sur la base locale (agedngyh_app_bd) :
--   SELECT COUNT(*) FROM comicbook
--   WHERE gender_names IS NOT NULL AND gender_names != ''
--     AND gender_names REGEXP '^[,]+$'
-- -> 6 series deja reduites a une chaine de virgules pures (id 28, 30, 31,
--    34, 35, 36). Cette requete EST la preuve du "depuis quand" demande par
--    le point 0 : elle ne dit pas de date (gender_names ne porte pas de
--    colonne updated_at), mais elle dit le nombre de series deja touchees
--    avant ce correctif.
--
-- Idempotent : ne touche que les lignes encore reduites a des virgules pures
-- (vide ou NULL n'est PAS une virgule pure, donc deja hors de portee) ; rejouer
-- ce script sur une base deja reparee ne change plus aucune ligne (0 lignes
-- affectees la seconde fois).
--
-- NE TOUCHE PAS au pivot `comicbook_gender` : une chaine de virgules pures ne
-- contient aucun identifiant exploitable, il n'y a donc rien a en tirer. Le
-- remplissage du pivot (point 2) l'ignore deja pour la meme raison (son
-- `SUBSTRING_INDEX`/split sur une chaine de virgules pures ne produit que des
-- chaines vides, filtrees par la clause WHERE de ce script-la).
--
-- NON APPLIQUE EN PRODUCTION au moment ou ce fichier est ecrit.

UPDATE comicbook
SET gender_names = NULL
WHERE gender_names IS NOT NULL
  AND gender_names != ''
  AND gender_names REGEXP '^[,]+$';
