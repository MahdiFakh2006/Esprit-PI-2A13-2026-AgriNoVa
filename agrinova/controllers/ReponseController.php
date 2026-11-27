<?php
/**
 * controllers/ReponseController.php
 * MODULE 5 — Réponses aux réclamations (côté « N » de la jointure Reclamation 1 — N Reponse).
 * Seul l'administrateur répond : chaque action est protégée dans index.php (requireRole('admin'))
 * ET revérifiée ici.
 */
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/ReclamationController.php';
require_once __DIR__ . '/../models/ReponseModel.php';

class ReponseController
{
    public const MSG_MIN = 5;
    public const MSG_MAX = 2000;

    private const SELECT = "SELECT p.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_nom, ro.libelle AS auteur_role
                            FROM reponse p
                            JOIN utilisateur u ON u.id_utilisateur = p.id_auteur
                            JOIN role ro ON ro.id_role = u.id_role";

    private PDO $db;
    private ReclamationController $reclamations;

    public function __construct(ReclamationController $reclamations)
    {
        $this->db = Database::getConnexion();
        $this->reclamations = $reclamations;
    }

    private static function csrfMessage(array $post): ?string
    {
        return AuthController::verifyCsrfToken($post['csrf_token'] ?? null)
            ? null
            : 'Jeton de sécurité invalide. Rechargez la page et réessayez.';
    }

    /** Validation du message (miroir du JavaScript). */
    private static function validateMessage(array $post): array
    {
        $message = trim((string) ($post['message'] ?? ''));
        if ($e = Validator::required($message, 'Message')) {
            return ['message' => $e];
        }
        if (mb_strlen($message) < self::MSG_MIN || mb_strlen($message) > self::MSG_MAX) {
            return ['message' => 'Le message doit contenir entre ' . self::MSG_MIN . ' et ' . self::MSG_MAX . ' caractères.'];
        }
        return [];
    }

    // ==============================================================
    // LECTURE
    // ==============================================================

    /** Le fil de réponses d'une réclamation, de la plus ancienne à la plus récente. */
    public function listByReclamation(int $idReclamation): array
    {
        $stmt = $this->db->prepare(
            self::SELECT . ' WHERE p.id_reclamation = :id ORDER BY p.date_reponse ASC, p.id_reponse ASC'
        );
        $stmt->execute([':id' => $idReclamation]);
        return array_map([ReponseModel::class, 'fromArray'], $stmt->fetchAll());
    }

    public function find(int $id): ?ReponseModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE p.id_reponse = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? ReponseModel::fromArray($row) : null;
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION
    // ==============================================================

    /** L'admin répond. Si la réclamation était « ouverte », elle passe automatiquement « en cours ». */
    public function create(int $idReclamation, int $authorId, array $post): array
    {
        if ($msg = self::csrfMessage($post)) {
            return ['global' => $msg];
        }
        if (!AuthController::hasRole('admin')) {
            return ['global' => 'Seul l’administrateur peut répondre aux réclamations.'];
        }
        $rec = $this->reclamations->find($idReclamation);
        if ($rec === null) {
            return ['global' => 'Réclamation introuvable.'];
        }
        if ($rec->getStatut() === 'cloturee') {
            return ['global' => 'Cette réclamation est clôturée : on ne peut plus y répondre.'];
        }
        $errors = self::validateMessage($post);
        if ($errors) {
            return $errors;
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'INSERT INTO reponse (id_reclamation, id_auteur, message) VALUES (:rec, :auteur, :message)'
            );
            $stmt->execute([
                ':rec'     => $idReclamation,
                ':auteur'  => $authorId,
                ':message' => trim((string) $post['message']),
            ]);
            if ($rec->getStatut() === 'ouverte') {
                $up = $this->db->prepare("UPDATE reclamation SET statut = 'en_cours' WHERE id_reclamation = :id");
                $up->execute([':id' => $idReclamation]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erreur ajout réponse : ' . $e->getMessage());
            return ['global' => 'Une erreur technique est survenue. Réessayez.'];
        }
        return [];
    }

    public function update(int $id, array $post): array
    {
        if ($msg = self::csrfMessage($post)) {
            return ['global' => $msg];
        }
        if (!AuthController::hasRole('admin')) {
            return ['global' => 'Action réservée à l’administrateur.'];
        }
        if ($this->find($id) === null) {
            return ['global' => 'Réponse introuvable.'];
        }
        $errors = self::validateMessage($post);
        if ($errors) {
            return $errors;
        }
        $stmt = $this->db->prepare('UPDATE reponse SET message = :message WHERE id_reponse = :id');
        $stmt->execute([':message' => trim((string) $post['message']), ':id' => $id]);
        return [];
    }

    public function delete(int $id, array $post): ?string
    {
        if ($msg = self::csrfMessage($post)) {
            return $msg;
        }
        if (!AuthController::hasRole('admin')) {
            return 'Action réservée à l’administrateur.';
        }
        $stmt = $this->db->prepare('DELETE FROM reponse WHERE id_reponse = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 0 ? 'Réponse introuvable.' : null;
    }
}
