Module 5 – Réclamations et Support

Projet AgriNoVa – Projet Technologies Web 2A – Esprit School of Engineering (2026–2027)

Responsable : Tasnim Trimich
Branche Git : feature/module5-reclamations

Table des matières

1. Objectif
2. Entités et relations
3. Fonctionnement du module
4. Requêtes SQL principales
5. Fonctionnalités
6. Règles métier et contrôles de saisie
7. Sécurité et rôles
8. Fichiers du module
9. Scénarios de test
10. Captures d’écran

⸻

1. Objectif

Le module Réclamations et Support permet d’accompagner les utilisateurs d’AgriNoVa après une interaction avec la plateforme : achat, location, diagnostic, formation ou parrainage.

Il permet de déposer une réclamation, de suivre son évolution et de consulter les réponses de l’administration.

L’objectif principal est d’assurer un suivi clair et organisé de chaque réclamation et d’améliorer la communication entre les utilisateurs et l’équipe administrative.

2. Entités et relations

Entité : Reclamation

Attribut	Type	Description
id_reclamation	INT, PK	Identifiant de la réclamation
id_utilisateur	INT, FK → utilisateur	Utilisateur ayant déposé la réclamation
sujet	VARCHAR	Sujet de la réclamation
categorie	VARCHAR	Catégorie de la réclamation
description	TEXT	Description détaillée du problème
date_creation	DATETIME	Date de création
statut	ENUM	État actuel de la réclamation

Entité : Reponse

Attribut	Type	Description
id_reponse	INT, PK	Identifiant de la réponse
id_reclamation	INT, FK → reclamation	Réclamation concernée
id_auteur	INT, FK → utilisateur	Auteur de la réponse
message	TEXT	Contenu de la réponse
date_reponse	DATETIME	Date de la réponse

Relation :

Une réclamation peut contenir plusieurs réponses.

Reclamation (1) — 👎 Reponse.

La clé étrangère id_reclamation permet de relier chaque réponse à sa réclamation correspondante.

3. Fonctionnement du module

Le fonctionnement du module se déroule en plusieurs étapes :

1. L’utilisateur accède au formulaire de réclamation.
2. Il choisit la catégorie concernée :
    * Commande
    * Location
    * Diagnostic
    * Formation
    * Parrainage
3. Il saisit le sujet et décrit son problème.
4. Il soumet sa réclamation.
5. La réclamation est enregistrée avec un statut initial.
6. L’administrateur consulte les réclamations reçues.
7. L’administrateur peut répondre à l’utilisateur et modifier le statut.
8. L’utilisateur consulte sa réclamation et suit les réponses depuis son espace personnel.

4. Requêtes SQL principales

Jointure : réclamations ouvertes et nombre de réponses

SELECT r.sujet, r.statut, COUNT(p.id_reponse) AS nb_rep
FROM reclamation r
LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation
WHERE r.statut <> 'cloturee'
GROUP BY r.id_reclamation;

Cette requête permet d’afficher les réclamations qui ne sont pas clôturées ainsi que le nombre de réponses associées à chacune.

La jointure LEFT JOIN permet de conserver les réclamations même lorsqu’elles ne possèdent aucune réponse.

5. Fonctionnalités

Front Office

Fonctionnalité	Rôle
Formulaire de dépôt d’une réclamation par catégorie	Agriculteur, vendeur, parrain
Consultation de la page « Mes réclamations »	Agriculteur, vendeur, parrain
Suivi du statut de chaque réclamation	Agriculteur, vendeur, parrain
Consultation du fil des réponses	Agriculteur, vendeur, parrain
Consultation de la FAQ	Utilisateurs

Back Office

Fonctionnalité	Rôle
Consultation de la liste des réclamations	Admin
Filtrage par statut et catégorie	Admin
Réponse aux réclamations	Admin
Modification du statut	Admin
Consultation des statistiques	Admin
Calcul du délai moyen de réponse	Admin
Statistiques des réclamations par catégorie	Admin

6. Règles métier et contrôles de saisie

Catégories disponibles

* Commande
* Location
* Diagnostic
* Formation
* Parrainage

Gestion des statuts

Une réclamation suit le cycle de vie suivant :

ouverte → en cours → résolue → clôturée

Validation des champs

Champ	Règle de validation
Sujet	Obligatoire, entre 5 et 150 caractères
Description	Obligatoire, minimum 20 caractères
Catégorie	Obligatoire, choix parmi les catégories disponibles

Ces règles permettent de garantir que les réclamations contiennent les informations nécessaires à leur traitement.

7. Sécurité et rôles

L’accès aux fonctionnalités dépend du rôle de l’utilisateur.

Front Office

Les rôles suivants peuvent déposer et suivre leurs propres réclamations :

* Agriculteur
* Vendeur
* Parrain

Chaque utilisateur ne peut consulter que ses propres réclamations.

Back Office

L’administrateur dispose des droits suivants :

* Consulter toutes les réclamations.
* Répondre aux utilisateurs.
* Modifier le statut des réclamations.
* Consulter les statistiques.

8. Fichiers du module

Models

models/
├── ReclamationModel.php
└── ReponseModel.php

* ReclamationModel.php : gestion des données des réclamations.
* ReponseModel.php : gestion des réponses associées aux réclamations.

Controllers

controllers/
├── ReclamationController.php
└── ReponseController.php

* ReclamationController.php : gestion des réclamations côté utilisateur et administration.
* ReponseController.php : gestion des réponses aux réclamations.

Views

views/
├── reclamation_form.php
├── mes_reclamations.php
├── reclamation_detail.php
├── faq.php
├── admin_reclamations.php
├── admin_reclamation_detail.php
└── admin_reponse_form.php

* reclamation_form.php : formulaire de dépôt d’une réclamation.
* mes_reclamations.php : historique des réclamations de l’utilisateur.
* reclamation_detail.php : détails d’une réclamation et ses réponses.
* faq.php : questions fréquentes.
* admin_reclamations.php : interface de gestion des réclamations.
* admin_reclamation_detail.php : détails d’une réclamation côté administration.
* admin_reponse_form.php : formulaire de réponse de l’administrateur.

Database

database/
└── m5_donnees_test.sql

Ce fichier contient les données de test du module.

9. Scénarios de test

#	Scénario	Résultat attendu
1	Un agriculteur dépose une réclamation concernant une commande	La réclamation est enregistrée
2	Un utilisateur soumet un formulaire sans sujet	La validation refuse le formulaire
3	Un utilisateur saisit une description de moins de 20 caractères	La validation refuse le formulaire
4	Un utilisateur ne sélectionne aucune catégorie	Un message de validation est affiché
5	Un utilisateur consulte « Mes réclamations »	Seules ses propres réclamations sont affichées
6	Un administrateur répond à une réclamation	La réponse est associée à la réclamation
7	Un administrateur modifie le statut	Le nouveau statut est enregistré
8	Un administrateur consulte les statistiques	Les statistiques des réclamations sont affichées

10. Captures d’écran

Front Office

* Formulaire de dépôt d’une réclamation.
* Page « Mes réclamations ».
* Détails d’une réclamation et fil des réponses.
* FAQ.

Back Office

* Liste des réclamations.
* Détails d’une réclamation.
* Formulaire de réponse.
* Statistiques des réclamations.