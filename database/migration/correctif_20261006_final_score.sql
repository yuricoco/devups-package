-- correctif du 2026-10-06 : recalcul de `stat_history.final_score`
--
-- Le calcul de `final_score` de `migration_20261006.sql` a echoue EN SILENCE
-- lors de la premiere application : `GREATEST(MAX(engagement_score), 1)` vaut
-- NULL quand toute la colonne est NULL (MySQL propage le NULL au lieu de le
-- rabattre sur la borne), donc les 86 lignes sont restees a NULL sans le
-- moindre message d'erreur. `engagement_score` est justement NULL partout
-- apres un retro-remplissage, l'engagement n'etant pas datable.
--
-- La migration d'origine est corrigee (IFNULL avant GREATEST, tendance
-- plafonnee a 2 dans la colonne). Ce fichier rattrape la base de developpement
-- deja migree : il plafonne `trend_score` puis recalcule `final_score`.
--
-- A supprimer une fois que toutes les bases sont passees par la migration
-- corrigee.
UPDATE stat_history
SET trend_score = LEAST(trend_score, 2)
WHERE trend_score IS NOT NULL AND trend_score > 2;

UPDATE stat_history sh
JOIN (SELECT IFNULL(GREATEST(MAX(engagement_score), 1), 1) AS max_engagement FROM stat_history) AS e ON 1 = 1
SET sh.final_score = 0.4 * (LN(1 + IFNULL(sh.views, 0)) / LN(1 + 100000))
                   + 0.4 * (IFNULL(sh.engagement_score, 0) / e.max_engagement)
                   + 0.2 * LEAST(IFNULL(sh.trend_score, 0) / 2, 1);

