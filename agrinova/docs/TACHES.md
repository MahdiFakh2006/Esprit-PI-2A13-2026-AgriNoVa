# Répartition des tâches et workflow Git

Chaque ligne ci-dessous devient **une carte (issue)** dans le GitHub Project Board, assignée à son responsable,
avec les colonnes **À faire → En cours → En revue → Terminé**.

## 1. Tâches communes (toute l’équipe)

| # | Tâche | Responsable |
|---|---|---|
| C1 | Créer le dépôt (nomenclature exigée), inviter les membres et le professeur | [Étudiant 1] |
| C2 | Créer le Project Board et y ajouter toutes les cartes de ce fichier | [Étudiant 1] |
| C3 | Importer `database/agrinova.sql` et vérifier que les 14 tables sont créées | Tous |
| C4 | Choisir la machine de démonstration et y installer XAMPP + le projet | [À désigner] |
| C5 | Test final complet sur la machine de démonstration (installation depuis zéro) | Tous |
| C6 | Compléter le README (noms, captures d’écran) | [À désigner] |

## 2. Module 1 — Utilisateurs (socle, à fusionner en premier)

| # | Tâche | État |
|---|---|---|
| M1-1 | Connexion PDO Singleton (`config/database.php`) | ✅ fait |
| M1-2 | Modèles `UserModel` et `RoleModel` | ✅ fait |
| M1-3 | Inscription avec choix du rôle + hachage | ✅ fait |
| M1-4 | Connexion, déconnexion, redirection FO / BO | ✅ fait |
| M1-5 | Sécurité des sessions (cookies, expiration, régénération, en-têtes) | ✅ fait |
| M1-6 | Jeton CSRF sur tous les formulaires | ✅ fait |
| M1-7 | `requireAuth()` / `requireRole()` + page 403 | ✅ fait |
| M1-8 | Back Office : CRUD utilisateurs, recherche, filtres, tri, pagination | ✅ fait |
| M1-9 | Blocage / déblocage d’un compte | ✅ fait |
| M1-10 | Profil et changement de mot de passe | ✅ fait |
| M1-11 | Validation JavaScript + PHP de tous les formulaires du module | ✅ fait |
| M1-12 | (Bonus) Mot de passe oublié par e-mail (PHPMailer) | à faire |

## 3. Modules 2 à 7 — mêmes tâches pour chaque membre

Remplacer *Xxx* par les entités du module (ex. Categorie / Produit).

| # | Tâche |
|---|---|
| Mx-1 | Modèles des deux entités (attributs privés, getters/setters, `fromArray()`) |
| Mx-2 | Contrôleur : CRUD de l’entité principale (requêtes préparées) |
| Mx-3 | Contrôleur : CRUD de l’entité liée + **jointure** entre les deux |
| Mx-4 | Vues Front Office (liste, détail, formulaire) |
| Mx-5 | Vues Back Office (tableau, ajout, modification, suppression) |
| Mx-6 | Protection des actions : `requireRole(...)` + jeton CSRF |
| Mx-7 | Validation JavaScript (`data-rules`) **et** PHP (`Validator`) de chaque formulaire |
| Mx-8 | Fonctionnalités métier (recherche, tri, statistiques…) |
| Mx-9 | Brancher le menu du module dans `layout_admin.php` et `user_space.php` |

Rôles autorisés par module (à utiliser dans `requireRole`) :

| Module | Front Office | Back Office |
|---|---|---|
| M2 Marketplace | agriculteur (acheter), vendeur (ses produits) | admin |
| M3 Diagnostic IA | agriculteur | admin |
| M4 Location | agriculteur (réserver), vendeur (son matériel) | admin |
| M5 Réclamations | agriculteur, vendeur, parrain | admin |
| M6 Formations | agriculteur (s’inscrire), vendeur (formateur) | admin |
| M7 Adopte un olivier | parrain (parrainer), agriculteur (ses arbres) | admin |

## 4. Workflow Git (à respecter par tout le monde)

```bash
# Une seule fois
git clone https://github.com/<organisation>/<nom-du-depot>.git agrinova
cd agrinova

# Avant de commencer une tâche : partir d’un main à jour
git checkout main
git pull origin main
git checkout -b module2-marketplace        # une branche par module (ou par fonctionnalité)

# Pendant le travail : petits commits clairs et fréquents
git add controllers/ProduitController.php views/produits.php
git commit -m "M2 : ajout du CRUD des produits (Back Office)"
git push -u origin module2-marketplace

# Récupérer régulièrement le travail des autres pour éviter les gros conflits
git pull origin main                       # fusionne main dans votre branche
```

Ensuite, sur GitHub : **Pull request** de votre branche vers `main`, relue par un autre membre, puis **Merge**.
Lier la pull request à sa carte du Project Board (« Closes #12 » dans la description).

Règles :
- Ne jamais pousser directement sur `main` (activer la protection de branche dans Settings → Branches).
- Ne jamais committer un mot de passe personnel : laisser `config/database.php` avec les valeurs XAMPP par défaut.
- Si vous ajoutez une table ou une colonne, mettez à jour `database/agrinova.sql` dans la même pull request.
- Chaque membre commite avec **son propre compte GitHub** : les contributions doivent être traçables.
