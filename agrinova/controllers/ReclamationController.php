<?php
/**
 * controllers/ReclamationController.php
 * MODULE 5 — Réclamations et support.
 * Toute la logique de l'entité Reclamation : requêtes SQL préparées, validation,
 * règles métier (statuts, propriété des réclamations), statistiques du Back Office.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/ReclamationModel.php';

class ReclamationController
{
    public const PER_PAGE = 8;

    /** Catégories autorisées (liste blanche) : clé stockée en base => libellé affiché. */
    public const CATEGORIES = [
        'commande'   => 'Commande',
        'location'   => 'Location',
        'diagnostic' => 'Diagnostic IA',
        'formation'  => 'Formation',
        'parrainage' => 'Parrainage',
    ];

    /** Statuts dans l'ordre du cycle de vie : ouverte → en cours → résolue → clôturée. */
    public const STATUTS = [
        'ouverte'  => 'Ouverte',
        'en_cours' => 'En cours',
        'resolue'  => 'Résolue',
        'cloturee' => 'Clôturée',
    ];

    public const SUJET_MIN = 5;
    public const SUJET_MAX = 150;
    public const DESC_MIN  = 20;
    public const DESC_MAX  = 2000;

    /** Tris autorisés pour la liste du Back Office (liste blanche : jamais de colonne venant de l'URL). */
    private const SORTS = [
        'recent' => 'r.date_creation DESC, r.id_reclamation DESC',
        'ancien' => 'r.date_creation ASC, r.id_reclamation ASC',
        'statut' => "FIELD(r.statut, 'ouverte', 'en_cours', 'resolue', 'cloturee'), r.date_creation ASC",
    ];

    /** Jointure Reclamation 1 — N Reponse (+ auteur) : nombre de réponses par réclamation. */
    private const SELECT = "SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_nom, u.email AS auteur_email,
                                   COUNT(p.id_reponse) AS nb_rep
                            FROM reclamation r
                            JOIN utilisateur u ON u.id_utilisateur = r.id_utilisateur
                            LEFT JOIN reponse p ON p.id_reclamation = r.id_reclamation";
    private const GROUP = ' GROUP BY r.id_reclamation, u.id_utilisateur';

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    // ==============================================================
    // OUTILS (libellés, droits de modification, formatage)
    // ==============================================================

    public static function categorieLabel(string $cle): string
    {
        return self::CATEGORIES[$cle] ?? ucfirst($cle);
    }

    public static function statutLabel(string $cle): string
    {
        return self::STATUTS[$cle] ?? ucfirst($cle);
    }

    /** Un client ne peut modifier / supprimer sa réclamation que tant que personne n'y a répondu. */
    public static function isEditable(ReclamationModel $rec): bool
    {
        return $rec->getStatut() === 'ouverte' && $rec->getNbReponses() === 0;
    }

    /** Statuts vers lesquels l'admin peut faire avancer la réclamation (jamais en arrière). */
    public static function nextStatuts(string $current): array
    {
        $order = array_keys(self::STATUTS);
        $pos = array_search($current, $order, true);
        if ($pos === false) {
            return [];
        }
        return array_slice($order, $pos + 1);
    }

    /** Durée lisible à partir d'un nombre de minutes (ex : « 3,5 h »). */
    public static function formatDelay(?float $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }
        if ($minutes < 60) {
            return (int) round($minutes) . ' min';
        }
        if ($minutes < 1440) {
            return number_format($minutes / 60, 1, ',', '') . ' h';
        }
        return number_format($minutes / 1440, 1, ',', '') . ' j';
    }

    private static function csrfMessage(array $post): ?string
    {
        return AuthController::verifyCsrfToken($post['csrf_token'] ?? null)
            ? null
            : 'Jeton de sécurité invalide. Rechargez la page et réessayez.';
    }

    // ==============================================================
    // VALIDATION CÔTÉ SERVEUR (miroir des règles data-rules du JavaScript)
    // ==============================================================

    private static function validate(array $post): array
    {
        $errors = [];
        $sujet       = trim((string) ($post['sujet'] ?? ''));
        $categorie   = (string) ($post['categorie'] ?? '');
        $description = trim((string) ($post['description'] ?? ''));

        if ($e = Validator::required($sujet, 'Sujet')) {
            $errors['sujet'] = $e;
        } elseif (mb_strlen($sujet) < self::SUJET_MIN || mb_strlen($sujet) > self::SUJET_MAX) {
            $errors['sujet'] = 'Le sujet doit contenir entre ' . self::SUJET_MIN . ' et ' . self::SUJET_MAX . ' caractères.';
        }

        if ($categorie === '') {
            $errors['categorie'] = 'Veuillez choisir une catégorie.';
        } elseif ($e = Validator::inList($categorie, array_keys(self::CATEGORIES), 'Catégorie')) {
            $errors['categorie'] = $e;
        }

        if ($e = Validator::required($description, 'Description')) {
            $errors['description'] = $e;
        } elseif (mb_strlen($description) < self::DESC_MIN) {
            $errors['description'] = 'La description doit contenir au moins ' . self::DESC_MIN . ' caractères.';
        } elseif (mb_strlen($description) > self::DESC_MAX) {
            $errors['description'] = 'La description ne doit pas dépasser ' . self::DESC_MAX . ' caractères.';
        }

        return $errors;
    }

    // ==============================================================
    // LECTURE (READ)
    // ==============================================================

    /** Une réclamation (n'importe laquelle) — réservé au Back Office. */
    public function find(int $id): ?ReclamationModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE r.id_reclamation = :id' . self::GROUP);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? ReclamationModel::fromArray($row) : null;
    }

    /** Une réclamation, SEULEMENT si elle appartient à l'utilisateur (sinon null). */
    public function findForUser(int $id, int $userId): ?ReclamationModel
    {
        $stmt = $this->db->prepare(
            self::SELECT . ' WHERE r.id_reclamation = :id AND r.id_utilisateur = :u' . self::GROUP
        );
        $stmt->execute([':id' => $id, ':u' => $userId]);
        $row = $stmt->fetch();
        return $row ? ReclamationModel::fromArray($row) : null;
    }

    /** « Mes réclamations » : uniquement celles de l'utilisateur connecté, filtre par statut + pagination. */
    public function listForUser(int $userId, array $query): array
    {
        $statut = (string) ($query['statut'] ?? '');
        if (!isset(self::STATUTS[$statut])) {
            $statut = '';
        }
        $page = max(1, (int) ($query['page'] ?? 1));

        $where = 'WHERE r.id_utilisateur = :u';
        $params = [':u' => $userId];
        if ($statut !== '') {
            $where .= ' AND r.statut = :statut';
            $params[':statut'] = $statut;
        }

        $count = $this->db->prepare("SELECT COUNT(*) FROM reclamation r $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $stmt = $this->db->prepare(
            self::SELECT . " $where" . self::GROUP
            . ' ORDER BY r.date_creation DESC, r.id_reclamation DESC'
            . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (int) $offset
        );
        $stmt->execute($params);

        return [
            'items'  => array_map([ReclamationModel::class, 'fromArray'], $stmt->fetchAll()),
            'total'  => $total,
            'page'   => $page,
            'pages'  => $pages,
            'statut' => $statut,
        ];
    }

    /** Back Office : liste filtrée par statut et par catégorie, avec recherche, tri et pagination. */
    public function search(array $query): array
    {
        $q         = trim((string) ($query['q'] ?? ''));
        $statut    = (string) ($query['statut'] ?? '');
        $categorie = (string) ($query['categorie'] ?? '');
        $sort      = (string) ($query['sort'] ?? 'recent');
        $page      = max(1, (int) ($query['page'] ?? 1));

        if (!isset(self::STATUTS[$statut])) {
            $statut = '';
        }
        if (!isset(self::CATEGORIES[$categorie])) {
            $categorie = '';
        }
        if (!isset(self::SORTS[$sort])) {
            $sort = 'recent';
        }

        $where = [];
        $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            // Un nom de paramètre par occurrence (obligatoire avec les vraies requêtes préparées)
            $where[] = '(r.sujet LIKE :q1 OR u.nom LIKE :q2 OR u.prenom LIKE :q3 OR u.email LIKE :q4)';
            $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
        }
        if ($statut !== '') {
            $where[] = 'r.statut = :statut';
            $params[':statut'] = $statut;
        }
        if ($categorie !== '') {
            $where[] = 'r.categorie = :categorie';
            $params[':categorie'] = $categorie;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $this->db->prepare(
            'SELECT COUNT(*) FROM reclamation r JOIN utilisateur u ON u.id_utilisateur = r.id_utilisateur' . $whereSql
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $stmt = $this->db->prepare(
            self::SELECT . $whereSql . self::GROUP
            . ' ORDER BY ' . self::SORTS[$sort]
            . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (int) $offset
        );
        $stmt->execute($params);

        return [
            'items'     => array_map([ReclamationModel::class, 'fromArray'], $stmt->fetchAll()),
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'q'         => $q,
            'statut'    => $statut,
            'categorie' => $categorie,
            'sort'      => $sort,
        ];
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION (CREATE, UPDATE, DELETE)
    // ==============================================================

    /** Dépose une réclamation. Renvoie un tableau d'erreurs (vide si tout est bon). */
    public function create(int $userId, array $post): array
    {
        if ($msg = self::csrfMessage($post)) {
            return ['global' => $msg];
        }
        $errors = self::validate($post);
        if ($errors) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reclamation (id_utilisateur, sujet, categorie, description, statut)
             VALUES (:u, :sujet, :categorie, :description, 'ouverte')"
        );
        $stmt->execute([
            ':u'           => $userId,
            ':sujet'       => trim((string) $post['sujet']),
            ':categorie'   => (string) $post['categorie'],
            ':description' => trim((string) $post['description']),
        ]);
        return [];
    }

    /** Modifie SA réclamation (seulement tant qu'elle est « ouverte » et sans réponse). */
    public function update(int $id, int $userId, array $post): array
    {
        if ($msg = self::csrfMessage($post)) {
            return ['global' => $msg];
        }
        $rec = $this->findForUser($id, $userId);
        if ($rec === null || !self::isEditable($rec)) {
            return ['global' => 'Cette réclamation ne peut plus être modifiée.'];
        }
        $errors = self::validate($post);
        if ($errors) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            "UPDATE reclamation
             SET sujet = :sujet, categorie = :categorie, description = :description
             WHERE id_reclamation = :id AND id_utilisateur = :u AND statut = 'ouverte'"
        );
        $stmt->execute([
            ':sujet'       => trim((string) $post['sujet']),
            ':categorie'   => (string) $post['categorie'],
            ':description' => trim((string) $post['description']),
            ':id'          => $id,
            ':u'           => $userId,
        ]);
        return [];
    }

    /** Supprime une réclamation. Client : la sienne et seulement si éditable. Admin : n'importe laquelle. */
    public function delete(int $id, int $userId, array $post, bool $asAdmin = false): ?string
    {
        if ($msg = self::csrfMessage($post)) {
            return $msg;
        }
        if ($asAdmin) {
            if (!AuthController::hasRole('admin')) {
                return 'Action réservée à l’administrateur.';
            }
            $stmt = $this->db->prepare('DELETE FROM reclamation WHERE id_reclamation = :id'); // réponses supprimées en cascade
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() === 0 ? 'Réclamation introuvable.' : null;
        }

        $rec = $this->findForUser($id, $userId);
        if ($rec === null) {
            return 'Réclamation introuvable.';
        }
        if (!self::isEditable($rec)) {
            return 'Une réclamation déjà traitée ne peut plus être supprimée.';
        }
        $stmt = $this->db->prepare('DELETE FROM reclamation WHERE id_reclamation = :id AND id_utilisateur = :u');
        $stmt->execute([':id' => $id, ':u' => $userId]);
        return null;
    }

    // ==============================================================
    // RÈGLE MÉTIER : changement de statut (Back Office)
    // ==============================================================

    public function changeStatus(int $id, array $post): ?string
    {
        if ($msg = self::csrfMessage($post)) {
            return $msg;
        }
        if (!AuthController::hasRole('admin')) {
            return 'Action réservée à l’administrateur.';
        }
        $new = (string) ($post['statut'] ?? '');
        if (!isset(self::STATUTS[$new])) {
            return 'Statut non autorisé.';
        }
        $rec = $this->find($id);
        if ($rec === null) {
            return 'Réclamation introuvable.';
        }
        if (!in_array($new, self::nextStatuts($rec->getStatut()), true)) {
            return 'Le statut ne peut avancer que dans l’ordre : ouverte → en cours → résolue → clôturée.';
        }
        $stmt = $this->db->prepare('UPDATE reclamation SET statut = :statut WHERE id_reclamation = :id');
        $stmt->execute([':statut' => $new, ':id' => $id]);
        return null;
    }

    // ==============================================================
    // STATISTIQUES DU BACK OFFICE
    // ==============================================================

    public function stats(): array
    {
        $byStatut = array_fill_keys(array_keys(self::STATUTS), 0);
        foreach ($this->db->query('SELECT statut, COUNT(*) AS nb FROM reclamation GROUP BY statut')->fetchAll() as $row) {
            if (isset($byStatut[$row['statut']])) {
                $byStatut[$row['statut']] = (int) $row['nb'];
            }
        }

        $byCategorie = array_fill_keys(array_keys(self::CATEGORIES), 0);
        foreach ($this->db->query('SELECT categorie, COUNT(*) AS nb FROM reclamation GROUP BY categorie')->fetchAll() as $row) {
            if (isset($byCategorie[$row['categorie']])) {
                $byCategorie[$row['categorie']] = (int) $row['nb'];
            }
        }

        // Délai moyen de réponse = temps entre la création et la PREMIÈRE réponse
        $avg = $this->db->query(
            'SELECT AVG(TIMESTAMPDIFF(MINUTE, r.date_creation, f.premiere))
             FROM reclamation r
             JOIN (SELECT id_reclamation, MIN(date_reponse) AS premiere FROM reponse GROUP BY id_reclamation) f
                  ON f.id_reclamation = r.id_reclamation'
        )->fetchColumn();

        $pending = array_map(
            [ReclamationModel::class, 'fromArray'],
            $this->db->query(
                self::SELECT . " WHERE r.statut = 'ouverte'" . self::GROUP
                . ' ORDER BY r.date_creation ASC LIMIT 5'
            )->fetchAll()
        );

        return [
            'total'       => array_sum($byStatut),
            'byStatut'    => $byStatut,
            'byCategorie' => $byCategorie,
            'avgMinutes'  => ($avg === false || $avg === null) ? null : (float) $avg,
            'pending'     => $pending,
        ];
    }
}
