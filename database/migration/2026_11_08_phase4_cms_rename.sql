-- Phase 4 (CMS), ETAPE G.2/G.3 : renommage cmstext -> cmscontent et
-- reconstruction de imagecms -> cmsresource (fichier directement sur
-- l'entite, comme Dv_image, au lieu d'une table de jointure vers
-- dv_image) - voir TODO.md section "Phase 4 (CMS)".
--
-- A executer via la commande de migration, APRES avoir verifie le
-- mapping de donnees (aucune perte de contenu CMS reel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), aucune execution
-- necessaire : le schema est recree automatiquement a partir des
-- entites (tests/bootstrap.php).
--
-- ETAPE I : ce script REPREND maintenant les donnees (INSERT INTO
-- cmsresource ... SELECT ... FROM imagecms JOIN dv_image, puis UPDATE
-- tree_item_image ... FROM dv_image) - les fichiers physiques restent
-- a l'endroit ou ils sont deja sur le disque : chaque ligne copiee
-- garde son `uploaddir` D'ORIGINE (celui ecrit par l'ancien Dv_image),
-- jamais force a "cms/"/"treeitem/" (les chemins par defaut utilises
-- pour les NOUVEAUX uploads par Cmsresource::uploadImage()/
-- Tree_item_image::uploadImage()) - aucun deplacement de fichier n'est
-- donc necessaire.
--
-- ETAPE T : les DROP (`imagecms`, `dv_image`) ne sont PLUS dans ce
-- fichier - ils sont dans la migration suivante,
-- 2026_11_08_phase4_drop_old_images.php, qui verifie elle-meme (par
-- comptage) que la reprise ci-dessous est complete avant de supprimer
-- quoi que ce soit (echoue sans rien supprimer sinon).

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

-- Comptage AVANT reprise (a executer et noter a la main) :
-- SELECT COUNT(*) AS avant_imagecms FROM imagecms;

INSERT INTO cmsresource (cmscontent_id, reference, name, image, uploaddir, size, width, height, created_at, updated_at)
  SELECT ic.cmstext_id, di.reference, di.name, di.image, di.uploaddir, di.size, di.width, di.height, di.created_at, di.updated_at
  FROM imagecms ic JOIN dv_image di ON di.id = ic.image_id;

-- Comptage APRES reprise : doit etre IDENTIQUE au comptage "avant" -
-- sinon des lignes imagecms n'ont pas trouve leur dv_image correspondant
-- (jointure INNER JOIN ci-dessus, donc silencieusement ignorees plutot
-- qu'en erreur) ; la migration suivante (ETAPE T) refusera alors de
-- supprimer `imagecms`.
-- SELECT COUNT(*) AS apres_cmsresource FROM cmsresource;

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

-- Comptage AVANT reprise (a executer et noter a la main) :
-- SELECT COUNT(*) AS avant_tree_item_image_avec_image FROM tree_item_image WHERE image_id IS NOT NULL;

UPDATE tree_item_image ti
  JOIN dv_image di ON di.id = ti.image_id
  SET ti.reference = di.reference, ti.name = di.name, ti.image = di.image,
      ti.uploaddir = di.uploaddir, ti.size = di.size, ti.width = di.width,
      ti.height = di.height;

-- Comptage APRES reprise : doit etre IDENTIQUE au comptage "avant" -
-- sinon la migration suivante (ETAPE T) refusera de retirer la colonne
-- image_id et de supprimer `dv_image`.
-- SELECT COUNT(*) AS apres_tree_item_image_avec_image FROM tree_item_image WHERE image IS NOT NULL;
