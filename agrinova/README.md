# AgriNoVa — L’intelligence au service de la terre

Plateforme web de **transition écologique et d’agriculture durable** pour les agriculteurs tunisiens :
marketplace de matériel agricole intelligent, location d’équipements, diagnostic des cultures par IA,
formations, support client et parrainage d’oliviers (« Adopte un olivier »).

Projet réalisé dans le cadre du module **Projet Technologies Web 2A** (Esprit — UP WEB, 2026-2027).

---
Problème résolu : ces services sont aujourd’hui dispersés (vente de matériel d’un côté, conseils agronomiques de l’autre), et le matériel intelligent reste trop cher à l’achat pour beaucoup d’exploitations. AgriNoVa les réunit dans une seule plateforme.



## Table des matières
 * Overview
 * Features
 * Tech Stack
 * Architecture
 * Security
 * Contributors
 * Academic Context
 * Getting Started
 * Acknowledgments
 * License




## Équipe et modules

| Module | Fonctionnalité | Entités (jointure) | Responsable |
|---|---|---|---|
| M1 | Gestion des utilisateurs, rôles, sécurité | Role ↔ Utilisateur | [Étudiant 1] |
| M2 | Marketplace | Categorie ↔ Produit | [Étudiant 2] |
| M3 | Diagnostic IA | Maladie ↔ Analyse | [Étudiant 3] |
| M4 | Location d’équipements | Equipement ↔ Reservation | [Étudiant 4] |
| M5 | Réclamations et support | Reclamation ↔ Reponse | [Étudiant 5] |
| M6 | Formations agricoles | Formation ↔ Inscription | [Étudiant 6] |
| M7 | Adopte un olivier | Arbre ↔ Parrainage | [Étudiant 7] |

## Fonctionnalités déjà intégrées (module 1)

- **Inscription** avec choix du type de compte (agriculteur, vendeur, parrain) — impossible de s’inscrire en administrateur.
- **Connexion / déconnexion** avec redirection selon le rôle : Back Office pour l’admin, Front Office pour les autres.
- **Profil** : modification des informations personnelles et du mot de passe.
- **Back Office** : tableau de bord (statistiques par rôle, comptes bloqués, derniers inscrits) et **CRUD complet des utilisateurs**
  avec recherche, filtres (rôle, statut), tri et pagination ; blocage / déblocage d’un compte.
- **Contrôle d’accès** par rôle sur chaque page (`requireRole()`), erreur 403 sinon.

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
