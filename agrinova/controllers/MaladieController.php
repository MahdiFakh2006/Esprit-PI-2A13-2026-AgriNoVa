<?php
/**
 * controllers/MaladieController.php
 * Module 3 – Diagnostic IA : gestion des maladies par l'administrateur (CRUD du Back Office).
 * Toute la logique est ici : requêtes préparées, validation, règles métier.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/MaladieModel.php';

class MaladieController
{
    public const COULEURS = ['jaune' => 'Taches jaunes', 'brun' => 'Taches brunes', 'blanc' => 'Feutrage blanc', 'noir' => 'Dépôt noir'];
    public const GRAVITES = ['faible' => 'Faible', 'moyenne' => 'Moyenne', 'elevee' => 'Élevée'];
    public const PER_PAGE = 8;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    private static function csrfError(array $post): ?array
    {
        return AuthController::verifyCsrfToken($post['csrf_token'] ?? null)
            ? null
            : ['global' => 'Jeton de sécurité invalide. Rechargez la page et réessayez.'];
    }

    // ==============================================================
    // LECTURE (READ)
    // ==============================================================

    /** Liste avec recherche, filtre par culture, tri (liste blanche) et pagination. */
    public function search(array $query): array
    {
        $q       = trim((string) ($query['q'] ?? ''));
        $culture = trim((string) ($query['culture'] ?? ''));
        $sort    = (string) ($query['sort'] ?? 'nom');
        $page    = max(1, (int) ($query['page'] ?? 1));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(m.nom LIKE :q1 OR m.symptomes LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . $q . '%';
        }
        if ($culture !== '' && in_array($culture, $this->cultures(), true)) {
            $where[] = 'm.culture = :culture';
            $params['culture'] = $culture;
        } else {
            $culture = '';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Tri : liste blanche (jamais une colonne saisie par l'utilisateur dans le SQL)
        $orders = [
            'nom'      => 'm.nom ASC',
            'culture'  => 'm.culture ASC, m.nom ASC',
            'analyses' => 'nb_analyses DESC, m.nom ASC',
        ];
        $sort = array_key_exists($sort, $orders) ? $sort : 'nom';

        $count = $this->db->prepare('SELECT COUNT(*) FROM maladie m' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        // JOINTURE : nombre d'analyses où chaque maladie a été détectée
        $stmt = $this->db->prepare(
            'SELECT m.*, COUNT(a.id_analyse) AS nb_analyses
             FROM maladie m LEFT JOIN analyse a ON a.id_maladie = m.id_maladie'
            . $whereSql . ' GROUP BY m.id_maladie ORDER BY ' . $orders[$sort]
            . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $maladies = array_map([MaladieModel::class, 'fromArray'], $stmt->fetchAll());

        return compact('maladies', 'total', 'page', 'pages', 'q', 'culture', 'sort');
    }

    public function find(int $id): ?MaladieModel
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, COUNT(a.id_analyse) AS nb_analyses
             FROM maladie m LEFT JOIN analyse a ON a.id_maladie = m.id_maladie
             WHERE m.id_maladie = :id GROUP BY m.id_maladie'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? MaladieModel::fromArray($row) : null;
    }

    /** Cultures connues (sert aussi de liste blanche pour les filtres et le formulaire de diagnostic). */
    public function cultures(): array
    {
        return $this->db->query('SELECT DISTINCT culture FROM maladie ORDER BY culture')->fetchAll(PDO::FETCH_COLUMN);
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION
    // ==============================================================

    public function create(array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        [$errors, $data] = $this->validate($post);
        if ($errors) {
            return $errors;
        }
        $this->db->prepare(
            'INSERT INTO maladie (nom, culture, symptomes, traitement, couleur_symptome, gravite)
             VALUES (:nom, :culture, :symptomes, :traitement, :couleur, :gravite)'
        )->execute($data);
        return [];
    }

    public function update(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        if ($this->find($id) === null) {
            return ['global' => 'Maladie introuvable.'];
        }
        [$errors, $data] = $this->validate($post, $id);
        if ($errors) {
            return $errors;
        }
        $data['id'] = $id;
        $this->db->prepare(
            'UPDATE maladie SET nom = :nom, culture = :culture, symptomes = :symptomes, traitement = :traitement,
                    couleur_symptome = :couleur, gravite = :gravite
             WHERE id_maladie = :id'
        )->execute($data);
        return [];
    }

    public function delete(int $id, array $post): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }
        $maladie = $this->find($id);
        if ($maladie === null) {
            return 'Maladie introuvable.';
        }
        // Règle métier : on garde l'historique des agriculteurs intact
        if ($maladie->getNbAnalyses() > 0) {
            return 'Suppression impossible : « ' . $maladie->getNom() . ' » a déjà été détectée dans '
                . $maladie->getNbAnalyses() . ' analyse(s). Modifiez-la plutôt.';
        }
        $this->db->prepare('DELETE FROM maladie WHERE id_maladie = :id')->execute(['id' => $id]);
        return null;
    }

    // ==============================================================
    // VALIDATION (contrôles de saisie côté serveur)
    // ==============================================================

    /** @return array [erreurs par champ, valeurs nettoyées] */
    private function validate(array $post, int $exceptId = 0): array
    {
        $data = [
            'nom'        => trim((string) ($post['nom'] ?? '')),
            'culture'    => trim((string) ($post['culture'] ?? '')),
            'symptomes'  => trim((string) ($post['symptomes'] ?? '')),
            'traitement' => trim((string) ($post['traitement'] ?? '')),
            'couleur'    => (string) ($post['couleur_symptome'] ?? ''),
            'gravite'    => (string) ($post['gravite'] ?? ''),
        ];
        $errors = [];
        $len = fn(string $s) => mb_strlen($s);

        if ($len($data['nom']) < 2 || $len($data['nom']) > 120) {
            $errors['nom'] = 'Le nom doit contenir de 2 à 120 caractères.';
        } elseif (!preg_match("/^[\p{L}0-9][\p{L}0-9 '’\-()]*$/u", $data['nom'])) {
            $errors['nom'] = 'Le nom contient des caractères non autorisés.';
        }
        if ($e = Validator::name($data['culture'], 'Culture')) {
            $errors['culture'] = $e;
        }
        if ($len($data['symptomes']) < 10 || $len($data['symptomes']) > 1000) {
            $errors['symptomes'] = 'Décrivez les symptômes en 10 à 1000 caractères.';
        }
        if ($len($data['traitement']) < 10 || $len($data['traitement']) > 1000) {
            $errors['traitement'] = 'Décrivez le traitement en 10 à 1000 caractères.';
        }
        if (!array_key_exists($data['couleur'], self::COULEURS)) {
            $errors['couleur_symptome'] = 'Choisissez la couleur des symptômes.';
        }
        if (!array_key_exists($data['gravite'], self::GRAVITES)) {
            $errors['gravite'] = 'Choisissez la gravité.';
        }

        // Règle métier : une maladie n'existe qu'une fois par culture (aussi garanti par l'index UNIQUE)
        if (!isset($errors['nom']) && !isset($errors['culture'])) {
            $stmt = $this->db->prepare(
                'SELECT id_maladie FROM maladie WHERE nom = :nom AND culture = :culture AND id_maladie <> :id LIMIT 1'
            );
            $stmt->execute(['nom' => $data['nom'], 'culture' => $data['culture'], 'id' => $exceptId]);
            if ($stmt->fetch()) {
                $errors['nom'] = 'Cette maladie existe déjà pour la culture « ' . $data['culture'] . ' ».';
            }
        }
        return [$errors, $data];
    }
}
