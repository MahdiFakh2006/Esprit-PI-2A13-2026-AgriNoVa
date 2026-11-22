<?php
/**
 * index.php — point d'entrée unique de l'application AgriNoVa.
 * Chaque page est appelée par index.php?action=...
 */
declare(strict_types=1);

// --------------------------------------------------------------
// 1. Configuration sécurisée de la session (AVANT session_start)
// --------------------------------------------------------------
ini_set('session.use_strict_mode', '1');      // refuse les identifiants de session inventés
ini_set('session.use_only_cookies', '1');     // jamais d'identifiant de session dans l'URL
ini_set('session.cookie_httponly', '1');      // cookie invisible pour JavaScript (vol via XSS)
ini_set('session.cookie_samesite', 'Lax');    // limite le CSRF venant de sites tiers
// ini_set('session.cookie_secure', '1');     // à activer si le site est servi en HTTPS
session_start();

// --------------------------------------------------------------
// 2. Expiration de la session après 30 minutes d'inactivité
// --------------------------------------------------------------
const SESSION_TIMEOUT = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Session expirée après 30 minutes d’inactivité. Reconnectez-vous.'];
}
$_SESSION['LAST_ACTIVITY'] = time();

// --------------------------------------------------------------
// 3. En-têtes HTTP de sécurité
// --------------------------------------------------------------
header('X-Frame-Options: DENY');                                   // anti clickjacking
header('X-Content-Type-Options: nosniff');                         // pas de devinette du type MIME
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:"); // limite le XSS
header('Cache-Control: no-store');                                 // pages protégées non rejouables avec « Précédent »

// --------------------------------------------------------------
// 4. Chargement des contrôleurs
// --------------------------------------------------------------
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/CategorieController.php';  // Module 2 : Marketplace
require_once __DIR__ . '/controllers/ProduitController.php';
require_once __DIR__ . '/controllers/CommandeController.php';

$authController = new AuthController();
$userController = new UserController($authController);
$categorieController = new CategorieController();
$produitController = new ProduitController();
$commandeController = new CommandeController();
$authController->syncSession(); // rôle et statut relus en base à chaque requête

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$csrfToken = AuthController::getCsrfToken();

// --------------------------------------------------------------
// 5. Routage
// --------------------------------------------------------------
$action = (string) ($_GET['action'] ?? 'home');

switch ($action) {

    // ---------- Accueil : redirection selon l'état de connexion ----------
    case 'home':
        header('Location: index.php?action=' . (AuthController::isAuthenticated() ? AuthController::homeAction() : 'login'));
        exit;

    // ---------- Inscription ----------
    case 'register':
        if (AuthController::isAuthenticated()) {
            header('Location: index.php?action=' . AuthController::homeAction());
            exit;
        }
        $errors = [];
        $old = [];
        if ($isPost) {
            $errors = $authController->register($_POST);
            if (!$errors) {
                AuthController::flash('success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
                header('Location: index.php?action=login');
                exit;
            }
            $old = $_POST;
        }
        require __DIR__ . '/views/register.php';
        break;

    // ---------- Connexion ----------
    case 'login':
        if (AuthController::isAuthenticated()) {
            header('Location: index.php?action=' . AuthController::homeAction());
            exit;
        }
        $errors = [];
        $oldEmail = '';
        if ($isPost) {
            $errors = $authController->login($_POST);
            if (!$errors) {
                header('Location: index.php?action=' . AuthController::homeAction());
                exit;
            }
            $oldEmail = (string) ($_POST['email'] ?? '');
        }
        require __DIR__ . '/views/login.php';
        break;

    // ---------- Déconnexion (POST + CSRF : un simple lien ne peut pas déconnecter) ----------
    case 'logout':
        if ($isPost && AuthController::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $authController->logout();
            session_start();
            AuthController::flash('success', 'Vous êtes déconnecté.');
        }
        header('Location: index.php?action=login');
        exit;

    // ---------- FRONT OFFICE : espace personnel ----------
    case 'dashboard':
        AuthController::requireRole('agriculteur', 'vendeur', 'parrain');
        $user = $authController->findById(AuthController::currentUserId());
        require __DIR__ . '/views/user_space.php';
        break;

    case 'profile':
        AuthController::requireAuth();
        $id = AuthController::currentUserId();
        $errors = [];
        $pwdErrors = [];
        if ($isPost && ($_POST['form'] ?? '') === 'profil') {
            $errors = $userController->updateProfile($id, $_POST);
            if (!$errors) {
                AuthController::flash('success', 'Profil mis à jour.');
                header('Location: index.php?action=profile');
                exit;
            }
        } elseif ($isPost && ($_POST['form'] ?? '') === 'motdepasse') {
            $pwdErrors = $userController->changePassword($id, $_POST);
            if (!$pwdErrors) {
                AuthController::flash('success', 'Mot de passe modifié.');
                header('Location: index.php?action=profile');
                exit;
            }
        }
        $user = $authController->findById($id);
        $old = $errors ? $_POST : [];
        $layout = AuthController::currentRole() === 'admin' ? 'layout_admin.php' : 'layout.php';
        require __DIR__ . '/views/profile.php';
        break;

    // ---------- BACK OFFICE : tableau de bord ----------
    case 'admin':
        AuthController::requireRole('admin');
        $stats = $userController->stats();
        require __DIR__ . '/views/admin_space.php';
        break;

    // ---------- BACK OFFICE : liste des utilisateurs (Read) ----------
    case 'admin_users':
        AuthController::requireRole('admin');
        $result = $userController->search($_GET);
        require __DIR__ . '/views/admin_users.php';
        break;

    // ---------- BACK OFFICE : ajout (Create) ----------
    case 'admin_user_create':
        AuthController::requireRole('admin');
        $errors = [];
        $old = ['role' => 'agriculteur', 'statut' => 'actif'];
        if ($isPost) {
            $errors = $userController->create($_POST);
            if (!$errors) {
                AuthController::flash('success', 'Utilisateur ajouté.');
                header('Location: index.php?action=admin_users');
                exit;
            }
            $old = $_POST;
        }
        $roles = $authController->getRoles();
        $editUser = null;
        require __DIR__ . '/views/admin_user_form.php';
        break;

    // ---------- BACK OFFICE : modification (Update) ----------
    case 'admin_user_edit':
        AuthController::requireRole('admin');
        $id = (int) ($_GET['id'] ?? 0);
        $editUser = $userController->find($id);
        if ($editUser === null) {
            AuthController::flash('error', 'Utilisateur introuvable.');
            header('Location: index.php?action=admin_users');
            exit;
        }
        $errors = [];
        $old = [
            'nom' => $editUser->getNom(), 'prenom' => $editUser->getPrenom(), 'email' => $editUser->getEmail(),
            'telephone' => $editUser->getTelephone(), 'role' => $editUser->getRole(), 'statut' => $editUser->getStatut(),
        ];
        if ($isPost) {
            $errors = $userController->update($id, $_POST);
            if (!$errors) {
                AuthController::flash('success', 'Utilisateur modifié.');
                header('Location: index.php?action=admin_users');
                exit;
            }
            $old = $_POST;
        }
        $roles = $authController->getRoles();
        require __DIR__ . '/views/admin_user_form.php';
        break;

    // ---------- BACK OFFICE : suppression (Delete) et blocage — POST uniquement ----------
    case 'admin_user_delete':
    case 'admin_user_toggle':
        AuthController::requireRole('admin');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $error = $action === 'admin_user_delete'
            ? $userController->delete($id, $_POST)
            : $userController->toggleStatus($id, $_POST);
        AuthController::flash($error ? 'error' : 'success', $error ?? ($action === 'admin_user_delete' ? 'Utilisateur supprimé.' : 'Statut du compte modifié.'));
        header('Location: index.php?action=admin_users');
        exit;

    // =====================================================================
    // MODULE 2 : MARKETPLACE
    // =====================================================================

    // ---------- FRONT OFFICE : catalogue, fiche produit, comparaison (agriculteur, vendeur) ----------
    case 'catalogue':
        AuthController::requireRole('agriculteur', 'vendeur');
        $result = $produitController->search($_GET, null, ProduitController::PER_PAGE_CATALOGUE);
        $categories = $categorieController->all();
        $canBuy = AuthController::hasRole('agriculteur');
        require __DIR__ . '/views/catalogue.php';
        break;

    case 'produit_detail':
        AuthController::requireRole('agriculteur', 'vendeur');
        $produit = $produitController->find((int) ($_GET['id'] ?? 0));
        if ($produit === null) {
            AuthController::flash('error', 'Produit introuvable.');
            header('Location: index.php?action=catalogue');
            exit;
        }
        $canBuy = AuthController::hasRole('agriculteur');
        require __DIR__ . '/views/produit_detail.php';
        break;

    case 'produit_comparer':
        AuthController::requireRole('agriculteur', 'vendeur');
        $selA = (int) ($_GET['a'] ?? 0);
        $selB = (int) ($_GET['b'] ?? 0);
        $a = $selA > 0 ? $produitController->find($selA) : null;
        $b = $selB > 0 ? $produitController->find($selB) : null;
        $error = '';
        if ($selA > 0 && $selB > 0) {
            if ($selA === $selB) {
                $error = 'Choisissez deux produits différents.';
                $a = $b = null;
            } elseif ($a === null || $b === null) {
                $error = 'Un des deux produits est introuvable.';
                $a = $b = null;
            }
        }
        $options = $produitController->listForSelect();
        require __DIR__ . '/views/produit_comparer.php';
        break;

    // ---------- FRONT OFFICE : panier et commandes (agriculteur) — actions en POST + CSRF ----------
    case 'panier':
        AuthController::requireRole('agriculteur');
        $cart = $commandeController->cartDetails();
        require __DIR__ . '/views/panier.php';
        break;

    case 'panier_ajouter':
    case 'panier_modifier':
    case 'panier_retirer':
        AuthController::requireRole('agriculteur');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = match ($action) {
            'panier_ajouter'  => $commandeController->addToCart($_POST),
            'panier_modifier' => $commandeController->updateCart($_POST),
            default           => $commandeController->removeFromCart($_POST),
        };
        if ($error !== null) {
            AuthController::flash('error', $error);
        } elseif ($action === 'panier_ajouter') {
            AuthController::flash('success', 'Produit ajouté au panier.');
        }
        header('Location: index.php?action=' . ($error !== null && $action === 'panier_ajouter'
            ? 'produit_detail&id=' . (int) ($_POST['id'] ?? 0) : 'panier'));
        exit;

    case 'commande_valider':
        AuthController::requireRole('agriculteur');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        [$error, $idCommande] = $commandeController->placeOrder($_POST, AuthController::currentUserId());
        if ($error !== null) {
            AuthController::flash('error', $error);
            header('Location: index.php?action=panier');
        } else {
            AuthController::flash('success', 'Commande n° ' . $idCommande . ' enregistrée. Merci pour votre achat !');
            header('Location: index.php?action=mes_commandes');
        }
        exit;

    case 'mes_commandes':
        AuthController::requireRole('agriculteur');
        $commandes = $commandeController->myOrders(AuthController::currentUserId());
        require __DIR__ . '/views/mes_commandes.php';
        break;

    case 'commande_annuler':
        AuthController::requireRole('agriculteur');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = $commandeController->changeStatus((int) ($_POST['id'] ?? 0), 'annulee', $_POST, AuthController::currentUserId());
        AuthController::flash($error ? 'error' : 'success', $error ?? 'Commande annulée.');
        header('Location: index.php?action=mes_commandes');
        exit;

    // ---------- FRONT OFFICE : « Mes produits » du vendeur (CRUD de SES produits uniquement) ----------
    case 'mes_produits':
        AuthController::requireRole('vendeur');
        $result = $produitController->search($_GET, AuthController::currentUserId());
        $categories = $categorieController->all();
        require __DIR__ . '/views/mes_produits.php';
        break;

    case 'mes_produit_create':
        AuthController::requireRole('vendeur');
        $errors = [];
        $old = ['stock' => '0'];
        if ($isPost) {
            $errors = $produitController->create($_POST, $_FILES, AuthController::currentUserId());
            if (!$errors) {
                AuthController::flash('success', 'Produit ajouté.');
                header('Location: index.php?action=mes_produits');
                exit;
            }
            $old = $_POST;
        }
        $mode = 'vendeur';
        $editProduit = null;
        $categories = $categorieController->all();
        $vendeurs = [];
        require __DIR__ . '/views/produit_form.php';
        break;

    case 'mes_produit_edit':
        AuthController::requireRole('vendeur');
        $id = (int) ($_GET['id'] ?? 0);
        $editProduit = $produitController->findForEdit($id, AuthController::currentUserId()); // un vendeur ne voit que SES produits
        if ($editProduit === null) {
            AuthController::flash('error', 'Produit introuvable.');
            header('Location: index.php?action=mes_produits');
            exit;
        }
        $errors = [];
        $old = [
            'nom' => $editProduit->getNom(), 'description' => $editProduit->getDescription(), 'prix' => (string) $editProduit->getPrix(),
            'stock' => (string) $editProduit->getStock(), 'id_categorie' => (string) $editProduit->getIdCategorie(),
        ];
        if ($isPost) {
            $errors = $produitController->update($id, $_POST, $_FILES, AuthController::currentUserId());
            if (!$errors) {
                AuthController::flash('success', 'Produit modifié.');
                header('Location: index.php?action=mes_produits');
                exit;
            }
            $old = $_POST;
        }
        $mode = 'vendeur';
        $categories = $categorieController->all();
        $vendeurs = [];
        require __DIR__ . '/views/produit_form.php';
        break;

    case 'mes_produit_delete':
        AuthController::requireRole('vendeur');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = $produitController->delete((int) ($_POST['id'] ?? 0), $_POST, AuthController::currentUserId());
        AuthController::flash($error ? 'error' : 'success', $error ?? 'Produit supprimé.');
        header('Location: index.php?action=mes_produits');
        exit;

    // ---------- BACK OFFICE : catégories (CRUD) ----------
    case 'admin_categories':
        AuthController::requireRole('admin');
        $result = $categorieController->search($_GET);
        require __DIR__ . '/views/admin_categories.php';
        break;

    case 'admin_categorie_create':
        AuthController::requireRole('admin');
        $errors = [];
        $old = [];
        if ($isPost) {
            $errors = $categorieController->create($_POST);
            if (!$errors) {
                AuthController::flash('success', 'Catégorie ajoutée.');
                header('Location: index.php?action=admin_categories');
                exit;
            }
            $old = $_POST;
        }
        $editCategorie = null;
        require __DIR__ . '/views/admin_categorie_form.php';
        break;

    case 'admin_categorie_edit':
        AuthController::requireRole('admin');
        $id = (int) ($_GET['id'] ?? 0);
        $editCategorie = $categorieController->find($id);
        if ($editCategorie === null) {
            AuthController::flash('error', 'Catégorie introuvable.');
            header('Location: index.php?action=admin_categories');
            exit;
        }
        $errors = [];
        $old = ['nom' => $editCategorie->getNom(), 'description' => $editCategorie->getDescription()];
        if ($isPost) {
            $errors = $categorieController->update($id, $_POST);
            if (!$errors) {
                AuthController::flash('success', 'Catégorie modifiée.');
                header('Location: index.php?action=admin_categories');
                exit;
            }
            $old = $_POST;
        }
        require __DIR__ . '/views/admin_categorie_form.php';
        break;

    case 'admin_categorie_delete':
        AuthController::requireRole('admin');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = $categorieController->delete((int) ($_POST['id'] ?? 0), $_POST);
        AuthController::flash($error ? 'error' : 'success', $error ?? 'Catégorie supprimée.');
        header('Location: index.php?action=admin_categories');
        exit;

    // ---------- BACK OFFICE : produits (CRUD) + statistiques ----------
    case 'admin_produits':
        AuthController::requireRole('admin');
        $result = $produitController->search($_GET);
        $stats = $produitController->stats();
        $categories = $categorieController->all();
        require __DIR__ . '/views/admin_produits.php';
        break;

    case 'admin_produit_create':
        AuthController::requireRole('admin');
        $errors = [];
        $old = ['stock' => '0'];
        if ($isPost) {
            $errors = $produitController->create($_POST, $_FILES, null);
            if (!$errors) {
                AuthController::flash('success', 'Produit ajouté.');
                header('Location: index.php?action=admin_produits');
                exit;
            }
            $old = $_POST;
        }
        $mode = 'admin';
        $editProduit = null;
        $categories = $categorieController->all();
        $vendeurs = $produitController->getVendeurs();
        require __DIR__ . '/views/produit_form.php';
        break;

    case 'admin_produit_edit':
        AuthController::requireRole('admin');
        $id = (int) ($_GET['id'] ?? 0);
        $editProduit = $produitController->findForEdit($id, null);
        if ($editProduit === null) {
            AuthController::flash('error', 'Produit introuvable.');
            header('Location: index.php?action=admin_produits');
            exit;
        }
        $errors = [];
        $old = [
            'nom' => $editProduit->getNom(), 'description' => $editProduit->getDescription(), 'prix' => (string) $editProduit->getPrix(),
            'stock' => (string) $editProduit->getStock(), 'id_categorie' => (string) $editProduit->getIdCategorie(),
            'id_vendeur' => (string) $editProduit->getIdVendeur(),
        ];
        if ($isPost) {
            $errors = $produitController->update($id, $_POST, $_FILES, null);
            if (!$errors) {
                AuthController::flash('success', 'Produit modifié.');
                header('Location: index.php?action=admin_produits');
                exit;
            }
            $old = $_POST;
        }
        $mode = 'admin';
        $categories = $categorieController->all();
        $vendeurs = $produitController->getVendeurs();
        require __DIR__ . '/views/produit_form.php';
        break;

    case 'admin_produit_delete':
        AuthController::requireRole('admin');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = $produitController->delete((int) ($_POST['id'] ?? 0), $_POST, null);
        AuthController::flash($error ? 'error' : 'success', $error ?? 'Produit supprimé.');
        header('Location: index.php?action=admin_produits');
        exit;

    // ---------- BACK OFFICE : commandes (suivi, confirmation, annulation) ----------
    case 'admin_commandes':
        AuthController::requireRole('admin');
        $result = $commandeController->search($_GET);
        require __DIR__ . '/views/admin_commandes.php';
        break;

    case 'admin_commande_statut':
        AuthController::requireRole('admin');
        if (!$isPost) {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        $error = $commandeController->changeStatus((int) ($_POST['id'] ?? 0), (string) ($_POST['statut'] ?? ''), $_POST, null);
        AuthController::flash($error ? 'error' : 'success', $error ?? 'Statut de la commande modifié.');
        header('Location: index.php?action=admin_commandes');
        exit;

    default:
        http_response_code(404);
        require __DIR__ . '/views/error_404.php';
        break;
}
