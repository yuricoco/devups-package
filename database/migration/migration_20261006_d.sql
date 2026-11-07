-- migration_20261006_d.sql
--
-- Programme de subvention 3agReader.
--
-- PRINCIPE
--
-- Chaque mois clos, les series ELIGIBLES se partagent une cagnotte selon des
-- points calcules sur leurs performances du mois. La subvention recompense la
-- publication reguliere et l'engagement ; ce n'est PAS un revenu garanti, et un
-- mois sans serie eligible ne distribue rien. C'est un choix de gestion assume :
-- l'objectif est d'ameliorer la regularite et la notoriete de l'application.
--
-- TROIS TABLES, ET POURQUOI
--
-- `subvention_rule`  : le bareme, EN BASE et non dans le code. Les seuils et les
--                      coefficients sont appeles a bouger selon les objectifs, et
--                      un reglage en base se modifie sans redeploiement. Une
--                      seule ligne `actif = 1` a la fois : le bareme applique a un
--                      mois est celui qui etait actif a ce moment-la.
--
-- `subvention_pool`  : la cagnotte d'un mois (montant initial, report entrant,
--                      distribue, report sortant). Le report est ce qui permet a
--                      une somme non distribuee de ne pas etre perdue, comme le
--                      prevoit le programme.
--
-- `subvention_award` : une ligne par serie et par mois, JOURNAL IMMUABLE des
--                      qu'elle est versee. Elle porte les mesures du mois, les
--                      points, le rang, la part et le montant, donc une
--                      contestation se tranche sur des chiffres figes et non
--                      recalcules.
--
-- MESURES : ELLES VIENNENT DE `stat_history`
--
-- Le gel mensuel calcule deja vues, lecteurs, likes et commentaires par serie et
-- par mois, de facon idempotente. Les recalculer ici creerait une deuxieme
-- formule qui divergerait au premier changement. La subvention LIT donc le mois
-- clos, et ne mesure rien elle-meme.
--
-- `likes` et `commenters` de `stat_history` sont des comptes INCREMENTAUX (la
-- table porte l'etat courant, pas l'historique de l'acte), attribues au mois de
-- la derniere lecture. C'est une approximation assumee et documentee dans le
-- worker ; elle est exacte pour le mois courant.

CREATE TABLE subvention_rule (
    id INT AUTO_INCREMENT NOT NULL,
    libelle VARCHAR(100) NOT NULL,

    -- Conditions d'acces.
    chapitres_min INT NOT NULL DEFAULT 3,
    series_pro_obligatoire TINYINT(1) NOT NULL DEFAULT 1,
    -- Categories de publication : deux couples (pages mini/maxi, jours maxi).
    -- Une serie doit entrer dans L'UNE des deux pour etre eligible.
    categorie_a_pages_min INT NOT NULL DEFAULT 12,
    categorie_a_pages_max INT NOT NULL DEFAULT 16,
    categorie_a_jours_max INT NOT NULL DEFAULT 14,
    categorie_b_pages_min INT NOT NULL DEFAULT 17,
    categorie_b_pages_max INT NOT NULL DEFAULT 32,
    categorie_b_jours_max INT NOT NULL DEFAULT 30,

    -- Conditions de performance, sur le mois concerne.
    seuil_vues INT NOT NULL DEFAULT 1200,
    seuil_likes INT NOT NULL DEFAULT 12,
    seuil_commentaires INT NOT NULL DEFAULT 9,

    -- bareme de points : poids et plafond de chaque terme.
    poids_vues DECIMAL(6,2) NOT NULL DEFAULT 60.00,
    poids_likes DECIMAL(6,2) NOT NULL DEFAULT 25.00,
    poids_commentaires DECIMAL(6,2) NOT NULL DEFAULT 15.00,
    -- Chaque terme est plafonne a ce multiple de son seuil, pour qu'une serie
    -- virale ne rafle pas la cagnotte a elle seule.
    plafond_terme DECIMAL(6,2) NOT NULL DEFAULT 3.00,

    -- Cagnotte et repartition.
    cagnotte_min DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    -- Part maximale d'une seule serie. 0.5 evite qu'un mois a une seule serie
    -- eligible lui verse tout ; le reste est reporte.
    part_max DECIMAL(5,4) NOT NULL DEFAULT 0.5000,
    -- En dessous de ce montant, on ne verse pas : un virement de 12 FCFA coute
    -- plus cher que ce qu'il apporte.
    montant_min_versement DECIMAL(12,2) NOT NULL DEFAULT 500.00,

    actif TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE TABLE subvention_pool (
    id INT AUTO_INCREMENT NOT NULL,
    periode VARCHAR(7) NOT NULL,
    montant_initial DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    report_entrant DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    montant_distribue DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    report_sortant DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    nb_beneficiaires INT NOT NULL DEFAULT 0,
    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    -- `ouvert`  : le mois est en cours, la cagnotte peut encore bouger.
    -- `calcule` : les parts sont arretees, en attente de versement.
    -- `verse`   : les transactions sont creees, le mois est clos.
    statut VARCHAR(20) NOT NULL DEFAULT 'ouvert',
    calcule_at DATETIME DEFAULT NULL,
    verse_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY UNIQ_SUBVENTION_POOL_PERIODE (periode)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

CREATE TABLE subvention_award (
    id INT AUTO_INCREMENT NOT NULL,

    -- Relations : la serie recompensee, et son proprietaire au moment du versement.
    -- Le proprietaire est FIGE ici parce que `comicbook.user_id` peut changer, et
    -- qu'un versement doit rester rattache a qui l'a recu.
    comicbook_id INT NOT NULL,
    user_id INT DEFAULT NULL,

    periode VARCHAR(7) NOT NULL,

    -- Mesures du mois, recopiees depuis `stat_history` : elles sont figees avec
    -- la decision, donc une contestation se tranche sur ces valeurs-la.
    views INT NOT NULL DEFAULT 0,
    readers INT NOT NULL DEFAULT 0,
    likes INT NOT NULL DEFAULT 0,
    commentaires INT NOT NULL DEFAULT 0,
    chapitres_publies INT NOT NULL DEFAULT 0,
    pages_moyennes INT NOT NULL DEFAULT 0,
    jours_entre_publications INT DEFAULT NULL,

    eligible TINYINT(1) NOT NULL DEFAULT 0,
    -- Pourquoi une serie n'est pas eligible, en clair : « vues insuffisantes
    -- (1040 / 1200) ». Un refus doit pouvoir s'expliquer sans relire le code.
    motif_ineligibilite VARCHAR(255) DEFAULT NULL,

    points DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    rang INT DEFAULT NULL,
    part DECIMAL(8,6) NOT NULL DEFAULT 0.000000,
    montant DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    -- `eligible`   : passe les conditions, en attente de calcul
    -- `retenu`     : dans la repartition
    -- `sous_le_min`: part calculee inferieure au minimum de versement
    -- `exclu`      : ecarte a la main (interactions artificielles, litige)
    -- `verse`      : transaction creee
    statut VARCHAR(20) NOT NULL DEFAULT 'eligible',
    -- Trace de l'exclusion manuelle, et de qui l'a decidee.
    motif_exclusion VARCHAR(255) DEFAULT NULL,
    exclu_par INT DEFAULT NULL,
    exclu_at DATETIME DEFAULT NULL,

    transaction_id INT DEFAULT NULL,
    verse_at DATETIME DEFAULT NULL,

    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    -- Une seule decision par serie et par mois : c'est ce qui rend le calcul
    -- rejouable sans creer de doublon.
    UNIQUE KEY UNIQ_SUBVENTION_AWARD (comicbook_id, periode),
    KEY IDX_SUBVENTION_AWARD_PERIODE (periode, statut),
    KEY IDX_SUBVENTION_AWARD_USER (user_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Le bareme par defaut, celui du programme tel qu'il est ecrit. Il est ACTIF,
-- sinon rien ne peut etre calcule, et il est modifiable ensuite.
INSERT INTO subvention_rule
    (libelle, actif, created_at)
VALUES
    ('Programme de subvention 3agReader (bareme initial 2026-10-06)', 1, NOW());
