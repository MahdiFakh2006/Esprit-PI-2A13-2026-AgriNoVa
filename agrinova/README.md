# AgriNoVa — L’intelligence au service de la terre

Plateforme web de **transition écologique et d’agriculture durable** pour les agriculteurs tunisiens :
marketplace de matériel agricole intelligent, location d’équipements, diagnostic des cultures par IA,
formations, support client et parrainage d’oliviers (« Adopte un olivier »).

Projet réalisé dans le cadre du module **Projet Technologies Web 2A** (Esprit — UP WEB, 2026-2027).

---
Problème résolu : ces services sont aujourd’hui dispersés (vente de matériel d’un côté, conseils agronomiques de l’autre), et le matériel intelligent reste trop cher à l’achat pour beaucoup d’exploitations. AgriNoVa les réunit dans une seule plateforme.



## Table des matières
- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Security](#security)
- [Contributors](#contributors)
- [Academic Context](#academic-context)
- [Getting Started](#getting-started)
- [Acknowledgments](#acknowledgments)
- [License](#license)




## Features

| Module | Fonctionnalités | Entités (jointure) |
|---|---|---|
| **M1 – Utilisateurs** | Inscription par rôle (agriculteur, vendeur, parrain), connexion sécurisée, profil, Back Office : CRUD des comptes, rôles, blocage, statistiques | Role ↔ Utilisateur |
| **M2 – Marketplace** | Catalogue de capteurs, drones, stations météo et kits IA ; gestion des produits par les vendeurs | Categorie ↔ Produit |
| **M3 – Diagnostic IA** | Analyse de la photo d’une feuille, maladie détectée et traitement conseillé, historique | Maladie ↔ Analyse |
| **M4 – Location d’équipements** | Réservation de matériel coûteux pour quelques jours au lieu de l’acheter | Equipement ↔ Reservation |
| **M5 – Réclamations et support** | Dépôt et suivi des réclamations, fil de réponses | Reclamation ↔ Reponse |
| **M6 – Formations agricoles** | Calendrier des formations (irrigation, drones, IA, bio), inscriptions | Formation ↔ Inscription |
| **M7 – Adopte un olivier** | Des citoyens parrainent un olivier et assurent un revenu stable à l’agriculteur | Arbre ↔ Parrainage |

Chaque module dispose d’un **Front Office** (utilisateurs) et d’un **Back Office** (administration).

### Détail des modules

- [Module 1 – Utilisateurs](#module-1--utilisateurs)
- [Module 2 – Marketplace](#module-2--marketplace)
- [Module 3 – Diagnostic IA](#module-3--diagnostic-ia)
- [Module 4 – Location d’équipements](#module-4--location-déquipements)
- [Module 5 – Réclamations et support](#module-5--réclamations-et-support)
- [Module 6 – Formations agricoles](#module-6--formations-agricoles)
- [Module 7 – Adopte un olivier](#module-7--adopte-un-olivier)

---


#### Module 1 – Utilisateurs

**Responsable :** [Mohamed MOALLA](https://github.com/MedMoalla) (chef de projet)

**Objectif :** gérer les comptes, les rôles et la sécurité de toute la plateforme. Tous les autres modules s’appuient
sur ce module, car chaque action demande d’être connecté avec le bon rôle.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Role` | id_role (PK), libelle, description |
| `Utilisateur` | id_utilisateur (PK), nom, prenom, email, mot_de_passe, telephone, id_role (FK → role), statut, date_creation |

Relation : **un rôle → plusieurs utilisateurs** (1 — N, clé étrangère `id_role`).

```sql
-- Liste des utilisateurs avec leur rôle
SELECT u.nom, u.prenom, u.email, r.libelle
FROM utilisateur u
JOIN role r ON u.id_role = r.id_role
ORDER BY r.libelle;
```

**Front Office**
- Inscription avec choix du type de compte : agriculteur, vendeur ou parrain.
- Connexion et déconnexion, redirection selon le rôle.
- Espace personnel avec les raccourcis vers les modules du rôle.
- Modification du profil et du mot de passe.

**Back Office**
- Tableau de bord : nombre d’utilisateurs par rôle, comptes bloqués, derniers inscrits.
- CRUD des utilisateurs avec recherche, filtres (rôle, statut), tri et pagination.
- Blocage et déblocage d’un compte, changement de rôle.

**Règles métier et contrôles de saisie**
- Impossible de s’inscrire comme administrateur (liste blanche des rôles).
- E-mail unique ; téléphone tunisien à 8 chiffres ; mot de passe de 8 caractères minimum avec majuscule, minuscule et chiffre.
- Un compte bloqué ne peut plus se connecter et sa session est fermée immédiatement.
- Un administrateur ne peut ni se supprimer, ni se bloquer, ni se retirer ses droits.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| S’inscrire, se connecter | visiteur |
| Espace personnel, profil | agriculteur, vendeur, parrain |
| Gestion des comptes, statistiques | admin |

**Fichiers du module**
```
models/UserModel.php, models/RoleModel.php
controllers/AuthController.php, controllers/UserController.php, controllers/Validator.php
views/login.php, views/register.php, views/user_space.php, views/profile.php
views/admin_space.php, views/admin_users.php, views/admin_user_form.php
```
#### Module 2 – Marketplace

**Responsable :** [Eya Hammami](https://github.com/eya617)

**Objectif :** rendre le matériel agricole intelligent accessible depuis une seule plateforme : capteurs, drones,
stations météo et kits IA, vendus par des vendeurs vérifiés.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Categorie` | id_categorie (PK), nom, description |
| `Produit` | id_produit (PK), nom, description, prix, stock, image, id_categorie (FK → categorie), id_vendeur (FK → utilisateur) |

Relation : **une catégorie → plusieurs produits** (1 — N, clé étrangère `id_categorie`).

```sql
-- Catalogue : produits disponibles et leur catégorie
SELECT p.nom, p.prix, p.stock, c.nom AS categorie
FROM produit p
JOIN categorie c ON p.id_categorie = c.id_categorie
WHERE p.stock > 0;
```

**Front Office**
- Catalogue avec filtres par catégorie et par prix, recherche par nom.
- Fiche produit détaillée.
- Comparaison de deux équipements.
- Panier et commande (agriculteur) ; gestion de ses produits (vendeur).

**Back Office**
- CRUD des catégories.
- CRUD des produits, avec suivi du stock.
- Statistiques : produits par catégorie, produits en rupture de stock.

**Règles métier et contrôles de saisie**
- Prix strictement positif, stock entier supérieur ou égal à 0.
- Nom de 2 à 120 caractères ; image au format JPG ou PNG.
- Un vendeur ne peut modifier que ses propres produits.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Parcourir le catalogue, commander | agriculteur |
| Ajouter et gérer ses produits | vendeur |
| Gérer les catégories et tous les produits | admin |

**Fichiers du module**
```
models/CategorieModel.php, models/ProduitModel.php
controllers/CategorieController.php, controllers/ProduitController.php
views/catalogue.php, views/produit_detail.php, views/mes_produits.php
views/admin_categories.php, views/admin_produits.php, views/admin_produit_form.php
```

---

#### Module 3 – Diagnostic IA

**Responsable :** [Mohamed MOALLA](https://github.com/MedMoalla)

**Objectif :** aider l’agriculteur à détecter tôt une maladie à partir de la photo d’une feuille, et lui proposer un traitement.
Le résultat est indicatif et ne remplace pas l’avis d’un agronome.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Maladie` | id_maladie (PK), nom, culture, symptomes, traitement |
| `Analyse` | id_analyse (PK), id_maladie (FK → maladie), id_agriculteur (FK → utilisateur), image, date_analyse, confiance, statut |

Relation : **une maladie → plusieurs analyses** (1 — N, clé étrangère `id_maladie`).

```sql
-- Historique des analyses d’un agriculteur
SELECT a.date_analyse, m.nom, a.confiance, m.traitement
FROM analyse a
JOIN maladie m ON a.id_maladie = m.id_maladie
WHERE a.id_agriculteur = ?;
```

**Front Office**
- Envoi de la photo d’une feuille malade.
- Résultat : maladie probable, taux de confiance et traitement conseillé.
- Historique de ses analyses.
- Lien vers le support (module 5) en cas de doute.

**Back Office**
- CRUD de la liste des maladies (symptômes, traitements).
- Consultation de toutes les analyses.
- Statistiques : maladies les plus détectées, par culture.

**Règles métier et contrôles de saisie**
- Image obligatoire, au format JPG ou PNG, de 5 Mo maximum.
- Confiance comprise entre 0 et 100 %.
- Prototype : analyse simulée ou modèle pré-entraîné.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Lancer une analyse, voir son historique | agriculteur |
| Gérer les maladies, voir toutes les analyses | admin |

**Fichiers du module**
```
models/MaladieModel.php, models/AnalyseModel.php
controllers/MaladieController.php, controllers/AnalyseController.php
views/diagnostic.php, views/resultat_analyse.php, views/mes_analyses.php
views/admin_maladies.php, views/admin_maladie_form.php, views/admin_analyses.php
```

---

#### Module 4 – Location d’équipements

**Responsable :** [Mahdi Dhaouadi](https://github.com/MahdiDhaouadi)

**Objectif :** permettre aux petites exploitations d’utiliser du matériel coûteux (drones, stations…) quelques jours
au lieu de l’acheter, ce qui réduit les coûts et le gaspillage de ressources.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Equipement` | id_equipement (PK), nom, type, tarif_jour, caution, etat, id_vendeur (FK → utilisateur) |
| `Reservation` | id_reservation (PK), id_equipement (FK → equipement), id_agriculteur (FK → utilisateur), date_debut, date_fin, montant, statut |

Relation : **un équipement → plusieurs réservations** (1 — N, clé étrangère `id_equipement`).

```sql
-- Mes réservations, avec le nom de l’équipement
SELECT e.nom, r.date_debut, r.date_fin, r.statut
FROM reservation r
JOIN equipement e ON r.id_equipement = e.id_equipement
WHERE r.id_agriculteur = ?;
```

**Front Office**
- Liste des équipements à louer avec le calendrier des disponibilités.
- Réservation d’une période et calcul automatique du montant.
- Annulation d’une réservation (agriculteur).
- Acceptation ou refus des demandes (vendeur).

**Back Office**
- CRUD des équipements.
- Supervision de toutes les réservations.
- Statistiques : taux d’occupation par équipement, chiffre d’affaires des locations.

**Règles métier et contrôles de saisie**
- Date de fin postérieure ou égale à la date de début, et date de début non passée.
- Pas de réservations qui se chevauchent pour un même équipement.
- Montant = tarif_jour × nombre de jours ; tarif strictement positif.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Réserver, annuler | agriculteur |
| Mettre en location, accepter ou refuser | vendeur |
| Superviser toutes les locations | admin |

**Fichiers du module**
```
models/EquipementModel.php, models/ReservationModel.php
controllers/EquipementController.php, controllers/ReservationController.php
views/location.php, views/equipement_detail.php, views/mes_reservations.php
views/admin_equipements.php, views/admin_equipement_form.php, views/admin_reservations.php
```

---

#### Module 5 – Réclamations et support

**Responsable :** [Tasnim Trimich](https://github.com/tassnim557)

**Objectif :** accompagner l’utilisateur après un achat, une location, un diagnostic, une formation ou un parrainage,
avec un suivi clair de chaque réclamation.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Reclamation` | id_reclamation (PK), id_utilisateur (FK → utilisateur), sujet, categorie, description, date_creation, statut |
| `Reponse` | id_reponse (PK), id_reclamation (FK → reclamation), id_auteur (FK → utilisateur), message, date_reponse |

Relation : **une réclamation → plusieurs réponses** (1 — N, clé étrangère `id_reclamation`).

```sql
-- Réclamations ouvertes et nombre de réponses
SELECT r.sujet, r.statut, COUNT(p.id_reponse) AS nb_rep
FROM reclamation r
LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation
WHERE r.statut <> 'cloturee'
GROUP BY r.id_reclamation;
```

**Front Office**
- Formulaire de réclamation par catégorie : commande, location, diagnostic, formation, parrainage.
- Page « Mes réclamations » avec le statut et le fil de réponses.
- FAQ des questions fréquentes.

**Back Office**
- Liste des réclamations filtrée par statut et par catégorie.
- Réponse au client et changement de statut.
- Statistiques : délai moyen de réponse, réclamations par catégorie.

**Règles métier et contrôles de saisie**
- Statuts : ouverte → en cours → résolue → clôturée.
- Sujet de 5 à 150 caractères, description de 20 caractères minimum, catégorie obligatoire.
- Un utilisateur ne voit que ses propres réclamations.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Déposer et suivre ses réclamations | agriculteur, vendeur, parrain |
| Répondre, changer le statut, statistiques | admin |

**Fichiers du module**
```
models/ReclamationModel.php, models/ReponseModel.php
controllers/ReclamationController.php, controllers/ReponseController.php
views/reclamation_form.php, views/mes_reclamations.php, views/reclamation_detail.php
views/admin_reclamations.php, views/admin_reclamation_detail.php
```

---

#### Module 6 – Formations agricoles

**Responsable :** [Mahdi FakhFakh](https://github.com/MahdiFakh2006)

**Objectif :** permettre aux agriculteurs d’apprendre à utiliser le matériel intelligent (capteurs, drones, IA)
et à mieux gérer l’eau, grâce à des formations proposées par les vendeurs et validées par l’administrateur.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Formation` | id_formation (PK), titre, theme, date_formation, places_max, support_cours, id_formateur (FK → utilisateur) |
| `Inscription` | id_inscription (PK), id_formation (FK → formation), id_agriculteur (FK → utilisateur), date_inscription, note_avis, statut |

Relation : **une formation → plusieurs inscriptions** (1 — N, clé étrangère `id_formation`).

```sql
-- Places restantes des formations à venir
SELECT f.titre, f.places_max - COUNT(i.id_inscription) AS reste
FROM formation f
LEFT JOIN inscription i ON i.id_formation = f.id_formation
WHERE f.date_formation >= CURDATE()
GROUP BY f.id_formation;
```

**Front Office**
- Calendrier des formations, avec filtre par thème (irrigation, drones, IA, agriculture biologique).
- Inscription en un clic et annulation de l’inscription (agriculteur).
- Page « Mes formations » : formations suivies, téléchargement du support de cours, avis de 1 à 5.
- Proposition d’une formation (vendeur formateur).

**Back Office**
- CRUD des formations : ajout, modification, suppression, validation ou annulation.
- Liste des inscrits par formation et gestion des présences.
- Statistiques : taux de remplissage par formation, note moyenne, thèmes les plus demandés.

**Règles métier et contrôles de saisie**
- Inscription refusée si la formation est complète ou déjà passée.
- Un agriculteur ne peut pas s’inscrire deux fois à la même formation (contrainte `UNIQUE`).
- Titre de 3 à 150 caractères, date future, nombre de places entre 1 et 200, note entre 1 et 5.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Consulter et s’inscrire | agriculteur |
| Proposer une formation, voir ses inscrits | vendeur |
| Valider, modifier, supprimer, statistiques | admin |

**Fichiers du module**
```
models/FormationModel.php, models/InscriptionModel.php
controllers/FormationController.php, controllers/InscriptionController.php
views/formations.php, views/formation_detail.php, views/mes_formations.php
views/admin_formations.php, views/admin_formation_form.php
```

---

#### Module 7 – Adopte un olivier

**Responsable :** [Amine Kochbati](https://github.com/mnkchbt-spec)

**Objectif :** des citoyens, en Tunisie ou à l’étranger, parrainent un olivier chez un agriculteur pour une saison.
L’agriculteur obtient un revenu stable pour cultiver de façon durable ; le parrain suit son arbre et reçoit une part de la récolte.

**Entités et jointure**

| Entité | Attributs principaux |
|---|---|
| `Arbre` | id_arbre (PK), id_agriculteur (FK → utilisateur), variete, age_ans, region, prix_annuel |
| `Parrainage` | id_parrainage (PK), id_arbre (FK → arbre), id_parrain (FK → utilisateur), date_debut, duree_mois, statut |

Relation : **un arbre → plusieurs parrainages** au fil des années (1 — N, clé étrangère `id_arbre`).

```sql
-- Parrainages actifs et revenus par région
SELECT a.region, COUNT(*) AS nb, SUM(a.prix_annuel) AS total
FROM parrainage p
JOIN arbre a ON p.id_arbre = a.id_arbre
WHERE p.statut = 'actif'
GROUP BY a.region;
```

**Front Office**
- Carte des oliviers à adopter, avec filtre par région et par variété.
- Parrainage d’un arbre, ou parrainage offert en cadeau.
- Journal de l’arbre : photos, saisons, récolte.
- Proposition de ses oliviers au parrainage (agriculteur).

**Back Office**
- Validation des arbres proposés.
- Suivi des parrainages actifs.
- Statistiques : revenus reversés aux agriculteurs, parrainages par région.

**Règles métier et contrôles de saisie**
- Un arbre ne peut avoir qu’un seul parrainage actif à la fois.
- Durée de 1 à 36 mois ; prix annuel strictement positif ; âge de l’arbre supérieur ou égal à 0.
- Seuls les arbres validés par l’administrateur sont visibles sur la carte.

**Accès selon le rôle**

| Action | Rôle autorisé |
|---|---|
| Parrainer un arbre, suivre son journal | parrain |
| Proposer ses arbres, publier photos et récolte | agriculteur |
| Valider les arbres, gérer les parrainages | admin |

**Fichiers du module**
```
models/ArbreModel.php, models/ParrainageModel.php
controllers/ArbreController.php, controllers/ParrainageController.php
views/oliviers.php, views/arbre_detail.php, views/mes_parrainages.php, views/mes_arbres.php
views/admin_arbres.php, views/admin_parrainages.php
```

## Tech Stack

### Frontend
- HTML5
- CSS3
- JavaScript (validation des formulaires côté client)

### Backend
- PHP 8 (architecture **MVC**, PDO)
- MySQL / MariaDB (14 tables)
- Serveur local : XAMPP (Apache + MySQL)

### Outils
- Git et GitHub (branches, pull requests, GitHub Project Board)
- phpMyAdmin














## Sécurité mise en place

| Risque | Protection | Où |
|---|---|---|
| Injection SQL | Requêtes préparées PDO, `ATTR_EMULATE_PREPARES = false`, tri par liste blanche | contrôleurs, `config/database.php` |
| Mots de passe en clair | `password_hash()` / `password_verify()` | `AuthController`, `UserController` |
| XSS | `htmlspecialchars()` (fonction `e()`) dans toutes les vues, validation des entrées, en-tête CSP | vues, `Validator`, `index.php` |
| CSRF | Jeton unique par session dans **tous** les formulaires POST, vérifié avec `hash_equals()` | `AuthController`, vues |
| Fixation de session | `session_regenerate_id(true)` après connexion | `login()` |
| Vol de session | Cookie `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, `use_only_cookies` | `index.php` |
| Clickjacking | `X-Frame-Options: DENY` | `index.php` |
| Élévation de privilèges | Rôle choisi dans une liste blanche à l’inscription, `requireRole()` sur chaque page sensible, rôle relu en base à chaque requête | `register()`, `index.php`, `syncSession()` |
| Force brute | 5 échecs de connexion → 5 minutes d’attente | `login()` |
| Sessions abandonnées | Expiration après 30 minutes d’inactivité | `index.php` |
| Actions destructrices | Suppression, blocage et déconnexion uniquement en POST + jeton CSRF | `index.php` |

Les formulaires sont **validés deux fois** : en JavaScript (`assets/js/validation.js`, messages sous chaque champ)
puis en PHP (`controllers/Validator.php`), car le JavaScript peut être désactivé.

## Architecture MVC

```
agrinova/
├── index.php                 # point d’entrée unique (routeur ?action=...) + sécurité de session
├── config/
│   ├── database.php          # connexion PDO (Singleton)
│   └── helpers.php           # e(), old(), fieldError()… pour les vues
├── models/                   # UNIQUEMENT attributs privés + getters/setters (aucun SQL)
│   ├── UserModel.php
│   └── RoleModel.php
├── controllers/              # TOUTE la logique : SQL, validation, sessions, rôles, CSRF
│   ├── AuthController.php
│   ├── UserController.php
│   └── Validator.php
├── views/                    # UNIQUEMENT de l’affichage (HTML + htmlspecialchars)
│   ├── layout.php            # habillage Front Office
│   ├── layout_admin.php      # habillage Back Office (barre latérale sombre)
│   ├── login.php, register.php, user_space.php, profile.php
│   ├── admin_space.php, admin_users.php, admin_user_form.php
│   └── error_403.php, error_404.php
├── assets/css/style.css
├── assets/js/validation.js
├── database/agrinova.sql     # les 14 tables des 7 modules + données de test
└── docs/TACHES.md            # répartition des tâches et workflow Git
```

## Installation

**Prérequis :** XAMPP (ou WAMP) avec PHP 8.1 ou plus et MySQL / MariaDB, Git.

1. Cloner le dépôt dans le dossier du serveur web :
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/<organisation>/<nom-du-depot>.git agrinova
   ```
2. Démarrer **Apache** et **MySQL** dans le panneau XAMPP.
3. Ouvrir **phpMyAdmin** (http://localhost/phpmyadmin) → onglet **Importer** → choisir `database/agrinova.sql` → **Exécuter**.
   La base `agrinova` est créée avec toutes les tables et des données de test.
4. Si besoin, adapter l’utilisateur et le mot de passe MySQL dans `config/database.php` (`DB_USER`, `DB_PASS`).
5. Ouvrir **http://localhost/agrinova/index.php**

## Comptes de test

Mot de passe pour tous les comptes : **`Agri@2026`**

| Rôle | E-mail | Arrive sur |
|---|---|---|
| Administrateur | admin@agrinova.tn | Back Office |
| Agriculteur | karim@agrinova.tn | Front Office |
| Vendeur | amel@agrinova.tn | Front Office |
| Parrain | youssef@agrinova.tn | Front Office |
| Agriculteur bloqué | sami.bloque@agrinova.tn | Connexion refusée |

## Scénarios à montrer lors de la validation

1. Se connecter en **agriculteur** → Front Office ; taper `index.php?action=admin` → **403 Accès refusé**.
2. Se connecter en **admin** → Back Office ; ajouter, modifier, bloquer puis supprimer un utilisateur.
3. Se déconnecter puis taper `index.php?action=dashboard` → redirection vers la connexion.
4. Saisir `' OR '1'='1` comme e-mail (après avoir passé le champ en `type="text"` avec F12) → connexion refusée (injection SQL bloquée).
5. S’inscrire avec le nom `<script>alert(1)</script>` → refusé par la validation ; un nom piégé inséré en base s’affiche en texte brut (XSS bloqué).
6. Supprimer le champ caché `csrf_token` avec F12 puis envoyer un formulaire → « Jeton de sécurité invalide ».
7. F12 → Application → Cookies : le cookie `PHPSESSID` est `HttpOnly` et change après chaque connexion.
8. Désactiver le JavaScript et envoyer un formulaire vide → les erreurs PHP s’affichent quand même.

## Intégrer un nouveau module (pour chaque membre)

1. Créer `models/XxxModel.php` (attributs privés, getters/setters, `fromArray()`), `controllers/XxxController.php` (SQL préparé, validation avec `Validator`) et ses vues.
2. Ajouter les cas dans le `switch` de `index.php` et protéger **chaque** action :
   ```php
   case 'produits':
       AuthController::requireRole('vendeur', 'admin');   // rôles autorisés
       ...
   ```
3. Dans chaque formulaire POST : `<input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">`,
   et dans le contrôleur : `if (!AuthController::verifyCsrfToken($post['csrf_token'] ?? null)) { ... }`.
4. Validation JavaScript : ajouter `class="js-validate" novalidate` au formulaire et `data-rules="required|number|min:1"` aux champs
   (règles disponibles en haut de `assets/js/validation.js`), plus un `<small class="field-error"></small>` dans le bloc `.field`.
5. Afficher toute donnée avec `<?= e($valeur) ?>`.
6. Remplacer le lien `#` du module dans `views/layout_admin.php` par son action.

## Workflow Git

Voir `docs/TACHES.md` : une branche par module, des commits réguliers, une pull request vers `main` pour chaque fonctionnalité,
et le suivi des tâches dans le **GitHub Project Board** du dépôt.
