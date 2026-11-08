-- ETAPE V.4 : trouve en testant l'application reelle de bout en bout -
-- le menu lateral (construit a partir des lignes `dvups_action`
-- groupees par `entity`) affichait encore Local_content/
-- Local_content_key/Page_mapped (Phase 3, Langues - C.4/C.5/C.7),
-- Request_history (Phase 2 - B.7), Imagecms/Dv_image (Phase 4/ETAPE T) -
-- six entites dont le code (Entity/Form/Controller) et la table ont ete
-- entierement retires, mais dont les lignes `dvups_action` (creees par
-- `php devups rights:sync` du temps ou ces modules existaient encore)
-- n'avaient jamais ete nettoyees : rights:sync n'AJOUTE que les actions
-- manquantes, il ne retire jamais les actions orphelines.
--
-- Cliquer sur un de ces liens du menu produisait un "Warning: Undefined
-- array key" (Router.php) suivi d'un message d'erreur generique - la
-- page n'est plus routable, le lien ne menait donc nulle part d'utile.
--
-- dvups_privilege.dvups_action_id -> dvups_action.id est ON DELETE
-- CASCADE : les droits accordes sur ces actions orphelines disparaissent
-- avec elles (aucune perte reelle, ces droits ne servaient plus a rien).
--
-- A executer via la commande de migration (jamais par un script mysql
-- manuel) :
--   php devups migrate
--   (ou, pour revue avant execution : php devups migrate --dry-run)
--
-- Sur l'environnement de test (devups_phpunit), le schema est recree
-- automatiquement a chaque run par Doctrine SchemaTool a partir des
-- entites (tests/bootstrap.php) : dvups_action y est toujours vide au
-- depart (seed par rights:sync si besoin), aucune ligne orpheline n'y
-- existe jamais - ne JAMAIS executer ce fichier dessus.

DELETE FROM `dvups_action`
WHERE `entity` IN (
    'Local_content',
    'Local_content_key',
    'Page_mapped',
    'Request_history',
    'Imagecms',
    'Dv_image'
);
