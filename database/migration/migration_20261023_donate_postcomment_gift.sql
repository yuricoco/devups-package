-- migration_20261023_donate_postcomment_gift.sql
--
-- [stickers-4] `donate` accueille le cadeau par sticker, `postcomment` sa
-- trace dans le fil. COLONNES SEULEMENT - aucun comportement, l'endpoint qui
-- les ecrit est le prochain chantier (voir
-- aiMemory/design_2026-10-13_stickers_reactions.md §4 et §6).
--
-- APPLIQUEE EN LOCAL le 2026-10-23. JAMAIS executee en production au
-- moment ou ce fichier est ecrit.
--
-- Pas destructrice : chaque colonne ajoutee est NULLABLE (ou porte un
-- DEFAULT qui ne change aucune ligne existante - `donate.type` a
-- 'donation', `donate.quantity` a 1, exactement ce qu'un don classique
-- deja ecrit represente). `postcomment.comment` est deja nullable en base
-- (verifie par information_schema avant ce fichier, aucune migration
-- necessaire sur cette colonne-la) - confirme ne rien casser (recherche
-- exhaustive des lectures de postcomment.comment dans ce depot,
-- memory_2026-10-23_v1.md).
--
-- Prix/taux/beneficiaires COPIES, jamais relus par jointure plus tard
-- (design note §4) : `unit_price`, `rate_applied`, `share_*`,
-- `sticker_author_id` sont tous nullables, renseignes uniquement sur les
-- lignes `type='gift'` a venir.

ALTER TABLE donate
    ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'donation' AFTER id,
    ADD COLUMN quantity INT NOT NULL DEFAULT 1,
    ADD COLUMN last_tap_at DATETIME DEFAULT NULL,
    ADD COLUMN client_token VARCHAR(100) DEFAULT NULL,
    ADD COLUMN page INT DEFAULT NULL,
    ADD COLUMN unit_price DOUBLE PRECISION DEFAULT NULL,
    ADD COLUMN share_platform DOUBLE PRECISION DEFAULT NULL,
    ADD COLUMN share_sticker_author DOUBLE PRECISION DEFAULT NULL,
    ADD COLUMN share_chapter_author DOUBLE PRECISION DEFAULT NULL,
    ADD COLUMN rate_applied INT DEFAULT NULL,
    ADD COLUMN sticker_id INT DEFAULT NULL,
    ADD COLUMN sticker_author_id INT DEFAULT NULL,
    ADD UNIQUE INDEX UNIQ_DONATE_CLIENT_TOKEN (client_token);

ALTER TABLE donate
    ADD CONSTRAINT FK_DONATE_STICKER FOREIGN KEY (sticker_id) REFERENCES sticker (id) ON DELETE SET NULL,
    ADD CONSTRAINT FK_DONATE_STICKER_AUTHOR FOREIGN KEY (sticker_author_id) REFERENCES user (id) ON DELETE SET NULL;

ALTER TABLE postcomment
    ADD COLUMN donate_id INT DEFAULT NULL;

ALTER TABLE postcomment
    ADD CONSTRAINT FK_POSTCOMMENT_DONATE FOREIGN KEY (donate_id) REFERENCES donate (id) ON DELETE SET NULL;
