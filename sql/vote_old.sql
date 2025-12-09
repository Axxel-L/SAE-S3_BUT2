-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 28 nov. 2025 à 09:50
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `candidat`
--

DROP TABLE IF EXISTS `candidat`;
CREATE TABLE IF NOT EXISTS `candidat` (
  `id_candidat` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_jeu` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_candidat`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_jeu` (`id_jeu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

DROP TABLE IF EXISTS `evenement`;
CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_ouverture` datetime NOT NULL,
  `date_fermeture` datetime NOT NULL,
  `statut` enum('preparation','ouvert','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_dates` (`date_ouverture`,`date_fermeture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `salt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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