-- migration_20261006_b.sql
--
-- Table `share` : un partage enregistre comme un evenement.
--
-- POURQUOI CETTE MIGRATION EST ECRITE A LA MAIN
--
-- Elle aurait du sortir de `php devups schema:update --force`. Le `--dump-sql`
-- a montre que ce n'est PAS possible ici en l'etat : le changeset contient, a
-- cote de la table `share` attendue, deux choses inacceptables :
--
--   1. `DROP INDEX IDX_ANALYTIC_ENTITY_DATE ON analytic` et
--      `DROP INDEX IDX_ANALYTIC_ENTITY_ENTITY_ID ON analytic`. Le premier est
--      l'index de lecture par serie ajoute la veille, et `Analytic` n'est pas
--      une entite annotee Doctrine (elle utilise `ModelTrait` directement), donc
--      le generateur ne le voit pas et propose de le supprimer. L'appliquer
--      ferait retomber l'agregation par serie sur un balayage complet.
--   2. Une trentaine de changements sans aucun rapport avec ce lot : des
--      renommages d'index et de cles etrangeres (slide, comicbook, illustration,
--      member, participant, payout, wallettransaction...), et un
--      `ALTER TABLE subscription DROP order_id` qui SUPPRIME une colonne. C'est
--      une derive preexistante entre la base et le mapping Doctrine, a traiter
--      comme un lot separe et pas en passant.
--
-- Cette migration ne contient donc que ce qui est demande : la table `share`.
-- Le SQL est celui propose par le generateur, tel quel.

CREATE TABLE share (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    uuid VARCHAR(155) DEFAULT NULL,
    channel VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    INDEX IDX_EF069D5AA76ED395 (user_id),
    -- Index de lecture metier : « les partages d'une serie », qui se fait par
    -- l'entite et sa date, comme pour les commentaires.
    INDEX IDX_SHARE_ENTITY_DATE (entity, entity_id, created_at),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

UPDATE share SET user_id = NULL WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0;
ALTER TABLE share ADD CONSTRAINT FK_EF069D5AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
