-- =====================================================================
-- AgriNoVa — base de données complète (7 modules, 14 tables)
-- Import : phpMyAdmin > Importer > ce fichier  (crée la base "agrinova")
-- Comptes de test : mot de passe « Agri@2026 » pour tous
-- =====================================================================

DROP DATABASE IF EXISTS agrinova;
CREATE DATABASE agrinova CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrinova;

-- ---------------------------------------------------------------------
-- MODULE 1 : Utilisateurs  (Role 1 — N Utilisateur)
-- ---------------------------------------------------------------------
CREATE TABLE role (
    id_role      INT AUTO_INCREMENT PRIMARY KEY,
    libelle      VARCHAR(30)  NOT NULL UNIQUE,
    description  VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(50)  NOT NULL,
    prenom         VARCHAR(50)  NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe   VARCHAR(255) NOT NULL,             -- hachage password_hash(), jamais en clair
    telephone      VARCHAR(20)  NOT NULL,
    id_role        INT          NOT NULL,
    statut         ENUM('actif', 'bloque') NOT NULL DEFAULT 'actif',
    date_creation  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role) REFERENCES role(id_role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 2 : Marketplace  (Categorie 1 — N Produit)
-- ---------------------------------------------------------------------
CREATE TABLE categorie (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(80)  NOT NULL UNIQUE,
    description  VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE produit (
    id_produit   INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(120)  NOT NULL,
    description  TEXT,
    prix         DECIMAL(10,2) NOT NULL CHECK (prix > 0),
    stock        INT           NOT NULL DEFAULT 0 CHECK (stock >= 0),
    image        VARCHAR(255),
    id_categorie INT NOT NULL,
    id_vendeur   INT NOT NULL,
    CONSTRAINT fk_produit_categorie FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie),
    CONSTRAINT fk_produit_vendeur   FOREIGN KEY (id_vendeur)   REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 3 : Diagnostic IA  (Maladie 1 — N Analyse)
-- ---------------------------------------------------------------------
CREATE TABLE maladie (
    id_maladie INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(120) NOT NULL,
    culture    VARCHAR(80)  NOT NULL,
    symptomes  TEXT,
    traitement TEXT
) ENGINE=InnoDB;

CREATE TABLE analyse (
    id_analyse     INT AUTO_INCREMENT PRIMARY KEY,
    id_maladie     INT NULL,                       -- NULL si aucune maladie détectée
    id_agriculteur INT NOT NULL,
    image          VARCHAR(255) NOT NULL,
    date_analyse   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confiance      DECIMAL(5,2) CHECK (confiance BETWEEN 0 AND 100),
    statut         ENUM('en_attente', 'terminee') NOT NULL DEFAULT 'en_attente',
    CONSTRAINT fk_analyse_maladie     FOREIGN KEY (id_maladie)     REFERENCES maladie(id_maladie) ON DELETE SET NULL,
    CONSTRAINT fk_analyse_agriculteur FOREIGN KEY (id_agriculteur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 4 : Location d'équipements  (Equipement 1 — N Reservation)
-- ---------------------------------------------------------------------
CREATE TABLE equipement (
    id_equipement INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(120)  NOT NULL,
    type          VARCHAR(60)   NOT NULL,
    tarif_jour    DECIMAL(10,2) NOT NULL CHECK (tarif_jour > 0),
    caution       DECIMAL(10,2) NOT NULL DEFAULT 0,
    etat          ENUM('disponible', 'maintenance', 'hors_service') NOT NULL DEFAULT 'disponible',
    id_vendeur    INT NOT NULL,
    CONSTRAINT fk_equipement_vendeur FOREIGN KEY (id_vendeur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

CREATE TABLE reservation (
    id_reservation INT AUTO_INCREMENT PRIMARY KEY,
    id_equipement  INT  NOT NULL,
    id_agriculteur INT  NOT NULL,
    date_debut     DATE NOT NULL,
    date_fin       DATE NOT NULL,
    montant        DECIMAL(10,2) NOT NULL,
    statut         ENUM('en_attente', 'acceptee', 'refusee', 'annulee', 'terminee') NOT NULL DEFAULT 'en_attente',
    CONSTRAINT chk_reservation_dates CHECK (date_fin >= date_debut),
    CONSTRAINT fk_reservation_equipement  FOREIGN KEY (id_equipement)  REFERENCES equipement(id_equipement) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_agriculteur FOREIGN KEY (id_agriculteur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 5 : Réclamations et support  (Reclamation 1 — N Reponse)
-- ---------------------------------------------------------------------
CREATE TABLE reclamation (
    id_reclamation INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    sujet          VARCHAR(150) NOT NULL,
    categorie      ENUM('commande', 'location', 'diagnostic', 'formation', 'parrainage', 'autre') NOT NULL,
    description    TEXT NOT NULL,
    date_creation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut         ENUM('ouverte', 'en_cours', 'resolue', 'cloturee') NOT NULL DEFAULT 'ouverte',
    CONSTRAINT fk_reclamation_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

CREATE TABLE reponse (
    id_reponse     INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT  NOT NULL,
    id_auteur      INT  NOT NULL,
    message        TEXT NOT NULL,
    date_reponse   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reponse_reclamation FOREIGN KEY (id_reclamation) REFERENCES reclamation(id_reclamation) ON DELETE CASCADE,
    CONSTRAINT fk_reponse_auteur      FOREIGN KEY (id_auteur)      REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 6 : Formations agricoles  (Formation 1 — N Inscription)
-- ---------------------------------------------------------------------
CREATE TABLE formation (
    id_formation   INT AUTO_INCREMENT PRIMARY KEY,
    titre          VARCHAR(150) NOT NULL,
    theme          VARCHAR(80)  NOT NULL,
    date_formation DATETIME     NOT NULL,
    places_max     INT          NOT NULL CHECK (places_max > 0),
    support_cours  VARCHAR(255),
    id_formateur   INT NOT NULL,
    CONSTRAINT fk_formation_formateur FOREIGN KEY (id_formateur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

CREATE TABLE inscription (
    id_inscription   INT AUTO_INCREMENT PRIMARY KEY,
    id_formation     INT NOT NULL,
    id_agriculteur   INT NOT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note_avis        TINYINT CHECK (note_avis BETWEEN 1 AND 5),
    statut           ENUM('inscrit', 'present', 'absent', 'annule') NOT NULL DEFAULT 'inscrit',
    CONSTRAINT uq_inscription UNIQUE (id_formation, id_agriculteur),
    CONSTRAINT fk_inscription_formation   FOREIGN KEY (id_formation)   REFERENCES formation(id_formation) ON DELETE CASCADE,
    CONSTRAINT fk_inscription_agriculteur FOREIGN KEY (id_agriculteur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MODULE 7 : Adopte un olivier  (Arbre 1 — N Parrainage)
-- ---------------------------------------------------------------------
CREATE TABLE arbre (
    id_arbre       INT AUTO_INCREMENT PRIMARY KEY,
    id_agriculteur INT NOT NULL,
    variete        VARCHAR(80)  NOT NULL,
    age_ans        INT          NOT NULL CHECK (age_ans >= 0),
    region         VARCHAR(80)  NOT NULL,
    prix_annuel    DECIMAL(10,2) NOT NULL CHECK (prix_annuel > 0),
    CONSTRAINT fk_arbre_agriculteur FOREIGN KEY (id_agriculteur) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

CREATE TABLE parrainage (
    id_parrainage INT AUTO_INCREMENT PRIMARY KEY,
    id_arbre      INT  NOT NULL,
    id_parrain    INT  NOT NULL,
    date_debut    DATE NOT NULL,
    duree_mois    INT  NOT NULL CHECK (duree_mois BETWEEN 1 AND 36),
    statut        ENUM('actif', 'termine', 'annule') NOT NULL DEFAULT 'actif',
    CONSTRAINT fk_parrainage_arbre   FOREIGN KEY (id_arbre)   REFERENCES arbre(id_arbre) ON DELETE CASCADE,
    CONSTRAINT fk_parrainage_parrain FOREIGN KEY (id_parrain) REFERENCES utilisateur(id_utilisateur)
) ENGINE=InnoDB;

-- =====================================================================
-- DONNÉES DE TEST
-- =====================================================================
INSERT INTO role (id_role, libelle, description) VALUES
(1, 'admin',       'Administrateur de la plateforme (Back Office)'),
(2, 'agriculteur', 'Achète, loue, fait diagnostiquer ses cultures, propose ses oliviers'),
(3, 'vendeur',     'Vend et loue du matériel, anime des formations'),
(4, 'parrain',     'Citoyen qui parraine un olivier');

-- Mot de passe de tous les comptes : Agri@2026
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, id_role, statut) VALUES
('Admin',    'AgriNoVa', 'admin@agrinova.tn',       '$2y$10$x/YShDeeEGowE1IEJNYyFee7h4P5EFEnxbWTVhSGUm/LXn6punzRG', '71000000', 1, 'actif'),
('Ben Salah','Karim',    'karim@agrinova.tn',       '$2y$10$x/YShDeeEGowE1IEJNYyFee7h4P5EFEnxbWTVhSGUm/LXn6punzRG', '22123456', 2, 'actif'),
('Trabelsi', 'Amel',     'amel@agrinova.tn',        '$2y$10$x/YShDeeEGowE1IEJNYyFee7h4P5EFEnxbWTVhSGUm/LXn6punzRG', '98765432', 3, 'actif'),
('Mansour',  'Youssef',  'youssef@agrinova.tn',     '$2y$10$x/YShDeeEGowE1IEJNYyFee7h4P5EFEnxbWTVhSGUm/LXn6punzRG', '55443322', 4, 'actif'),
('Gharbi',   'Sami',     'sami.bloque@agrinova.tn', '$2y$10$x/YShDeeEGowE1IEJNYyFee7h4P5EFEnxbWTVhSGUm/LXn6punzRG', '29876543', 2, 'bloque');

INSERT INTO categorie (nom, description) VALUES
('Capteurs', 'Humidité du sol, température, pH'),
('Drones', 'Drones de surveillance et de pulvérisation'),
('Stations météo', 'Stations connectées pour l’exploitation'),
('Kits IA', 'Caméras et kits de diagnostic');

INSERT INTO produit (nom, description, prix, stock, id_categorie, id_vendeur) VALUES
('Capteur d’humidité du sol', 'Capteur connecté longue portée', 189.00, 25, 1, 3),
('Drone de surveillance AgriFly', 'Autonomie 35 min, caméra multispectrale', 4990.00, 4, 2, 3);

INSERT INTO maladie (nom, culture, symptomes, traitement) VALUES
('Œil de paon', 'Olivier', 'Taches circulaires sur les feuilles', 'Traitement cuprique en automne'),
('Mildiou', 'Tomate', 'Taches brunes, duvet blanc', 'Fongicide adapté, aérer les cultures');

INSERT INTO equipement (nom, type, tarif_jour, caution, id_vendeur) VALUES
('Drone de pulvérisation T20', 'Drone', 250.00, 1500.00, 3);

INSERT INTO formation (titre, theme, date_formation, places_max, id_formateur) VALUES
('Irrigation goutte à goutte connectée', 'Irrigation', '2026-12-15 09:00:00', 20, 3);

INSERT INTO arbre (id_agriculteur, variete, age_ans, region, prix_annuel) VALUES
(2, 'Chemlali', 45, 'Sfax', 120.00),
(2, 'Chétoui', 30, 'Béja', 100.00);
