-- =====================================================================
--  RugbyMeet : création de la base de données + données de démonstration
--  Cible : MySQL 8 (Laragon), moteur InnoDB, encodage utf8mb4
--  Usage : importer ce fichier en entier (HeidiSQL, phpMyAdmin ou ligne de commande)
--  ATTENTION : le script SUPPRIME puis recrée la base "rugbymeet".
-- =====================================================================

DROP DATABASE IF EXISTS rugbymeet;
CREATE DATABASE rugbymeet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rugbymeet;

-- ---------------------------------------------------------------------
--  1. TABLES
-- ---------------------------------------------------------------------

-- Clubs du championnat
CREATE TABLE club (
    id_club      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100) NOT NULL,
    abreviation  VARCHAR(5)   NOT NULL,
    logo         VARCHAR(255) NOT NULL,
    theme        VARCHAR(30)  NULL,          -- NULL = pas de thème conforme WCAG
    CONSTRAINT pk_club PRIMARY KEY (id_club),
    CONSTRAINT uq_club_nom UNIQUE (nom),
    CONSTRAINT uq_club_abreviation UNIQUE (abreviation),
    CONSTRAINT uq_club_logo UNIQUE (logo),
    CONSTRAINT uq_club_theme UNIQUE (theme)  -- plusieurs NULL autorisés
) ENGINE=InnoDB;

-- Rôles (remplace un ENUM) : 1 = supporter, 2 = gerant, 3 = admin
CREATE TABLE `role` (
    id_role INT UNSIGNED NOT NULL,            -- saisi à la main, pas d'auto-incrément
    libelle VARCHAR(20)  NOT NULL,
    CONSTRAINT pk_role PRIMARY KEY (id_role),
    CONSTRAINT uq_role_libelle UNIQUE (libelle)
) ENGINE=InnoDB;

-- Utilisateurs : supporters, gérants de bar et administrateurs
CREATE TABLE utilisateur (
    id_utilisateur   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_profil       VARCHAR(50)  NOT NULL,
    email            VARCHAR(255) NOT NULL,
    mot_de_passe     VARCHAR(255) NOT NULL,   -- haché avec password_hash(), jamais en clair
    ville            VARCHAR(100) NULL,
    date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_role          INT UNSIGNED NOT NULL,
    id_club          INT UNSIGNED NULL,       -- club de coeur (facultatif)
    CONSTRAINT pk_utilisateur PRIMARY KEY (id_utilisateur),
    CONSTRAINT uq_utilisateur_nom_profil UNIQUE (nom_profil),
    CONSTRAINT uq_utilisateur_email UNIQUE (email),
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role)
        REFERENCES `role` (id_role) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_utilisateur_club FOREIGN KEY (id_club)
        REFERENCES club (id_club) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Compétitions (pour l'instant uniquement le Top 14)
CREATE TABLE competition (
    id_competition INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(100) NOT NULL,
    saison         VARCHAR(9)   NOT NULL,     -- ex. 2026-2027
    CONSTRAINT pk_competition PRIMARY KEY (id_competition),
    CONSTRAINT uq_competition_nom_saison UNIQUE (nom, saison)
) ENGINE=InnoDB;

-- Rencontres ("match" est un mot réservé de MySQL)
-- Règle vérifiée en PHP : le club à domicile doit être différent du club à l'extérieur.
CREATE TABLE rencontre (
    id_rencontre      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    journee           INT UNSIGNED NOT NULL,
    date_match        DATE         NOT NULL,
    heure_coup_envoi  TIME         NULL,
    id_competition    INT UNSIGNED NOT NULL,
    id_club_domicile  INT UNSIGNED NOT NULL,
    id_club_exterieur INT UNSIGNED NOT NULL,
    CONSTRAINT pk_rencontre PRIMARY KEY (id_rencontre),
    CONSTRAINT uq_rencontre_affiche UNIQUE (id_competition, id_club_domicile, id_club_exterieur),
    CONSTRAINT ck_rencontre_journee CHECK (journee >= 1),
    CONSTRAINT fk_rencontre_competition FOREIGN KEY (id_competition)
        REFERENCES competition (id_competition) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rencontre_domicile FOREIGN KEY (id_club_domicile)
        REFERENCES club (id_club) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_rencontre_exterieur FOREIGN KEY (id_club_exterieur)
        REFERENCES club (id_club) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Bars (un gérant = un seul bar)
CREATE TABLE bar (
    id_bar         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom            VARCHAR(100) NOT NULL,
    adresse        VARCHAR(255) NOT NULL,
    code_postal    CHAR(5)      NOT NULL,
    ville          VARCHAR(100) NOT NULL,
    telephone      VARCHAR(20)  NULL,
    `description`  TEXT         NULL,
    photo          VARCHAR(255) NULL,         -- chemin du fichier, jamais l'image
    nb_ecrans      INT UNSIGNED NOT NULL DEFAULT 1,
    restauration   BOOLEAN      NOT NULL DEFAULT 0,
    ecran_geant    BOOLEAN      NOT NULL DEFAULT 0,
    acces_pmr      BOOLEAN      NOT NULL DEFAULT 0,
    id_utilisateur INT UNSIGNED NOT NULL,
    CONSTRAINT pk_bar PRIMARY KEY (id_bar),
    CONSTRAINT uq_bar_gerant UNIQUE (id_utilisateur),
    CONSTRAINT uq_bar_adresse UNIQUE (adresse, code_postal, ville),
    CONSTRAINT ck_bar_code_postal CHECK (code_postal REGEXP '^[0-9]{5}$'),
    CONSTRAINT fk_bar_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Évènements : un bar diffuse une rencontre à une date et une heure données
CREATE TABLE evenement (
    id_evenement          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    date_heure_diffusion  DATETIME NOT NULL,
    infos_complementaires TEXT     NULL,      -- limité à 500 caractères dans le formulaire
    id_bar                INT UNSIGNED NOT NULL,
    id_rencontre          INT UNSIGNED NOT NULL,
    CONSTRAINT pk_evenement PRIMARY KEY (id_evenement),
    CONSTRAINT uq_evenement_bar_rencontre UNIQUE (id_bar, id_rencontre),
    CONSTRAINT fk_evenement_bar FOREIGN KEY (id_bar)
        REFERENCES bar (id_bar) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_evenement_rencontre FOREIGN KEY (id_rencontre)
        REFERENCES rencontre (id_rencontre) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Bars favoris des supporters (association utilisateur - bar)
CREATE TABLE utilisateur_bar (
    id_utilisateur INT UNSIGNED NOT NULL,
    id_bar         INT UNSIGNED NOT NULL,
    date_ajout     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_utilisateur_bar PRIMARY KEY (id_utilisateur, id_bar),
    CONSTRAINT fk_utilisateur_bar_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_utilisateur_bar_bar FOREIGN KEY (id_bar)
        REFERENCES bar (id_bar) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  2. DONNÉES DE RÉFÉRENCE
-- ---------------------------------------------------------------------

INSERT INTO `role` (id_role, libelle) VALUES
    (1, 'supporter'), (2, 'gerant'), (3, 'admin');

INSERT INTO competition (id_competition, nom, saison) VALUES
    (1, 'Top 14', '2026-2027');

-- Les 14 clubs (logos dans Images/Logos/). Seuls La Rochelle et Toulouse ont un thème.
INSERT INTO club (id_club, nom, abreviation, logo, theme) VALUES
    ( 1, 'Stade Rochelais',           'SR',   'Images/Logos/Logo-SR.png',   'stade-rochelais'),
    ( 2, 'Aviron Bayonnais',          'AB',   'Images/Logos/Logo-AB.svg',   NULL),
    ( 3, 'ASM Clermont',              'ASM',  'Images/Logos/Logo-ASM.png',  NULL),
    ( 4, 'Castres Olympique',         'CO',   'Images/Logos/Logo-CO.webp',  NULL),
    ( 5, 'LOU Rugby',                 'LOU',  'Images/Logos/Logo-LOU.webp', NULL),
    ( 6, 'Montpellier Hérault Rugby', 'MHR',  'Images/Logos/Logo-MHR.png',  NULL),
    ( 7, 'Racing 92',                 'R92',  'Images/Logos/Logo-R92.png',  NULL),
    ( 8, 'RC Toulon',                 'RCT',  'Images/Logos/Logo-RCT.png',  NULL),
    ( 9, 'RC Vannes',                 'RCV',  'Images/Logos/Logo-RCV.svg',  NULL),
    (10, 'Stade Français Paris',      'SF',   'Images/Logos/Logo-SF.png',   NULL),
    (11, 'Section Paloise',           'SP',   'Images/Logos/Logo-SP.png',   NULL),
    (12, 'Stade Toulousain',          'ST',   'Images/Logos/Logo-ST.png',   'stade-toulousain'),
    (13, 'Union Bordeaux-Bègles',     'UBB',  'Images/Logos/Logo-UBB.png',  NULL),
    (14, 'USA Perpignan',             'USAP', 'Images/Logos/Logo-USAP.png', NULL);

-- Top 14 2026-2027 : 26 journées, 182 rencontres (compétition 1)
-- Horaires NULL = coup d'envoi pas encore communiqué. Dates à vérifier au fil de la saison.
INSERT INTO rencontre (id_rencontre, journee, date_match, heure_coup_envoi, id_competition, id_club_domicile, id_club_exterieur) VALUES
    (1, 1, '2026-09-05', '19:05:00', 1,  2,  8), -- Bayonne - Toulon
    (2, 1, '2026-09-05', '19:05:00', 1,  4,  9), -- Castres - Vannes
    (3, 1, '2026-09-05', '19:05:00', 1,  5,  3), -- Lyon - Clermont
    (4, 1, '2026-09-05', '19:05:00', 1,  6, 11), -- Montpellier - Pau
    (5, 1, '2026-09-05', '19:05:00', 1, 10, 14), -- Stade Français - Perpignan
    (6, 1, '2026-09-05', '21:15:00', 1, 13,  7), -- Bordeaux-Bègles - Racing 92
    (7, 1, '2026-09-06', '21:05:00', 1,  1, 12), -- La Rochelle - Toulouse
    (8, 2, '2026-09-12', '16:35:00', 1,  3, 10), -- Clermont - Stade Français
    (9, 2, '2026-09-12', '16:35:00', 1, 11,  2), -- Pau - Bayonne
    (10, 2, '2026-09-12', '16:35:00', 1, 14,  4), -- Perpignan - Castres
    (11, 2, '2026-09-12', '16:35:00', 1,  7,  5), -- Racing 92 - Lyon
    (12, 2, '2026-09-12', '16:35:00', 1,  9,  6), -- Vannes - Montpellier
    (13, 2, '2026-09-12', '21:00:00', 1,  8,  1), -- Toulon - La Rochelle
    (14, 2, '2026-09-13', '21:05:00', 1, 12, 13), -- Toulouse - Bordeaux-Bègles
    (15, 3, '2026-09-19', '14:30:00', 1,  4,  8), -- Castres - Toulon
    (16, 3, '2026-09-19', '16:35:00', 1,  2,  3), -- Bayonne - Clermont
    (17, 3, '2026-09-19', '16:35:00', 1,  1,  7), -- La Rochelle - Racing 92
    (18, 3, '2026-09-19', '16:35:00', 1,  5, 11), -- Lyon - Pau
    (19, 3, '2026-09-19', '16:35:00', 1,  6, 14), -- Montpellier - Perpignan
    (20, 3, '2026-09-19', '21:00:00', 1,  9, 12), -- Vannes - Toulouse
    (21, 3, '2026-09-20', '21:05:00', 1, 13, 10), -- Bordeaux-Bègles - Stade Français
    (22, 4, '2026-09-26', '14:30:00', 1, 14, 13), -- Perpignan - Bordeaux-Bègles
    (23, 4, '2026-09-26', '16:35:00', 1,  3,  4), -- Clermont - Castres
    (24, 4, '2026-09-26', '16:35:00', 1, 10,  5), -- Stade Français - Lyon
    (25, 4, '2026-09-26', '16:35:00', 1,  7,  2), -- Racing 92 - Bayonne
    (26, 4, '2026-09-26', '16:35:00', 1,  8,  9), -- Toulon - Vannes
    (27, 4, '2026-09-26', '21:00:00', 1, 11,  1), -- Pau - La Rochelle
    (28, 4, '2026-09-27', '21:05:00', 1, 12,  6), -- Toulouse - Montpellier
    (29, 5, '2026-10-03', '14:30:00', 1, 13,  5), -- Bordeaux-Bègles - Lyon
    (30, 5, '2026-10-03', '16:35:00', 1,  2, 10), -- Bayonne - Stade Français
    (31, 5, '2026-10-03', '16:35:00', 1,  1,  3), -- La Rochelle - Clermont
    (32, 5, '2026-10-03', '16:35:00', 1,  7, 14), -- Racing 92 - Perpignan
    (33, 5, '2026-10-03', '16:35:00', 1,  9, 11), -- Vannes - Pau
    (34, 5, '2026-10-03', '21:00:00', 1,  4, 12), -- Castres - Toulouse
    (35, 5, '2026-10-04', '21:05:00', 1,  6,  8), -- Montpellier - Toulon
    (36, 6, '2026-10-10', '14:30:00', 1, 10,  6), -- Stade Français - Montpellier
    (37, 6, '2026-10-10', '16:35:00', 1,  5,  1), -- Lyon - La Rochelle
    (38, 6, '2026-10-10', '16:35:00', 1, 11,  4), -- Pau - Castres
    (39, 6, '2026-10-10', '16:35:00', 1, 14,  9), -- Perpignan - Vannes
    (40, 6, '2026-10-10', '16:35:00', 1,  8,  7), -- Toulon - Racing 92
    (41, 6, '2026-10-10', '21:00:00', 1,  3, 13), -- Clermont - Bordeaux-Bègles
    (42, 6, '2026-10-11', '21:05:00', 1, 12,  2), -- Toulouse - Bayonne
    (43, 7, '2026-10-24', '14:30:00', 1,  9,  3), -- Vannes - Clermont
    (44, 7, '2026-10-24', '16:35:00', 1,  2,  5), -- Bayonne - Lyon
    (45, 7, '2026-10-24', '16:35:00', 1,  4, 10), -- Castres - Stade Français
    (46, 7, '2026-10-24', '16:35:00', 1,  7,  6), -- Racing 92 - Montpellier
    (47, 7, '2026-10-24', '16:35:00', 1, 12, 14), -- Toulouse - Perpignan
    (48, 7, '2026-10-24', '21:00:00', 1,  8, 11), -- Toulon - Pau
    (49, 7, '2026-10-25', '21:05:00', 1,  1, 13), -- La Rochelle - Bordeaux-Bègles
    (50, 8, '2026-10-31', '14:30:00', 1,  3,  7), -- Clermont - Racing 92
    (51, 8, '2026-10-31', '16:35:00', 1, 13,  2), -- Bordeaux-Bègles - Bayonne
    (52, 8, '2026-10-31', '16:35:00', 1,  5,  9), -- Lyon - Vannes
    (53, 8, '2026-10-31', '16:35:00', 1, 10,  1), -- Stade Français - La Rochelle
    (54, 8, '2026-10-31', '21:00:00', 1, 14,  8), -- Perpignan - Toulon
    (55, 8, '2026-11-01', '15:00:00', 1,  6,  4), -- Montpellier - Castres
    (56, 8, '2026-11-01', '21:05:00', 1, 11, 12), -- Pau - Toulouse
    (57, 9, '2026-11-07', '14:30:00', 1, 12,  3), -- Toulouse - Clermont
    (58, 9, '2026-11-07', '16:35:00', 1,  4,  7), -- Castres - Racing 92
    (59, 9, '2026-11-07', '16:35:00', 1,  6,  5), -- Montpellier - Lyon
    (60, 9, '2026-11-07', '16:35:00', 1, 11, 14), -- Pau - Perpignan
    (61, 9, '2026-11-07', '16:35:00', 1,  8, 10), -- Toulon - Stade Français
    (62, 9, '2026-11-08', '15:00:00', 1,  9, 13), -- Vannes - Bordeaux-Bègles
    (63, 9, '2026-11-08', '21:05:00', 1,  1,  2), -- La Rochelle - Bayonne
    (64, 10, '2026-11-28', '16:35:00', 1,  2,  4), -- Bayonne - Castres
    (65, 10, '2026-11-28', '14:30:00', 1, 13,  6), -- Bordeaux-Bègles - Montpellier
    (66, 10, '2026-11-29', '21:05:00', 1,  3,  8), -- Clermont - Toulon
    (67, 10, '2026-11-28', '16:35:00', 1,  1, 14), -- La Rochelle - Perpignan
    (68, 10, '2026-11-28', '21:00:00', 1,  5, 12), -- Lyon - Toulouse
    (69, 10, '2026-11-28', '16:35:00', 1, 10,  9), -- Stade Français - Vannes
    (70, 10, '2026-11-28', '16:35:00', 1,  7, 11), -- Racing 92 - Pau
    (71, 11, '2026-12-05', NULL, 1, 12,  7), -- Toulouse - Racing 92
    (72, 11, '2026-12-05', NULL, 1,  4,  5), -- Castres - Lyon
    (73, 11, '2026-12-05', NULL, 1,  6,  1), -- Montpellier - La Rochelle
    (74, 11, '2026-12-05', NULL, 1, 11, 10), -- Pau - Stade Français
    (75, 11, '2026-12-05', NULL, 1,  9,  2), -- Vannes - Bayonne
    (76, 11, '2026-12-05', NULL, 1, 14,  3), -- Perpignan - Clermont
    (77, 11, '2026-12-06', NULL, 1,  8, 13), -- Toulon - Bordeaux-Bègles
    (78, 12, '2026-12-19', NULL, 1,  2, 14), -- Bayonne - Perpignan
    (79, 12, '2026-12-19', NULL, 1, 13, 11), -- Bordeaux-Bègles - Pau
    (80, 12, '2026-12-19', NULL, 1,  3,  6), -- Clermont - Montpellier
    (81, 12, '2026-12-19', NULL, 1,  1,  4), -- La Rochelle - Castres
    (82, 12, '2026-12-19', NULL, 1,  5,  8), -- Lyon - Toulon
    (83, 12, '2026-12-19', NULL, 1, 10, 12), -- Stade Français - Toulouse
    (84, 12, '2026-12-19', NULL, 1,  7,  9), -- Racing 92 - Vannes
    (85, 13, '2026-12-26', NULL, 1,  4, 13), -- Castres - Bordeaux-Bègles
    (86, 13, '2026-12-26', NULL, 1,  6,  2), -- Montpellier - Bayonne
    (87, 13, '2026-12-26', NULL, 1, 11,  3), -- Pau - Clermont
    (88, 13, '2026-12-26', NULL, 1, 14,  5), -- Perpignan - Lyon
    (89, 13, '2026-12-26', NULL, 1,  7, 10), -- Racing 92 - Stade Français
    (90, 13, '2026-12-26', NULL, 1, 12,  8), -- Toulouse - Toulon
    (91, 13, '2026-12-26', NULL, 1,  9,  1), -- Vannes - La Rochelle
    (92, 14, '2027-01-02', NULL, 1,  2, 12), -- Bayonne - Toulouse
    (93, 14, '2027-01-02', NULL, 1, 13, 14), -- Bordeaux-Bègles - Perpignan
    (94, 14, '2027-01-02', NULL, 1,  3,  9), -- Clermont - Vannes
    (95, 14, '2027-01-02', NULL, 1,  1, 11), -- La Rochelle - Pau
    (96, 14, '2027-01-02', NULL, 1,  5,  7), -- Lyon - Racing 92
    (97, 14, '2027-01-02', NULL, 1, 10,  4), -- Stade Français - Castres
    (98, 14, '2027-01-02', NULL, 1,  8,  6), -- Toulon - Montpellier
    (99, 15, '2027-01-23', NULL, 1,  9, 14), -- Vannes - Perpignan
    (100, 15, '2027-01-23', NULL, 1,  4,  3), -- Castres - Clermont
    (101, 15, '2027-01-23', NULL, 1,  6, 10), -- Montpellier - Stade Français
    (102, 15, '2027-01-23', NULL, 1, 11,  5), -- Pau - Lyon
    (103, 15, '2027-01-23', NULL, 1,  7, 13), -- Racing 92 - Bordeaux-Bègles
    (104, 15, '2027-01-23', NULL, 1,  8,  2), -- Toulon - Bayonne
    (105, 15, '2027-01-23', NULL, 1, 12,  1), -- Toulouse - La Rochelle
    (106, 16, '2027-01-30', NULL, 1, 13,  9), -- Bordeaux-Bègles - Vannes
    (107, 16, '2027-01-30', NULL, 1,  3, 12), -- Clermont - Toulouse
    (108, 16, '2027-01-30', NULL, 1,  1,  8), -- La Rochelle - Toulon
    (109, 16, '2027-01-30', NULL, 1,  5,  2), -- Lyon - Bayonne
    (110, 16, '2027-01-30', NULL, 1, 11,  6), -- Pau - Montpellier
    (111, 16, '2027-01-30', NULL, 1, 14, 10), -- Perpignan - Stade Français
    (112, 16, '2027-01-30', NULL, 1,  7,  4), -- Racing 92 - Castres
    (113, 17, '2027-02-20', NULL, 1,  2,  1), -- Bayonne - La Rochelle
    (114, 17, '2027-02-20', NULL, 1,  6,  7), -- Montpellier - Racing 92
    (115, 17, '2027-02-20', NULL, 1, 10, 13), -- Stade Français - Bordeaux-Bègles
    (116, 17, '2027-02-20', NULL, 1, 14, 11), -- Perpignan - Pau
    (117, 17, '2027-02-20', NULL, 1,  8,  3), -- Toulon - Clermont
    (118, 17, '2027-02-20', NULL, 1, 12,  5), -- Toulouse - Lyon
    (119, 17, '2027-02-20', NULL, 1,  9,  4), -- Vannes - Castres
    (120, 18, '2027-02-27', NULL, 1, 13,  8), -- Bordeaux-Bègles - Toulon
    (121, 18, '2027-02-27', NULL, 1,  4, 14), -- Castres - Perpignan
    (122, 18, '2027-02-27', NULL, 1,  3,  2), -- Clermont - Bayonne
    (123, 18, '2027-02-27', NULL, 1,  1, 10), -- La Rochelle - Stade Français
    (124, 18, '2027-02-27', NULL, 1,  5,  6), -- Lyon - Montpellier
    (125, 18, '2027-02-27', NULL, 1, 11,  9), -- Pau - Vannes
    (126, 18, '2027-02-27', NULL, 1,  7, 12), -- Racing 92 - Toulouse
    (127, 19, '2027-03-20', NULL, 1,  2, 13), -- Bayonne - Bordeaux-Bègles
    (128, 19, '2027-03-20', NULL, 1,  4,  1), -- Castres - La Rochelle
    (129, 19, '2027-03-20', NULL, 1,  6,  3), -- Montpellier - Clermont
    (130, 19, '2027-03-20', NULL, 1, 10, 11), -- Stade Français - Pau
    (131, 19, '2027-03-20', NULL, 1, 14,  7), -- Perpignan - Racing 92
    (132, 19, '2027-03-20', NULL, 1,  8,  5), -- Toulon - Lyon
    (133, 19, '2027-03-20', NULL, 1, 12,  9), -- Toulouse - Vannes
    (134, 20, '2027-03-27', NULL, 1,  2,  6), -- Bayonne - Montpellier
    (135, 20, '2027-03-27', NULL, 1, 13, 12), -- Bordeaux-Bègles - Toulouse
    (136, 20, '2027-03-27', NULL, 1,  3, 11), -- Clermont - Pau
    (137, 20, '2027-03-27', NULL, 1,  5,  4), -- Lyon - Castres
    (138, 20, '2027-03-27', NULL, 1, 14,  1), -- Perpignan - La Rochelle
    (139, 20, '2027-03-27', NULL, 1,  7,  8), -- Racing 92 - Toulon
    (140, 20, '2027-03-27', NULL, 1,  9, 10), -- Vannes - Stade Français
    (141, 21, '2027-04-17', NULL, 1,  4,  2), -- Castres - Bayonne
    (142, 21, '2027-04-17', NULL, 1,  1,  9), -- La Rochelle - Vannes
    (143, 21, '2027-04-17', NULL, 1,  5, 14), -- Lyon - Perpignan
    (144, 21, '2027-04-17', NULL, 1,  6, 13), -- Montpellier - Bordeaux-Bègles
    (145, 21, '2027-04-17', NULL, 1, 10,  3), -- Stade Français - Clermont
    (146, 21, '2027-04-17', NULL, 1, 11,  7), -- Pau - Racing 92
    (147, 21, '2027-04-17', NULL, 1,  8, 12), -- Toulon - Toulouse
    (148, 22, '2027-04-24', NULL, 1,  2, 11), -- Bayonne - Pau
    (149, 22, '2027-04-24', NULL, 1, 13,  1), -- Bordeaux-Bègles - La Rochelle
    (150, 22, '2027-04-24', NULL, 1,  3,  5), -- Clermont - Lyon
    (151, 22, '2027-04-24', NULL, 1, 10,  7), -- Stade Français - Racing 92
    (152, 22, '2027-04-24', NULL, 1, 14,  6), -- Perpignan - Montpellier
    (153, 22, '2027-04-24', NULL, 1, 12,  4), -- Toulouse - Castres
    (154, 22, '2027-04-24', NULL, 1,  9,  8), -- Vannes - Toulon
    (155, 23, '2027-05-08', NULL, 1,  2,  9), -- Bayonne - Vannes
    (156, 23, '2027-05-08', NULL, 1,  3, 14), -- Clermont - Perpignan
    (157, 23, '2027-05-08', NULL, 1,  5, 10), -- Lyon - Stade Français
    (158, 23, '2027-05-08', NULL, 1,  6, 12), -- Montpellier - Toulouse
    (159, 23, '2027-05-08', NULL, 1, 11, 13), -- Pau - Bordeaux-Bègles
    (160, 23, '2027-05-08', NULL, 1,  7,  1), -- Racing 92 - La Rochelle
    (161, 23, '2027-05-08', NULL, 1,  8,  4), -- Toulon - Castres
    (162, 24, '2027-05-15', NULL, 1, 13,  3), -- Bordeaux-Bègles - Clermont
    (163, 24, '2027-05-15', NULL, 1,  4,  6), -- Castres - Montpellier
    (164, 24, '2027-05-15', NULL, 1,  1,  5), -- La Rochelle - Lyon
    (165, 24, '2027-05-15', NULL, 1, 10,  8), -- Stade Français - Toulon
    (166, 24, '2027-05-15', NULL, 1, 14,  2), -- Perpignan - Bayonne
    (167, 24, '2027-05-15', NULL, 1, 12, 11), -- Toulouse - Pau
    (168, 24, '2027-05-15', NULL, 1,  9,  7), -- Vannes - Racing 92
    (169, 25, '2027-05-29', NULL, 1,  2,  7), -- Bayonne - Racing 92
    (170, 25, '2027-05-29', NULL, 1,  4, 11), -- Castres - Pau
    (171, 25, '2027-05-29', NULL, 1,  3,  1), -- Clermont - La Rochelle
    (172, 25, '2027-05-29', NULL, 1,  5, 13), -- Lyon - Bordeaux-Bègles
    (173, 25, '2027-05-29', NULL, 1,  6,  9), -- Montpellier - Vannes
    (174, 25, '2027-05-29', NULL, 1,  8, 14), -- Toulon - Perpignan
    (175, 25, '2027-05-29', NULL, 1, 12, 10), -- Toulouse - Stade Français
    (176, 26, '2027-06-05', NULL, 1, 13,  4), -- Bordeaux-Bègles - Castres
    (177, 26, '2027-06-05', NULL, 1,  1,  6), -- La Rochelle - Montpellier
    (178, 26, '2027-06-05', NULL, 1, 10,  2), -- Stade Français - Bayonne
    (179, 26, '2027-06-05', NULL, 1, 11,  8), -- Pau - Toulon
    (180, 26, '2027-06-05', NULL, 1, 14, 12), -- Perpignan - Toulouse
    (181, 26, '2027-06-05', NULL, 1,  7,  3), -- Racing 92 - Clermont
    (182, 26, '2027-06-05', NULL, 1,  9,  5);  -- Vannes - Lyon

-- ---------------------------------------------------------------------
--  3. DONNÉES DE DÉMONSTRATION (à supprimer avant une mise en ligne)
-- ---------------------------------------------------------------------
-- Mot de passe de TOUS les comptes de démo : RugbyMeet2026!
-- Le compte "admin" doit changer de mot de passe avant toute mise en ligne.

INSERT INTO utilisateur (id_utilisateur, nom_profil, email, mot_de_passe, ville, id_role, id_club) VALUES
    (1, 'admin',         'admin@rugbymeet.local',   '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', NULL,         3, NULL),
    (2, 'gerant_taur',   'taur@rugbymeet.local',    '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', NULL,         2, NULL),
    (3, 'gerant_ovalie', 'ovalie@rugbymeet.local',  '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', NULL,         2, NULL),
    (4, 'gerant_maul',   'maul@rugbymeet.local',    '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', NULL,         2, NULL),
    (5, 'gerant_enbut',  'enbut@rugbymeet.local',   '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', NULL,         2, NULL),
    (6, 'Camille',       'camille@rugbymeet.local', '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', 'Toulouse',   1, 12),
    (7, 'Julien',        'julien@rugbymeet.local',  '$2y$10$AK1Qa4mGBsdT5hGB3/41jONpqCHHNbi2264oKDTFD/ZrOLjZbVdEW', 'La Rochelle',1, 1);

INSERT INTO bar (id_bar, nom, adresse, code_postal, ville, telephone, `description`, photo, nb_ecrans, restauration, ecran_geant, acces_pmr, id_utilisateur) VALUES
    (1, 'Le Troisième Mi-Temps', '12 rue du Taur',           '31000', 'Toulouse',    '05 61 00 00 01',
        'Ambiance rouge et noir garantie, tous les matchs du Top 14 sur grand écran.', NULL, 6, 1, 1, 1, 2),
    (2, 'Chez Ovalie',           '4 quai Jauréguiberry',     '64100', 'Bayonne',     '05 59 00 00 02',
        'Bar de quartier, ambiance basque et bodega.',                                 NULL, 3, 1, 0, 0, 3),
    (3, 'Le Maul',               '27 rue sur les Murs',      '17000', 'La Rochelle', '05 46 00 00 03',
        'Un écran géant et une grande terrasse face au port.',                         NULL, 2, 0, 1, 1, 4),
    (4, 'L''En-But',             '8 place Saint-Pierre',     '31000', 'Toulouse',    '05 61 00 00 04',
        'Pub sportif, bières artisanales et burgers.',                                 NULL, 4, 1, 0, 0, 5);

-- Évènements de démonstration : diffusions des journées 10 et 11
INSERT INTO evenement (id_evenement, date_heure_diffusion, infos_complementaires, id_bar, id_rencontre) VALUES
    (1, '2026-11-28 20:45:00', 'Planche à partager offerte.', 1, 68),
    (2, '2026-12-05 20:30:00', NULL, 1, 71),
    (3, '2026-11-27 20:30:00', 'Réservation conseillée.', 2, 64),
    (4, '2026-11-28 16:30:00', 'Ouverture 1h avant le coup d''envoi.', 3, 67),
    (5, '2026-12-05 20:30:00', NULL, 4, 71);

-- Bars favoris des supporters de démo
INSERT INTO utilisateur_bar (id_utilisateur, id_bar) VALUES
    (6, 1), (6, 4),
    (7, 3);
