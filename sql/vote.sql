-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 09 déc. 2025 à 08:30
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vote`
--

-- --------------------------------------------------------

--
-- Structure de la table `bulletin_categorie`
--

DROP TABLE IF EXISTS `bulletin_categorie`;
CREATE TABLE IF NOT EXISTS `bulletin_categorie` (
  `id_bulletin` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_vote` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bulletin`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_date_vote` (`date_vote`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bulletin_categorie`
--

INSERT INTO `bulletin_categorie` (`id_bulletin`, `id_jeu`, `id_categorie`, `id_evenement`, `date_vote`) VALUES
(1, 1, 1, 5, '2025-12-09 09:17:49'),
(2, 2, 2, 5, '2025-12-09 09:24:47');

-- --------------------------------------------------------

--
-- Structure de la table `bulletin_final`
--

DROP TABLE IF EXISTS `bulletin_final`;
CREATE TABLE IF NOT EXISTS `bulletin_final` (
  `id_bulletin_final` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_vote` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bulletin_final`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_date_vote` (`date_vote`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bulletin_final`
--

INSERT INTO `bulletin_final` (`id_bulletin_final`, `id_jeu`, `id_evenement`, `date_vote`) VALUES
(1, 1, 5, '2025-12-09 09:19:09');

-- --------------------------------------------------------

--
-- Structure de la table `candidat`
--

DROP TABLE IF EXISTS `candidat`;
CREATE TABLE IF NOT EXISTS `candidat` (
  `id_candidat` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `id_jeu` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `bio` text,
  `motivation` text,
  `photo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_candidat`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_jeu` (`id_jeu`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `candidat`
--

INSERT INTO `candidat` (`id_candidat`, `id_utilisateur`, `nom`, `id_jeu`, `date_inscription`, `status`, `bio`, `motivation`, `photo`) VALUES
(1, 10, 'Jean Marc', 1, '2025-12-01 09:37:02', 'approved', 'Je sais pas qui je suis', NULL, NULL),
(2, 11, 'Abdel-Malek', 2, '2025-12-01 09:38:15', 'approved', 'Je suis un gars chill', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
CREATE TABLE IF NOT EXISTS `categorie` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `id_evenement` int NOT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `id_evenement`, `nom`, `description`) VALUES
(1, 5, 'RPG', 'Un rpg quoi'),
(2, 5, 'MOBA', 'Un moba quoi'),
(3, 6, 'MOBA', ''),
(4, 6, 'RPG', ''),
(5, 6, 'MMORPG', '');

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

DROP TABLE IF EXISTS `commentaire`;
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_jeu` int NOT NULL,
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_commentaire` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_commentaire`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_date` (`date_commentaire`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id_commentaire`, `id_utilisateur`, `id_jeu`, `contenu`, `date_commentaire`) VALUES
(1, 11, 2, 'Allez les bleus', '2025-12-01 09:47:43');

-- --------------------------------------------------------

--
-- Structure de la table `contenu_campagne`
--

DROP TABLE IF EXISTS `contenu_campagne`;
CREATE TABLE IF NOT EXISTS `contenu_campagne` (
  `id_contenu` int NOT NULL AUTO_INCREMENT,
  `id_candidat` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('texte','video','image','message') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'texte',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contenu`),
  KEY `idx_id_candidat` (`id_candidat`),
  KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emargement_categorie`
--

DROP TABLE IF EXISTS `emargement_categorie`;
CREATE TABLE IF NOT EXISTS `emargement_categorie` (
  `id_emargement` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_emargement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_emargement`),
  UNIQUE KEY `unique_emargement_categorie` (`id_utilisateur`,`id_categorie`,`id_evenement`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emargement_categorie`
--

INSERT INTO `emargement_categorie` (`id_emargement`, `id_utilisateur`, `id_categorie`, `id_evenement`, `date_emargement`) VALUES
(1, 7, 1, 5, '2025-12-09 09:17:49'),
(2, 7, 2, 5, '2025-12-09 09:24:47');

-- --------------------------------------------------------

--
-- Structure de la table `emargement_final`
--

DROP TABLE IF EXISTS `emargement_final`;
CREATE TABLE IF NOT EXISTS `emargement_final` (
  `id_emargement_final` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_emargement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_emargement_final`),
  UNIQUE KEY `unique_emargement_final` (`id_utilisateur`,`id_evenement`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emargement_final`
--

INSERT INTO `emargement_final` (`id_emargement_final`, `id_utilisateur`, `id_evenement`, `date_emargement`) VALUES
(1, 7, 5, '2025-12-09 09:19:09');

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

DROP TABLE IF EXISTS `evenement`;
CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_ouverture` datetime NOT NULL,
  `date_fermeture` datetime NOT NULL,
  `statut` enum('preparation','ouvert','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  `nb_max_candidats` int DEFAULT '0',
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_dates` (`date_ouverture`,`date_fermeture`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `nom`, `description`, `date_ouverture`, `date_fermeture`, `statut`, `nb_max_candidats`) VALUES
(5, 'Test', 'AutreTest', '2025-12-09 09:10:00', '2025-12-09 09:30:00', 'ouvert', 0),
(6, 'VOTE2', 'Test123_', '2025-12-09 09:30:00', '2025-12-09 09:41:00', 'preparation', 0);

-- --------------------------------------------------------

--
-- Structure de la table `event_candidat`
--

DROP TABLE IF EXISTS `event_candidat`;
CREATE TABLE IF NOT EXISTS `event_candidat` (
  `id_event_candidat` int NOT NULL AUTO_INCREMENT,
  `id_evenement` int NOT NULL,
  `id_candidat` int NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `statut_candidature` enum('en_attente','approuve','refuse') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `motif_refus` text COLLATE utf8mb4_unicode_ci,
  `date_validation` datetime DEFAULT NULL,
  `valide_par` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_event_candidat`),
  UNIQUE KEY `unique_candidat_categorie` (`id_evenement`,`id_categorie`,`id_candidat`),
  KEY `id_candidat` (`id_candidat`),
  KEY `fk_event_candidat_valideur` (`valide_par`),
  KEY `idx_statut_candidature` (`statut_candidature`),
  KEY `idx_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `event_candidat`
--

INSERT INTO `event_candidat` (`id_event_candidat`, `id_evenement`, `id_candidat`, `id_categorie`, `statut_candidature`, `motif_refus`, `date_validation`, `valide_par`, `date_inscription`) VALUES
(9, 5, 2, NULL, 'approuve', NULL, NULL, NULL, '2025-12-09 09:08:39'),
(10, 5, 1, NULL, 'approuve', NULL, NULL, NULL, '2025-12-09 09:08:48');

-- --------------------------------------------------------

--
-- Structure de la table `export`
--

DROP TABLE IF EXISTS `export`;
CREATE TABLE IF NOT EXISTS `export` (
  `id_export` int NOT NULL AUTO_INCREMENT,
  `id_admin` int NOT NULL,
  `id_evenement` int NOT NULL,
  `type_export` enum('PDF','CSV','JSON') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_export` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_export`),
  KEY `idx_admin` (`id_admin`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_date` (`date_export`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jeu`
--

DROP TABLE IF EXISTS `jeu`;
CREATE TABLE IF NOT EXISTS `jeu` (
  `id_jeu` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `editeur` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_sortie` date DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_jeu`),
  KEY `idx_titre` (`titre`),
  KEY `idx_editeur` (`editeur`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `jeu`
--

INSERT INTO `jeu` (`id_jeu`, `titre`, `editeur`, `image`, `date_sortie`, `description`) VALUES
(1, 'Minecraft', 'Mojang', NULL, NULL, NULL),
(2, 'Domms', 'Mojang', NULL, '2006-01-12', 'C\'est un fake');

-- --------------------------------------------------------

--
-- Structure de la table `journal_securite`
--

DROP TABLE IF EXISTS `journal_securite`;
CREATE TABLE IF NOT EXISTS `journal_securite` (
  `id_journal` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `jeton_vote` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_journal`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_action` (`action`),
  KEY `idx_date` (`date_action`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_securite`
--

INSERT INTO `journal_securite` (`id_journal`, `id_utilisateur`, `action`, `date_action`, `details`, `jeton_vote`) VALUES
(1, 10, 'USER_REGISTRATION', '2025-12-01 09:27:00', 'Type: candidat', NULL),
(2, 10, 'CANDIDAT_CREATION', '2025-12-01 09:37:02', 'Candidat créé avec nouveau jeu: Minecraft', NULL),
(3, 11, 'USER_REGISTRATION', '2025-12-01 09:37:35', 'Type: candidat', NULL),
(4, 11, 'CANDIDAT_CREATION', '2025-12-01 09:38:15', 'Candidat créé avec nouveau jeu: Domms', NULL),
(5, 11, 'CAMPAGNE_COMMENT_ADD', '2025-12-01 09:47:43', 'Commentaire sur jeu: 2', NULL),
(6, 8, 'ADMIN_USER_STATUS_CHANGE', '2025-12-08 08:45:57', 'Utilisateur 10: is_active = 0', NULL),
(7, 8, 'ADMIN_USER_STATUS_CHANGE', '2025-12-08 08:48:27', 'Utilisateur 10: is_active = 1', NULL),
(8, 8, 'ADMIN_EVENT_CREATE', '2025-12-08 08:49:36', 'Événement créé: Evenement 1', NULL),
(9, 8, 'ADMIN_EVENT_CREATE', '2025-12-08 09:11:55', 'Événement créé: Eveneùent test', NULL),
(10, 8, 'ADMIN_CANDIDAT_APPROVE', '2025-12-08 09:36:09', 'Candidat 2 approuvé', NULL),
(11, 8, 'ADMIN_CANDIDAT_APPROVE', '2025-12-08 09:36:12', 'Candidat 1 approuvé', NULL),
(12, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 08:56:37', 'Événement créé: Test', NULL),
(13, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:08:31', 'Événement créé: Test', NULL),
(14, 7, 'VOTE_CATEGORIE', '2025-12-09 09:17:49', 'Catégorie 1, événement 5', NULL),
(15, 7, 'VOTE_FINAL', '2025-12-09 09:19:09', 'Événement: 5', NULL),
(16, 7, 'VOTE_CATEGORIE', '2025-12-09 09:24:47', 'Catégorie 2, événement 5', NULL),
(17, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:25:58', 'Événement créé: VOTE2', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `nomination`
--

DROP TABLE IF EXISTS `nomination`;
CREATE TABLE IF NOT EXISTS `nomination` (
  `id_nomination` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  PRIMARY KEY (`id_nomination`),
  UNIQUE KEY `unique_nomination` (`id_jeu`,`id_categorie`,`id_evenement`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `nomination`
--

INSERT INTO `nomination` (`id_nomination`, `id_jeu`, `id_categorie`, `id_evenement`) VALUES
(2, 1, 1, 5),
(3, 1, 2, 5),
(1, 2, 1, 5),
(4, 2, 2, 5);

-- --------------------------------------------------------

--
-- Structure de la table `registre_electoral`
--

DROP TABLE IF EXISTS `registre_electoral`;
CREATE TABLE IF NOT EXISTS `registre_electoral` (
  `id_registre` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_registre`),
  UNIQUE KEY `unique_inscription` (`id_utilisateur`,`id_evenement`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `registre_electoral`
--

INSERT INTO `registre_electoral` (`id_registre`, `id_utilisateur`, `id_evenement`, `date_inscription`) VALUES
(2, 7, 5, '2025-12-09 09:10:06'),
(3, 12, 5, '2025-12-09 09:23:45');

-- --------------------------------------------------------

--
-- Structure de la table `resultat`
--

DROP TABLE IF EXISTS `resultat`;
CREATE TABLE IF NOT EXISTS `resultat` (
  `id_resultat` int NOT NULL AUTO_INCREMENT,
  `id_evenement` int NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `id_jeu` int NOT NULL,
  `nb_voix` int NOT NULL DEFAULT '0',
  `pourcentage` decimal(5,2) DEFAULT NULL,
  `classement` int DEFAULT NULL,
  `type_resultat` enum('categorie','final') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_resultat`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_type` (`type_resultat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_inscription` date NOT NULL,
  `type` enum('joueur','admin','candidat') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'joueur',
  `salt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `email`, `mot_de_passe`, `date_inscription`, `type`, `salt`, `is_active`, `last_login`, `is_banned`) VALUES
(7, 'joueur@gmail.com', '060e66eaea04d5a2d341b88fa528f43046c17ad10e87d17d368bba866ee2417f', '2025-11-28', 'joueur', '4a0af2da25573f8331acd0935f98b62a', 1, NULL, 0),
(8, 'admin@gmail.com', '156db2432c7b76e5f52baf1de004bedde825fb8f5e5bbc959d6b502760dd4388', '2025-11-28', 'admin', '72b2a54a9d629df77a7900b5e3d1ed29', 1, NULL, 0),
(9, 'candidat@gmail.com', 'a147de687146bd9e5dc97c97fb5e9d7e6cd6e98b3dc7780b046dcce4cdb692bf', '2025-11-28', 'candidat', 'd3faad88d5f5162a1bfb533ee9ffbda1', 1, NULL, 0),
(10, 'Test123_@gmail.com', '1c3ee693d6620f9f76603ae44265498849423e1f70d81de49da4367c3aa1b9d3', '2025-12-01', 'candidat', '710f23669962bf8b4999fcecab73fd72', 1, NULL, 0),
(11, 'Test1234_@gmail.com', '06a485700038dc926dd1577cd2180990da0b689d9264225464c1a26cc31a1b51', '2025-12-01', 'candidat', 'ee42c630f88136e738be16ec1ed532b7', 1, NULL, 0),
(12, 'joueur2@gmail.com', '8351c3cb020816a8afb090e2f1e9f87e2a15854de04db8f23a524275a0e1e1d0', '2025-12-09', 'joueur', 'bde3d4163b97007c1d9e5427e7d02336', 1, NULL, 0);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_candidats_stats`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_candidats_stats`;
CREATE TABLE IF NOT EXISTS `v_candidats_stats` (
`bio` text
,`date_inscription` datetime
,`email` varchar(255)
,`id_candidat` int
,`id_utilisateur` int
,`jeu_titre` varchar(255)
,`nb_commentaires` bigint
,`nb_contenus` bigint
,`photo` varchar(500)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_candidatures_details`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_candidatures_details`;
CREATE TABLE IF NOT EXISTS `v_candidatures_details` (
`candidat_email` varchar(255)
,`candidat_nom` varchar(255)
,`categorie_nom` varchar(255)
,`date_inscription` datetime
,`date_validation` datetime
,`evenement_nom` varchar(255)
,`evenement_statut` enum('preparation','ouvert','cloture')
,`id_candidat` int
,`id_categorie` int
,`id_evenement` int
,`id_event_candidat` int
,`id_jeu` int
,`jeu_image` varchar(500)
,`jeu_titre` varchar(255)
,`motif_refus` text
,`statut_candidature` enum('en_attente','approuve','refuse')
,`valide_par_email` varchar(255)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_peut_voter_categorie`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_peut_voter_categorie`;
CREATE TABLE IF NOT EXISTS `v_peut_voter_categorie` (
`id_categorie` int
,`id_evenement` int
,`id_utilisateur` int
,`peut_voter` varchar(3)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_votes_categorie`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_votes_categorie`;
CREATE TABLE IF NOT EXISTS `v_votes_categorie` (
`id_categorie` int
,`id_evenement` int
,`id_jeu` int
,`nb_votes` bigint
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_votes_final`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_votes_final`;
CREATE TABLE IF NOT EXISTS `v_votes_final` (
`id_evenement` int
,`id_jeu` int
,`nb_votes` bigint
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_candidats_stats`
--
DROP TABLE IF EXISTS `v_candidats_stats`;

DROP VIEW IF EXISTS `v_candidats_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_candidats_stats`  AS SELECT `c`.`id_candidat` AS `id_candidat`, `c`.`id_utilisateur` AS `id_utilisateur`, `u`.`email` AS `email`, `j`.`titre` AS `jeu_titre`, `c`.`bio` AS `bio`, `c`.`photo` AS `photo`, `c`.`date_inscription` AS `date_inscription`, count(distinct `cc`.`id_contenu`) AS `nb_contenus`, count(distinct `com`.`id_commentaire`) AS `nb_commentaires` FROM ((((`candidat` `c` join `utilisateur` `u` on((`c`.`id_utilisateur` = `u`.`id_utilisateur`))) left join `jeu` `j` on((`c`.`id_jeu` = `j`.`id_jeu`))) left join `contenu_campagne` `cc` on((`c`.`id_candidat` = `cc`.`id_candidat`))) left join `commentaire` `com` on((`c`.`id_jeu` = `com`.`id_jeu`))) GROUP BY `c`.`id_candidat` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_candidatures_details`
--
DROP TABLE IF EXISTS `v_candidatures_details`;

DROP VIEW IF EXISTS `v_candidatures_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_candidatures_details`  AS SELECT `ec`.`id_event_candidat` AS `id_event_candidat`, `ec`.`id_evenement` AS `id_evenement`, `e`.`nom` AS `evenement_nom`, `e`.`statut` AS `evenement_statut`, `ec`.`id_categorie` AS `id_categorie`, `cat`.`nom` AS `categorie_nom`, `ec`.`id_candidat` AS `id_candidat`, `c`.`nom` AS `candidat_nom`, `c`.`id_jeu` AS `id_jeu`, `j`.`titre` AS `jeu_titre`, `j`.`image` AS `jeu_image`, `u`.`email` AS `candidat_email`, `ec`.`statut_candidature` AS `statut_candidature`, `ec`.`date_inscription` AS `date_inscription`, `ec`.`date_validation` AS `date_validation`, `ec`.`motif_refus` AS `motif_refus`, `admin`.`email` AS `valide_par_email` FROM ((((((`event_candidat` `ec` join `evenement` `e` on((`ec`.`id_evenement` = `e`.`id_evenement`))) left join `categorie` `cat` on((`ec`.`id_categorie` = `cat`.`id_categorie`))) join `candidat` `c` on((`ec`.`id_candidat` = `c`.`id_candidat`))) join `utilisateur` `u` on((`c`.`id_utilisateur` = `u`.`id_utilisateur`))) left join `jeu` `j` on((`c`.`id_jeu` = `j`.`id_jeu`))) left join `utilisateur` `admin` on((`ec`.`valide_par` = `admin`.`id_utilisateur`))) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_peut_voter_categorie`
--
DROP TABLE IF EXISTS `v_peut_voter_categorie`;

DROP VIEW IF EXISTS `v_peut_voter_categorie`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_peut_voter_categorie`  AS SELECT `r`.`id_utilisateur` AS `id_utilisateur`, `r`.`id_evenement` AS `id_evenement`, `c`.`id_categorie` AS `id_categorie`, (case when (`e`.`id_emargement` is null) then 'OUI' else 'NON' end) AS `peut_voter` FROM ((`registre_electoral` `r` join `categorie` `c` on((`c`.`id_evenement` = `r`.`id_evenement`))) left join `emargement_categorie` `e` on(((`e`.`id_utilisateur` = `r`.`id_utilisateur`) and (`e`.`id_categorie` = `c`.`id_categorie`) and (`e`.`id_evenement` = `r`.`id_evenement`)))) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_votes_categorie`
--
DROP TABLE IF EXISTS `v_votes_categorie`;

DROP VIEW IF EXISTS `v_votes_categorie`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_votes_categorie`  AS SELECT `bulletin_categorie`.`id_evenement` AS `id_evenement`, `bulletin_categorie`.`id_categorie` AS `id_categorie`, `bulletin_categorie`.`id_jeu` AS `id_jeu`, count(0) AS `nb_votes` FROM `bulletin_categorie` GROUP BY `bulletin_categorie`.`id_evenement`, `bulletin_categorie`.`id_categorie`, `bulletin_categorie`.`id_jeu` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_votes_final`
--
DROP TABLE IF EXISTS `v_votes_final`;

DROP VIEW IF EXISTS `v_votes_final`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_votes_final`  AS SELECT `bulletin_final`.`id_evenement` AS `id_evenement`, `bulletin_final`.`id_jeu` AS `id_jeu`, count(0) AS `nb_votes` FROM `bulletin_final` GROUP BY `bulletin_final`.`id_evenement`, `bulletin_final`.`id_jeu` ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bulletin_categorie`
--
ALTER TABLE `bulletin_categorie`
  ADD CONSTRAINT `bulletin_categorie_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_categorie_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_categorie_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bulletin_final`
--
ALTER TABLE `bulletin_final`
  ADD CONSTRAINT `bulletin_final_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_final_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD CONSTRAINT `categorie_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaire_ibfk_2` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contenu_campagne`
--
ALTER TABLE `contenu_campagne`
  ADD CONSTRAINT `fk_contenu_candidat` FOREIGN KEY (`id_candidat`) REFERENCES `candidat` (`id_candidat`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emargement_categorie`
--
ALTER TABLE `emargement_categorie`
  ADD CONSTRAINT `emargement_categorie_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_categorie_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_categorie_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emargement_final`
--
ALTER TABLE `emargement_final`
  ADD CONSTRAINT `emargement_final_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_final_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `event_candidat`
--
ALTER TABLE `event_candidat`
  ADD CONSTRAINT `event_candidat_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_candidat_ibfk_2` FOREIGN KEY (`id_candidat`) REFERENCES `candidat` (`id_candidat`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_candidat_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_candidat_valideur` FOREIGN KEY (`valide_par`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `export`
--
ALTER TABLE `export`
  ADD CONSTRAINT `export_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `export_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `journal_securite`
--
ALTER TABLE `journal_securite`
  ADD CONSTRAINT `journal_securite_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `nomination`
--
ALTER TABLE `nomination`
  ADD CONSTRAINT `nomination_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `nomination_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `nomination_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `registre_electoral`
--
ALTER TABLE `registre_electoral`
  ADD CONSTRAINT `registre_electoral_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `registre_electoral_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Contraintes pour la table `resultat`
--
ALTER TABLE `resultat`
  ADD CONSTRAINT `resultat_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultat_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE SET NULL,
  ADD CONSTRAINT `resultat_ibfk_3` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
