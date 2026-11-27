# AgriNoVa — Module 5 : Réclamations et support

**Responsable :** [Tasnim Trimich](https://github.com/utilisateur5)
Projet Technologies Web 2A — Esprit (UP WEB, 2026-2027) — groupe 2A13

## Description

**Objectif :** accompagner l’utilisateur après un achat, une location, un diagnostic, une formation ou un parrainage, avec un suivi clair de chaque réclamation.

**Problème résolu :** un agriculteur, un vendeur ou un parrain qui rencontre un souci n’a aucun moyen de le signaler ni de suivre la réponse. Ce module offre un formulaire par catégorie, un suivi par statut et un fil de réponses ; l’administrateur dispose d’un tableau de traitement avec statistiques.

**Fonctionnalités principales :**
- Dépôt, modification et suppression d’une réclamation par catégorie (commande, location, diagnostic, formation, parrainage).
- Page « Mes réclamations » avec statut et fil de réponses, et FAQ.
- Back Office : liste filtrée par statut et catégorie, réponse au client, changement de statut, statistiques.

## Table des Matières
- [Entités et jointure](#entités-et-jointure)
- [Base de données](#base-de-données)
- [Fonctionnalités](#fonctionnalités)
- [Règles métier et contrôles de saisie](#règles-métier-et-contrôles-de-saisie)
- [Accès selon le rôle](#accès-selon-le-rôle)
- [Architecture et fichiers du module](#architecture-et-fichiers-du-module)
- [Installation](#installation)
- [Utilisation](#utilisation)
- [Contributions](#contributions)
- [Licence](#licence)

## Entités et jointure

| Entité | Attributs principaux |
|---|---|
| `Reclamation` | id_reclamation (PK), id_utilisateur (FK → utilisateur), sujet, categorie, description, date_creation, statut |
| `Reponse` | id_reponse (PK), id_reclamation (FK → reclamation), id_auteur (FK → utilisateur), message, date_reponse |

Relation : **une réclamation → plusieurs réponses** (1 — N, clé étrangère `id_reclamation`, suppression en cascade).

## Base de données

Les tables sont créées par `database/agrinova.sql` (import complet du projet). Le fichier `database/m5_structure.sql` en est l’extrait documenté.

### Schéma

```
utilisateur 1 ──── N reclamation 1 ──── N reponse N ──── 1 utilisateur (auteur)
```

### Table `reclamation`

| Colonne | Type | Contraintes |
|---|---|---|
| `id_reclamation` | INT | PK, AUTO_INCREMENT |
| `id_utilisateur` | INT | NOT NULL, FK → `utilisateur.id_utilisateur` |
| `sujet` | VARCHAR(150) | NOT NULL |
| `categorie` | ENUM('commande','location','diagnostic','formation','parrainage','autre') | NOT NULL |
| `description` | TEXT | NOT NULL |
| `date_creation` | DATETIME | NOT NULL, défaut `CURRENT_TIMESTAMP` |
| `statut` | ENUM('ouverte','en_cours','resolue','cloturee') | NOT NULL, défaut `ouverte` |

### Table `reponse`

| Colonne | Type | Contraintes |
|---|---|---|
| `id_reponse` | INT | PK, AUTO_INCREMENT |
| `id_reclamation` | INT | NOT NULL, FK → `reclamation.id_reclamation`, `ON DELETE CASCADE` |
| `id_auteur` | INT | NOT NULL, FK → `utilisateur.id_utilisateur` |
| `message` | TEXT | NOT NULL |
| `date_reponse` | DATETIME | NOT NULL, défaut `CURRENT_TIMESTAMP` |

> L’interface n’utilise que les 5 catégories du cahier des charges ; la valeur `autre` existe dans la base mais n’est pas proposée.

### Requête de jointure

```sql
-- Réclamations ouvertes et nombre de réponses
SELECT r.sujet, r.statut, COUNT(p.id_reponse) AS nb_rep
FROM reclamation r
LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation
WHERE r.statut <> 'cloturee'
GROUP BY r.id_reclamation;
```

### Données de test (`database/m5_donnees_test.sql`)

| # | Client | Sujet | Catégorie | Statut | Réponses |
|---|---|---|---|---|---|
| 1 | karim (agriculteur) | Capteur d’humidité défectueux | commande | en_cours | 1 |
| 2 | karim (agriculteur) | Drone indisponible à la date réservée | location | ouverte | 0 |
| 3 | amel (vendeur) | Diagnostic incorrect sur mes tomates | diagnostic | resolue | 2 |
| 4 | youssef (parrain) | Journal de mon olivier jamais reçu | parrainage | cloturee | 1 |
| 5 | karim (agriculteur) | Impossible de m’inscrire à la formation irrigation | formation | ouverte | 0 |
| 6 | amel (vendeur) | Facture de commande incomplète | commande | ouverte | 0 |

## Fonctionnalités

**Front Office**
- Formulaire de réclamation par catégorie : commande, location, diagnostic, formation, parrainage.
- Page « Mes réclamations » avec le statut et le fil de réponses.
- FAQ des questions fréquentes.

**Back Office**
- Liste des réclamations filtrée par statut et par catégorie (recherche, tri, pagination).
- Réponse au client et changement de statut.
- Statistiques : délai moyen de réponse, réclamations par catégorie.

## Règles métier et contrôles de saisie

- Statuts : **ouverte → en cours → résolue → clôturée** (jamais de retour en arrière).
- Une réponse de l’administrateur fait passer une réclamation « ouverte » en « en cours ».
- Sujet de 5 à 150 caractères, description de 20 caractères minimum, catégorie obligatoire.
- Un utilisateur ne voit que ses propres réclamations.
- Le client ne modifie ou ne supprime sa réclamation que si elle est « ouverte » et sans réponse.
- Une réclamation clôturée n’accepte plus de réponse.
- Validation en JavaScript (`data-rules`) **et** en PHP (`Validator`), requêtes PDO préparées, `htmlspecialchars` via `e()`, jeton CSRF dans chaque formulaire POST.

## Accès selon le rôle

| Action | Rôle autorisé |
|---|---|
| Déposer et suivre ses réclamations | agriculteur, vendeur, parrain |
| Répondre, changer le statut, statistiques | admin |

## Architecture et fichiers du module

```
models/ReclamationModel.php, models/ReponseModel.php
controllers/ReclamationController.php, controllers/ReponseController.php
views/reclamation_form.php, views/mes_reclamations.php, views/reclamation_detail.php, views/faq.php
views/admin_reclamations.php, views/admin_reclamation_detail.php, views/admin_reponse_form.php
database/m5_structure.sql, database/m5_donnees_test.sql
```

| Action (`index.php?action=`) | Rôle | Méthode |
|---|---|---|
| `mes_reclamations`, `faq`, `reclamation_detail` | agriculteur, vendeur, parrain | GET |
| `reclamation_new`, `reclamation_edit` | agriculteur, vendeur, parrain | GET + POST |
| `reclamation_delete` | agriculteur, vendeur, parrain | POST |
| `admin_reclamations`, `admin_reclamation_detail` | admin | GET |
| `admin_reponse_edit` | admin | GET + POST |
| `admin_reclamation_reply`, `admin_reclamation_status`, `admin_reclamation_delete`, `admin_reponse_delete` | admin | POST |

## Installation

1. Cloner le repository :
   ```bash
   git clone https://github.com/MahdiFakh2006/Esprit-PI-2A13-2026-AgriNoVa.git
   cd Esprit-PI-2A13-2026-AgriNoVa
   git checkout module5-reclamations
   ```
2. Si vous utilisez WAMP ou XAMPP :

* Placez le dossier `agrinova` dans `www` (WAMP) ou `htdocs` (XAMPP).

* Démarrez Apache et MySQL depuis l’interface de WAMP/XAMPP.

* Dans phpMyAdmin (http://localhost/phpmyadmin) → **Importer** : `database/agrinova.sql`, puis `database/m5_donnees_test.sql`.

* Si besoin, adaptez `DB_USER` et `DB_PASS` dans `config/database.php`.

* Accédez au projet via http://localhost/agrinova/index.php.

## Utilisation

Prérequis : **PHP 8.1 ou plus** (vérifier avec `php -v`), MySQL / MariaDB, Apache.

Mot de passe de tous les comptes de test : **`Agri@2026`**

| Rôle | E-mail | Ce qu’il peut faire |
|---|---|---|
| Agriculteur | karim@agrinova.tn | Déposer et suivre ses réclamations |
| Vendeur | amel@agrinova.tn | Déposer et suivre ses réclamations |
| Parrain | youssef@agrinova.tn | Déposer et suivre ses réclamations |
| Administrateur | admin@agrinova.tn | Répondre, changer le statut, voir les statistiques |

**Scénarios de validation :**
1. Connecté en **agriculteur** : menu « Support » → déposer une réclamation → la retrouver dans « Mes réclamations ».
2. Taper `index.php?action=admin_reclamations` en agriculteur → **403 Accès refusé**.
3. Connecté en **admin** : « M5 · Réclamations » → filtrer par statut/catégorie → ouvrir une réclamation → répondre → le statut passe « en cours ».
4. Faire avancer le statut jusqu’à « clôturée » : la réponse n’est plus possible.
5. Ouvrir la réclamation d’un autre utilisateur par son `id` en tant que client → « Réclamation introuvable ».
6. Supprimer le champ `csrf_token` avec F12 puis envoyer un formulaire → « Jeton de sécurité invalide ».

## Contributions

### Contributeurs

- [Tasnim Trimich](https://github.com/utilisateur5) — Module 5 : modèles, contrôleurs, vues Front/Back Office, base de données, documentation.

### Comment contribuer ?

1. **Fork le projet** : sur la page GitHub, cliquer sur **Fork**.
2. **Clonez votre fork** :
   ```bash
   git clone https://github.com/votre-utilisateur/Esprit-PI-2A13-2026-AgriNoVa.git
   cd Esprit-PI-2A13-2026-AgriNoVa
   ```
3. **Créez une branche** : `git checkout -b module5-ma-fonctionnalite`
4. **Commitez et poussez** : `git add .`, `git commit -m "M5 : description"`, `git push origin module5-ma-fonctionnalite`
5. Ouvrez une **pull request** vers `main`.

## Licence

Ce projet est un projet académique réalisé à **Esprit** (Projet Technologies Web 2A, 2026-2027). Il est distribué sous la licence **MIT**.

### Détails sur la licence MIT

La licence MIT est une licence de logiciel libre permissive : elle autorise l’utilisation, la copie, la modification et la redistribution du code, à condition de conserver la mention de copyright et le texte de la licence.
