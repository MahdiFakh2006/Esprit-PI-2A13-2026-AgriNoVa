-- =====================================================================
-- MODULE 5 — Réclamations et support : structure des tables
-- Reclamation 1 — N Reponse
-- Extrait de database/agrinova.sql (déjà inclus dans l'import complet).
-- Ce fichier est fourni pour la documentation : il suppose que la table
-- `utilisateur` (module 1) existe déjà.
-- =====================================================================
USE agrinova;

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

-- Requête de jointure du module : réclamations ouvertes et nombre de réponses
SELECT r.sujet, r.statut, COUNT(p.id_reponse) AS nb_rep
FROM reclamation r
LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation
WHERE r.statut <> 'cloturee'
GROUP BY r.id_reclamation;
