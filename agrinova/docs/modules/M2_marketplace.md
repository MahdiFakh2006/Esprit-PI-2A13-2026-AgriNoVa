# Module 2 – Marketplace

> Projet **AgriNoVa** – Projet Technologies Web 2A – **Esprit School of Engineering** (2026–2027)

**Branche Git :** `module2-marketplace`

## Objectif
Rendre le matériel agricole intelligent (capteurs, drones, stations météo, kits IA) accessible depuis une seule plateforme.

## Entités et relations
- **Categorie** 1 — N **Produit** (clé étrangère `id_categorie`)
- **Utilisateur (vendeur)** 1 — N **Produit** (clé étrangère `id_vendeur`)
- **Commande** 1 — N **Ligne_commande** ; **Produit** 1 — N **Ligne_commande** (`ON DELETE SET NULL`)

## Requêtes SQL principales
```sql
-- Catalogue (jointures)
SELECT p.*, c.nom AS categorie_nom, CONCAT(u.prenom,' ',u.nom) AS vendeur_nom
FROM produit p JOIN categorie c ON p.id_categorie = c.id_categorie
JOIN utilisateur u ON p.id_vendeur = u.id_utilisateur;

-- Statistiques : produits et stock par catégorie
SELECT c.nom, COUNT(p.id_produit) AS nb, COALESCE(SUM(p.stock),0) AS stock_total
FROM categorie c LEFT JOIN produit p ON p.id_categorie = c.id_categorie GROUP BY c.id_categorie;
```

## Contrôles de saisie (JavaScript `data-rules` + PHP)
| Champ | JavaScript | PHP |
|---|---|---|
| Nom du produit | `required\|minlen:2\|maxlen:120` | 2 à 120 caractères |
| Description | `maxlen:2000` | 2000 caractères maximum |
| Prix | `required\|number\|min:0.01` | strictement positif, 2 décimales |
| Stock | `required\|integer\|min:0` | entier ≥ 0 |
| Catégorie / vendeur | `required` | doit exister en base (le vendeur doit avoir le rôle vendeur) |
| Image | `image` | JPG ou PNG réel (finfo + getimagesize), 2 Mo |
| Nom de catégorie | `required\|minlen:2\|maxlen:80` | 2 à 80 caractères, unique |

## Scénarios de test
| # | Scénario | Résultat attendu |
|---|---|---|
| 1 | Agriculteur : catalogue, filtre par catégorie / prix, fiche, comparaison de 2 produits | Résultats corrects |
| 2 | Agriculteur : ajouter 3 exemplaires d'un produit ayant 4 en stock, puis 3 de plus | Refus « Stock insuffisant » |
| 3 | Agriculteur : valider le panier | Commande créée, stock diminué ; annulation → stock remis |
| 4 | Vendeur : ajouter, modifier, supprimer un de ses produits | Succès |
| 5 | Vendeur : ouvrir `mes_produit_edit&id=` d'un produit d'un autre vendeur | Redirection « Produit introuvable » |
| 6 | Vendeur : prix `-5`, stock `abc`, fichier PHP renommé en .png | Erreurs affichées (JS puis PHP), rien enregistré |
| 7 | Admin : supprimer une catégorie qui contient des produits | Refusé avec le nombre de produits |
| 8 | Agriculteur : `index.php?action=admin_produits` | 403 |
| 9 | Nom de produit `<script>alert(1)</script>` | Affiché en texte brut (XSS bloqué) |
| 10 | Formulaire sans `csrf_token` | « Jeton de sécurité invalide » |
