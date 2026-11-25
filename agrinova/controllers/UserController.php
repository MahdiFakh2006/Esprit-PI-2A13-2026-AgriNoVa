<?php
/**
 * controllers/UserController.php
 * Gestion des utilisateurs : CRUD du Back Office, statistiques,
 * profil et mot de passe de l'utilisateur connecté.
 */
require_once __DIR__ . '/AuthController.php';

class UserController
{
    public const PER_PAGE = 8;

    private PDO $db;
    private AuthController $auth;

    public function __construct(AuthController $auth)
    {
        $this->db = Database::getConnexion();
        $this->auth = $auth;
    }

    private static function csrfError(array $post): ?array
    {
        return AuthController::verifyCsrfToken($post['csrf_token'] ?? null)
            ? null
            : ['global' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.'];
    }

    // ==============================================================
    // LECTURE (READ) : liste avec recherche, filtre, tri et pagination
    // ==============================================================

    public function search(array $query): array
    {
        $q      = trim((string) ($query['q'] ?? ''));
        $role   = (string) ($query['role'] ?? '');
        $statut = (string) ($query['statut'] ?? '');
        $sort   = (string) ($query['sort'] ?? 'recent');
        $page   = max(1, (int) ($query['page'] ?? 1));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.nom LIKE :q1 OR u.prenom LIKE :q2 OR u.email LIKE :q3)';
            $params['q1'] = $params['q2'] = $params['q3'] = '%' . $q . '%';
        }
        if (in_array($role, AuthController::ALL_ROLES, true)) {
            $where[] = 'r.libelle = :role';
            $params['role'] = $role;
        } else {
            $role = '';
        }
        if (in_array($statut, ['actif', 'bloque'], true)) {
            $where[] = 'u.statut = :statut';
            $params['statut'] = $statut;
        } else {
            $statut = '';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Le tri vient d'une liste blanche : jamais de colonne saisie par l'utilisateur dans le SQL
        $orders = [
            'recent' => 'u.date_creation DESC, u.id_utilisateur DESC',
            'ancien' => 'u.date_creation ASC, u.id_utilisateur ASC',
            'nom'    => 'u.nom ASC, u.prenom ASC',
        ];
        $sort = array_key_exists($sort, $orders) ? $sort : 'recent';

        $count = $this->db->prepare('SELECT COUNT(*) FROM utilisateur u JOIN role r ON u.id_role = r.id_role' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $stmt = $this->db->prepare(
            'SELECT u.*, r.libelle AS role FROM utilisateur u JOIN role r ON u.id_role = r.id_role'
            . $whereSql . ' ORDER BY ' . $orders[$sort] . ' LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $users = array_map([UserModel::class, 'fromArray'], $stmt->fetchAll());

        return compact('users', 'total', 'page', 'pages', 'q', 'role', 'statut', 'sort');
    }

    /** Statistiques du tableau de bord : total, par rôle, comptes bloqués, derniers inscrits. */
    public function stats(): array
    {
        $byRole = array_fill_keys(AuthController::ALL_ROLES, 0);
        $rows = $this->db->query(
            'SELECT r.libelle, COUNT(u.id_utilisateur) AS nb
             FROM role r LEFT JOIN utilisateur u ON u.id_role = r.id_role
             GROUP BY r.id_role, r.libelle'
        )->fetchAll();
        foreach ($rows as $row) {
            $byRole[$row['libelle']] = (int) $row['nb'];
        }
        $blocked = (int) $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE statut = 'bloque'")->fetchColumn();
        $latest = array_map(
            [UserModel::class, 'fromArray'],
            $this->db->query(
                'SELECT u.*, r.libelle AS role FROM utilisateur u JOIN role r ON u.id_role = r.id_role
                 ORDER BY u.date_creation DESC, u.id_utilisateur DESC LIMIT 5'
            )->fetchAll()
        );
        return [
            'total'   => array_sum($byRole),
            'byRole'  => $byRole,
            'blocked' => $blocked,
            'latest'  => $latest,
        ];
    }

    public function find(int $id): ?UserModel
    {
        return $this->auth->findById($id);
    }

    // ==============================================================
    // CRÉATION (CREATE) par l'administrateur
    // ==============================================================

    public function create(array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        [$errors, $data] = $this->auth->validateIdentity($post);
        $password = (string) ($post['mot_de_passe'] ?? '');
        $role     = (string) ($post['role'] ?? '');
        $statut   = (string) ($post['statut'] ?? 'actif');
        $errors += array_filter([
            'mot_de_passe' => Validator::password($password),
            'confirmation' => Validator::matches($password, (string) ($post['confirmation'] ?? '')),
            'role'         => Validator::inList($role, AuthController::ALL_ROLES, 'Rôle'),
            'statut'       => Validator::inList($statut, ['actif', 'bloque'], 'Statut'),
        ]);
        if ($errors) {
            return $errors;
        }

        $user = new UserModel();
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);
        $user->setTelephone($data['telephone']);
        $user->setMotDePasse(password_hash($password, PASSWORD_DEFAULT));
        $user->setIdRole((int) $this->auth->findRoleId($role));
        $user->setStatut($statut);
        $user->setEmailVerifie(true); // compte créé par l'administrateur : pas de vérification par e-mail
        $this->auth->insertUser($user);
        return [];
    }

    // ==============================================================
    // MODIFICATION (UPDATE) par l'administrateur
    // ==============================================================

    public function update(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        $existing = $this->find($id);
        if ($existing === null) {
            return ['global' => 'Utilisateur introuvable.'];
        }
        [$errors, $data] = $this->auth->validateIdentity($post, $id);
        $role   = (string) ($post['role'] ?? '');
        $statut = (string) ($post['statut'] ?? 'actif');
        $errors += array_filter([
            'role'   => Validator::inList($role, AuthController::ALL_ROLES, 'Rôle'),
            'statut' => Validator::inList($statut, ['actif', 'bloque'], 'Statut'),
        ]);

        // Un administrateur ne peut pas se retirer ses propres droits ni se bloquer lui-même
        if ($id === AuthController::currentUserId() && ($role !== 'admin' || $statut !== 'actif')) {
            $errors['role'] = 'Vous ne pouvez pas retirer votre propre rôle administrateur ni bloquer votre compte.';
        }

        // Mot de passe facultatif en modification : on ne le change que s'il est rempli
        $password = (string) ($post['mot_de_passe'] ?? '');
        if ($password !== '') {
            $errors += array_filter([
                'mot_de_passe' => Validator::password($password),
                'confirmation' => Validator::matches($password, (string) ($post['confirmation'] ?? '')),
            ]);
        }
        if ($errors) {
            return $errors;
        }

        $sql = 'UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :tel,
                id_role = :role, statut = :statut';
        $params = [
            'nom'    => $data['nom'],
            'prenom' => $data['prenom'],
            'email'  => $data['email'],
            'tel'    => $data['telephone'],
            'role'   => (int) $this->auth->findRoleId($role),
            'statut' => $statut,
            'id'     => $id,
        ];
        if ($password !== '') {
            $sql .= ', mot_de_passe = :mdp';
            $params['mdp'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->db->prepare($sql . ' WHERE id_utilisateur = :id')->execute($params);
        return [];
    }

    // ==============================================================
    // SUPPRESSION (DELETE) et blocage
    // ==============================================================

    public function delete(int $id, array $post): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }
        if ($id === AuthController::currentUserId()) {
            return 'Vous ne pouvez pas supprimer votre propre compte.';
        }
        if ($this->find($id) === null) {
            return 'Utilisateur introuvable.';
        }

        // Transaction : soit l'utilisateur ET toutes ses données liées sont supprimés,
        // soit rien n'est supprimé (en cas d'erreur, la base revient à son état initial).
        $this->db->beginTransaction();
        try {
            $this->deleteWithDependencies('utilisateur', 'id_utilisateur', [$id]);
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Suppression utilisateur ' . $id . ' : ' . $e->getMessage());
            return 'La suppression a échoué, aucune donnée n’a été modifiée.';
        }
        return null;
    }

    /**
     * Supprime les lignes de $table dont $column vaut l'une des $values,
     * APRÈS avoir supprimé (récursivement) toutes les lignes qui les référencent
     * par une clé étrangère : produits, réservations de ces produits, inscriptions, etc.
     * Les tables liées sont lues dans information_schema : rien à modifier quand
     * un nouveau module ajoute ses tables.
     */
    private function deleteWithDependencies(string $table, string $column, array $values, int $depth = 0): void
    {
        if (!$values || $depth > 10) {   // garde-fou contre une boucle de clés étrangères
            return;
        }
        $in = implode(',', array_fill(0, count($values), '?'));

        foreach ($this->foreignKeysTo($table) as $fk) {
            // Valeurs de la colonne référencée pour les lignes qu'on va supprimer
            // (ex : les id_produit des produits du vendeur supprimé)
            $stmt = $this->db->prepare(
                'SELECT DISTINCT `' . $fk['REFERENCED_COLUMN_NAME'] . '` FROM `' . $table . '` WHERE `' . $column . '` IN (' . $in . ')'
            );
            $stmt->execute($values);
            $referenced = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Suppression d'abord des lignes « enfants » (ex : les réservations de ces produits)
            $this->deleteWithDependencies($fk['TABLE_NAME'], $fk['COLUMN_NAME'], $referenced, $depth + 1);
        }

        $this->db->prepare('DELETE FROM `' . $table . '` WHERE `' . $column . '` IN (' . $in . ')')->execute($values);
    }

    /**
     * Liste des clés étrangères qui pointent vers $table dans la base courante.
     * Les noms de tables et de colonnes viennent de MySQL lui-même (jamais de l'utilisateur).
     */
    private function foreignKeysTo(string $table): array
    {
        $stmt = $this->db->prepare(
            'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = :t'
        );
        $stmt->execute(['t' => $table]);
        return $stmt->fetchAll();
    }

    public function toggleStatus(int $id, array $post): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }
        if ($id === AuthController::currentUserId()) {
            return 'Vous ne pouvez pas bloquer votre propre compte.';
        }
        $user = $this->find($id);
        if ($user === null) {
            return 'Utilisateur introuvable.';
        }
        $this->db->prepare('UPDATE utilisateur SET statut = :s WHERE id_utilisateur = :id')
            ->execute(['s' => $user->isBloque() ? 'actif' : 'bloque', 'id' => $id]);
        return null;
    }

    // ==============================================================
    // PROFIL DE L'UTILISATEUR CONNECTÉ (Front Office)
    // ==============================================================

    public function updateProfile(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        [$errors, $data] = $this->auth->validateIdentity($post, $id);
        if ($errors) {
            return $errors;
        }
        // Le rôle et le statut ne sont PAS modifiables par l'utilisateur lui-même
        $this->db->prepare(
            'UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :tel
             WHERE id_utilisateur = :id'
        )->execute([
            'nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'],
            'tel' => $data['telephone'], 'id' => $id,
        ]);
        $_SESSION['user_name'] = $data['prenom'] . ' ' . $data['nom'];
        return [];
    }

    public function changePassword(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        $user = $this->find($id);
        $current = (string) ($post['actuel'] ?? '');
        $new     = (string) ($post['nouveau'] ?? '');
        if ($user === null || !password_verify($current, $user->getMotDePasse())) {
            return ['actuel' => 'Le mot de passe actuel est incorrect.'];
        }
        $errors = array_filter([
            'nouveau'      => Validator::password($new),
            'confirmation' => Validator::matches($new, (string) ($post['confirmation'] ?? '')),
        ]);
        if ($errors) {
            return $errors;
        }
        $this->db->prepare('UPDATE utilisateur SET mot_de_passe = :mdp WHERE id_utilisateur = :id')
            ->execute(['mdp' => password_hash($new, PASSWORD_DEFAULT), 'id' => $id]);
        session_regenerate_id(true);
        return [];
    }
}
