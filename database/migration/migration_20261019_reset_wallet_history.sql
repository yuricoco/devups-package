-- migration_20261019_reset_wallet_history.sql
--
-- ============================================================================
-- NON EXECUTE EN PRODUCTION AU MOMENT OU CE FICHIER EST ECRIT ([money-4],
-- 2026-10-14). Deja execute en LOCAL uniquement, sur une copie de la base de
-- production, apres TROIS sauvegardes verifiees (mysqldump complet, table
-- d'archive, capture des soldes) - voir aiMemory/memory_2026-10-19_v1.md.
-- ============================================================================
--
-- CE QUE CE FICHIER DETRUIT, EN PRODUCTION, SI ON L'EXECUTE :
--   - 468 lignes `wallettransaction` (322 `type='credit'`, 146 `type='debit'`)
--     - ces chiffres sont ceux mesures sur la copie LOCALE le 2026-10-14 ;
--       RECOMPTER avant d'executer en production, les nombres y seront
--       probablement differents ;
--   - les 2 lignes `wallettransaction` dont `wallet_id IS NULL` (deja
--     incluses dans les 468 ci-dessus sur la copie locale - verifie que ce
--     soit aussi le cas en production, sinon ajuster le compte) ;
--   - PUIS ecrase `wallet.amount`/`wallet.bonus`/`wallet.total` des 1430
--     portefeuilles (recompter en production) avec la somme recalculee des
--     seules lignes restantes (`kola`, `donation`).
--
-- Arbitrage du proprietaire du projet (2026-10-14) : la monnaie n'est pas
-- encore active en production, les donnees heritees 2022-2024 sont jetees.
-- `kola` (11 lignes) et `donation` (9 lignes) sont CONSERVEES : recentes
-- (2026-07/09), ecrites par du code vivant et joignable, sur des comptes
-- identifiables.
--
-- PREALABLE OBLIGATOIRE, dans cet ordre, AVANT d'executer ce fichier en
-- production :
--   1. mysqldump complet de la base de production, horodate, hors du depot ;
--   2. `CREATE TABLE wallettransaction_archive_<date> AS SELECT * FROM
--      wallettransaction WHERE type IN ('credit','debit');` puis verifier le
--      compte obtenu ;
--   3. `CREATE TABLE wallet_snapshot_<date> AS SELECT id AS wallet_id, amount,
--      bonus, total FROM wallet WHERE amount <> 0 OR bonus <> 0 OR total <> 0;`
-- Si l'une des trois echoue, NE PAS CONTINUER.

-- --- 1. suppression des lignes heritees ----------------------------------
DELETE FROM wallettransaction WHERE type IN ('credit', 'debit');
DELETE FROM wallettransaction WHERE wallet_id IS NULL;

-- --- 2. recalcul de TOUS les portefeuilles (pas seulement ceux qui ont une
--        ligne wallettransaction : des portefeuilles peuvent porter un solde
--        non nul sans aucune ligne, voir le rapport) ------------------------
UPDATE wallet w
LEFT JOIN (
    SELECT wallet_id, SUM(amount) s
    FROM wallettransaction
    WHERE type IN ('kola', 'donation')
    GROUP BY wallet_id
) t ON t.wallet_id = w.id
SET w.amount = COALESCE(t.s, 0),
    w.bonus  = 0,
    w.total  = COALESCE(t.s, 0);
