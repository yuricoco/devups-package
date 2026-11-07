-- migration_20261018.sql
--
-- MIGRATION DES 16 COLONNES D'ARGENT VERS DECIMAL.
--
-- ============================================================================
-- APPLIQUEE EN LOCAL le 2026-10-14 ([money-6]), apres les trois sauvegardes
-- de l'etape 1 ([money-3]/[money-4]/[money-5] de ce meme chantier) et la
-- remise a zero des types `credit`/`debit` ([money-4]). JAMAIS EXECUTEE EN
-- PRODUCTION au moment ou ce fichier est ecrit - voir
-- ../../../aiMemory/memory_2026-10-19_v1.md pour la preuve (comparaison
-- valeur par valeur avant/apres, cinq lectures d'API exercees).
-- ============================================================================
--
-- ORDRE D'APPLICATION EN PRODUCTION, si ce fichier y est un jour execute :
--   1. les trois sauvegardes de l'etape 1 de aiMemory/memory_2026-10-19_v1.md
--      (mysqldump complet, table d'archive wallettransaction, capture des
--      soldes) ;
--   2. la remise a zero (migration_20261019_reset_wallet_history.sql) ;
--   3. CE fichier.
-- Executer celui-ci SANS avoir fait la remise a zero au prealable modifierait
-- quand meme les TYPES (sans perte, voir plus bas), mais les colonnes
-- `wallet`/`wallettransaction` porteraient alors encore les 468 lignes
-- `credit`/`debit` heritees.
--
-- Pourquoi DECIMAL et jamais FLOAT/DOUBLE : argent en virgule flottante
-- binaire (IEEE 754) accumule des erreurs d'arrondi (0.1 + 0.2 != 0.3) sur des
-- colonnes qui se SOMMENT (soldes, totaux de commission) - voir
-- aiMemory/design_2026-10-13_stickers_reactions.md (version a jour : kolacoin
-- a 2 decimales, DECIMAL jamais float) et aiMemory/memory_2026-10-16_v2.md
-- §7 constat 8.
--
-- MESURE (2026-10-14, base locale) : aucune des 16 colonnes ne porte plus de
-- 2 decimales reelles (une exception a 1 decimale : product.kolacoin), et
-- AUCUNE ligne existante ne change de valeur sous DECIMAL(n,2) - verifie ICI,
-- apres application, par comparaison valeur par valeur (pas seulement avant
-- l'ecriture du fichier comme au commit precedent).
--
-- CORRECTIF DE RAPPORT : aiMemory/memory_2026-10-18_v1.md disait a tort que
-- les 4 colonnes `donate` etaient annotees `type="float"` dans Donate.php -
-- elles sont en realite annotees `type="integer"`, EN ACCORD avec le type
-- reel `int(11)`. Les passer en DECIMAL(n,2) est donc un CHANGEMENT DE
-- MODELE assume (l'ancien modele : « le kolacoin n'a pas de decimale »), pas
-- une correction de derive. Donate.php est modifie dans le meme commit
-- (annotations `type="integer"` -> `type="float"`, seul type reconnu par ce
-- cadre ORM pour une valeur decimale - aucune entite de ce depot n'utilise
-- `type="decimal"`, qui n'existe pas comme type reconnu).
--
-- Precision (p,s) choisie par colonne, s=2 partout, p avec marge au-dela du
-- maximum mesure APRES la remise a zero (etape 2) et la suppression de la
-- ligne de test payout id=38 ([money-6]) :

-- --- wallet ------------------------------------------------------------
-- max mesure (apres remise a zero) : amount=30, bonus=0, total=30.
-- DECIMAL(12,2) : marge tres large, un portefeuille de lecteur reste petit
-- par construction (credite par ses propres achats).
ALTER TABLE wallet
    MODIFY COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN bonus  DECIMAL(10,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN total  DECIMAL(12,2) NOT NULL DEFAULT 0;

-- --- payout --------------------------------------------------------------
-- max mesure (ligne de test id=38 a 999999 supprimee, voir [money-6]) : 321
-- (reste des lignes de test nommees, non supprimees - non demande). Maximum
-- REEL (hors lignes nommees Test/Victime/Usurpation) : 250. DECIMAL(12,2) :
-- marge large car c'est un montant de retrait, potentiellement important
-- pour un createur dont les gains se sont accumules.
ALTER TABLE payout
    MODIFY COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0;

-- --- transaction -----------------------------------------------------------
-- max mesure : amount=500, kolacoin=50, amount_before=200, amount_after=500.
-- amount_before/amount_after sont un registre CUMULATIF (le solde courant
-- d'un createur, construit par clonage de la derniere ligne - voir
-- memory_2026-10-16_v2.md §7 constat 7) : marge large retenue.
ALTER TABLE `transaction`
    MODIFY COLUMN amount         DECIMAL(12,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN kolacoin       DECIMAL(10,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN amount_before  DECIMAL(12,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN amount_after   DECIMAL(12,2) NOT NULL DEFAULT 0;

-- --- product ---------------------------------------------------------------
-- max mesure : kolacoin=900 (1 decimale reelle presente), price=9000.
ALTER TABLE product
    MODIFY COLUMN kolacoin DECIMAL(10,2) NULL,
    MODIFY COLUMN price    DECIMAL(12,2) NULL;

-- --- wallettransaction -------------------------------------------------
-- max mesure (apres suppression credit/debit) : amount=50. `total` : 0 ligne
-- non nulle mesuree - elargie quand meme, par coherence avec `amount`.
ALTER TABLE wallettransaction
    MODIFY COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    MODIFY COLUMN total  DECIMAL(12,2) NULL;

-- --- donate ------------------------------------------------------------
-- max mesure : amount=500, kolacoin=50, fee=30, net_to_pay=500. Changement de
-- modele assume (int(11) + annotation "integer" -> DECIMAL(n,2) + annotation
-- "float", voir plus haut).
ALTER TABLE donate
    MODIFY COLUMN amount      DECIMAL(10,2) NULL,
    MODIFY COLUMN kolacoin    DECIMAL(10,2) NULL,
    MODIFY COLUMN fee         DECIMAL(10,2) NULL,
    MODIFY COLUMN net_to_pay  DECIMAL(10,2) NULL;

-- --- payout.commission ([money-7], ajoutee ici pour que la migration de
--      production porte tout en un seul passage) --------------------------
-- N'existe pas aujourd'hui (voir aiMemory/memory_2026-10-16_v2.md §7 constat
-- 1). DECIMAL(10,2), nullable : la commission n'est calculee qu'a la
-- creation de la demande (stockee une fois pour toutes, jamais reevaluee).
ALTER TABLE payout
    ADD COLUMN commission DECIMAL(10,2) NULL DEFAULT NULL AFTER amount;
