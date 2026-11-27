-- =====================================================================
-- MODULE 5 — données de test (Réclamations et support)
-- À importer APRÈS database/agrinova.sql :  phpMyAdmin > base agrinova > Importer
-- Utilisateurs : 1 = admin, 2 = karim (agriculteur), 3 = amel (vendeur), 4 = youssef (parrain)
-- Ré-importable : vide d'abord les deux tables du module.
-- =====================================================================
USE agrinova;

DELETE FROM reponse;
DELETE FROM reclamation;
ALTER TABLE reclamation AUTO_INCREMENT = 1;
ALTER TABLE reponse AUTO_INCREMENT = 1;

INSERT INTO reclamation (id_utilisateur, sujet, categorie, description, date_creation, statut) VALUES
(2, 'Capteur d’humidité défectueux', 'commande',
    'Le capteur reçu hier n’affiche aucune valeur après l’installation dans ma parcelle. J’ai essayé deux piles neuves.', '2026-11-02 09:15:00', 'en_cours'),
(2, 'Drone indisponible à la date réservée', 'location',
    'Mon drone de pulvérisation devait être disponible le 10 novembre mais le loueur m’a dit qu’il était déjà parti.', '2026-11-05 14:30:00', 'ouverte'),
(3, 'Diagnostic incorrect sur mes tomates', 'diagnostic',
    'L’analyse indique le mildiou alors que la photo montre clairement une carence en magnésium. Pouvez-vous vérifier ?', '2026-10-28 08:00:00', 'resolue'),
(4, 'Journal de mon olivier jamais reçu', 'parrainage',
    'Je parraine un olivier Chemlali à Sfax depuis un mois et je n’ai reçu aucune nouvelle ni aucune photo.', '2026-10-15 17:45:00', 'cloturee'),
(2, 'Impossible de m’inscrire à la formation irrigation', 'formation',
    'Le bouton d’inscription affiche une erreur alors qu’il reste des places pour la session de décembre.', '2026-11-10 10:20:00', 'ouverte'),
(3, 'Facture de commande incomplète', 'commande',
    'La facture de ma dernière commande ne mentionne pas la TVA ni l’adresse de livraison demandée.', '2026-11-12 11:05:00', 'ouverte');

INSERT INTO reponse (id_reclamation, id_auteur, message, date_reponse) VALUES
(1, 1, 'Bonjour Karim, merci pour votre message. Pouvez-vous nous indiquer la référence du capteur ? Nous préparons un échange.', '2026-11-02 11:40:00'),
(3, 1, 'Bonjour Amel, nous avons relancé l’analyse avec votre photo : il s’agit bien d’une carence en magnésium. Le diagnostic a été corrigé.', '2026-10-28 15:10:00'),
(3, 1, 'Nous passons cette réclamation en « résolue ». Merci pour votre retour qui améliore notre modèle.', '2026-10-29 09:00:00'),
(4, 1, 'Bonsoir Youssef, le journal de votre olivier vous a été envoyé par e-mail. Dossier clôturé.', '2026-10-16 10:30:00');
