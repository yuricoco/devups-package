-- migration_20261006_c.sql
--
-- Date de lecture explicite sur `chapter_user` et `analytic`.
--
-- POURQUOI CETTE COLONNE EXISTE
--
-- Le gel mensuel (`Stat_month_worker`) borne ses periodes sur `updated_at`, et
-- cette colonne est FAUSSE dans la majorite des cas. Mesure du 2026-10-06 :
--
--   chapter_user : 2 791 lignes sur 3 382 (83 %) ont `updated_at` NULL
--   analytic     : 40 143 lignes sur 40 885 (98 %) ont `updated_at` NULL
--
-- La cause est mecanique : `ChapterFrontController::read()` ne pose PAS
-- `updated_at` a la CREATION d'une ligne, seulement lors des mises a jour
-- ulterieures. Une ligne creee et jamais relue reste donc sans date, et le gel
-- mensuel l'ignore totalement.
--
-- Consequence mesuree sur le mois 2026-07 : `stat_history` annonce 2 618 vues
-- alors que `chapter_user` contient 23 807 pages sur la meme fenetre (par
-- `created_at`), soit un rapport de 9. Et 1 896 lignes n'ont NI `created_at` NI
-- `updated_at`, donc elles sont invisibles a toute analyse temporelle.
--
-- CE QUE LA COLONNE CHANGE
--
-- `read_at` est posee a CHAQUE lecture, sans exception, par tous les chemins
-- d'ecriture. Elle devient la seule source de verite temporelle de ces deux
-- tables, et le gel mensuel comme le programme de subvention s'y appuient.
--
-- Le retro-remplissage est une RECONSTRUCTION, pas une verite : il prend la
-- meilleure date disponible, en le disant.

ALTER TABLE chapter_user
    ADD COLUMN read_at DATETIME DEFAULT NULL AFTER page_views,
    ADD INDEX IDX_CHAPTER_USER_READ_AT (read_at);

ALTER TABLE analytic
    ADD COLUMN read_at DATETIME DEFAULT NULL AFTER counter,
    ADD INDEX IDX_ANALYTIC_READ_AT (read_at);

-- Retro-remplissage : `created_at` d'abord, parce que la ligne est creee a la
-- PREMIERE lecture, donc c'est la seule date qui soit reellement une date de
-- lecture. `updated_at` ne sert que s'il manque, en sachant qu'il date la
-- derniere ecriture et non la lecture.
--
-- Les lignes ou les deux sont NULL restent NULL : inventer une date, par exemple
-- avec la date de creation du chapitre, daterait une lecture de 2026 a la
-- parution de 2024. Elles sont comptees a part pour etre connues, pas masquees.
UPDATE chapter_user
SET read_at = COALESCE(created_at, updated_at)
WHERE read_at IS NULL AND (created_at IS NOT NULL OR updated_at IS NOT NULL);

UPDATE analytic
SET read_at = COALESCE(created_at, updated_at)
WHERE read_at IS NULL AND (created_at IS NOT NULL OR updated_at IS NOT NULL);
