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

$authController = new AuthController();
$userController = new UserController($authController);
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
                // Compte créé mais pas encore actif : direction la saisie du code reçu par e-mail
                header('Location: index.php?action=verify');
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
            if (!empty($errors['unverified'])) {
                AuthController::flash('error', 'Votre adresse e-mail n’est pas encore vérifiée. Saisissez le code reçu.');
                header('Location: index.php?action=verify');
                exit;
            }
            $oldEmail = (string) ($_POST['email'] ?? '');
        }
        require __DIR__ . '/views/login.php';
        break;

    // ---------- Vérification de l'adresse e-mail (code à 6 chiffres) ----------
    case 'verify':
        if (AuthController::isAuthenticated()) {
            header('Location: index.php?action=' . AuthController::homeAction());
            exit;
        }
        $pendingEmail = AuthController::pendingVerificationEmail();
        if ($pendingEmail === null) {
            header('Location: index.php?action=login');
            exit;
        }
        $errors = [];
        if ($isPost) {
            $errors = $authController->verifyEmail($_POST);
            if (!$errors) {
                AuthController::flash('success', 'Adresse e-mail vérifiée : votre compte est activé. Vous pouvez vous connecter.');
                header('Location: index.php?action=login');
                exit;
            }
        }
        require __DIR__ . '/views/verify_email.php';
        break;

    // ---------- Nouveau code de vérification (POST + CSRF) ----------
    case 'verify_resend':
        if ($isPost) {
            $error = $authController->resendVerificationCode($_POST);
            if ($error) {
                AuthController::flash('error', $error);
            }
        }
        header('Location: index.php?action=verify');
        exit;

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

    default:
        http_response_code(404);
        require __DIR__ . '/views/error_404.php';
        break;
}
