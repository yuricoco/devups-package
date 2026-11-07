-- migration_20261023_sticker.sql
--
-- [stickers-3] Cree la table `sticker`. APPLIQUEE EN LOCAL le 2026-10-23,
-- JAMAIS executee en production au moment ou ce fichier est ecrit.
--
-- Colonnes d'`ImageCore` (reference/name/description/image/uploaddir/size/
-- width/height/extensionnpm) + `created_at`/`updated_at`/`deleted_at` : les
-- memes que celles d'`illustration`, qui etend la meme classe - meme
-- convention de ce depot, pas une particularite de ce chantier.
--
-- Pas de rattachement a une oeuvre (aucune colonne comicbook_id/chapter_id) :
-- un sticker appartient a son createur et est utilisable partout (design
-- note aiMemory/design_2026-10-13_stickers_reactions.md §1).
--
-- `unit_price` est un `DOUBLE` comme les autres colonnes d'argent recentes de
-- ce depot AVANT leur passage en DECIMAL ([money-6]) - le prix lui-meme
-- n'est JAMAIS calcule (il vient d'une liste fermee de 5 valeurs exactes,
-- `Sticker::UNIT_PRICES`), donc aucune erreur d'arrondi n'est possible ici.
-- Le calcul de repartition ([stickers-5]) lit cette liste en bcmath, jamais
-- la colonne flottante pour calculer.

CREATE TABLE sticker (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    reference VARCHAR(123) DEFAULT NULL,
    name VARCHAR(123) DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    uploaddir VARCHAR(255) NOT NULL,
    size DOUBLE PRECISION DEFAULT NULL,
    width DOUBLE PRECISION DEFAULT NULL,
    height DOUBLE PRECISION DEFAULT NULL,
    extensionnpm DOUBLE PRECISION DEFAULT NULL,
    unit_price DOUBLE PRECISION NOT NULL,
    status INT NOT NULL DEFAULT 0,
    validated_at DATETIME DEFAULT NULL,
    validated_by INT DEFAULT NULL,
    rejection_reason VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY(id),
    INDEX IDX_STICKER_USER (user_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

ALTER TABLE sticker ADD CONSTRAINT FK_STICKER_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
