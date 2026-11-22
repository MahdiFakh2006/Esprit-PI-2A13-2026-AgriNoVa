<?php
/**
 * controllers/CommandeController.php  —  MODULE 2 : Marketplace
 * Panier (stocké en session) et commandes de l'agriculteur ; suivi des commandes pour l'admin.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/ProduitModel.php';
require_once __DIR__ . '/../models/CommandeModel.php';

class CommandeController
{
    public const PER_PAGE = 8;
    public const MAX_QTY = 99;
    public const STATUTS = ['en_attente', 'confirmee', 'annulee'];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    private static function csrfOk(array $post): bool
    {
        return AuthController::verifyCsrfToken($post['csrf_token'] ?? null);
    }

    // ==============================================================
    // PANIER (session) : [id_produit => quantité]
    // ==============================================================

    /** Nombre d'articles dans le panier (affiché dans le menu). */
    public static function cartCount(): int
    {
        return array_sum($_SESSION['panier'] ?? []);
    }

    private function stockOf(int $idProduit): ?int
    {
        $stmt = $this->db->prepare('SELECT stock FROM produit WHERE id_produit = :id');
        $stmt->execute(['id' => $idProduit]);
        $stock = $stmt->fetchColumn();
        return $stock === false ? null : (int) $stock;
    }

    /** Ajoute un produit au panier. Renvoie un message d'erreur ou null. */
    public function addToCart(array $post): ?string
    {
        if (!self::csrfOk($post)) {
            return 'Jeton de sécurité invalide.';
        }
        $id  = (int) ($post['id'] ?? 0);
        $qty = (int) ($post['quantite'] ?? 1);
        $stock = $this->stockOf($id);
        if ($stock === null) {
            return 'Produit introuvable.';
        }
        if ($stock <= 0) {
            return 'Ce produit est en rupture de stock.';
        }
        if ($qty < 1 || $qty > self::MAX_QTY) {
            return 'La quantité doit être comprise entre 1 et ' . self::MAX_QTY . '.';
        }
        $new = ($_SESSION['panier'][$id] ?? 0) + $qty;
        if ($new > $stock) {
            return 'Stock insuffisant : il reste seulement ' . $stock . ' exemplaire(s).';
        }
        $_SESSION['panier'][$id] = $new;
        return null;
    }

    /** Modifie la quantité d'une ligne (0 = retirer). */
    public function updateCart(array $post): ?string
    {
        if (!self::csrfOk($post)) {
            return 'Jeton de sécurité invalide.';
        }
        $id  = (int) ($post['id'] ?? 0);
        $qty = (int) ($post['quantite'] ?? 0);
        if (!isset($_SESSION['panier'][$id])) {
            return 'Ce produit n’est pas dans votre panier.';
        }
        if ($qty <= 0) {
            unset($_SESSION['panier'][$id]);
            return null;
        }
        $stock = $this->stockOf($id);
        if ($stock === null || $qty > min($stock, self::MAX_QTY)) {
            return 'Quantité indisponible (stock : ' . (int) $stock . ').';
        }
        $_SESSION['panier'][$id] = $qty;
        return null;
    }

    public function removeFromCart(array $post): ?string
    {
        if (!self::csrfOk($post)) {
            return 'Jeton de sécurité invalide.';
        }
        unset($_SESSION['panier'][(int) ($post['id'] ?? 0)]);
        return null;
    }

    /**
     * Contenu détaillé du panier. Les produits supprimés sont retirés automatiquement.
     * @return array{lignes: array, total: float}
     */
    public function cartDetails(): array
    {
        $cart = $_SESSION['panier'] ?? [];
        if (!$cart) {
            return ['lignes' => [], 'total' => 0.0];
        }
        $ids = array_map('intval', array_keys($cart));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            'SELECT p.*, c.nom AS categorie_nom, \'\' AS vendeur_nom
             FROM produit p JOIN categorie c ON p.id_categorie = c.id_categorie
             WHERE p.id_produit IN (' . $in . ')'
        );
        $stmt->execute($ids);
        $lignes = [];
        $total = 0.0;
        $found = [];
        foreach ($stmt->fetchAll() as $row) {
            $p = ProduitModel::fromArray($row);
            $qty = (int) $cart[$p->getId()];
            $lignes[] = ['produit' => $p, 'quantite' => $qty, 'sous_total' => $p->getPrix() * $qty];
            $total += $p->getPrix() * $qty;
            $found[$p->getId()] = $qty;
        }
        $_SESSION['panier'] = $found; // supprime les produits qui n'existent plus
        return ['lignes' => $lignes, 'total' => $total];
    }

    // ==============================================================
    // COMMANDE : valide le panier (transaction : tout ou rien)
    // ==============================================================

    /** @return array [message d'erreur|null, id de la commande|null] */
    public function placeOrder(array $post, int $idAgriculteur): array
    {
        if (!self::csrfOk($post)) {
            return ['Jeton de sécurité invalide.', null];
        }
        $cart = $_SESSION['panier'] ?? [];
        if (!$cart) {
            return ['Votre panier est vide.', null];
        }

        $this->db->beginTransaction();
        try {
            $lignes = [];
            $total = 0.0;
            foreach ($cart as $id => $qty) {
                // FOR UPDATE : verrouille la ligne pour éviter que deux clients achètent le dernier exemplaire
                $stmt = $this->db->prepare('SELECT id_produit, nom, prix, stock FROM produit WHERE id_produit = :id FOR UPDATE');
                $stmt->execute(['id' => (int) $id]);
                $p = $stmt->fetch();
                if (!$p) {
                    $this->db->rollBack();
                    return ['Un produit de votre panier n’existe plus. Vérifiez votre panier.', null];
                }
                if ((int) $p['stock'] < (int) $qty) {
                    $this->db->rollBack();
                    return ['Stock insuffisant pour « ' . $p['nom'] . ' » (disponible : ' . (int) $p['stock'] . ').', null];
                }
                $lignes[] = [$p, (int) $qty];
                $total += (float) $p['prix'] * (int) $qty;
            }

            $this->db->prepare('INSERT INTO commande (id_agriculteur, total) VALUES (:a, :t)')
                ->execute(['a' => $idAgriculteur, 't' => $total]);
            $idCommande = (int) $this->db->lastInsertId();

            $insLigne = $this->db->prepare(
                'INSERT INTO ligne_commande (id_commande, id_produit, nom_produit, prix_unitaire, quantite)
                 VALUES (:c, :p, :n, :prix, :q)'
            );
            $updStock = $this->db->prepare('UPDATE produit SET stock = stock - :q WHERE id_produit = :p');
            foreach ($lignes as [$p, $qty]) {
                $insLigne->execute(['c' => $idCommande, 'p' => $p['id_produit'], 'n' => $p['nom'], 'prix' => $p['prix'], 'q' => $qty]);
                $updStock->execute(['q' => $qty, 'p' => $p['id_produit']]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erreur commande : ' . $e->getMessage());
            return ['Une erreur est survenue, la commande n’a pas été enregistrée.', null];
        }
        unset($_SESSION['panier']);
        return [null, $idCommande];
    }

    // ==============================================================
    // LECTURE des commandes
    // ==============================================================

    private function loadLines(array $commandes): array
    {
        if (!$commandes) {
            return [];
        }
        $ids = array_map(fn(CommandeModel $c) => $c->getId(), $commandes);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare('SELECT * FROM ligne_commande WHERE id_commande IN (' . $in . ') ORDER BY id_ligne');
        $stmt->execute($ids);
        $byOrder = [];
        foreach ($stmt->fetchAll() as $l) {
            $byOrder[(int) $l['id_commande']][] = $l;
        }
        foreach ($commandes as $c) {
            $c->setLignes($byOrder[$c->getId()] ?? []);
        }
        return $commandes;
    }

    /** @return CommandeModel[] commandes de l'agriculteur connecté */
    public function myOrders(int $idAgriculteur): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS agriculteur_nom
             FROM commande c JOIN utilisateur u ON c.id_agriculteur = u.id_utilisateur
             WHERE c.id_agriculteur = :id ORDER BY c.date_commande DESC, c.id_commande DESC"
        );
        $stmt->execute(['id' => $idAgriculteur]);
        return $this->loadLines(array_map([CommandeModel::class, 'fromArray'], $stmt->fetchAll()));
    }

    /** Liste paginée pour le Back Office (filtre par statut). */
    public function search(array $query): array
    {
        $statut = (string) ($query['statut'] ?? '');
        $page   = max(1, (int) ($query['page'] ?? 1));
        $where = '';
        $params = [];
        if (in_array($statut, self::STATUTS, true)) {
            $where = ' WHERE c.statut = :statut';
            $params['statut'] = $statut;
        } else {
            $statut = '';
        }
        $count = $this->db->prepare('SELECT COUNT(*) FROM commande c' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $stmt = $this->db->prepare(
            "SELECT c.*, CONCAT(u.prenom, ' ', u.nom) AS agriculteur_nom
             FROM commande c JOIN utilisateur u ON c.id_agriculteur = u.id_utilisateur"
            . $where . ' ORDER BY c.date_commande DESC, c.id_commande DESC LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
        );
        $stmt->execute($params);
        $commandes = $this->loadLines(array_map([CommandeModel::class, 'fromArray'], $stmt->fetchAll()));
        return compact('commandes', 'total', 'page', 'pages', 'statut');
    }

    // ==============================================================
    // CHANGEMENT DE STATUT : confirmer / annuler (l'annulation remet le stock)
    // $ownerId : l'agriculteur ne peut annuler que ses propres commandes (admin : null)
    // ==============================================================

    public function changeStatus(int $id, string $newStatus, array $post, ?int $ownerId): ?string
    {
        if (!self::csrfOk($post)) {
            return 'Jeton de sécurité invalide.';
        }
        if (!in_array($newStatus, ['confirmee', 'annulee'], true)) {
            return 'Statut non autorisé.';
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM commande WHERE id_commande = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $c = $stmt->fetch();
            if (!$c || ($ownerId !== null && (int) $c['id_agriculteur'] !== $ownerId)) {
                $this->db->rollBack();
                return 'Commande introuvable.';
            }
            if ($c['statut'] !== 'en_attente') {
                $this->db->rollBack();
                return 'Seule une commande en attente peut changer de statut.';
            }
            if ($newStatus === 'annulee') {
                $this->db->prepare(
                    'UPDATE produit p JOIN ligne_commande l ON l.id_produit = p.id_produit
                     SET p.stock = p.stock + l.quantite WHERE l.id_commande = :id'
                )->execute(['id' => $id]);
            }
            $this->db->prepare('UPDATE commande SET statut = :s WHERE id_commande = :id')
                ->execute(['s' => $newStatus, 'id' => $id]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erreur statut commande : ' . $e->getMessage());
            return 'Une erreur est survenue.';
        }
        return null;
    }
}
