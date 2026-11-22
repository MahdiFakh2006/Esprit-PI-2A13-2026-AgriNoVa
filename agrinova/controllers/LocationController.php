
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/EquipementModel.php';

class LocationController
{
    private PDO $db;

    private const PER_PAGE = 8;

    private const ETATS = [
        'disponible',
        'maintenance',
        'hors_service'
    ];

    private const STATUTS = [
        'en_attente',
        'acceptee',
        'refusee',
        'annulee',
        'terminee'
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

    public function search(array $query): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        $type = trim((string) ($query['type'] ?? ''));
        $etat = (string) ($query['etat'] ?? '');

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = 'nom LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($type !== '') {
            $where[] = 'type = :type';
            $params['type'] = $type;
        }

        if (in_array($etat, self::ETATS, true)) {
            $where[] = 'etat = :etat';
            $params['etat'] = $etat;
        } else {
            $etat = '';
        }

        $whereSql = $where
            ? ' WHERE ' . implode(' AND ', $where)
            : '';

        $page = max(1, (int) ($query['page'] ?? 1));

        $count = $this->db->prepare(
            'SELECT COUNT(*) FROM equipement' . $whereSql
        );

        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $stmt = $this->db->prepare(
            'SELECT * FROM equipement'
            . $whereSql
            . ' ORDER BY id_equipement DESC'
            . ' LIMIT ' . self::PER_PAGE
            . ' OFFSET ' . (($page - 1) * self::PER_PAGE)
        );

        $stmt->execute($params);

        $equipements = array_map(
            [EquipementModel::class, 'fromArray'],
            $stmt->fetchAll()
        );

        return compact(
            'equipements',
            'total',
            'page',
            'pages',
            'q',
            'type',
            'etat'
        );
    }

   
    public function find(int $id): ?EquipementModel
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM equipement
             WHERE id_equipement = :id'
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row
            ? EquipementModel::fromArray($row)
            : null;
    }

  
    public function create(array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }

        $nom = trim((string) ($post['nom'] ?? ''));
        $type = trim((string) ($post['type'] ?? ''));
        $tarif = (float) ($post['tarif_jour'] ?? 0);
        $caution = (float) ($post['caution'] ?? 0);
        $etat = (string) ($post['etat'] ?? 'disponible');
        $idVendeur = AuthController::currentUserId();

        $errors = [];

        if ($nom === '' || strlen($nom) > 120) {
            $errors['nom'] = 'Nom obligatoire (120 caractères maximum).';
        }

        if ($type === '' || strlen($type) > 60) {
            $errors['type'] = 'Type obligatoire (60 caractères maximum).';
        }

        if ($tarif <= 0) {
            $errors['tarif_jour'] = 'Le tarif doit être supérieur à zéro.';
        }

        if ($caution < 0) {
            $errors['caution'] = 'La caution ne peut pas être négative.';
        }

        if (!in_array($etat, self::ETATS, true)) {
            $errors['etat'] = 'État invalide.';
        }

        if ($errors) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO equipement
            (nom, type, tarif_jour, caution, etat, id_vendeur)
            VALUES
            (:nom, :type, :tarif, :caution, :etat, :vendeur)'
        );

        $stmt->execute([
            'nom' => $nom,
            'type' => $type,
            'tarif' => $tarif,
            'caution' => $caution,
            'etat' => $etat,
            'vendeur' => $idVendeur
        ]);

        return [];
    }

    
    public function update(int $id, array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }

        $equipement = $this->find($id);

        if ($equipement === null) {
            return ['global' => 'Équipement introuvable.'];
        }

        $nom = trim((string) ($post['nom'] ?? ''));
        $type = trim((string) ($post['type'] ?? ''));
        $tarif = (float) ($post['tarif_jour'] ?? 0);
        $caution = (float) ($post['caution'] ?? 0);
        $etat = (string) ($post['etat'] ?? '');

        $errors = [];

        if ($nom === '' || strlen($nom) > 120) {
            $errors['nom'] = 'Nom invalide.';
        }

        if ($type === '' || strlen($type) > 60) {
            $errors['type'] = 'Type invalide.';
        }

        if ($tarif <= 0) {
            $errors['tarif_jour'] = 'Tarif invalide.';
        }

        if ($caution < 0) {
            $errors['caution'] = 'Caution invalide.';
        }

        if (!in_array($etat, self::ETATS, true)) {
            $errors['etat'] = 'État invalide.';
        }

        if ($errors) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            'UPDATE equipement
             SET nom = :nom,
                 type = :type,
                 tarif_jour = :tarif,
                 caution = :caution,
                 etat = :etat
             WHERE id_equipement = :id'
        );

        $stmt->execute([
            'nom' => $nom,
            'type' => $type,
            'tarif' => $tarif,
            'caution' => $caution,
            'etat' => $etat,
            'id' => $id
        ]);

        return [];
    }

  
    public function delete(int $id, array $post): ?string
    {
        if (self::csrfError($post)) {
            return 'Jeton de sécurité invalide.';
        }

        if ($this->find($id) === null) {
            return 'Équipement introuvable.';
        }

        try {
            $stmt = $this->db->prepare(
                'DELETE FROM equipement
                 WHERE id_equipement = :id'
            );

            $stmt->execute(['id' => $id]);

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'Impossible de supprimer cet équipement.';
            }

            throw $e;
        }

        return null;
    }

    public function createReservation(array $post): array
    {
        if ($e = self::csrfError($post)) {
            return $e;
        }

        $idEquipement = (int) ($post['id_equipement'] ?? 0);
        $dateDebut = (string) ($post['date_debut'] ?? '');
        $dateFin = (string) ($post['date_fin'] ?? '');

        $errors = [];

        $equipement = $this->find($idEquipement);

        if ($equipement === null) {
            return ['global' => 'Équipement introuvable.'];
        }

        if (!$equipement->isDisponible()) {
            return ['global' => 'Cet équipement n’est pas disponible.'];
        }

        if ($equipement->getIdVendeur() === AuthController::currentUserId()) {
            return ['global' => 'Vous ne pouvez pas réserver votre propre équipement.'];
        }

        $debut = \DateTime::createFromFormat('!Y-m-d', $dateDebut);
        $fin = \DateTime::createFromFormat('!Y-m-d', $dateFin);

        if (
            !$debut || $debut->format('Y-m-d') !== $dateDebut ||
            !$fin || $fin->format('Y-m-d') !== $dateFin
        ) {
            return ['global' => 'Dates invalides.'];
        }

        $today = new \DateTime('today');

        if ($debut < $today) {
            $errors['date_debut'] = 'La date de début doit être future ou actuelle.';
        }

        if ($fin < $debut) {
            $errors['date_fin'] = 'La date de fin doit être après la date de début.';
        }

        if ($errors) {
            return $errors;
        }

        $check = $this->db->prepare(
            "SELECT COUNT(*) FROM reservation
             WHERE id_equipement = :id
             AND statut IN ('en_attente', 'acceptee')
             AND date_debut <= :fin
             AND date_fin >= :debut"
        );

        $check->execute([
            'id' => $idEquipement,
            'debut' => $dateDebut,
            'fin' => $dateFin
        ]);

        if ((int) $check->fetchColumn() > 0) {
            return ['global' => 'Cet équipement est déjà réservé pendant cette période.'];
        }

        // Calcul du montant : nombre de jours × tarif journalier
        $jours = (int) $debut->diff($fin)->days + 1;
        $montant = $jours * $equipement->getTarifJour();

        $stmt = $this->db->prepare(
            'INSERT INTO reservation
            (id_equipement, id_agriculteur, date_debut,
             date_fin, montant, statut)
            VALUES
            (:equipement, :agriculteur, :debut, :fin,
             :montant, :statut)'
        );

        $stmt->execute([
            'equipement' => $idEquipement,
            'agriculteur' => AuthController::currentUserId(),
            'debut' => $dateDebut,
            'fin' => $dateFin,
            'montant' => $montant,
            'statut' => 'en_attente'
        ]);

        return [];
    }


    public function getReservations(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, e.nom AS nom_equipement,
                    e.type AS type_equipement,
                    u.nom AS nom_agriculteur
             FROM reservation r
             JOIN equipement e
                 ON r.id_equipement = e.id_equipement
             JOIN utilisateur u
                 ON r.id_agriculteur = u.id_utilisateur
             WHERE r.id_agriculteur = :id
             ORDER BY r.id_reservation DESC'
        );

        $stmt->execute(['id' => $userId]);

        return $stmt->fetchAll();
    }


    public function getSellerReservations(int $vendeurId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, e.nom AS nom_equipement,
                    u.nom AS nom_agriculteur
             FROM reservation r
             JOIN equipement e
                 ON r.id_equipement = e.id_equipement
             JOIN utilisateur u
                 ON r.id_agriculteur = u.id_utilisateur
             WHERE e.id_vendeur = :vendeur
             ORDER BY r.id_reservation DESC'
        );

        $stmt->execute(['vendeur' => $vendeurId]);

        return $stmt->fetchAll();
    }

    public function updateReservationStatus(
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
            'SELECT r.id_reservation, r.statut,
                    e.id_vendeur
             FROM reservation r
             JOIN equipement e
                 ON r.id_equipement = e.id_equipement
             WHERE r.id_reservation = :id'
        );

        $stmt->execute(['id' => $id]);

        $reservation = $stmt->fetch();

        if (!$reservation) {
            return 'Réservation introuvable.';
        }

       
        if (
            (int) $reservation['id_vendeur']
            !== AuthController::currentUserId()
        ) {
            return 'Vous ne pouvez pas modifier cette réservation.';
        }

        if ($reservation['statut'] !== 'en_attente') {
            return 'Cette réservation a déjà été traitée.';
        }

        if (!in_array($statut, ['acceptee', 'refusee'], true)) {
            return 'Action non autorisée.';
        }

        $update = $this->db->prepare(
            'UPDATE reservation
             SET statut = :statut
             WHERE id_reservation = :id'
        );

        $update->execute([
            'statut' => $statut,
            'id' => $id
        ]);

        return null;
    }
}
