<?php
/**
 * controllers/ProduitController.php  —  MODULE 2 : Marketplace
 * Catalogue (Front Office), gestion de ses produits (vendeur), CRUD complet + statistiques (admin).
 * Toute la logique est ici : SQL préparé, validation, upload d'image, CSRF, propriété des produits.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/ProduitModel.php';

class ProduitController
{
    public const PER_PAGE_CATALOGUE = 9;
    public const PER_PAGE_LISTE = 8;
    public const UPLOAD_DIR = __DIR__ . '/../uploads/produits';
    public const UPLOAD_URL = 'uploads/produits/';
    public const MAX_IMAGE_BYTES = 2097152; // 2 Mo

    /** Jointure Produit N — 1 Categorie et Produit N — 1 Utilisateur (vendeur). */
    private const SELECT = 'SELECT p.*, c.nom AS categorie_nom, CONCAT(u.prenom, \' \', u.nom) AS vendeur_nom
                            FROM produit p
                            JOIN categorie c ON p.id_categorie = c.id_categorie
                            JOIN utilisateur u ON p.id_vendeur = u.id_utilisateur';

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
    // LECTURE (READ) : recherche, filtres, tri et pagination
    // Utilisée par le catalogue, « Mes produits » ($vendeurId) et le Back Office.
    // ==============================================================

    public function search(array $query, ?int $vendeurId = null, int $perPage = self::PER_PAGE_LISTE): array
    {
        $q         = trim((string) ($query['q'] ?? ''));
        $categorie = (int) ($query['categorie'] ?? 0);
        $stockF    = (string) ($query['stock'] ?? '');
        $sort      = (string) ($query['sort'] ?? 'recent');
        $page      = max(1, (int) ($query['page'] ?? 1));
        $prixMin   = self::parsePrice((string) ($query['prix_min'] ?? ''));
        $prixMax   = self::parsePrice((string) ($query['prix_max'] ?? ''));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.nom LIKE :q1 OR p.description LIKE :q2)';
            $params['q1'] = $params['q2'] = '%' . $q . '%';
        }
        if ($categorie > 0) {
            $where[] = 'p.id_categorie = :cat';
            $params['cat'] = $categorie;
        } else {
            $categorie = 0;
        }
        if ($prixMin !== null) {
            $where[] = 'p.prix >= :pmin';
            $params['pmin'] = $prixMin;
        }
        if ($prixMax !== null) {
            $where[] = 'p.prix <= :pmax';
            $params['pmax'] = $prixMax;
        }
        if ($stockF === 'rupture') {
            $where[] = 'p.stock = 0';
        } elseif ($stockF === 'dispo') {
            $where[] = 'p.stock > 0';
        } else {
            $stockF = '';
        }
        if ($vendeurId !== null) {
            $where[] = 'p.id_vendeur = :vendeur';
            $params['vendeur'] = $vendeurId;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Le tri vient d'une liste blanche : jamais de colonne saisie par l'utilisateur dans le SQL
        $orders = [
            'recent'    => 'p.id_produit DESC',
            'prix_asc'  => 'p.prix ASC, p.id_produit ASC',
            'prix_desc' => 'p.prix DESC, p.id_produit ASC',
            'nom'       => 'p.nom ASC',
            'stock'     => 'p.stock ASC, p.nom ASC',
        ];
        $sort = array_key_exists($sort, $orders) ? $sort : 'recent';

        $count = $this->db->prepare('SELECT COUNT(*) FROM produit p' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min($page, $pages);

        $stmt = $this->db->prepare(
            self::SELECT . $whereSql . ' ORDER BY ' . $orders[$sort]
            . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
        );
        $stmt->execute($params);
        $produits = array_map([ProduitModel::class, 'fromArray'], $stmt->fetchAll());

        return [
            'produits' => $produits, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'q' => $q, 'categorie' => $categorie, 'stock' => $stockF, 'sort' => $sort,
            'prix_min' => $prixMin !== null ? (string) $prixMin : '', 'prix_max' => $prixMax !== null ? (string) $prixMax : '',
        ];
    }

    public function find(int $id): ?ProduitModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE p.id_produit = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ProduitModel::fromArray($row) : null;
    }

    /**
     * Produit à modifier : un vendeur ne peut accéder qu'à SES produits ($ownerId),
     * l'administrateur ($ownerId = null) à tous.
     */
    public function findForEdit(int $id, ?int $ownerId): ?ProduitModel
    {
        $p = $this->find($id);
        if ($p === null || ($ownerId !== null && $p->getIdVendeur() !== $ownerId)) {
            return null;
        }
        return $p;
    }

    /** @return ProduitModel[] pour la comparaison et les listes déroulantes (200 maximum) */
    public function listForSelect(): array
    {
        $rows = $this->db->query(self::SELECT . ' ORDER BY p.nom ASC LIMIT 200')->fetchAll();
        return array_map([ProduitModel::class, 'fromArray'], $rows);
    }

    /** @return array<int, string> id => « Prénom Nom » des vendeurs actifs (liste du formulaire admin) */
    public function getVendeurs(): array
    {
        $rows = $this->db->query(
            "SELECT u.id_utilisateur, CONCAT(u.prenom, ' ', u.nom) AS nom
             FROM utilisateur u JOIN role r ON u.id_role = r.id_role
             WHERE r.libelle = 'vendeur' AND u.statut = 'actif' ORDER BY u.nom, u.prenom"
        )->fetchAll();
        $list = [];
        foreach ($rows as $row) {
            $list[(int) $row['id_utilisateur']] = (string) $row['nom'];
        }
        return $list;
    }

    // ==============================================================
    // STATISTIQUES du Back Office : produits par catégorie, ruptures de stock
    // ==============================================================

    public function stats(): array
    {
        $parCategorie = $this->db->query(
            'SELECT c.nom, COUNT(p.id_produit) AS nb, COALESCE(SUM(p.stock), 0) AS stock_total
             FROM categorie c LEFT JOIN produit p ON p.id_categorie = c.id_categorie
             GROUP BY c.id_categorie, c.nom ORDER BY nb DESC, c.nom ASC'
        )->fetchAll();
        $ruptures = $this->db->query(
            self::SELECT . ' WHERE p.stock = 0 ORDER BY p.nom ASC LIMIT 10'
        )->fetchAll();
        $totaux = $this->db->query(
            'SELECT COUNT(*) AS nb, COALESCE(SUM(stock = 0), 0) AS ruptures, COALESCE(SUM(prix * stock), 0) AS valeur FROM produit'
        )->fetch();

        return [
            'parCategorie' => $parCategorie,
            'ruptures'     => array_map([ProduitModel::class, 'fromArray'], $ruptures),
            'total'        => (int) $totaux['nb'],
            'nbRuptures'   => (int) $totaux['ruptures'],
            'valeurStock'  => (float) $totaux['valeur'],
        ];
    }

    // ==============================================================
    // VALIDATION (mêmes règles que le JavaScript)
    // ==============================================================

    /** « 1 250,50 » ou « 1250.5 » → float ; null si vide ou invalide. */
    private static function parsePrice(string $raw): ?float
    {
        $raw = str_replace([' ', ','], ['', '.'], trim($raw));
        return preg_match('/^\d{1,8}(\.\d{1,2})?$/', $raw) ? (float) $raw : null;
    }

    private function categorieExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT id_categorie FROM categorie WHERE id_categorie = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch();
    }

    private function vendeurExists(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_utilisateur FROM utilisateur u JOIN role r ON u.id_role = r.id_role
             WHERE u.id_utilisateur = :id AND r.libelle = 'vendeur' LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch();
    }

    /** Vérifie le fichier image (JPG ou PNG, 2 Mo maximum). Renvoie [message|null, extension|null]. */
    private static function checkImage(array $files): array
    {
        $file = $files['image'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null]; // pas d'image envoyée : facultatif
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['L’envoi de l’image a échoué (fichier trop volumineux ou interrompu).', null];
        }
        if ($file['size'] > self::MAX_IMAGE_BYTES) {
            return ['L’image ne doit pas dépasser 2 Mo.', null];
        }
        // On vérifie le VRAI contenu du fichier (pas son nom ni le type envoyé par le navigateur)
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
        if ($ext === null || @getimagesize($file['tmp_name']) === false) {
            return ['L’image doit être au format JPG ou PNG.', null];
        }
        return [null, $ext];
    }

    /** Enregistre l'image sous un nom aléatoire et renvoie ce nom (jamais le nom d'origine). */
    private static function storeImage(array $files, string $ext): ?string
    {
        if (!is_dir(self::UPLOAD_DIR) && !mkdir(self::UPLOAD_DIR, 0755, true) && !is_dir(self::UPLOAD_DIR)) {
            return null;
        }
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        return move_uploaded_file($files['image']['tmp_name'], self::UPLOAD_DIR . '/' . $name) ? $name : null;
    }

    private static function deleteImageFile(string $name): void
    {
        // basename() : on ne supprime jamais un fichier en dehors du dossier des images
        if ($name !== '' && is_file(self::UPLOAD_DIR . '/' . basename($name))) {
            @unlink(self::UPLOAD_DIR . '/' . basename($name));
        }
    }

    /**
     * Valide le formulaire produit.
     * @return array [erreurs par champ, données nettoyées, extension de l'image|null]
     */
    private function validate(array $post, array $files, ?int $forcedVendeur): array
    {
        $nom   = trim((string) ($post['nom'] ?? ''));
        $desc  = trim((string) ($post['description'] ?? ''));
        $prix  = self::parsePrice((string) ($post['prix'] ?? ''));
        $stockRaw = trim((string) ($post['stock'] ?? ''));
        $cat   = (int) ($post['id_categorie'] ?? 0);
        $errors = [];

        if ($nom === '') {
            $errors['nom'] = 'Le nom du produit est obligatoire.';
        } elseif (textLength($nom) < 2 || textLength($nom) > 120) {
            $errors['nom'] = 'Le nom doit contenir entre 2 et 120 caractères.';
        }
        if (textLength($desc) > 2000) {
            $errors['description'] = 'La description ne doit pas dépasser 2000 caractères.';
        }
        if ($prix === null || $prix <= 0) {
            $errors['prix'] = 'Le prix doit être un nombre strictement positif (2 décimales maximum).';
        }
        if (!preg_match('/^\d{1,6}$/', $stockRaw)) {
            $errors['stock'] = 'Le stock doit être un entier supérieur ou égal à 0.';
        }
        if ($cat <= 0 || !$this->categorieExists($cat)) {
            $errors['id_categorie'] = 'Veuillez choisir une catégorie valide.';
        }

        // Vendeur : imposé (compte connecté) pour un vendeur, choisi dans la liste pour l'admin
        $vendeur = $forcedVendeur ?? (int) ($post['id_vendeur'] ?? 0);
        if ($forcedVendeur === null && ($vendeur <= 0 || !$this->vendeurExists($vendeur))) {
            $errors['id_vendeur'] = 'Veuillez choisir un vendeur valide.';
        }

        [$imageError, $ext] = self::checkImage($files);
        if ($imageError !== null) {
            $errors['image'] = $imageError;
        }

        $data = [
            'nom' => $nom, 'description' => $desc, 'prix' => $prix ?? 0.0,
            'stock' => (int) $stockRaw, 'id_categorie' => $cat, 'id_vendeur' => $vendeur,
        ];
        return [$errors, $data, $ext];
    }

    // ==============================================================
    // CRÉATION (CREATE)
    // $forcedVendeur : id du vendeur connecté (Front Office) ou null (admin : vendeur choisi dans le formulaire)
    // ==============================================================

    public function create(array $post, array $files, ?int $forcedVendeur): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        [$errors, $data, $ext] = $this->validate($post, $files, $forcedVendeur);
        if ($errors) {
            return $errors;
        }
        $image = '';
        if ($ext !== null) {
            $image = self::storeImage($files, $ext);
            if ($image === null) {
                return ['image' => 'Impossible d’enregistrer l’image sur le serveur.'];
            }
        }
        $data['image'] = $image === '' ? null : $image;
        $this->db->prepare(
            'INSERT INTO produit (nom, description, prix, stock, image, id_categorie, id_vendeur)
             VALUES (:nom, :description, :prix, :stock, :image, :id_categorie, :id_vendeur)'
        )->execute($data);
        return [];
    }

    // ==============================================================
    // MODIFICATION (UPDATE) — un vendeur ne modifie que ses propres produits
    // ==============================================================

    public function update(int $id, array $post, array $files, ?int $ownerId): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }
        $existing = $this->findForEdit($id, $ownerId);
        if ($existing === null) {
            return ['global' => 'Produit introuvable ou non autorisé.'];
        }
        // Un vendeur garde son identité ; l'admin peut réaffecter le produit à un autre vendeur
        [$errors, $data, $ext] = $this->validate($post, $files, $ownerId);
        if ($errors) {
            return $errors;
        }

        $image = $existing->getImage();
        $removeOld = false;
        if ($ext !== null) {
            $new = self::storeImage($files, $ext);
            if ($new === null) {
                return ['image' => 'Impossible d’enregistrer l’image sur le serveur.'];
            }
            $removeOld = $image !== '';
            $oldName = $image;
            $image = $new;
        } elseif (!empty($post['supprimer_image'])) {
            $removeOld = $image !== '';
            $oldName = $image;
            $image = '';
        }

        $data['image'] = $image === '' ? null : $image;
        $data['id'] = $id;
        $this->db->prepare(
            'UPDATE produit SET nom = :nom, description = :description, prix = :prix, stock = :stock,
             image = :image, id_categorie = :id_categorie, id_vendeur = :id_vendeur WHERE id_produit = :id'
        )->execute($data);

        if ($removeOld) {
            self::deleteImageFile($oldName);
        }
        return [];
    }

    // ==============================================================
    // SUPPRESSION (DELETE) — l'historique des commandes est conservé (ON DELETE SET NULL)
    // ==============================================================

    public function delete(int $id, array $post, ?int $ownerId): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }
        $p = $this->findForEdit($id, $ownerId);
        if ($p === null) {
            return 'Produit introuvable ou non autorisé.';
        }
        $this->db->prepare('DELETE FROM produit WHERE id_produit = :id')->execute(['id' => $id]);
        self::deleteImageFile($p->getImage());
        return null;
    }
}
