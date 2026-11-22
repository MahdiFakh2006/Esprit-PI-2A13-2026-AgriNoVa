<?php
/**
 * controllers/CategorieController.php  —  MODULE 2 : Marketplace
 * CRUD des catégories de produits (Back Office, rôle admin).
 * Toute la logique est ici : SQL préparé, validation, CSRF. Le modèle et les vues n'en contiennent aucune.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/CategorieModel.php';

class CategorieController
{
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
    // LECTURE (READ) : liste paginée avec recherche + nombre de produits (jointure)
    // ==============================================================

    public function search(array $query): array
    {
        $q    = trim((string) ($query['q'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = ' WHERE c.nom LIKE :q1 OR c.description LIKE :q2';
            $params = ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%'];
        }

        $count = $this->db->prepare('SELECT COUNT(*) FROM categorie c' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        // Jointure Categorie 1 — N Produit : nombre de produits par catégorie
        $stmt = $this->db->prepare(
            'SELECT c.*, COUNT(p.id_produit) AS nb_produits
             FROM categorie c
             LEFT JOIN produit p ON p.id_categorie = c.id_categorie'
            . $where . ' GROUP BY c.id_categorie ORDER BY c.nom ASC
             LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $categories = array_map([CategorieModel::class, 'fromArray'], $stmt->fetchAll());

        return compact('categories', 'total', 'page', 'pages', 'q');
    }

    /** @return CategorieModel[] toutes les catégories (pour les listes déroulantes et les filtres) */
    public function all(): array
    {
        $rows = $this->db->query('SELECT * FROM categorie ORDER BY nom ASC')->fetchAll();
        return array_map([CategorieModel::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?CategorieModel
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, (SELECT COUNT(*) FROM produit p WHERE p.id_categorie = c.id_categorie) AS nb_produits
             FROM categorie c WHERE c.id_categorie = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? CategorieModel::fromArray($row) : null;
    }

    // ==============================================================
    // VALIDATION (mêmes règles que le JavaScript : nom 2 à 80, description 255 max)
    // ==============================================================

    private function validate(array $post, int $exceptId = 0): array
    {
        $nom  = trim((string) ($post['nom'] ?? ''));
        $desc = trim((string) ($post['description'] ?? ''));
        $errors = [];

        if ($nom === '') {
            $errors['nom'] = 'Le nom de la catégorie est obligatoire.';
        } elseif (textLength($nom) < 2 || textLength($nom) > 80) {
            $errors['nom'] = 'Le nom doit contenir entre 2 et 80 caractères.';
        } else {
            $stmt = $this->db->prepare('SELECT id_categorie FROM categorie WHERE nom = :nom AND id_categorie <> :id LIMIT 1');
            $stmt->execute(['nom' => $nom, 'id' => $exceptId]);
            if ($stmt->fetch()) {
                $errors['nom'] = 'Une catégorie portant ce nom existe déjà.';
            }
        }
        if (textLength($desc) > 255) {
            $errors['description'] = 'La description ne doit pas dépasser 255 caractères.';
        }
        return [$errors, ['nom' => $nom, 'description' => $desc]];
    }

    // ==============================================================
    // CRÉATION (CREATE)
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
        $this->db->prepare('INSERT INTO categorie (nom, description) VALUES (:nom, :description)')->execute($data);
        return [];
    }

    // ==============================================================
    // MODIFICATION (UPDATE)
    // ==============================================================

    public function update(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        if ($this->find($id) === null) {
            return ['global' => 'Catégorie introuvable.'];
        }
        [$errors, $data] = $this->validate($post, $id);
        if ($errors) {
            return $errors;
        }
        $data['id'] = $id;
        $this->db->prepare('UPDATE categorie SET nom = :nom, description = :description WHERE id_categorie = :id')->execute($data);
        return [];
    }

    // ==============================================================
    // SUPPRESSION (DELETE) — refusée si la catégorie contient encore des produits
    // ==============================================================

    public function delete(int $id, array $post): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }
        $categorie = $this->find($id);
        if ($categorie === null) {
            return 'Catégorie introuvable.';
        }
        if ($categorie->getNbProduits() > 0) {
            return 'Suppression impossible : cette catégorie contient ' . $categorie->getNbProduits()
                . ' produit(s). Déplacez ou supprimez-les d’abord.';
        }
        try {
            $this->db->prepare('DELETE FROM categorie WHERE id_categorie = :id')->execute(['id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'Suppression impossible : cette catégorie est encore utilisée.';
            }
            throw $e;
        }
        return null;
    }
}
