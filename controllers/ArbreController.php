<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ArbreModel.php';

class ArbreController
{
    private PDO \$db;

    private const PER_PAGE = 8;

    private const STATUTS_VALIDATION = [
        'en_attente',
        'valide',
        'refuse'
    ];

    public function __construct()
    {
        \$this->db = Database::getConnexion();
    }

    private static function csrfError(array \$post): ?array
    {
        return AuthController::verifyCsrfToken(
            \$post['csrf_token'] ?? null
        ) ? null : [
            'global' => 'Jeton de sécurité invalide.'
        ];
    }

    public function search(array \$query): array
    {
        $region = trim((string) ($query['region'] ?? ''));
        $variete = trim((string) ($query['variete'] ?? ''));
        $q = trim((string) ($query['q'] ?? ''));

        $where = ['statut_validation = :statut_valide'];$params = ['statut_valide' => 'valide'];

        if ($region !== '') {$where[] = 'region = :region';
            $params['region'] =$region;
        }

        if ($variete !== '') {$where[] = 'variete = :variete';
            $params['variete'] =$variete;
        }

        if ($q !== '') {$where[] = '(variete LIKE :q OR region LIKE :q)';
            $params['q'] = '\%' .$q . '%';
        }

        $whereSql =$where
            ? ' WHERE ' . implode(' AND ', \$where)
            : '';

        $page = max(1, (int) ($query['page'] ?? 1));

        $count =$this->db->prepare(
            'SELECT COUNT(*) FROM arbre' . \$whereSql
        );

        $count->execute($params);
        $total = (int)$count->fetchColumn();

        \$pages = max(1, (int) ceil($total / self::PER_PAGE));$page = min($page,$pages);

        $stmt =$this->db->prepare(
            'SELECT * FROM arbre'
            . \$whereSql
            . ' ORDER BY id_arbre DESC'
            . ' LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . ((\$page - 1) * self::PER_PAGE)
        );

        $stmt->execute($params);

        \$arbres = array_map(
            [ArbreModel::class, 'fromArray'],
            \$stmt->fetchAll()
        );

        return compact(
            'arbres',
            'total',
            'page',
            'pages',
            'region',
            'variete',
            'q'
        );
    }

    public function find(int \$id): ?ArbreModel
    {
        $stmt =$this->db->prepare(
            'SELECT * FROM arbre
             WHERE id_arbre = :id'
        );

        $stmt->execute(['id' =>$id]);

        $row =$stmt->fetch();

        return \$row
            ? ArbreModel::fromArray(\$row)
            : null;
    }

    public function create(array \$post): array
    {
        if ($e = self::csrfError($post)) {
            return \$e;
        }

        \$variete = trim((string) ($post['variete'] ?? ''));$region = trim((string) ($post['region'] ?? ''));$ageAns = (int) ($post['age_ans'] ?? -1);$prixAnnuel = (float) ($post['prix_annuel'] ?? 0);$idAgriculteur = AuthController::currentUserId();

        \$errors = [];

        if (\$variete === '' || strlen($variete) > 100) {$errors['variete'] = 'Variété obligatoire (100 caractères maximum).';
        }

        if (\$region === '' || strlen($region) > 100) {$errors['region'] = 'Région obligatoire (100 caractères maximum).';
        }

        if ($ageAns < 0) {$errors['age_ans'] = 'L’âge doit être supérieur ou égal à zéro.';
        }

        if ($prixAnnuel <= 0) {$errors['prix_annuel'] = 'Le prix annuel doit être supérieur à zéro.';
        }

        if (\$errors) {
            return \$errors;
        }

        $stmt =$this->db->prepare(
            'INSERT INTO arbre
            (id_agriculteur, variete, age_ans, region, prix_annuel, statut_validation)
            VALUES
            (:agriculteur, :variete, :age, :region, :prix, :statut)'
        );

        \$stmt->execute([
            'agriculteur' => \$idAgriculteur,
            'variete' => \$variete,
            'age' => \$ageAns,
            'region' => \$region,
            'prix' => \$prixAnnuel,
            'statut' => 'en_attente'
        ]);

        return [];
    }

    public function update(int $id, array$post): array
    {
        if ($e = self::csrfError($post)) {
            return \$e;
        }

        \$arbre = $this->find($id);

        if (\$arbre === null) {
            return ['global' => 'Olivier introuvable.'];
        }

        $variete = trim((string) ($post['variete'] ?? ''));
        $region = trim((string) ($post['region'] ?? ''));
        $ageAns = (int) ($post['age_ans'] ?? -1);
        $prixAnnuel = (float) ($post['prix_annuel'] ?? 0);

        \$errors = [];

        if (\$variete === '' || strlen($variete) > 100) {$errors['variete'] = 'Variété invalide.';
        }

        if (\$region === '' || strlen($region) > 100) {$errors['region'] = 'Région invalide.';
        }

        if ($ageAns < 0) {$errors['age_ans'] = 'Âge invalide.';
        }

        if ($prixAnnuel <= 0) {$errors['prix_annuel'] = 'Prix annuel invalide.';
        }

        if (\$errors) {
            return \$errors;
        }

        $stmt =$this->db->prepare(
            'UPDATE arbre
             SET variete = :variete,
                 region = :region,
                 age_ans = :age,
                 prix_annuel = :prix
             WHERE id_arbre = :id'
        );

        \$stmt->execute([
            'variete' => \$variete,
            'region' => \$region,
            'age' => \$ageAns,
            'prix' => \$prixAnnuel,
            'id' => \$id
        ]);

        return [];
    }

    public function delete(int $id, array$post): ?string
    {
        if (self::csrfError(\$post)) {
            return 'Jeton de sécurité invalide.';
        }

        if ($this->find($id) === null) {
            return 'Olivier introuvable.';
        }

        try {
            $stmt =$this->db->prepare(
                'DELETE FROM arbre
