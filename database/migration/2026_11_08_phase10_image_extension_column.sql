-- ETAPE V.3 : colonne manquante decouverte en comparant le schema reel
-- (apres les migrations ETAPE T/phase4_cms_rename.sql) aux entites via
-- `doctrine orm:schema-tool:update --dump-sql` sur la base de dev.
--
-- dclass/extends/ImageCore.php (MappedSuperclass herite par Cmsresource
-- et Tree_item_image) declare une colonne `extensionnpm` (float,
-- nullable) - jamais ajoutee par phase4_cms_rename.sql ni a `cmsresource`
-- (table neuve) ni a `tree_item_image` (ALTER TABLE qui ajoutait les
-- autres colonnes d'ImageCore, mais pas celle-ci). Sans cette colonne,
-- tout INSERT/UPDATE Doctrine sur ces deux entites echoue ("Unknown
-- column 'extensionnpm'") - Tree_item_image::uploadImage() ecrit meme
-- explicitement dans cette propriete (`$this->extension = ...`).
--
-- Aucune donnee supprimee : ajout additif, nullable.
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php), qui declarent deja cette colonne : ne
-- JAMAIS executer ce fichier dessus.

ALTER TABLE `cmsresource`
    ADD COLUMN `extensionnpm` DOUBLE PRECISION DEFAULT NULL;

ALTER TABLE `tree_item_image`
    ADD COLUMN `extensionnpm` DOUBLE PRECISION DEFAULT NULL;
