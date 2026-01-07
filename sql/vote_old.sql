-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 05 jan. 2026 à 08:18
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bulletin_categorie`
--

INSERT INTO `bulletin_categorie` (`id_bulletin`, `id_jeu`, `id_categorie`, `id_evenement`, `date_vote`) VALUES
(5, 3, 32, 15, '2026-01-05 09:10:12'),
(6, 4, 31, 15, '2026-01-05 09:10:16'),
(7, 3, 32, 15, '2026-01-05 09:10:29'),
(8, 4, 31, 15, '2026-01-05 09:10:31'),
(9, 4, 32, 15, '2026-01-05 09:10:40'),
(10, 4, 31, 15, '2026-01-05 09:11:15');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bulletin_final`
--

INSERT INTO `bulletin_final` (`id_bulletin_final`, `id_jeu`, `id_evenement`, `date_vote`) VALUES
(3, 3, 15, '2026-01-05 09:14:09'),
(4, 3, 15, '2026-01-05 09:14:25'),
(5, 4, 15, '2026-01-05 09:14:36');

-- --------------------------------------------------------

--
-- Structure de la table `candidat`
--

DROP TABLE IF EXISTS `candidat`;
CREATE TABLE IF NOT EXISTS `candidat` (
  `id_candidat` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_jeu` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `bio` text COLLATE utf8mb4_unicode_ci,
  `motivation` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('en_attente','valide','refuse') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente' COMMENT 'Statut de validation de la candidature',
  PRIMARY KEY (`id_candidat`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_jeu` (`id_jeu`),
  KEY `idx_candidat_statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `candidat`
--

INSERT INTO `candidat` (`id_candidat`, `id_utilisateur`, `nom`, `id_jeu`, `date_inscription`, `status`, `bio`, `motivation`, `photo`, `statut`) VALUES
(1, 10, 'Jean Marc', 1, '2025-12-01 09:37:02', 'approved', 'Je sais pas qui je suis', NULL, NULL, 'valide'),
(2, 11, 'Abdel-Malek', 2, '2025-12-01 09:38:15', 'approved', 'Je suis un gars chill', NULL, 'https://www.photographie-cours.fr/wp-content/uploads/2020/06/photographie-cours-banner-scaled.jpg', 'valide'),
(3, 12, 'joueur2@gmail.com', NULL, '2025-12-11 12:21:38', 'pending', NULL, NULL, NULL, 'valide'),
(4, 13, 'Misange Déchu', 3, '2025-12-11 12:42:27', 'pending', 'Un test basique en tant qu&#039;ange finalement', NULL, 'https://www.galerie-com.com/grand_img/0849590001641489156.jpeg', 'valide'),
(5, 15, 'Malek Simon', 4, '2026-01-05 08:46:33', 'pending', 'Je teste le site', NULL, 'https://www.photographie-cours.fr/wp-content/uploads/2020/06/photographie-cours-banner-scaled.jpg', 'valide');

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `id_evenement`, `nom`, `description`) VALUES
(31, 15, 'Voix', 'Meilleur voix'),
(32, 15, 'Gameplay', 'Meilleur Gameplay');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id_commentaire`, `id_utilisateur`, `id_jeu`, `contenu`, `date_commentaire`) VALUES
(1, 11, 2, 'Allez les bleus', '2025-12-01 09:47:43'),
(2, 15, 4, 'Votez pour moi les gars s&#039;il vous plait', '2026-01-05 08:49:18'),
(3, 14, 2, 'Je teste', '2026-01-05 08:50:31'),
(4, 7, 4, 'Tkt je te suis', '2026-01-05 09:11:01');

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emargement_categorie`
--

INSERT INTO `emargement_categorie` (`id_emargement`, `id_utilisateur`, `id_categorie`, `id_evenement`, `date_emargement`) VALUES
(5, 14, 32, 15, '2026-01-05 09:10:12'),
(6, 14, 31, 15, '2026-01-05 09:10:16'),
(7, 12, 32, 15, '2026-01-05 09:10:29'),
(8, 12, 31, 15, '2026-01-05 09:10:31'),
(9, 7, 32, 15, '2026-01-05 09:10:40'),
(10, 7, 31, 15, '2026-01-05 09:11:15');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emargement_final`
--

INSERT INTO `emargement_final` (`id_emargement_final`, `id_utilisateur`, `id_evenement`, `date_emargement`) VALUES
(3, 7, 15, '2026-01-05 09:14:09'),
(4, 12, 15, '2026-01-05 09:14:25'),
(5, 14, 15, '2026-01-05 09:14:36');

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
  `statut` enum('preparation','ouvert_categories','ferme_categories','ouvert_final','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  `nb_max_candidats` int DEFAULT '0',
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_dates` (`date_ouverture`,`date_fermeture`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `nom`, `description`, `date_ouverture`, `date_fermeture`, `date_debut_vote_final`, `date_fermeture_vote_final`, `statut`, `nb_max_candidats`) VALUES
(15, 'Test', 'Test', '2026-01-05 09:10:00', '2026-01-05 09:13:00', '2026-01-05 09:13:00', '2026-01-05 09:15:00', 'cloture', 0);

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `event_candidat`
--

INSERT INTO `event_candidat` (`id_event_candidat`, `id_evenement`, `id_candidat`, `id_categorie`, `statut_candidature`, `motif_refus`, `date_validation`, `valide_par`, `date_inscription`) VALUES
(29, 15, 4, 32, 'approuve', NULL, '2026-01-05 09:09:00', 8, '2026-01-05 09:08:04'),
(30, 15, 5, 31, 'approuve', NULL, '2026-01-05 09:08:58', 8, '2026-01-05 09:08:15'),
(31, 15, 5, 32, 'approuve', NULL, '2026-01-05 09:08:56', 8, '2026-01-05 09:08:18');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `jeu`
--

INSERT INTO `jeu` (`id_jeu`, `titre`, `editeur`, `image`, `date_sortie`, `description`) VALUES
(1, 'Minecraft', 'Mojang', NULL, NULL, NULL),
(2, 'Domms', 'Mojang', NULL, '2006-01-12', 'C\'est un fake'),
(3, 'League of Legends', 'RIOT GAME', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSd42Ia4ljXpfsa0Nsu1uUkZ4KQKhOBl50M1g&s', '2009-04-14', 'J&#039;ai mis une date au pif'),
(4, 'Mario Kart', 'Nintendo', 'https://www.nintendo.com/fr-fr/Jeux/Jeux-Nintendo-Switch-2/Mario-Kart-World-2790000.html?srsltid=AfmBOoriRdnyrUUE3mxo51IspMx8vu9vKf-Gm3DfIpKch4seL_MRw6an', NULL, NULL);

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
  `adresse_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse IP',
  PRIMARY KEY (`id_journal`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_action` (`action`),
  KEY `idx_date` (`date_action`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_securite`
--

INSERT INTO `journal_securite` (`id_journal`, `id_utilisateur`, `action`, `date_action`, `details`, `jeton_vote`, `adresse_ip`) VALUES
(1, 10, 'USER_REGISTRATION', '2025-12-01 09:27:00', 'Type: candidat', NULL, NULL),
(2, 10, 'CANDIDAT_CREATION', '2025-12-01 09:37:02', 'Candidat créé avec nouveau jeu: Minecraft', NULL, NULL),
(3, 11, 'USER_REGISTRATION', '2025-12-01 09:37:35', 'Type: candidat', NULL, NULL),
(4, 11, 'CANDIDAT_CREATION', '2025-12-01 09:38:15', 'Candidat créé avec nouveau jeu: Domms', NULL, NULL),
(5, 11, 'CAMPAGNE_COMMENT_ADD', '2025-12-01 09:47:43', 'Commentaire sur jeu: 2', NULL, NULL),
(6, 8, 'ADMIN_USER_STATUS_CHANGE', '2025-12-08 08:45:57', 'Utilisateur 10: is_active = 0', NULL, NULL),
(7, 8, 'ADMIN_USER_STATUS_CHANGE', '2025-12-08 08:48:27', 'Utilisateur 10: is_active = 1', NULL, NULL),
(8, 8, 'ADMIN_EVENT_CREATE', '2025-12-08 08:49:36', 'Événement créé: Evenement 1', NULL, NULL),
(9, 8, 'ADMIN_EVENT_CREATE', '2025-12-08 09:11:55', 'Événement créé: Eveneùent test', NULL, NULL),
(10, 8, 'ADMIN_CANDIDAT_APPROVE', '2025-12-08 09:36:09', 'Candidat 2 approuvé', NULL, NULL),
(11, 8, 'ADMIN_CANDIDAT_APPROVE', '2025-12-08 09:36:12', 'Candidat 1 approuvé', NULL, NULL),
(12, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 08:56:37', 'Événement créé: Test', NULL, NULL),
(13, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:08:31', 'Événement créé: Test', NULL, NULL),
(14, 7, 'VOTE_CATEGORIE', '2025-12-09 09:17:49', 'Catégorie 1, événement 5', NULL, NULL),
(15, 7, 'VOTE_FINAL', '2025-12-09 09:19:09', 'Événement: 5', NULL, NULL),
(16, 7, 'VOTE_CATEGORIE', '2025-12-09 09:24:47', 'Catégorie 2, événement 5', NULL, NULL),
(17, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:25:58', 'Événement créé: VOTE2', NULL, NULL),
(18, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:43:47', 'Événement créé: Noveau', NULL, NULL),
(19, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:43:54', 'Catégorie \'RPG\' créée pour événement #7', NULL, NULL),
(20, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:43:57', 'Catégorie \'MMO\' créée pour événement #7', NULL, NULL),
(21, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:44:03', 'Catégorie \'MMORPG\' créée pour événement #7', NULL, NULL),
(22, 11, 'CANDIDATURE_SOUMISE', '2025-12-09 09:44:35', 'Événement: 7, Catégorie: MMO, Jeu: Domms', NULL, NULL),
(23, 11, 'CANDIDATURE_SOUMISE', '2025-12-09 09:44:41', 'Événement: 7, Catégorie: MMORPG, Jeu: Domms', NULL, NULL),
(24, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 09:53:23', 'Événement créé: Abdel-Malek', NULL, NULL),
(25, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:29', 'Catégorie \'RPG\' créée pour événement #8', NULL, NULL),
(26, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:33', 'Catégorie \'MMO\' créée pour événement #8', NULL, NULL),
(27, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:37', 'Catégorie \'MMORPG\' créée pour événement #8', NULL, NULL),
(28, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 09:53:40', 'Catégorie \'LOL\' créée pour événement #8', NULL, NULL),
(29, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:46', 'Événement 8 supprimé', NULL, NULL),
(30, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:49', 'Événement 7 supprimé', NULL, NULL),
(31, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:51', 'Événement 6 supprimé', NULL, NULL),
(32, 8, 'ADMIN_EVENT_DELETE', '2025-12-09 10:20:54', 'Événement 5 supprimé', NULL, NULL),
(33, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 10:21:09', 'Événement créé: Abdel-Malek', NULL, NULL),
(34, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:13', 'Catégorie \'RPG\' créée pour événement #9', NULL, NULL),
(35, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:17', 'Catégorie \'MMORPG\' créée pour événement #9', NULL, NULL),
(36, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:19', 'Catégorie \'MOBA\' créée pour événement #9', NULL, NULL),
(37, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:22', 'Catégorie \'MMORPG\' créée pour événement #9', NULL, NULL),
(38, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:21:32', 'Catégorie \'MMO\' créée pour événement #9', NULL, NULL),
(39, 8, 'ADMIN_EVENT_CREATE', '2025-12-09 10:28:19', 'Événement créé: Test', NULL, NULL),
(40, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:23', 'Catégorie \'RPG\' créée pour événement #10', NULL, NULL),
(41, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:25', 'Catégorie \'Test\' créée pour événement #10', NULL, NULL),
(42, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:27', 'Catégorie \'MOBA\' créée pour événement #10', NULL, NULL),
(43, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-09 10:28:29', 'Catégorie \'Abdel-Malek\' créée pour événement #10', NULL, NULL),
(44, 10, 'CANDIDATURE_SOUMISE', '2025-12-09 10:31:01', 'Événement: 10, Catégorie: Abdel-Malek, Jeu: Minecraft', NULL, NULL),
(45, 10, 'CANDIDATURE_SOUMISE', '2025-12-09 10:45:45', 'Événement: 10, Catégorie: RPG, Jeu: Minecraft', NULL, NULL),
(46, 8, 'ADMIN_EVENT_DELETE', '2025-12-11 07:44:08', 'Événement 10 supprimé', NULL, NULL),
(47, 8, 'ADMIN_EVENT_DELETE', '2025-12-11 07:44:10', 'Événement 9 supprimé', NULL, NULL),
(48, 8, 'ADMIN_EVENT_CREATE', '2025-12-11 07:45:34', 'Événement créé: Test', NULL, NULL),
(49, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:45:39', 'Catégorie \'RPG\' créée pour événement #11', NULL, NULL),
(50, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:45:43', 'Catégorie \'MMO\' créée pour événement #11', NULL, NULL),
(51, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:45:48', 'Catégorie \'MOBA\' créée pour événement #11', NULL, NULL),
(52, 10, 'CANDIDATURE_SOUMISE', '2025-12-11 07:46:01', 'Événement: 11, Catégorie: MMO, Jeu: Minecraft', NULL, NULL),
(53, 10, 'CANDIDATURE_SOUMISE', '2025-12-11 07:46:03', 'Événement: 11, Catégorie: MOBA, Jeu: Minecraft', NULL, NULL),
(54, 11, 'CANDIDATURE_SOUMISE', '2025-12-11 07:46:14', 'Événement: 11, Catégorie: MMO, Jeu: Domms', NULL, NULL),
(55, 11, 'CANDIDATURE_SOUMISE', '2025-12-11 07:46:16', 'Événement: 11, Catégorie: RPG, Jeu: Domms', NULL, NULL),
(56, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:46:46', 'Candidature #18 approuvée - Jeu: Domms', NULL, NULL),
(57, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:46:48', 'Candidature #17 approuvée - Jeu: Domms', NULL, NULL),
(58, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:46:50', 'Candidature #16 approuvée - Jeu: Minecraft', NULL, NULL),
(59, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:46:54', 'Candidature #15 approuvée - Jeu: Minecraft', NULL, NULL),
(60, 8, 'ADMIN_EVENT_CREATE', '2025-12-11 07:55:22', 'Événement créé: Test', NULL, NULL),
(61, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:55:26', 'Catégorie \'RPG\' créée pour événement #12', NULL, NULL),
(62, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:55:31', 'Catégorie \'MMO\' créée pour événement #12', NULL, NULL),
(63, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 07:55:33', 'Catégorie \'MOBA\' créée pour événement #12', NULL, NULL),
(64, 10, 'CANDIDATURE_SOUMISE', '2025-12-11 07:55:52', 'Événement: 12, Catégorie: MMO, Jeu: Minecraft', NULL, NULL),
(65, 10, 'CANDIDATURE_SOUMISE', '2025-12-11 07:55:54', 'Événement: 12, Catégorie: MOBA, Jeu: Minecraft', NULL, NULL),
(66, 11, 'CANDIDATURE_SOUMISE', '2025-12-11 07:56:03', 'Événement: 12, Catégorie: MMO, Jeu: Domms', NULL, NULL),
(67, 11, 'CANDIDATURE_SOUMISE', '2025-12-11 07:56:05', 'Événement: 12, Catégorie: RPG, Jeu: Domms', NULL, NULL),
(68, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:56:30', 'Candidature #22 approuvée - Jeu: Domms', NULL, NULL),
(69, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:56:33', 'Candidature #21 approuvée - Jeu: Domms', NULL, NULL),
(70, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:56:35', 'Candidature #20 approuvée - Jeu: Minecraft', NULL, NULL),
(71, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 07:56:37', 'Candidature #19 approuvée - Jeu: Minecraft', NULL, NULL),
(72, 7, 'VOTE_CATEGORIE', '2025-12-11 07:58:58', 'Catégorie 25, événement 12', NULL, NULL),
(73, 7, 'VOTE_FINAL', '2025-12-11 08:01:06', 'Événement: 12', NULL, NULL),
(74, 8, 'LOGIN_SUCCESS', '2025-12-11 08:21:10', 'Connexion admin', NULL, '::1'),
(75, 8, 'LOGIN_SUCCESS', '2025-12-11 12:20:54', 'Connexion admin', NULL, '::1'),
(76, 11, 'LOGIN_SUCCESS', '2025-12-11 12:22:34', 'Connexion candidat', NULL, '::1'),
(77, 11, 'CANDIDAT_PROFIL_UPDATE', '2025-12-11 12:23:32', 'Mise à jour du profil', NULL, '::1'),
(78, 13, 'USER_REGISTRATION', '2025-12-11 12:40:22', 'Type: candidat', NULL, '::1'),
(79, 13, 'CANDIDAT_REGISTRATION', '2025-12-11 12:42:27', 'Candidat: Misange Déchu, Jeu: 3', NULL, '::1'),
(80, 8, 'LOGIN_SUCCESS', '2025-12-11 12:42:55', 'Connexion admin', NULL, '::1'),
(81, 8, 'ADMIN_CANDIDAT_VALIDE', '2025-12-11 12:43:20', 'Candidat ID: 4 validé', NULL, '::1'),
(82, 8, 'LOGIN_SUCCESS', '2025-12-11 12:43:35', 'Connexion admin', NULL, '::1'),
(83, 8, 'ADMIN_EVENT_CREATE', '2025-12-11 12:43:54', 'Événement créé: MOBA', NULL, NULL),
(84, 8, 'ADMIN_EVENT_DELETE', '2025-12-11 12:43:57', 'Événement 12 supprimé', NULL, NULL),
(85, 8, 'ADMIN_CATEGORY_CREATE', '2025-12-11 12:44:01', 'Catégorie \'MOBA\' créée pour événement #13', NULL, NULL),
(86, 13, 'LOGIN_SUCCESS', '2025-12-11 12:44:21', 'Connexion candidat', NULL, '::1'),
(87, 13, 'CANDIDATURE_SOUMISE', '2025-12-11 12:44:25', 'Événement: 13, Catégorie: MOBA, Jeu: League of Legends', NULL, NULL),
(88, 10, 'LOGIN_SUCCESS', '2025-12-11 12:44:38', 'Connexion candidat', NULL, '::1'),
(89, 10, 'CANDIDATURE_SOUMISE', '2025-12-11 12:44:41', 'Événement: 13, Catégorie: MOBA, Jeu: Minecraft', NULL, NULL),
(90, 11, 'LOGIN_SUCCESS', '2025-12-11 12:44:47', 'Connexion candidat', NULL, '::1'),
(91, 11, 'CANDIDATURE_SOUMISE', '2025-12-11 12:44:51', 'Événement: 13, Catégorie: MOBA, Jeu: Domms', NULL, NULL),
(92, 7, 'LOGIN_SUCCESS', '2025-12-11 12:45:01', 'Connexion joueur', NULL, '::1'),
(93, 8, 'LOGIN_SUCCESS', '2025-12-11 12:45:19', 'Connexion admin', NULL, '::1'),
(94, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 12:45:26', 'Candidature #25 approuvée - Jeu: Domms', NULL, NULL),
(95, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 12:45:28', 'Candidature #24 approuvée - Jeu: Minecraft', NULL, NULL),
(96, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2025-12-11 12:45:30', 'Candidature #23 approuvée - Jeu: League of Legends', NULL, NULL),
(97, 7, 'LOGIN_SUCCESS', '2025-12-11 12:45:35', 'Connexion joueur', NULL, '::1'),
(98, 7, 'VOTE_CATEGORIE', '2025-12-11 12:45:39', 'Catégorie 28, événement 13', NULL, NULL),
(99, 8, 'LOGIN_SUCCESS', '2025-12-11 12:48:10', 'Connexion admin', NULL, '::1'),
(100, 14, 'USER_REGISTRATION', '2026-01-05 08:41:57', 'Type: joueur', NULL, '::1'),
(101, 15, 'USER_REGISTRATION', '2026-01-05 08:42:30', 'Type: candidat', NULL, '::1'),
(102, 15, 'CANDIDAT_REGISTRATION', '2026-01-05 08:46:33', 'Candidat: Malek Simon, Jeu: 4', NULL, '::1'),
(103, 8, 'LOGIN_SUCCESS', '2026-01-05 08:46:55', 'Connexion admin', NULL, '::1'),
(104, 8, 'ADMIN_EVENT_DELETE', '2026-01-05 08:47:46', 'Événement 13 supprimé', NULL, NULL),
(105, 8, 'LOGIN_SUCCESS', '2026-01-05 08:48:48', 'Connexion admin', NULL, '::1'),
(106, 8, 'ADMIN_CANDIDAT_VALIDE', '2026-01-05 08:48:53', 'Candidat ID: 5 validé', NULL, '::1'),
(107, 15, 'LOGIN_SUCCESS', '2026-01-05 08:48:59', 'Connexion candidat', NULL, '::1'),
(108, 15, 'CAMPAGNE_POST', '2026-01-05 08:49:18', 'Jeu: 4', NULL, '::1'),
(109, 14, 'LOGIN_SUCCESS', '2026-01-05 08:50:11', 'Connexion joueur', NULL, '::1'),
(110, 14, 'COMMENT_ADD', '2026-01-05 08:50:31', 'Jeu: 2', NULL, '::1'),
(111, 8, 'LOGIN_SUCCESS', '2026-01-05 08:54:34', 'Connexion admin', NULL, '::1'),
(112, 8, 'ADMIN_EVENT_CREATE', '2026-01-05 08:55:47', 'Événement créé: Evenement', NULL, NULL),
(113, 8, 'ADMIN_CATEGORY_CREATE', '2026-01-05 08:55:56', 'Catégorie \'Meilleur naration\' créée pour événement #14', NULL, NULL),
(114, 8, 'ADMIN_CATEGORY_CREATE', '2026-01-05 08:56:04', 'Catégorie \'Meilleur voix\' créée pour événement #14', NULL, NULL),
(115, 13, 'LOGIN_SUCCESS', '2026-01-05 08:56:24', 'Connexion candidat', NULL, '::1'),
(116, 13, 'CANDIDATURE_SOUMISE', '2026-01-05 08:56:50', 'Événement: 14, Catégorie: Meilleur naration, Jeu: League of Legends', NULL, NULL),
(117, 13, 'CANDIDATURE_SOUMISE', '2026-01-05 08:56:55', 'Événement: 14, Catégorie: Meilleur voix, Jeu: League of Legends', NULL, NULL),
(118, 15, 'LOGIN_SUCCESS', '2026-01-05 08:57:06', 'Connexion candidat', NULL, '::1'),
(119, 15, 'CANDIDATURE_SOUMISE', '2026-01-05 08:57:09', 'Événement: 14, Catégorie: Meilleur naration, Jeu: Mario Kart', NULL, NULL),
(120, 8, 'LOGIN_SUCCESS', '2026-01-05 08:59:27', 'Connexion admin', NULL, '::1'),
(121, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 08:59:36', 'Candidature #28 approuvée - Jeu: Mario Kart', NULL, NULL),
(122, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 08:59:37', 'Candidature #27 approuvée - Jeu: League of Legends', NULL, NULL),
(123, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 08:59:39', 'Candidature #26 approuvée - Jeu: League of Legends', NULL, NULL),
(124, 7, 'LOGIN_SUCCESS', '2026-01-05 08:59:45', 'Connexion joueur', NULL, '::1'),
(125, 8, 'LOGIN_SUCCESS', '2026-01-05 09:00:10', 'Connexion admin', NULL, '::1'),
(126, 7, 'LOGIN_SUCCESS', '2026-01-05 09:02:41', 'Connexion joueur', NULL, '::1'),
(127, 8, 'LOGIN_SUCCESS', '2026-01-05 09:06:41', 'Connexion admin', NULL, '::1'),
(128, 8, 'ADMIN_EVENT_DELETE', '2026-01-05 09:07:00', 'Événement 14 supprimé', NULL, NULL),
(129, 8, 'ADMIN_EVENT_CREATE', '2026-01-05 09:07:25', 'Événement créé: Test', NULL, NULL),
(130, 8, 'ADMIN_CATEGORY_CREATE', '2026-01-05 09:07:40', 'Catégorie \'Voix\' créée pour événement #15', NULL, NULL),
(131, 8, 'ADMIN_CATEGORY_CREATE', '2026-01-05 09:07:51', 'Catégorie \'Gameplay\' créée pour événement #15', NULL, NULL),
(132, 13, 'LOGIN_SUCCESS', '2026-01-05 09:08:00', 'Connexion candidat', NULL, '::1'),
(133, 13, 'CANDIDATURE_SOUMISE', '2026-01-05 09:08:04', 'Événement: 15, Catégorie: Gameplay, Jeu: League of Legends', NULL, NULL),
(134, 15, 'LOGIN_SUCCESS', '2026-01-05 09:08:13', 'Connexion candidat', NULL, '::1'),
(135, 15, 'CANDIDATURE_SOUMISE', '2026-01-05 09:08:15', 'Événement: 15, Catégorie: Voix, Jeu: Mario Kart', NULL, NULL),
(136, 15, 'CANDIDATURE_SOUMISE', '2026-01-05 09:08:18', 'Événement: 15, Catégorie: Gameplay, Jeu: Mario Kart', NULL, NULL),
(137, 8, 'LOGIN_SUCCESS', '2026-01-05 09:08:52', 'Connexion admin', NULL, '::1'),
(138, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 09:08:56', 'Candidature #31 approuvée - Jeu: Mario Kart', NULL, NULL),
(139, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 09:08:58', 'Candidature #30 approuvée - Jeu: Mario Kart', NULL, NULL),
(140, 8, 'ADMIN_CANDIDATURE_APPROUVE', '2026-01-05 09:09:00', 'Candidature #29 approuvée - Jeu: League of Legends', NULL, NULL),
(141, 7, 'LOGIN_SUCCESS', '2026-01-05 09:09:11', 'Connexion joueur', NULL, '::1'),
(142, 12, 'LOGIN_SUCCESS', '2026-01-05 09:09:25', 'Connexion joueur', NULL, '::1'),
(143, 14, 'LOGIN_SUCCESS', '2026-01-05 09:09:36', 'Connexion joueur', NULL, '::1'),
(144, 14, 'VOTE_CATEGORIE', '2026-01-05 09:10:12', 'Catégorie 32, événement 15', NULL, '::1'),
(145, 14, 'VOTE_CATEGORIE', '2026-01-05 09:10:16', 'Catégorie 31, événement 15', NULL, '::1'),
(146, 12, 'LOGIN_SUCCESS', '2026-01-05 09:10:22', 'Connexion joueur', NULL, '::1'),
(147, 12, 'VOTE_CATEGORIE', '2026-01-05 09:10:29', 'Catégorie 32, événement 15', NULL, '::1'),
(148, 12, 'VOTE_CATEGORIE', '2026-01-05 09:10:31', 'Catégorie 31, événement 15', NULL, '::1'),
(149, 7, 'LOGIN_SUCCESS', '2026-01-05 09:10:37', 'Connexion joueur', NULL, '::1'),
(150, 7, 'VOTE_CATEGORIE', '2026-01-05 09:10:40', 'Catégorie 32, événement 15', NULL, '::1'),
(151, 7, 'COMMENT_ADD', '2026-01-05 09:11:01', 'Jeu: 4', NULL, '::1'),
(152, 7, 'VOTE_CATEGORIE', '2026-01-05 09:11:15', 'Catégorie 31, événement 15', NULL, '::1'),
(153, 8, 'LOGIN_SUCCESS', '2026-01-05 09:11:23', 'Connexion admin', NULL, '::1'),
(154, 7, 'LOGIN_SUCCESS', '2026-01-05 09:12:25', 'Connexion joueur', NULL, '::1'),
(155, 7, 'VOTE_FINAL', '2026-01-05 09:14:09', 'Événement: 15', NULL, NULL),
(156, 12, 'LOGIN_SUCCESS', '2026-01-05 09:14:22', 'Connexion joueur', NULL, '::1'),
(157, 12, 'VOTE_FINAL', '2026-01-05 09:14:25', 'Événement: 15', NULL, NULL),
(158, 14, 'LOGIN_SUCCESS', '2026-01-05 09:14:33', 'Connexion joueur', NULL, '::1'),
(159, 14, 'VOTE_FINAL', '2026-01-05 09:14:36', 'Événement: 15', NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `nomination`
--

INSERT INTO `nomination` (`id_nomination`, `id_jeu`, `id_categorie`, `id_evenement`) VALUES
(21, 3, 32, 15),
(20, 4, 31, 15),
(19, 4, 32, 15);

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `registre_electoral`
--

INSERT INTO `registre_electoral` (`id_registre`, `id_utilisateur`, `id_evenement`, `date_inscription`) VALUES
(7, 7, 15, '2026-01-05 09:09:14'),
(8, 12, 15, '2026-01-05 09:09:27'),
(9, 14, 15, '2026-01-05 09:09:53');

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
  `pseudo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pseudonyme affiché publiquement',
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_type` (`type`),
  KEY `idx_utilisateur_pseudo` (`pseudo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `email`, `mot_de_passe`, `date_inscription`, `type`, `salt`, `is_active`, `last_login`, `is_banned`, `pseudo`) VALUES
(7, 'joueur@gmail.com', '060e66eaea04d5a2d341b88fa528f43046c17ad10e87d17d368bba866ee2417f', '2025-11-28', 'joueur', '4a0af2da25573f8331acd0935f98b62a', 1, NULL, 0, 'Joueur_7'),
(8, 'admin@gmail.com', '156db2432c7b76e5f52baf1de004bedde825fb8f5e5bbc959d6b502760dd4388', '2025-11-28', 'admin', '72b2a54a9d629df77a7900b5e3d1ed29', 1, NULL, 0, 'Admin_8'),
(9, 'candidat@gmail.com', 'a147de687146bd9e5dc97c97fb5e9d7e6cd6e98b3dc7780b046dcce4cdb692bf', '2025-11-28', 'candidat', 'd3faad88d5f5162a1bfb533ee9ffbda1', 1, NULL, 0, NULL),
(10, 'Test123_@gmail.com', '1c3ee693d6620f9f76603ae44265498849423e1f70d81de49da4367c3aa1b9d3', '2025-12-01', 'candidat', '710f23669962bf8b4999fcecab73fd72', 1, NULL, 0, 'Jean Marc'),
(11, 'Test1234_@gmail.com', '06a485700038dc926dd1577cd2180990da0b689d9264225464c1a26cc31a1b51', '2025-12-01', 'candidat', 'ee42c630f88136e738be16ec1ed532b7', 1, NULL, 0, 'Abdel-Malek'),
(12, 'joueur2@gmail.com', '8351c3cb020816a8afb090e2f1e9f87e2a15854de04db8f23a524275a0e1e1d0', '2025-12-09', 'joueur', 'bde3d4163b97007c1d9e5427e7d02336', 1, NULL, 0, 'Joueur_12'),
(13, 'candidat2@gmail.com', '1a9bc389e802bd87b76952667b3d2eabe0071998aad11583af9a140061dc51d0', '2025-12-11', 'candidat', '730f0e28ad99035078156e24481b4e86', 1, NULL, 0, 'Misange Déchu'),
(14, 'malekjoueur@gmail.com', '4617ef5ad999d733e159f8a3d0d738fff87941758313b3d8e6df972dd0140c10', '2026-01-05', 'joueur', '874776710a2db33694160191a13d8dd3', 1, NULL, 0, 'Misange'),
(15, 'malekcandidat@gmail.com', 'ea3902c0659fdf4734ac8aa5e097b25a41c53306c6d30908337a66a4bf934be1', '2026-01-05', 'candidat', '4e14489897156b59b35ccacffa811cb4', 1, NULL, 0, 'Malek Simon');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_candidats_stats`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `v_candidats_stats`;
CREATE TABLE IF NOT EXISTS `v_candidats_stats` (
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
