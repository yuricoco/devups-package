
--
-- Déchargement des données de la table `dvups_lang`
--

INSERT INTO `dvups_lang` (`id`, `name`, `main`, `active`, `iso_code`, `language_code`, `locale`, `date_format_lite`, `date_format_full`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Français (French)', 0, '1', 'fr', 'fr', 'fr', 'd/m/Y', 'd/m/Y H:i:s', NULL, NULL, NULL),
(2, 'English (English)', 1, '1', 'en', 'en-us', 'en', 'm/d/Y', 'm/d/Y H:i:s', NULL, NULL, NULL);

--
-- Déchargement des données de la table `dvups_role`
--

INSERT INTO `dvups_role` (`id`, `name`, `alias`) VALUES
(1, 'admin', 'admin');


INSERT INTO `dvups_admin` (`id`, `name`, `login`, `password`, `firstconnexion`, dvups_role_id) VALUES
(1, 'admin', 'dv_admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 1, 1);

--
-- Déchargement des données de la table `dvups_module`
--

--
-- Déchargement des données de la table `dvups_entity`
--

INSERT INTO `dvups_entity` (`id`, `name`,`url`, `dvups_module_id`) VALUES
(1, 'dvups_admin','dvups-admin', 1),
(2, 'dvups_role', 'dvups-role', 1);
INSERT INTO `dvups_entity_lang` (`lang_id`, `dvups_entity_id`, `label`) VALUES
                                                                            (1, 1, 'Admin'),
                                                                            (2, 1, 'Admin'),
                                                                            (1, 2, 'Role'),
                                                                            (2, 2, 'Role');


--
-- Déchargement des données de la table `dvups_right`
--

INSERT INTO `dvups_right` (`id`, `name`) VALUES
(1, 'create'),
(2, 'read'),
(3, 'update'),
(4, 'delete');

--
-- Déchargement des données de la table `dvups_right_dvups_role`
--

INSERT INTO `dvups_right_dvups_role` (`id`, `dvups_role_id`, `dvups_right_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4);

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Déchargement des données de la table `dvups_role_dvups_admin`
--

INSERT INTO `dvups_role_dvups_admin` (`id`, `dvups_admin_id`, `dvups_role_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Déchargement des données de la table `dvups_role_dvups_module`
--


