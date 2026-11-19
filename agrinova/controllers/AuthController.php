<?php
/**
 * controllers/AuthController.php
 * Logique d'authentification : inscription, connexion, déconnexion, sessions,
 * rôles (autorisation) et jeton CSRF.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/RoleModel.php';
require_once __DIR__ . '/Validator.php';

class AuthController
{
    /** Rôles qu'un visiteur peut choisir à l'inscription (jamais "admin"). */
    public const PUBLIC_ROLES = ['agriculteur', 'vendeur', 'parrain'];

    /** Tous les rôles de l'application. */
    public const ALL_ROLES = ['admin', 'agriculteur', 'vendeur', 'parrain'];

    /** Anti force brute : nombre d'échecs autorisés et durée du blocage (secondes). */
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    // ==============================================================
    // ÉTAT DE LA SESSION ET AUTORISATION
    // ==============================================================

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function currentRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::isAuthenticated() && in_array(self::currentRole(), $roles, true);
    }

    /** Redirige vers la connexion si l'utilisateur n'est pas connecté. */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            self::flash('error', 'Veuillez vous connecter pour accéder à cette page.');
            header('Location: index.php?action=login');
            exit;
        }
    }

    /**
     * Bloque l'accès (erreur 403) si l'utilisateur n'a aucun des rôles demandés.
     * À appeler en haut de CHAQUE page sensible, pas seulement à la connexion.
     * Exemple : AuthController::requireRole('vendeur', 'admin');
     */
    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!in_array(self::currentRole(), $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../views/error_403.php';
            exit;
        }
    }

    /** Espace d'arrivée selon le rôle : Back Office pour l'admin, Front Office pour les autres. */
    public static function homeAction(): string
    {
        return self::currentRole() === 'admin' ? 'admin' : 'dashboard';
    }

    /**
     * Relit le compte en base à chaque requête : si l'utilisateur a été supprimé ou bloqué,
     * ou si son rôle a changé, la session est mise à jour ou fermée immédiatement.
     */
    public function syncSession(): void
    {
        if (!self::isAuthenticated()) {
            return;
        }
        $user = $this->findById(self::currentUserId());
        if ($user === null || $user->isBloque()) {
            $this->logout();
            session_start();
            self::flash('error', 'Votre session a été fermée : compte introuvable ou bloqué.');
            header('Location: index.php?action=login');
            exit;
        }
        $_SESSION['role'] = $user->getRole();
        $_SESSION['user_name'] = $user->getNomComplet();
    }

    // ==============================================================
    // MESSAGES FLASH (affichés une seule fois après une redirection)
    // ==============================================================

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function takeFlashes(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    // ==============================================================
    // CSRF
    // ==============================================================

    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        if (!$token || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // ==============================================================
    // ACCÈS AUX DONNÉES (requêtes préparées uniquement)
    // ==============================================================

    private const SELECT_USER = 'SELECT u.*, r.libelle AS role
                                 FROM utilisateur u
                                 JOIN role r ON u.id_role = r.id_role';

    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->db->prepare(self::SELECT_USER . ' WHERE u.email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(self::SELECT_USER . ' WHERE u.id_utilisateur = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    public function emailExists(string $email, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id_utilisateur FROM utilisateur WHERE email = :email AND id_utilisateur <> :id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }

    public function findRoleId(string $libelle): ?int
    {
        $stmt = $this->db->prepare('SELECT id_role FROM role WHERE libelle = :libelle LIMIT 1');
        $stmt->execute(['libelle' => $libelle]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @return RoleModel[] */
    public function getRoles(): array
    {
        $rows = $this->db->query('SELECT * FROM role ORDER BY id_role')->fetchAll();
        return array_map([RoleModel::class, 'fromArray'], $rows);
    }

    public function insertUser(UserModel $user): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, id_role, statut)
             VALUES (:nom, :prenom, :email, :mdp, :tel, :role, :statut)'
        );
        $stmt->execute([
            'nom'    => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email'  => $user->getEmail(),
            'mdp'    => $user->getMotDePasse(),
            'tel'    => $user->getTelephone(),
            'role'   => $user->getIdRole(),
            'statut' => $user->getStatut(),
        ]);
        return (int) $this->db->lastInsertId();
    }

    // ==============================================================
    // VALIDATION COMMUNE (inscription + création par l'admin)
    // ==============================================================

    /**
     * Valide les champs d'identité d'un formulaire utilisateur.
     * Renvoie [erreurs par champ, valeurs nettoyées].
     */
    public function validateIdentity(array $post, int $exceptId = 0): array
    {
        $data = [
            'nom'       => trim($post['nom'] ?? ''),
            'prenom'    => trim($post['prenom'] ?? ''),
            'email'     => strtolower(trim($post['email'] ?? '')),
            'telephone' => Validator::normalizePhone($post['telephone'] ?? ''),
        ];
        $errors = array_filter([
            'nom'       => Validator::name($data['nom'], 'Nom'),
            'prenom'    => Validator::name($data['prenom'], 'Prénom'),
            'email'     => Validator::email($data['email']),
            'telephone' => Validator::phone($data['telephone']),
        ]);
        if (!isset($errors['email']) && $this->emailExists($data['email'], $exceptId)) {
            $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
        }
        return [$errors, $data];
    }

    // ==============================================================
    // INSCRIPTION
    // ==============================================================

    /** @return array erreurs par champ (tableau vide = succès) */
    public function register(array $post): array
    {
        if (!self::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return ['global' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.'];
        }

        [$errors, $data] = $this->validateIdentity($post);
        $password = (string) ($post['mot_de_passe'] ?? '');
        $confirm  = (string) ($post['confirmation'] ?? '');
        $role     = (string) ($post['role'] ?? '');

        $errors += array_filter([
            'mot_de_passe' => Validator::password($password),
            'confirmation' => Validator::matches($password, $confirm),
            // Liste blanche : impossible de s'inscrire comme administrateur
            'role'         => Validator::inList($role, self::PUBLIC_ROLES, 'Type de compte'),
        ]);
        if (empty($post['cgu'])) {
            $errors['cgu'] = 'Vous devez accepter les conditions d’utilisation.';
        }
        if ($errors) {
            return $errors;
        }

        $user = new UserModel();
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);
        $user->setTelephone($data['telephone']);
        $user->setMotDePasse(password_hash($password, PASSWORD_DEFAULT)); // hachage bcrypt + sel automatique
        $user->setIdRole((int) $this->findRoleId($role));
        $user->setStatut('actif');
        $this->insertUser($user);

        return [];
    }

    // ==============================================================
    // CONNEXION / DÉCONNEXION
    // ==============================================================

    public function login(array $post): array
    {
        if (!self::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return ['global' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.'];
        }

        // Anti force brute : 5 échecs => 5 minutes d'attente
        $lockedUntil = $_SESSION['login_locked_until'] ?? 0;
        if ($lockedUntil > time()) {
            $minutes = (int) ceil(($lockedUntil - time()) / 60);
            return ['global' => "Trop de tentatives. Réessayez dans $minutes minute(s)."];
        }

        $email    = strtolower(trim($post['email'] ?? ''));
        $password = (string) ($post['mot_de_passe'] ?? '');
        if ($email === '' || $password === '') {
            return ['global' => 'Veuillez saisir votre e-mail et votre mot de passe.'];
        }

        $user = $this->findByEmail($email);
        if ($user === null || !password_verify($password, $user->getMotDePasse())) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_locked_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_attempts'] = 0;
            }
            // Message volontairement générique : on ne dit pas si c'est l'e-mail ou le mot de passe
            return ['global' => 'E-mail ou mot de passe incorrect.'];
        }
        if ($user->isBloque()) {
            return ['global' => 'Ce compte est bloqué. Contactez l’administrateur.'];
        }

        // Anti fixation de session : nouvel identifiant après la connexion
        session_regenerate_id(true);
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        $_SESSION['user_id']   = $user->getId();
        $_SESSION['role']      = $user->getRole(); // le rôle vient de la base, jamais du formulaire
        $_SESSION['user_name'] = $user->getNomComplet();
        $_SESSION['LAST_ACTIVITY'] = time();

        return [];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
