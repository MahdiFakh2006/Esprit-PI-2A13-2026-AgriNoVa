RÉCLAMATIONS ET SUPPORT
Projet AgriNoVa – Projet Technologies Web 2A
Esprit School of Engineering | 2026–2027
Responsable : Tasnim Trimich
1. Présentation et objectif
Le module Réclamations et support permet d’accompagner les utilisateurs d’AgriNoVa après une interaction avec la plateforme : achat, location, diagnostic, formation ou parrainage. Il leur offre un moyen de déposer une réclamation, de consulter son état d’avancement et de suivre les réponses échangées avec l’administration.
2. Entités et relations
Le module repose sur deux entités principales : Reclamation et Reponse.
Entité	Attribut	Type / relation	Description
Reclamation	id_reclamation	INT, PK	Identifiant unique de la réclamation.
Reclamation	id_utilisateur	INT, FK → utilisateur	Utilisateur ayant déposé la réclamation.
Reclamation	sujet	VARCHAR	Sujet de la réclamation.
Reclamation	categorie	VARCHAR / catégorie	Domaine concerné : commande, location, diagnostic, formation ou parrainage.
Reclamation	description	TEXT	Détails du problème ou de la demande.
Reclamation	date_creation	DATETIME	Date de création de la réclamation.
Reclamation	statut	Statut	État actuel de traitement de la réclamation.
Reponse	id_reponse	INT, PK	Identifiant unique de la réponse.
Reponse	id_reclamation	INT, FK → reclamation	Réclamation à laquelle la réponse est associée.
Reponse	id_auteur	INT, FK → utilisateur	Utilisateur ayant rédigé la réponse.
Reponse	message	TEXT	Contenu de la réponse.
Reponse	date_reponse	DATETIME	Date d’envoi de la réponse.

Relation entre les entités
Une réclamation peut contenir plusieurs réponses, tandis qu’une réponse appartient à une seule réclamation. La relation est donc de type 1 — N : Reclamation (1) → Reponse (N), assurée par la clé étrangère id_reclamation.
3. Fonctionnement général
1.	L’utilisateur ouvre le formulaire de réclamation et choisit une catégorie.
2.	Il saisit le sujet et décrit le problème, puis envoie le formulaire.
3.	La réclamation est enregistrée avec sa date de création et un statut initial.
4.	L’utilisateur consulte la page « Mes réclamations » pour suivre le statut et les réponses.
5.	L’administrateur consulte les réclamations, répond à l’utilisateur et met à jour le statut selon l’avancement du traitement.
6.	La FAQ permet également à l’utilisateur de consulter les réponses aux questions fréquentes.
4. Requête SQL principale
Cette requête affiche les réclamations qui ne sont pas encore clôturées ainsi que le nombre de réponses associées.
SELECT r.sujet, r.statut, COUNT(p.id_reponse) AS nb_rep
FROM reclamation r
LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation
WHERE r.statut <> 'cloturee'
GROUP BY r.id_reclamation;
5. Fonctionnalités
Front Office
Fonctionnalité	Description
Formulaire de réclamation	Permet de déposer une réclamation et de sélectionner une catégorie : commande, location, diagnostic, formation ou parrainage.
Mes réclamations	Affiche les réclamations de l’utilisateur, leur statut et le fil des réponses.
Détail d’une réclamation	Permet de consulter les informations et les échanges liés à une réclamation.
FAQ	Présente les réponses aux questions fréquentes.

Back Office
Fonctionnalité	Description
Liste des réclamations	Affiche les réclamations et permet de les filtrer par statut et par catégorie.
Réponse au client	Permet à l’administrateur d’envoyer une réponse liée à une réclamation.
Gestion du statut	Permet de faire évoluer le statut d’une réclamation selon son traitement.
Statistiques	Prévoit le suivi du délai moyen de réponse et du nombre de réclamations par catégorie.

6. Règles métier et contrôles de saisie
Élément	Règle
Sujet	Obligatoire ; entre 5 et 150 caractères.
Description	Obligatoire ; 20 caractères minimum.
Catégorie	Obligatoire.
Statuts	Cycle de traitement : ouverte → en cours → résolue → clôturée.
Confidentialité	Un utilisateur ne peut consulter que ses propres réclamations.

7. Accès selon les rôles
Action	Rôle autorisé
Déposer une réclamation	Agriculteur, vendeur, parrain
Consulter et suivre ses réclamations	Agriculteur, vendeur, parrain
Répondre à une réclamation	Administrateur
Modifier le statut d’une réclamation	Administrateur
Consulter les statistiques	Administrateur

8. Fichiers du module
Dossier / fichier	Rôle
models/ReclamationModel.php	Gestion des données liées aux réclamations.
models/ReponseModel.php	Gestion des données liées aux réponses.
controllers/ReclamationController.php	Traitement des actions relatives aux réclamations.
controllers/ReponseController.php	Traitement des réponses aux réclamations.
views/reclamation_form.php	Formulaire de dépôt d’une réclamation.
views/mes_reclamations.php	Liste des réclamations de l’utilisateur.
views/reclamation_detail.php	Détail d’une réclamation et suivi des réponses.
views/admin_reclamations.php	Interface d’administration des réclamations.
views/admin_reclamation_detail.php	Détail d’une réclamation côté administration.

9. Scénarios de test
N°	Scénario	Résultat attendu
1	Un utilisateur dépose une réclamation avec une catégorie, un sujet et une description valides.	La réclamation est enregistrée et apparaît dans « Mes réclamations ».
2	L’utilisateur tente d’envoyer un sujet de moins de 5 caractères.	La saisie est refusée.
3	L’utilisateur envoie une description de moins de 20 caractères.	La saisie est refusée.
4	Un utilisateur consulte ses réclamations.	Seules ses propres réclamations sont affichées.
5	L’administrateur ouvre une réclamation et envoie une réponse.	La réponse est associée à la bonne réclamation.
6	L’administrateur modifie le statut d’une réclamation.	Le nouveau statut est affiché dans le suivi.

10. Conclusion
Le module Réclamations et support centralise les demandes des utilisateurs et facilite leur suivi. La séparation entre l’espace utilisateur et l’espace administrateur permet de structurer le traitement des réclamations et de conserver l’historique des échanges.
