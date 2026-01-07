-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `vote`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `vote`;

-- 1. Table utilisateur
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_inscription` date NOT NULL,
  `type` enum('joueur','admin','candidat') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'joueur',
  `salt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'utilisateurs
INSERT INTO `utilisateur` (`email`, `mot_de_passe`, `date_inscription`, `type`) VALUES
('admin@vote.fr', SHA2(CONCAT('admin123', 'salt'), 256), '2025-11-28', 'admin'),
('joueur1@vote.fr', SHA2(CONCAT('joueur123', 'salt'), 256), '2025-11-28', 'joueur'),
('candidat1@vote.fr', SHA2(CONCAT('candidat123', 'salt'), 256), '2025-11-28', 'candidat');

-- 2. Table événement
CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_ouverture` datetime NOT NULL,
  `date_fermeture` datetime NOT NULL,
  `statut` enum('preparation','ouvert','cloture') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preparation',
  PRIMARY KEY (`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `evenement` (`nom`, `date_ouverture`, `date_fermeture`, `statut`) VALUES
('Jeux de l\'Année 2025', '2025-11-01 00:00:00', '2025-12-31 23:59:59', 'ouvert');

-- 3. Table jeu
CREATE TABLE IF NOT EXISTS `jeu` (
  `id_jeu` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `editeur` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_sortie` date DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_jeu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jeu` (`titre`, `editeur`, `date_sortie`, `description`) VALUES
('Elden Ring', 'Bandai Namco', '2022-02-25', 'Action-RPG développé par FromSoftware'),
('The Legend of Zelda: Tears of the Kingdom', 'Nintendo', '2023-05-12', 'Jeu d\'aventure et d\'action'),
('Baldur\'s Gate 3', 'Larian Studios', '2023-08-03', 'RPG basé sur D&D');

-- 4. Table catégorie
CREATE TABLE IF NOT EXISTS `categorie` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `id_evenement` int NOT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorie` (`id_evenement`, `nom`, `description`) VALUES
(1, 'Meilleur Jeu de l\'Année', 'Le jeu le plus remarquable de l\'année'),
(1, 'Meilleur Design', 'Excellence en design visuel'),
(1, 'Meilleure Histoire', 'Narrative et scénario exceptionnels');

-- 5. Table bulletin_categorie
CREATE TABLE IF NOT EXISTS `bulletin_categorie` (
  `id_bulletin` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_vote` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bulletin`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table bulletin_final
CREATE TABLE IF NOT EXISTS `bulletin_final` (
  `id_bulletin_final` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_vote` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bulletin_final`),
  KEY `idx_jeu` (`id_jeu`),
  KEY `idx_evenement` (`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table candidat
CREATE TABLE IF NOT EXISTS `candidat` (
  `id_candidat` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_jeu` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_candidat`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_jeu` (`id_jeu`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `candidat` (`id_utilisateur`, `id_jeu`) VALUES
(3, 1);

-- 8. Table commentaire
CREATE TABLE IF NOT EXISTS `commentaire` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_jeu` int NOT NULL,
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_commentaire` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_commentaire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table emargement_categorie
CREATE TABLE IF NOT EXISTS `emargement_categorie` (
  `id_emargement` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_emargement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_emargement`),
  UNIQUE KEY `unique_emargement_categorie` (`id_utilisateur`,`id_categorie`,`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Table emargement_final
CREATE TABLE IF NOT EXISTS `emargement_final` (
  `id_emargement_final` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_emargement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_emargement_final`),
  UNIQUE KEY `unique_emargement_final` (`id_utilisateur`,`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Table export
CREATE TABLE IF NOT EXISTS `export` (
  `id_export` int NOT NULL AUTO_INCREMENT,
  `id_admin` int NOT NULL,
  `id_evenement` int NOT NULL,
  `type_export` enum('PDF','CSV','JSON') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_export` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_export`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Table journal_securite
CREATE TABLE IF NOT EXISTS `journal_securite` (
  `id_journal` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `jeton_vote` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_journal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Table nomination
CREATE TABLE IF NOT EXISTS `nomination` (
  `id_nomination` int NOT NULL AUTO_INCREMENT,
  `id_jeu` int NOT NULL,
  `id_categorie` int NOT NULL,
  `id_evenement` int NOT NULL,
  PRIMARY KEY (`id_nomination`),
  UNIQUE KEY `unique_nomination` (`id_jeu`,`id_categorie`,`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `nomination` (`id_jeu`, `id_categorie`, `id_evenement`) VALUES
(1, 1, 1),
(2, 1, 1),
(3, 1, 1);

-- 14. Table registre_electoral
CREATE TABLE IF NOT EXISTS `registre_electoral` (
  `id_registre` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_evenement` int NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_registre`),
  UNIQUE KEY `unique_inscription` (`id_utilisateur`,`id_evenement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Table resultat
CREATE TABLE IF NOT EXISTS `resultat` (
  `id_resultat` int NOT NULL AUTO_INCREMENT,
  `id_evenement` int NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `id_jeu` int NOT NULL,
  `nb_voix` int NOT NULL DEFAULT '0',
  `pourcentage` decimal(5,2) DEFAULT NULL,
  `classement` int DEFAULT NULL,
  `type_resultat` enum('categorie','final') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_resultat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Création des vues
CREATE OR REPLACE VIEW `v_peut_voter_categorie` AS
SELECT 
    r.id_utilisateur,
    r.id_evenement,
    c.id_categorie,
    CASE 
        WHEN e.id_emargement IS NULL THEN 'OUI' 
        ELSE 'NON' 
    END AS peut_voter
FROM registre_electoral r
JOIN categorie c ON c.id_evenement = r.id_evenement
LEFT JOIN emargement_categorie e ON e.id_utilisateur = r.id_utilisateur 
    AND e.id_categorie = c.id_categorie 
    AND e.id_evenement = r.id_evenement;

CREATE OR REPLACE VIEW `v_votes_categorie` AS
SELECT 
    id_evenement,
    id_categorie,
    id_jeu,
    COUNT(*) AS nb_votes
FROM bulletin_categorie
GROUP BY id_evenement, id_categorie, id_jeu;

CREATE OR REPLACE VIEW `v_votes_final` AS
SELECT 
    id_evenement,
    id_jeu,
    COUNT(*) AS nb_votes
FROM bulletin_final
GROUP BY id_evenement, id_jeu;

-- Ajout des contraintes de clés étrangères
ALTER TABLE `bulletin_categorie`
  ADD CONSTRAINT `bulletin_categorie_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_categorie_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_categorie_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `bulletin_final`
  ADD CONSTRAINT `bulletin_final_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_final_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `categorie`
  ADD CONSTRAINT `categorie_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `commentaire`
  ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaire_ibfk_2` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE;

ALTER TABLE `emargement_categorie`
  ADD CONSTRAINT `emargement_categorie_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_categorie_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_categorie_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `emargement_final`
  ADD CONSTRAINT `emargement_final_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `emargement_final_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `export`
  ADD CONSTRAINT `export_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `export_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `journal_securite`
  ADD CONSTRAINT `journal_securite_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

ALTER TABLE `nomination`
  ADD CONSTRAINT `nomination_ibfk_1` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE,
  ADD CONSTRAINT `nomination_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `nomination_ibfk_3` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `registre_electoral`
  ADD CONSTRAINT `registre_electoral_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `registre_electoral_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

ALTER TABLE `resultat`
  ADD CONSTRAINT `resultat_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `resultat_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON DELETE SET NULL,
  ADD CONSTRAINT `resultat_ibfk_3` FOREIGN KEY (`id_jeu`) REFERENCES `jeu` (`id_jeu`) ON DELETE CASCADE;