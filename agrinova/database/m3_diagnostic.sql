-- ============================================================
-- AgriNoVa — Module 3 : Diagnostic IA  (Maladie 1 — N Analyse)
-- Complète les tables maladie et analyse créées par agrinova.sql.
-- À exécuter dans phpMyAdmin (base agrinova → onglet SQL). Peut être relancé sans erreur.
-- ============================================================

-- ---------- maladie : couleur du symptôme (utilisée par l'analyse d'image) et gravité ----------
ALTER TABLE maladie
    ADD COLUMN IF NOT EXISTS couleur_symptome ENUM('jaune', 'brun', 'blanc', 'noir') NULL DEFAULT NULL AFTER traitement,
    ADD COLUMN IF NOT EXISTS gravite ENUM('faible', 'moyenne', 'elevee') NOT NULL DEFAULT 'moyenne' AFTER couleur_symptome;

-- Règle métier : une maladie n'est enregistrée qu'une fois par culture
ALTER TABLE maladie
    ADD UNIQUE INDEX IF NOT EXISTS uq_maladie_nom_culture (nom, culture);

-- ---------- analyse : culture choisie par l'agriculteur et pourcentage de feuille atteinte ----------
ALTER TABLE analyse
    ADD COLUMN IF NOT EXISTS culture VARCHAR(80) NOT NULL DEFAULT '' AFTER id_agriculteur,
    ADD COLUMN IF NOT EXISTS taux_atteinte DECIMAL(5,2) NULL DEFAULT NULL AFTER confiance;

-- ---------- Données de test ----------
UPDATE maladie SET couleur_symptome = 'brun', gravite = 'moyenne' WHERE nom = 'Œil de paon' AND culture = 'Olivier';
UPDATE maladie SET couleur_symptome = 'brun', gravite = 'elevee'  WHERE nom = 'Mildiou'     AND culture = 'Tomate';

INSERT IGNORE INTO maladie (nom, culture, symptomes, traitement, couleur_symptome, gravite) VALUES
('Fumagine', 'Olivier', 'Dépôt noir sur les feuilles, souvent après une attaque de cochenilles',
 'Lutter contre les cochenilles, laver le feuillage, tailler pour aérer', 'noir', 'faible'),
('Chlorose ferrique', 'Agrumes', 'Feuilles jaunes avec des nervures qui restent vertes',
 'Apport de chélate de fer, corriger le pH du sol', 'jaune', 'moyenne'),
('Oïdium', 'Vigne', 'Feutrage blanc poudreux sur les feuilles et les grappes',
 'Soufre en poudre ou mouillable, éviter l’excès d’azote', 'blanc', 'moyenne'),
('Alternariose', 'Tomate', 'Taches brunes en cercles concentriques sur les vieilles feuilles',
 'Retirer les feuilles atteintes, fongicide à base de cuivre', 'brun', 'moyenne'),
('Septoriose', 'Blé', 'Taches brunes allongées avec des points noirs',
 'Rotation des cultures, fongicide au stade épiaison', 'brun', 'elevee'),
('Rouille jaune', 'Blé', 'Pustules jaunes alignées le long des feuilles',
 'Variétés résistantes, fongicide dès les premiers symptômes', 'jaune', 'elevee');
