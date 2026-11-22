<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ArbreModel.php';

class ParrainageController
{
    private PDO $db;

    private const STATUTS = [
        'en_attente',
        'actif',
        'termine',
        'annule'
    ];

    public function __construct()
    {
        $this->db = Database::getConnexion();
    }

    private static function csrfError(array $post): ?array
    {
        return AuthController::verifyCsrfToken(
            $post['csrf_token'] ?? null
        ) ? null : [
            'global' => 'Jeton de sécurité invalide.'
        ];
    }

    public function create(array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }

        $idArbre = (int) ($post['id_arbre'] ?? 0);
        $dateDebut = (string) ($post['date_debut'] ?? '');
        $dureeMois = (int) ($post['duree_mois'] ?? 0);

        $errors = [];

        $stmtArbre = $this->db->prepare(
            'SELECT * FROM arbre
             WHERE id_arbre = :id'
        );

        $stmtArbre->execute(['id' => $idArbre]);
        $arbre = $stmtArbre->fetch();

        if (!$arbre) {
            return ['global' => 'Olivier introuvable.'];
        }

        if (($arbre['statut_validation'] ?? '') !== 'valide') {
            return ['global' => 'Cet olivier n’est pas encore validé.'];
        }

        if ((int) ($arbre['id_agriculteur'] ?? 0) === AuthController::currentUserId()) {
            return ['global' => 'Vous ne pouvez pas parrainer votre propre olivier.'];
        }

        $debut = \DateTime::createFromFormat('!Y-m-d', $dateDebut);

        if (!$debut || $debut->format('Y-m-d') !== $dateDebut) {
            return ['global' => 'Date invalide.'];
        }

        $today = new \DateTime('today');

        if ($debut < $today) {
            $errors['date_debut'] = 'La date de début doit être future ou actuelle.';
        }

        if ($dureeMois < 1 || $dureeMois > 36) {
            $errors['duree_mois'] = 'La durée doit être comprise entre 1 et 36 mois.';
        }

        if ($errors) {
            return $errors;
        }

        $check = $this->db->prepare(
            "SELECT COUNT(*) FROM parrainage
             WHERE id_arbre = :id
             AND statut = 'actif'"
        );

        $check->execute(['id' => $idArbre]);

        if ((int) $check->fetchColumn() > 0) {
            return ['global' => 'Cet olivier a déjà un parrainage actif.'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO parrainage
             (id_arbre, id_parrain, date_debut, duree_mois, statut)
             VALUES
             (:arbre, :parrain, :debut, :duree, :statut)'
        );

        $stmt->execute([
            'arbre' => $idArbre,
            'parrain' => AuthController::currentUserId(),
            'debut' => $dateDebut,
            'duree' => $dureeMois,
            'statut' => 'actif'
        ]);

        return [];
    }

    public function getParrainages(int $parrainId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, a.variete, a.region,
                    a.prix_annuel, u.nom AS nom_agriculteur
             FROM parrainage p
             JOIN arbre a
                 ON p.id_arbre = a.id_arbre
             JOIN utilisateur u
                 ON a.id_agriculteur = u.id_utilisateur
             WHERE p.id_parrain = :id
             ORDER BY p.id_parrainage DESC'
        );

        $stmt->execute(['id' => $parrainId]);

        return $stmt->fetchAll();
    }

    public function getFarmerParrainages(int $agriculteurId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, a.variete, a.region,
                    u.nom AS nom_parrain
             FROM parrainage p
             JOIN arbre a
                 ON p.id_arbre = a.id_arbre
             JOIN utilisateur u
                 ON p.id_parrain = u.id_utilisateur
             WHERE a.id_agriculteur = :agriculteur
             ORDER BY p.id_parrainage DESC'
        );

        $stmt->execute(['agriculteur' => $agriculteurId]);

        return $stmt->fetchAll();
    }

    public function updateStatus(
        int $id,
        string $statut,
        array $post
    ): ?string {

        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }

        if (!in_array($statut, self::STATUTS, true)) {
            return 'Statut invalide.';
        }

        $stmt = $this->db->prepare(
            'SELECT p.id_parrainage, p.statut,
                    a.id_agriculteur
             FROM parrainage p
             JOIN arbre a
                 ON p.id_arbre = a.id_arbre
             WHERE p.id_parrainage = :id'
        );

        $stmt->execute(['id' => $id]);

        $parrainage = $stmt->fetch();

        if (!$parrainage) {
            return 'Parrainage introuvable.';
        }

        if (
            (int) $parrainage['id_agriculteur']
            !== AuthController::currentUserId()
        ) {
            return 'Vous ne pouvez pas modifier ce parrainage.';
        }

        if ($parrainage['statut'] !== 'en_attente') {
            return 'Ce parrainage a déjà été traité.';
        }

        if (!in_array($statut, ['actif', 'annule'], true)) {
            return 'Action non autorisée.';
        }

        $update = $this->db->prepare(
            'UPDATE parrainage
             SET statut = :statut
             WHERE id_parrainage = :id'
        );

        $update->execute([
            'statut' => $statut,
            'id' => $id
        ]);

        return null;
    }
}
