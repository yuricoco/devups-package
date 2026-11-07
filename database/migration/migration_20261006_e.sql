-- migration_20261006_e.sql
--
-- Passage a l'anglais des tables du programme de subvention, et nouvelle
-- identite de `subvention_award`.
--
-- POURQUOI CE RENOMMAGE
--
-- Les attributs et le code du module avaient ete ecrits en francais. La
-- convention du projet est l'anglais : les colonnes suivent donc le code, pour
-- qu'il n'y ait pas d'ecart permanent entre les deux qu'il faudrait expliquer a
-- chaque relecture. Une migration de renommage conserve les donnees, contrairement
-- a un DROP/CREATE.
--
-- NOUVELLE IDENTITE DE `subvention_award`
--
-- La table portait un `id` auto-incremente et une contrainte unique
-- `(comicbook_id, periode)`. Elle passe a une CLE PRIMAIRE COMPOSITE
-- `(comicbook_id, period)`, ce qui rend la contrainte unique inutile : la cle fait
-- le meme travail. C'est ce qui rend le calcul mensuel rejouable sans doublon.
--
-- `user_id` reste une colonne mais N'ENTRE PAS dans la cle : c'est le
-- proprietaire AU MOMENT DU VERSEMENT, et il doit rester fige meme si la serie
-- change de main. L'identite d'une subvention est « quelle serie, quel mois »,
-- pas « quel beneficiaire ».
--
-- Les statuts passent aussi en anglais. Ils sont mis a jour AVANT le renommage de
-- leur colonne, pour que la correspondance reste lisible.

-- ---------------------------------------------------------------------------
-- subvention_rule : renommage des colonnes
-- ---------------------------------------------------------------------------
ALTER TABLE subvention_rule
    CHANGE libelle label VARCHAR(100) NOT NULL,
    CHANGE chapitres_min min_chapters INT NOT NULL DEFAULT 3,
    CHANGE series_pro_obligatoire pro_series_required TINYINT(1) NOT NULL DEFAULT 1,
    CHANGE categorie_a_pages_min category_a_min_pages INT NOT NULL DEFAULT 12,
    CHANGE categorie_a_pages_max category_a_max_pages INT NOT NULL DEFAULT 16,
    CHANGE categorie_a_jours_max category_a_max_days INT NOT NULL DEFAULT 14,
    CHANGE categorie_b_pages_min category_b_min_pages INT NOT NULL DEFAULT 17,
    CHANGE categorie_b_pages_max category_b_max_pages INT NOT NULL DEFAULT 32,
    CHANGE categorie_b_jours_max category_b_max_days INT NOT NULL DEFAULT 30,
    CHANGE seuil_vues min_views INT NOT NULL DEFAULT 1200,
    CHANGE seuil_likes min_likes INT NOT NULL DEFAULT 12,
    CHANGE seuil_commentaires min_comments INT NOT NULL DEFAULT 9,
    CHANGE poids_vues views_weight DOUBLE PRECISION NOT NULL DEFAULT 60,
    CHANGE poids_likes likes_weight DOUBLE PRECISION NOT NULL DEFAULT 25,
    CHANGE poids_commentaires comments_weight DOUBLE PRECISION NOT NULL DEFAULT 15,
    CHANGE plafond_terme term_cap DOUBLE PRECISION NOT NULL DEFAULT 3,
    CHANGE cagnotte_min min_pool DOUBLE PRECISION NOT NULL DEFAULT 10000,
    CHANGE devise currency VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    CHANGE part_max max_share DOUBLE PRECISION NOT NULL DEFAULT 0.5,
    CHANGE montant_min_versement min_payout DOUBLE PRECISION NOT NULL DEFAULT 500,
    CHANGE actif active TINYINT(1) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------------
-- subvention_pool : renommage des colonnes
-- ---------------------------------------------------------------------------
ALTER TABLE subvention_pool
    CHANGE periode period VARCHAR(7) NOT NULL,
    CHANGE montant_initial initial_amount DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE report_entrant carried_in DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE montant_distribue distributed_amount DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE report_sortant carried_out DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE nb_beneficiaires beneficiaries_count INT NOT NULL DEFAULT 0,
    CHANGE devise currency VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    CHANGE statut status VARCHAR(20) NOT NULL DEFAULT 'open',
    CHANGE calcule_at computed_at DATETIME DEFAULT NULL,
    CHANGE verse_at paid_at DATETIME DEFAULT NULL;

-- Le nom de la contrainte unique ne dit plus rien du contenu une fois `periode`
-- devenue `period` : on la recree sous un nom coherent, en gardant la meme
-- colonne, donc sans changer la contrainte elle-meme.
ALTER TABLE subvention_pool
    DROP INDEX UNIQ_SUBVENTION_POOL_PERIODE,
    ADD UNIQUE KEY UNIQ_SUBVENTION_POOL_PERIOD (period);

-- ---------------------------------------------------------------------------
-- subvention_award : statuts, colonnes, puis identite
-- ---------------------------------------------------------------------------

-- 1. Les statuts, AVANT de renommer leur colonne.
UPDATE subvention_award SET statut = 'selected'      WHERE statut = 'retenu';
UPDATE subvention_award SET statut = 'below_minimum' WHERE statut = 'sous_le_minimum';
UPDATE subvention_award SET statut = 'excluded'      WHERE statut = 'exclu';
UPDATE subvention_award SET statut = 'paid'          WHERE statut = 'verse';

-- 2. Les colonnes.
ALTER TABLE subvention_award
    CHANGE periode period VARCHAR(7) NOT NULL,
    CHANGE commentaires comments INT NOT NULL DEFAULT 0,
    CHANGE chapitres_publies published_chapters INT NOT NULL DEFAULT 0,
    CHANGE pages_moyennes average_pages INT NOT NULL DEFAULT 0,
    CHANGE jours_entre_publications days_between_publications INT DEFAULT NULL,
    CHANGE motif_ineligibilite ineligibility_reason VARCHAR(255) DEFAULT NULL,
    -- `rank_position` et `share_portion` sont choisis pour eviter les MOTS RESERVES,
    -- et c'est une precaution fondee : `PORTION` est reserve en MariaDB, et
    -- `CHANGE part portion DOUBLE PRECISION ...` echoue avec « You have an error
    -- in your SQL syntax ». L'echec se propageait ensuite jusqu'a la pose de la
    -- cle primaire, qui echouait avec « Key column 'period' doesn't exist in
    -- table ». Les deux noms ci-dessous ont ete valides un par un sur des copies
    -- jetables des tables. `period` et `status` sont des mots-cles NON reserves :
    -- ils passent, verifie aussi.
    CHANGE rang rank_position INT DEFAULT NULL,
    CHANGE part share_portion DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE montant amount DOUBLE PRECISION NOT NULL DEFAULT 0,
    CHANGE statut status VARCHAR(20) NOT NULL DEFAULT 'eligible',
    CHANGE motif_exclusion exclusion_reason VARCHAR(255) DEFAULT NULL,
    CHANGE exclu_par excluded_by INT DEFAULT NULL,
    CHANGE exclu_at excluded_at DATETIME DEFAULT NULL,
    CHANGE verse_at paid_at DATETIME DEFAULT NULL,
    CHANGE devise currency VARCHAR(10) NOT NULL DEFAULT 'FCFA';

-- 3. L'ancienne contrainte unique devient inutile : la cle primaire prend sa
--    place. On la retire AVANT de poser la cle, sinon deux index identiques
--    cohabiteraient sur les memes colonnes.
ALTER TABLE subvention_award DROP INDEX UNIQ_SUBVENTION_AWARD;

-- 4. Nouvelle identite. L'ancien `id` disparait : il n'a plus de role, et le
--    garder laisserait deux identites concurrentes pour la meme ligne.
ALTER TABLE subvention_award
    DROP PRIMARY KEY,
    DROP COLUMN id;

ALTER TABLE subvention_award
    ADD PRIMARY KEY (comicbook_id, period);
