
--
-- Structure de la table `dvups_admin`
--

--
-- Structure de la table `dvups_lang`
--


--
INSERT INTO `dvups_lang` (`id`, `name`, `main`, `active`, `iso_code`, `language_code`, `locale`, `date_format_lite`, `date_format_full`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Français (French)', 0, '1', 'fr', 'fr', 'fr', 'd/m/Y', 'd/m/Y H:i:s', NULL, NULL, NULL),
(2, 'English (English)', 1, '1', 'en', 'en-us', 'en', 'm/d/Y', 'm/d/Y H:i:s', NULL, NULL, NULL);

--
-- Déchargement des données de la table `dvups_role`
--

--
-- Structure de la table `dvups_role`
--

INSERT INTO `dvups_role` (`id`, `name`, `alias`) VALUES
(1, 'admin', 'admin');


INSERT INTO `dvups_admin` (`id`, `name`, `login`, `password`, `firstconnexion`, dvups_role_id) VALUES
(1, 'admin', 'dv_admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 1, 1);

--
-- Déchargement des données de la table `dvups_module`
--
  

