-- Phase 4 (CMS), ETAPE G.2/G.3 : renommage cmstext -> cmscontent et
-- reconstruction de imagecms -> cmsresource (fichier directement sur
-- l'entite, comme Dv_image, au lieu d'une table de jointure vers
-- dv_image) - voir TODO.md section "Phase 4 (CMS)".
--
-- A executer MANUELLEMENT sur chaque environnement concerne, APRES
-- avoir verifie le mapping de donnees (aucune perte de contenu CMS
-- reel) :
--   mysql -u <user> -p <dbname> < database/migration/2026_11_08_phase4_cms_rename.sql
--
-- Sur l'environnement de test (devups_phpunit), aucune execution
-- necessaire : le schema est recree automatiquement a partir des
-- entites (tests/bootstrap.php).
--
-- IMPORTANT : la table `cmsresource` creee ci-dessous reste VIDE apres
-- ce script - elle doit etre peuplee depuis `imagecms JOIN dv_image`
-- (le mapping colonne a colonne est direct : dv_image.image/uploaddir/
-- size/width/height -> cmsresource.image/uploaddir/size/width/height,
-- imagecms.cmstext_id -> cmsresource.cmscontent_id) avant le DROP de
-- `imagecms` plus bas. Ce script ne fait PAS cette migration de
-- donnees automatiquement : a verifier et executer a la main (le
-- nombre de lignes migrees doit correspondre au nombre de lignes
-- imagecms existantes) avant de decommenter le DROP.

RENAME TABLE cmstext TO cmscontent;
RENAME TABLE cmstext_lang TO cmscontent_lang;
ALTER TABLE cmscontent_lang CHANGE cmstext_id cmscontent_id INT(11) NOT NULL;

CREATE TABLE `cmsresource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cmscontent_id` int(11) DEFAULT NULL,
  `reference` varchar(123) DEFAULT NULL,
  `name` varchar(123) DEFAULT NULL,
  `description` text,
  `image` varchar(255) NOT NULL,
  `uploaddir` varchar(255) NOT NULL,
  `size` float DEFAULT NULL,
  `width` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cmsresource_cmscontent` (`cmscontent_id`),
  CONSTRAINT `fk_cmsresource_cmscontent` FOREIGN KEY (`cmscontent_id`) REFERENCES `cmscontent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A decommenter UNE FOIS la migration de donnees imagecms->cmsresource
-- verifiee (voir avertissement en tete de fichier) :
-- INSERT INTO cmsresource (cmscontent_id, reference, name, image, uploaddir, size, width, height, created_at, updated_at)
--   SELECT ic.cmstext_id, di.reference, di.name, di.image, di.uploaddir, di.size, di.width, di.height, di.created_at, di.updated_at
--   FROM imagecms ic JOIN dv_image di ON di.id = ic.image_id;
-- DROP TABLE imagecms;

CREATE TABLE `cmscomment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cmscontent_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pseudo` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cmscomment_cmscontent` (`cmscontent_id`),
  KEY `idx_cmscomment_user` (`user_id`),
  CONSTRAINT `fk_cmscomment_cmscontent` FOREIGN KEY (`cmscontent_id`) REFERENCES `cmscontent` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmscomment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La table `comment` legacy (jamais generee, aucune entite ni code
-- ne l'utilisait - voir TODO.md) n'existe sur aucun environnement
-- connu ; aucun DROP necessaire.

-- ETAPE G.4 : suppression de ModuleGallery (dv_image/imagecms).
-- Tree_item_image (ModuleTree) n'a plus de relation `image` (FK vers
-- dv_image) : il herite directement de ImageCore, comme cmsresource
-- plus haut - memes colonnes a ajouter ici.

ALTER TABLE tree_item_image
  ADD COLUMN `reference` varchar(123) DEFAULT NULL AFTER `tree_item_id`,
  ADD COLUMN `name` varchar(123) DEFAULT NULL AFTER `reference`,
  ADD COLUMN `description` text AFTER `name`,
  ADD COLUMN `image` varchar(255) DEFAULT NULL AFTER `description`,
  ADD COLUMN `uploaddir` varchar(255) DEFAULT NULL AFTER `image`,
  ADD COLUMN `size` float DEFAULT NULL AFTER `uploaddir`,
  ADD COLUMN `width` float DEFAULT NULL AFTER `size`,
  ADD COLUMN `height` float DEFAULT NULL AFTER `width`;

-- A decommenter UNE FOIS la migration de donnees image_id->colonnes
-- directes verifiee (meme precaution qu'imagecms->cmsresource plus
-- haut : comparer le nombre de lignes tree_item_image.image_id NOT
-- NULL avant/apres) :
-- UPDATE tree_item_image ti
--   JOIN dv_image di ON di.id = ti.image_id
--   SET ti.reference = di.reference, ti.name = di.name, ti.image = di.image,
--       ti.uploaddir = di.uploaddir, ti.size = di.size, ti.width = di.width,
--       ti.height = di.height;
-- ALTER TABLE tree_item_image DROP FOREIGN KEY <nom_contrainte_image_id>;
-- ALTER TABLE tree_item_image DROP COLUMN image_id;

-- A decommenter UNIQUEMENT une fois les deux migrations de donnees
-- ci-dessus (imagecms->cmsresource ET tree_item_image.image_id ->
-- colonnes directes) verifiees et executees - plus aucun code ne lit
-- `dv_image` apres l'ETAPE G.4, mais la table peut encore contenir des
-- fichiers references nulle part ailleurs :
-- DROP TABLE dv_image;
