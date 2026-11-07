-- migration_20261006.sql
--
-- Volet statistiques, partie 1 : le socle de données.
--
-- Trois changements, dans cet ordre :
--   1. `chapter_user` gagne `duration_seconds` (temps de lecture réel) ;
--   2. `book_stats` échange `recency_score` contre `trend_score` + `final_score` ;
--   3. `stat_history` naît : le gel mensuel qui rend l'évolution lisible.
--
-- POURQUOI `stat_history`
--
-- `book_stats` est CUMULATIF : `Comicbook::workerCompleted()` y écrit des
-- `SUM()` sur toute la vie de la série. C'est un bon score de classement et un
-- très mauvais indicateur d'évolution : « +18 % ce mois-ci » n'est
-- reconstructible nulle part après coup, parce qu'on ne saura jamais ce qui
-- appartenait à quel mois. Les deux `ratetype` de
-- `ComicbookFrontController::index()` qui devaient filtrer sur le mois
-- courant ont d'ailleurs leurs lignes commentées : elles trient donc sur un
-- cumul, ce qui n'est pas ce que leur nom annonce.
--
-- POURQUOI `duration_seconds`
--
-- Aucun canal ne mesure le temps passé sur un chapitre. C'est la seule métrique
-- qui distingue un lecteur qui dévore d'un lecteur qui survole, et rien ne la
-- produit aujourd'hui (`last_page` seul ne dit rien sans le nombre de pages).
-- À plafonner côté client : une application laissée ouverte gonflerait la
-- valeur.
--
-- POURQUOI `recency_score` DISPARAÎT
--
-- Elle n'a jamais été écrite (0 ligne sur 26 dans `book_stats`), et une
-- métrique de récence mesurerait ce que `trend_score` dit déjà en mieux : la
-- comparaison de deux périodes plutôt que le seul regard sur la plus récente.
-- La garder aurait imposé de maintenir deux définitions pour un classement.
--
-- NOTE SUR DOCTRINE : les migrations de ce dossier sont générées depuis les
-- annotations des entités. `Stat_history` a donc son entité
-- (`ModuleComicbook/Entity/Stat_history.php`) et `Chapter_user` /
-- `Book_stats` ont été mis à jour dans le même commit. L'index ajouté sur
-- `analytic` est en revanche écrit à la main : `Analytic` n'est pas une entité
-- Doctrine annotée (elle utilise `ModelTrait` directement), donc aucune
-- migration générée ne le supprimera.

-- ---------------------------------------------------------------------------
-- 1. chapter_user : le temps de lecture
-- ---------------------------------------------------------------------------
ALTER TABLE chapter_user ADD duration_seconds INT DEFAULT NULL;

-- ---------------------------------------------------------------------------
-- 2. book_stats : deux scores vivants, une colonne morte en moins
-- ---------------------------------------------------------------------------
ALTER TABLE book_stats DROP recency_score;
ALTER TABLE book_stats ADD final_score DOUBLE DEFAULT NULL

-- ---------------------------------------------------------------------------
-- 3. stat_history : le gel mensuel
-- ---------------------------------------------------------------------------
-- Clé primaire (book_id, period) : c'est elle qui rend le gel IDEMPOTENT.
-- Un mois n'est jamais « fini » au moment où on le gèle la première fois, donc
-- le worker doit pouvoir le recalculer plusieurs fois sans créer de doublon.
-- Les vues et les lecteurs sont rétro-remplissables (ils sont datés) ; les
-- colonnes d'engagement ne le sont pas, un like de mars remonté en septembre
-- serait daté faux : elles restent donc NULL avant le premier gel réel, et
-- c'est `frozen_at` qui dit si la ligne est un vrai gel ou un rétro-remplissage
-- partiel.
CREATE TABLE stat_history (
    book_id          INT NOT NULL,
    period           CHAR(7) NOT NULL COMMENT 'Mois civil, format AAAA-MM',
    views            INT DEFAULT 0 COMMENT 'Pages lues : chapter_user.page_views + analytic.counter',
    readers          INT DEFAULT 0 COMMENT 'Lecteurs distincts du mois (chapter_user)',
    new_readers      INT DEFAULT 0 COMMENT 'Dont la première lecture de la série tombe ce mois',
    completions      INT DEFAULT 0,
    likes            INT DEFAULT 0,
    shares           INT DEFAULT 0,
    -- Commentaires du mois, comptes depuis `postcomment` (`target = 'chapter'`
    -- + `target_id` + `created_at`) : c'est la SEULE mesure de ce lot qui soit
    -- exactement datable, parce que chaque commentaire porte sa propre date,
    -- contrairement aux comptes incrementaux de `chapter_user`.
    --
    -- Ils ont leur colonne et ne sont PAS un poids de `engagement_score` : les
    -- commentaires sont agreges au niveau de la serie depuis leur propre table,
    -- les compter une deuxieme fois dans un score serait un doublon, et le
    -- poids « commentaires x 3 » de la formule historique pesait zero depuis
    -- toujours (la colonne `chapter_user.comments` n'avait jamais ete ecrite).
    commenters       INT DEFAULT 0,
    -- Somme des temps de lecture du mois, en secondes. NULL et non 0 sur les
    -- lignes de rétro-remplissage : la donnee n'existait pas avant le
    -- 2026-10-06, et 0 voudrait dire « personne n'a lu ».
    duration_seconds INT DEFAULT NULL,
    engagement_score DOUBLE DEFAULT NULL COMMENT 'Même formule que book_stats, calculée sur le mois',
    fidelity_score   DOUBLE DEFAULT NULL,
    trend_score      DOUBLE DEFAULT NULL COMMENT 'Vues du mois / moyenne des mois précédents',
    final_score      DOUBLE DEFAULT NULL COMMENT 'Score composite, écrit par le worker',
    frozen_at        DATETIME DEFAULT NULL COMMENT 'NULL = ligne de rétro-remplissage partiel, jamais un gel réel',
    created_at       DATETIME DEFAULT NULL,
    updated_at       DATETIME DEFAULT NULL,
    -- Le socle Devups ajoute `deleted_at` de lui-meme a toute entite qui
    -- utilise `ModelTrait` (suppression logique) : sans la colonne, la moindre
    -- lecture echoue sur « Unknown column 'stat_history.deleted_at' ».
    deleted_at       DATETIME DEFAULT NULL,
    INDEX IDX_STAT_HISTORY_PERIOD (period),
    PRIMARY KEY (book_id, period)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

UPDATE stat_history SET book_id = NULL WHERE (SELECT COUNT(*) FROM comicbook WHERE id = book_id) = 0;
ALTER TABLE stat_history ADD CONSTRAINT FK_STAT_HISTORY_BOOK FOREIGN KEY (book_id) REFERENCES comicbook (id) ON DELETE CASCADE;

-- ---------------------------------------------------------------------------
-- 4. analytic : index de la lecture par série et par mois
-- ---------------------------------------------------------------------------
-- La route `GET /api/analytic/stats` filtre sur `entity` + une liste de
-- `entity_id` dérivée d'un `comicbook_id`, et découpe sur `updated_at`. La clé
-- primaire de la table commence par `user_id`, donc elle n'aide pas : sans cet
-- index, l'agrégation par série balaie les 41 000 lignes.
CREATE INDEX IDX_ANALYTIC_ENTITY_DATE ON analytic (entity, entity_id, updated_at);

-- ---------------------------------------------------------------------------
-- 5. Rétro-remplissage des mois passés (vues et lecteurs uniquement)
-- ---------------------------------------------------------------------------
-- Deux sources de vues, exactement comme `workerCompleted()` :
--   - les lecteurs identifiés : `chapter_user.page_views`, attribués au mois de
--     leur dernière écriture (c'est le budget de `updated_at`, qui porte le
--     mois où les pages ont réellement été tournées) ;
--   - les lecteurs anonymes : `analytic.counter`, attribués au mois de
--     `updated_at` pour la même raison. Les lignes sans date sont écartées :
--     leur date est perdue (40 143 des 41 000 lignes de la table, soit 12 894
--     des 29 386 vues « chapter »), et les attribuer à un mois inventé serait
--     pire que de ne pas les compter.
-- Les colonnes d'engagement restent NULL : elles ne sont pas datables.
INSERT INTO stat_history (book_id, period, views, readers, created_at, updated_at)
SELECT aggregated.book_id, aggregated.period, SUM(aggregated.pages), COUNT(DISTINCT aggregated.user_id), NOW(), NOW()
FROM (
    SELECT c.comicbook_id AS book_id,
           DATE_FORMAT(cu.updated_at, '%Y-%m') AS period,
           cu.user_id,
           cu.page_views AS pages
    FROM chapter_user cu
    JOIN chapter c ON c.id = cu.chapter_id
    WHERE cu.updated_at IS NOT NULL
      AND cu.page_views > 0
      AND c.comicbook_id IS NOT NULL
    UNION ALL
    SELECT c.comicbook_id AS book_id,
           DATE_FORMAT(a.updated_at, '%Y-%m') AS period,
           0 AS user_id,
           a.counter AS pages
    FROM analytic a
    JOIN chapter c ON c.id = a.entity_id
    WHERE a.entity = 'chapter'
      AND a.updated_at IS NOT NULL
      AND c.comicbook_id IS NOT NULL
) AS aggregated
GROUP BY aggregated.book_id, aggregated.period
ON DUPLICATE KEY UPDATE
    views = VALUES(views),
    readers = VALUES(readers),
    updated_at = VALUES(updated_at);

-- Nouveaux lecteurs : la première lecture de la série tombe dans ce mois.
UPDATE stat_history sh
JOIN (
    SELECT c.comicbook_id AS book_id,
           DATE_FORMAT(MIN(cu.created_at), '%Y-%m') AS first_period,
           COUNT(*) AS total
    FROM chapter_user cu
    JOIN chapter c ON c.id = cu.chapter_id
    WHERE cu.created_at IS NOT NULL
      AND c.comicbook_id IS NOT NULL
    GROUP BY c.comicbook_id, cu.user_id
) AS premiers ON premiers.book_id = sh.book_id AND premiers.first_period = sh.period
SET sh.new_readers = premiers.total;

-- Premiers scores de tendance et score final, calcules sur les lignes
-- fraichement retro-remplies. `trend_score` compare le mois a la moyenne des
-- mois precedents de la meme serie ; sans antecedent, on retombe sur le mois
-- precedent (et a defaut, sur NULL plutot qu'un ratio invente).
--
-- Le ratio est PLAFONNE a 2 des l'ecriture : brut, il n'est pas borne et on a
-- mesure 9,8 en base. Stocker 9,8 n'est pas faux mathematiquement mais n'est
-- pas exploitable (un ecran afficherait « tendance 9,8 », et un tri par cette
-- colonne ecraserait tout le reste). 2 signifie « au moins le double de sa
-- moyenne habituelle », ce qui est deja un signal fort. Le plafond est pose
-- ICI, dans la colonne, pour que tous les lecteurs en heritent, plutot que
-- d'etre repete par chaque appelant.
UPDATE stat_history sh
LEFT JOIN (
    SELECT s1.book_id, s1.period, AVG(s2.views) AS moyenne_precedente
    FROM stat_history s1
    JOIN stat_history s2 ON s2.book_id = s1.book_id AND s2.period < s1.period
    GROUP BY s1.book_id, s1.period
) AS hist ON hist.book_id = sh.book_id AND hist.period = sh.period
SET sh.trend_score = CASE
        WHEN hist.moyenne_precedente IS NULL OR hist.moyenne_precedente = 0 THEN NULL
        ELSE LEAST(sh.views / hist.moyenne_precedente, 2)
    END;

-- final_score, avec des termes NORMALISES.
--
-- Le piège d'échelle, mesuré en base : `engagement_score` monte à 390 sur les
-- séries réelles, alors que `log(1 + vues)` plafonne vers 8 avec 3 700 vues.
-- Additionner les deux bruts ferait disparaître les vues : le « score final »
-- ne serait qu'un classement d'engagement déguisé, et les trois blocs de
-- l'accueil afficheraient les mêmes séries.
--
-- Deuxième piège, rencontré pour de vrai en écrivant cette migration :
-- **`GREATEST(MAX(colonne), 1)` vaut NULL, pas 1, quand toute la colonne est
-- NULL.** MySQL propage le NULL au lieu de le rabattre sur la borne, donc tout
-- le calcul devenait NULL en silence (86 lignes, aucun message d'erreur). Il
-- faut donc `IFNULL` AVANT `GREATEST`. `engagement_score` est justement NULL
-- partout après un rétro-remplissage, puisque l'engagement n'est pas datable.
--
-- Les termes :
--   - vues : `log(1 + vues) / log(1 + 100000)`, dans [0, 1] avec un plafond
--     volontaire à 100 000 pages ;
--   - engagement : normalisé par le maximum du mois ;
--   - tendance : `trend_score / 2`, puisque la colonne est deja plafonnee a 2
--     (voir plus bas). Le ratio brut n'etait pas borne, on en a mesure 9,8.
--
-- Coefficients decides avec l'utilisateur : 0.4 vues, 0.4 engagement, 0.2
-- tendance. Regroupes ici, et repris a l'identique dans
-- `Stat_history::computeFinalScore()` pour que la formule n'existe qu'une fois.
UPDATE stat_history sh
JOIN (SELECT IFNULL(GREATEST(MAX(engagement_score), 1), 1) AS max_engagement FROM stat_history) AS e ON 1 = 1
SET sh.final_score = 0.4 * (LN(1 + IFNULL(sh.views, 0)) / LN(1 + 100000))
                   + 0.4 * (IFNULL(sh.engagement_score, 0) / e.max_engagement)
                   + 0.2 * LEAST(IFNULL(sh.trend_score, 0) / 2, 1);



