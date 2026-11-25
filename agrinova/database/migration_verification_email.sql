-- ============================================================
-- AgriNoVa — Module 1 : vérification de l'adresse e-mail
-- À exécuter UNE fois dans phpMyAdmin (base agrinova → onglet SQL)
-- ============================================================

ALTER TABLE utilisateur
    ADD COLUMN email_verifie     TINYINT(1)   NOT NULL DEFAULT 0    AFTER statut,
    ADD COLUMN code_verification VARCHAR(255) NULL     DEFAULT NULL AFTER email_verifie,  -- code HACHÉ (password_hash)
    ADD COLUMN code_expiration   DATETIME     NULL     DEFAULT NULL AFTER code_verification,
    ADD COLUMN code_tentatives   TINYINT      NOT NULL DEFAULT 0    AFTER code_expiration;

-- Les comptes qui existent déjà (admin, comptes de test…) sont considérés comme vérifiés
UPDATE utilisateur SET email_verifie = 1;
