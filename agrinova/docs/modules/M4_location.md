#  M4 – Location d’équipements (Equipment Rental

##  Aperçu (Overview)

Le module **Location d’équipements** fait partie d’**AgriNoVa**, une plateforme numérique agricole conçue pour faciliter l'accès des agriculteurs tunisiens aux équipements agricoles.

Ce module permet aux vendeurs de publier et de gérer leurs équipements à louer. Les agriculteurs peuvent consulter les équipements disponibles, vérifier leurs informations et effectuer des réservations pour des périodes spécifiques.

##  Objectifs

- Faciliter l'accès aux équipements agricoles.
- Permettre aux vendeurs de gérer leur matériel en location.
- Permettre aux agriculteurs de consulter les équipements disponibles.
- Simplifier le processus de réservation.
- Éviter le chevauchement des réservations.
- Assurer un suivi des demandes de location.

##  Rôles des utilisateurs

### 1. Vendeur

- Ajouter des équipements à louer.
- Modifier les informations de ses équipements.
- Supprimer ses équipements.
- Consulter les demandes de réservation.
- Accepter ou refuser les demandes de réservation.

### 2. Agriculteur 

- Parcourir les équipements disponiblrs.
- Consulter les détails des équipements.
- Vérifier leur disponibilité.
- Réserver un équipement pour une période donnée.
- Consulter ses réservations personnelles.
- Annuler une réservation selon les règles de gestion.

### 3. Administrateur

- Superviser les activités de location.
- Consulter les équipements enregistrés.
- Surveiller les réservations.
- Assurer le suivi global du module.

##  Front Office

Le Front Office permet aux utilisateurs d'accéder aux fonctionnalités principales du module :

- Affichage des équipements disponibles à la location.
- Recherche et filtrage des équipements.
- Consultation des détails d'un équipement.
- Consultation de la disponibilité des équipements.
- Réservation d'un équipement.
- Calcul automatique du montant de la location.
- Consultation des réservations personnelles.

##  Back Office

Le Back Office permet la gestion administrative du module :

- Gestion des équipements (CRUD : ajout, affichage, modification et suppression).
- Gestion des informations des équipements.
- Consultation des demandes de réservation.
- Acceptation ou refus des demandes par les vendeurs.
- Supervision des équipements et des réservations par l'administrateur.

##  Structure de la base de données

### 1. Équipement (`equipement`)

| Champ | Description |
| --- | --- |
| `id_equipement` | Clé primaire |
| `nom` | Nom de l'équipement |
| `type` | Type d'équipement |
| `tarif_jour` | Tarif de location journalier |
| `caution` | Dépôt de garantie |
| `etat` | État de l'équipement |
| `id_vendeur` | Identifiant du vendeur (clé étrangère) |

### 2. Réservation (`reservation`)

| Champ | Description |
| --- | --- |
| `id_reservation` | Clé primaire |
| `id_equipement` | Identifiant de l'équipement (clé étrangère) |
| `id_agriculteur` | Identifiant de l'agriculteur |
| `date_debut` | Date de début de la réservation |
| `date_fin` | Date de fin de la réservation |
| `montant` | Montant total de la location |
| `statut` | Statut de la réservation |

##  Règles de gestion et contrôles de saisie

- Le nom de l'équipement est obligatoire.
- Le type d'équipement est obligatoire.
- Le tarif journalier doit être strictement positif.
- La caution ne peut pas être négative.
- Chaque réservation doit être associée à un équipement existant.
- Les dates de réservation doivent être valides.
- La date de début ne peut pas être dans le passé.
- La date de fin doit être supérieure ou égale à la date de début.
- Deux réservations confirmées ou en attente pour le même équipement ne peuvent pas se chevaucher.
- Le montant total est calculé automatiquement selon la formule :

  `montant = tarif_jour × nombre_de_jours`

- Un agriculteur doit être authentifié avant d'effectuer une réservation.
- Un vendeur ne peut gérer que ses propres équipements.
- Seul le vendeur concerné peut accepter ou refuser une demande de réservation.

##  Accès selon le rôle (Role-Based Access)

| Fonctionnalité | Rôle autorisé |
| --- | --- |
| Consulter les équipements | Agriculteur |
| Effectuer une réservation | Agriculteur |
| Consulter ses réservations | Agriculteur |
| Ajouter un équipement | Vendeur |
| Modifier un équipement | Vendeur |
| Supprimer un équipement | Vendeur |
| Accepter ou refuser une réservation | Vendeur |
| Superviser les équipements et réservations | Administrateur |

##  Exemple de requête SQL

Récupérer les réservations d'un agriculteur avec les noms des équipementd correspondants :

```sql
SELECT e.nom, r.date_debut, r.date_fin, r.statut
FROM reservation r
JOIN equipement e
    ON r.id_equipement = e.id_equipement
WHERE r.id_agriculteur = ?;
```

##  Structure des fichiers du module

```text
agrinoca
│
├── models/
│   └── EquipementModel.php
│
├── controllers/
│   └── LocationController.php
│
├── views/
│   └── (Interfaces du module)
│
└── README.md
```

##  Technologies utilisées

- **PHP** : Logique métier et gestion des fonctionnalités.
- **MySQL** : Stockage et gestion des données.
- **HTML** : Structure des interfaces.
- **CSS** : Mise en forme des interfaces.
- **JavaScript** : Interactions dynamiques.

##  Informations sur le module

| Détail | Information |
| --- | --- |
| **Projet** | AgriNoVa |
| **Module** | M4 – Location d’équipements |
| **Responsable** | Mahdi Dhaouadi |
| **Entités principales** | Equipement, Reservation |

---

*AgriNoVa – L’intelligence au service de la terre.*
