-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 11 déc. 2025 à 06:42
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

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `update_event_statuts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_event_statuts` ()   BEGIN
    -- Passer en ouvert_categories si la date d'ouverture est atteinte
    UPDATE evenement 
    SET statut = 'ouvert_categories'
    WHERE statut = 'preparation' 
    AND NOW() >= date_ouverture 
    AND NOW() < date_fermeture;
    
    -- Passer en ferme_categories si la date de fermeture catégories est atteinte
    UPDATE evenement 
    SET statut = 'ferme_categories'
    WHERE statut = 'ouvert_categories' 
    AND NOW() >= date_fermeture 
    AND (date_debut_vote_final IS NULL OR NOW() < date_debut_vote_final);
    
    -- Passer en ouvert_final si la date de début du vote final est atteinte
    UPDATE evenement 
    SET statut = 'ouvert_final'
    WHERE statut IN ('ouvert_categories', 'ferme_categories')
    AND date_debut_vote_final IS NOT NULL
    AND NOW() >= date_debut_vote_final 
    AND NOW() < date_fermeture_vote_final;
    
    -- Passer en cloture si la date de clôture finale est atteinte
    UPDATE evenement 
    SET statut = 'cloture'
    WHERE statut IN ('ouvert_categories', 'ferme_categories', 'ouvert_final')
    AND date_fermeture_vote_final IS NOT NULL
    AND NOW() >= date_fermeture_vote_final;
    
    -- Cas où il n'y a pas de vote final défini : clôturer après les catégories
    UPDATE evenement 
    SET statut = 'cloture'
    WHERE statut = 'ferme_categories'
    AND date_debut_vote_final IS NULL
    AND NOW() >= DATE_ADD(date_fermeture, INTERVAL 1 DAY);
END$$

DELIMITER ;

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `id_evenement`, `nom`, `description`) VALUES
(13, 9, 'RPG', ''),
(14, 9, 'MMORPG', ''),
(15, 9, 'MOBA', ''),
(17, 9, 'MMO', ''),
(18, 10, 'RPG', ''),
(19, 10, 'Test', ''),
(20, 10, 'MOBA', ''),
(21, 10, 'Abdel-Malek', '');

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
  `date_debut_vote_final` datetime DEFAULT NULL COMMENT 'Date de début du vote final',
  `date_fermeture_vote_final` datetime DEFAULT NULL COMMENT 'Date de clôture définitive du vote final',
  `statut` enum('preparation','ouvert_categories','ferme_categories','ouvert_final','cloture') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  `nb_max_candidats` int DEFAULT '0',
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_dates` (`date_ouverture`,`date_fermeture`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `nom`, `description`, `date_ouverture`, `date_fermeture`, `date_debut_vote_final`, `date_fermeture_vote_final`, `statut`, `nb_max_candidats`) VALUES
(9, 'Abdel-Malek', '', '2025-12-09 10:25:00', '2025-12-09 10:35:00', '2025-12-10 10:35:00', '2025-12-17 10:35:00', 'cloture', 0),
(10, 'Test', '', '2025-12-09 14:28:00', '2025-12-09 16:28:00', '2025-12-10 16:28:00', '2025-12-17 16:28:00', 'cloture', 0);

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
  `statut_candidature` enum('en_attente','approuve','refuse') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `motif_refus` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_validation` datetime DEFAULT NULL,
  `valide_par` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_event_candidat`),
  UNIQUE KEY `unique_candidat_categorie` (`id_evenement`,`id_categorie`,`id_candidat`),
  KEY `id_candidat` (`id_candidat`),
  KEY `fk_event_candidat_valideur` (`valide_par`),
  KEY `idx_statut_candidature` (`statut_candidature`),
  KEY `idx_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `event_candidat`
--

INSERT INTO `event_candidat` (`id_event_candidat`, `id_evenement`, `id_candidat`, `id_categorie`, `statut_candidature`, `motif_refus`, `date_validation`, `valide_par`, `date_inscription`) VALUES
(13, 10, 1, 21, 'en_attente', NULL, NULL, NULL, '2025-12-09 10:31:01'),
(14, 10, 1, 18, 'en_attente', NULL, NULL, NULL, '2025-12-09 10:45:45');

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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(17, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:25:58', 'Événement créé: VOTE2', NULL),
(18, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:43:47', 'Événement créé: Noveau', NULL),
(19, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:43:54', 'Catégorie \'RPG\' créée pour événement #7', NULL),
(20, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:43:57', 'Catégorie \'MMO\' créée pour événement #7', NULL),
(21, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:44:03', 'Catégorie \'MMORPG\' créée pour événement #7', NULL),
(22, 11, 'CANDIDATURE_SOUMISE', '2025-12-09 09:44:35', 'Événement: 7, Catégorie: MMO, Jeu: Domms', NULL),
(23, 11, 'CANDIDATURE_SOUMISE', '2025-12-09 09:44:41', 'Événement: 7, Catégorie: MMORPG, Jeu: Domms', NULL),
(24, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:53:23', 'Événement créé: Abdel-Malek', NULL),
(25, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:29', 'Catégorie \'RPG\' créée pour événement #8', NULL),
(26, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:33', 'Catégorie \'MMO\' créée pour événement #8', NULL),
(27, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:37', 'Catégorie \'MMORPG\' créée pour événement #8', NULL),
(28, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:40', 'Catégorie \'LOL\' créée pour événement #8', NULL),
(29, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:46', 'Événement 8 supprimé', NULL),
(30, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:49', 'Événement 7 supprimé', NULL),
(31, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:51', 'Événement 6 supprimé', NULL),
(32, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:54', 'Événement 5 supprimé', NULL),
(33, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 10:21:09', 'Événement créé: Abdel-Malek', NULL),
(34, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:13', 'Catégorie \'RPG\' créée pour événement #9', NULL),
(35, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:17', 'Catégorie \'MMORPG\' créée pour événement #9', NULL),
(36, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:19', 'Catégorie \'MOBA\' créée pour événement #9', NULL),
(37, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:22', 'Catégorie \'MMORPG\' créée pour événement #9', NULL),
(38, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:32', 'Catégorie \'MMO\' créée pour événement #9', NULL),
(39, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 10:28:19', 'Événement créé: Test', NULL),
(40, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:23', 'Catégorie \'RPG\' créée pour événement #10', NULL),
(41, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:25', 'Catégorie \'Test\' créée pour événement #10', NULL),
(42, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:27', 'Catégorie \'MOBA\' créée pour événement #10', NULL),
(43, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:29', 'Catégorie \'Abdel-Malek\' créée pour événement #10', NULL),
(44, 10, 'CANDIDATURE_SOUMISE', '2025-12-09 10:31:01', 'Événement: 10, Catégorie: Abdel-Malek, Jeu: Minecraft', NULL),
(45, 10, 'CANDIDATURE_SOUMISE', '2025-12-09 10:45:45', 'Événement: 10, Catégorie: RPG, Jeu: Minecraft', NULL);

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
`id_candidat` int
,`id_utilisateur` int
,`email` varchar(255)
,`jeu_titre` varchar(255)
,`bio` text
,`photo` varchar(500)
,`date_inscription` datetime
,`nb_contenus` bigint
,`nb_commentaires` bigint
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_candidatures_details`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_candidatures_details`;
CREATE TABLE IF NOT EXISTS `v_candidatures_details` (
`id_event_candidat` int
,`id_evenement` int
,`evenement_nom` varchar(255)
,`evenement_statut` enum('preparation','ouvert_categories','ferme_categories','ouvert_final','cloture')
,`id_categorie` int
,`categorie_nom` varchar(255)
,`id_candidat` int
,`candidat_nom` varchar(255)
,`id_jeu` int
,`jeu_titre` varchar(255)
,`jeu_image` varchar(500)
,`candidat_email` varchar(255)
,`statut_candidature` enum('en_attente','approuve','refuse')
,`date_inscription` datetime
,`date_validation` datetime
,`motif_refus` text
,`valide_par_email` varchar(255)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_peut_voter_categorie`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_peut_voter_categorie`;
CREATE TABLE IF NOT EXISTS `v_peut_voter_categorie` (
`id_utilisateur` int
,`id_evenement` int
,`id_categorie` int
,`peut_voter` varchar(3)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_votes_categorie`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_votes_categorie`;
CREATE TABLE IF NOT EXISTS `v_votes_categorie` (
`id_evenement` int
,`id_categorie` int
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

DELIMITER $$
--
-- Évènements
--
DROP EVENT IF EXISTS `auto_update_event_statuts`$$
CREATE DEFINER=`root`@`localhost` EVENT `auto_update_event_statuts` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-12-11 07:41:54' ON COMPLETION NOT PRESERVE ENABLE DO CALL update_event_statuts()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
