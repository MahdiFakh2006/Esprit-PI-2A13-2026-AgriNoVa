# M7 – Adopte un olivier (Olive Tree Sponsorship)

## Aperçu (Overview)

Le module **Adopte un olivier** fait partie d’**AgriNoVa**, une plateforme numérique agricole conçue pour soutenir l'agriculture durable en Tunisie.

Ce module permet à des citoyens, en Tunisie ou à l’étranger, de parrainer un olivier chez un agriculteur pour une saison donnée. L’agriculteur obtient ainsi un revenu stable pour cultiver de façon durable, tandis que le parrain suit l’évolution de son arbre et reçoit une part de la récolte.

## Objectifs

- Assurer un revenu stable aux agriculteurs pratiquant une agriculture durable.
- Permettre aux agriculteurs de proposer leurs oliviers au parrainage.
- Permettre aux parrains de découvrir et parrainer des oliviers selon la région et la variété.
- Assurer un suivi transparent de l’arbre à travers un journal (photos, saisons, récolte).
- Garantir la fiabilité des offres grâce à la validation des arbres par l'administrateur.
- Éviter les doublons grâce à la gestion d'un seul parrainage actif par arbre.

## Rôles des utilisateurs

### 1. Parrain

- Parcourir la carte et la liste des oliviers disponibles à l'adoption.
- Filtrer les oliviers par région et par variété.
- Consulter les détails et le journal d'un olivier.
- Parrainer un olivier (pour soi ou offert en cadeau).
- Consulter ses parrainages personnels et suivre son arbre.

### 2. Agriculteur

- Proposer ses oliviers au parrainage.
- Modifier ou gérer les informations de ses arbres proposés.
- Publier des photos et des mises à jour sur la récolte dans le journal de l'arbre.
- Consulter l'état de parrainage de ses oliviers.

### 3. Administrateur

- Valider les oliviers proposés par les agriculteurs avant leur publication.
- Superviser et gérer les parrainages actifs.
- Consulter les statistiques (revenus reversés aux agriculteurs, parrainages par région).
- Assurer le suivi global du module.

## Front Office

Le Front Office permet aux utilisateurs d'accéder aux fonctionnalités principales du module :

- Affichage de la carte et de la liste des oliviers validés à adopter.
- Filtrage des oliviers par région et par variété.
- Consultation des détails d'un olivier et de son journal (photos, saisons, récolte).
- Souscription d'un parrainage (ou parrainage offert en cadeau).
- Consultation de ses parrainages personnels (Parrain).
- Proposition d'oliviers et publication des suivis de récolte (Agriculteur).

## Back Office

Le Back Office permet la gestion administrative du module :

- Validation des oliviers proposés par les agriculteurs.
- Gestion et supervision des arbres enregistrés.
- Suivi des parrainages actifs.
- Consultation des statistiques : revenus reversés aux agriculteurs et parrainages par région.

## Structure de la base de données

### 1. Arbre (`arbre`)

| Champ | Description |
| --- | --- |
| `id_arbre` | Clé primaire |
| `id_agriculteur` | Identifiant de l'agriculteur (clé étrangère) |
| `variete` | Variété de l'olivier |
| `age_ans` | Âge de l'olivier en années |
| `region` | Région de culture de l'arbre |
| `prix_annuel` | Tarif annuel du parrainage |

### 2. Parrainage (`parrainage`)

| Champ | Description |
| --- | --- |
| `id_parrainage` | Clé primaire |
| `id_arbre` | Identifiant de l'olivier (clé étrangère) |
| `id_parrain` | Identifiant du parrain (clé étrangère) |
| `date_debut` | Date de début du parrainage |
| `duree_mois` | Durée du parrainage en mois |
| `statut` | Statut de la réservation / du parrainage |

## Règles de gestion et contrôles de saisie

- La variété et la région de l'olivier sont obligatoires.
- L'âge de l'arbre doit être supérieur ou égal à 0 (`age_ans >= 0`).
- Le prix annuel doit être strictement positif (`prix_annuel > 0`).
- Seuls les arbres validés par l’administrateur sont visibles sur la carte publique.
- Chaque parrainage doit être associé à un arbre validé et existant.
- La durée du parrainage doit être comprise entre 1 et 36 mois (`1 <= duree_mois <= 36`).
- La date de début du parrainage doit être valide.
- Un arbre ne peut avoir qu’un seul parrainage actif à la fois.
- Un parrain doit être authentifié avant d'effectuer un parrainage.
- Un agriculteur ne peut publier des photos et des informations de récolte que sur ses propres arbres.

## Accès selon le rôle (Role-Based Access)

| Fonctionnalité | Rôle autorisé |
| --- | --- |
| Consulter la carte des oliviers | Parrain |
| Parrainer un arbre | Parrain |
| Suivre le journal de son arbre | Parrain |
| Proposer ses arbres au parrainage | Agriculteur |
| Publier photos et récolte | Agriculteur |
| Valider les arbres proposés | Administrateur |
| Gérer les parrainages et statistiques | Administrateur |

## Exemple de requête SQL

Récupérer les parrainages actifs et les revenus générés par région :

```sql
SELECT a.region, COUNT(*) AS nb, SUM(a.prix_annuel) AS total
FROM parrainage p
JOIN arbre a
    ON p.id_arbre = a.id_arbre
WHERE p.statut = 'actif'
GROUP BY a.region;
```

## Structure des fichiers du module

```text
M7/
│
├── models/
│   ├── ArbreModel.php
│   └── ParrainageModel.php
│
├── controllers/
│   ├── ArbreController.php
│   └── ParrainageController.php
│
├── views/
│   ├── oliviers.php
│   ├── arbre_detail.php
│   ├── mes_parrainages.php
│   ├── mes_arbres.php
│   ├── admin_arbres.php
│   └── admin_parrainages.php
│
└── README.md
```

## Technologies utilisées

- **PHP** : Logique métier et gestion des fonctionnalités (architecture MVC, PDO).
- **MySQL** : Stockage et gestion des données.
- **HTML** : Structure des interfaces.
- **CSS** : Mise en forme des interfaces.
- **JavaScript** : Interactions dynamiques et validation côté client.

## Informations sur le module

| Détail | Information |
| --- | --- |
| **Projet** | AgriNoVa |
| **Module** | M7 – Adopte un olivier |
| **Responsable** | Amine Kochbati |
| **Entités principales** | Arbre, Parrainage |

---

*AgriNoVa – L’intelligence au service de la terre.*