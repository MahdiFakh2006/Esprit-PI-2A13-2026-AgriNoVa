<?php
/**
 * models/UserModel.php
 * Représente UN utilisateur. Uniquement des attributs privés + getters/setters.
 * Aucune requête SQL ici (règle MVC du projet).
 */
class UserModel
{
    private int $id = 0;
    private string $nom = '';
    private string $prenom = '';
    private string $email = '';
    private string $motDePasse = '';
    private string $telephone = '';
    private int $idRole = 0;
    private string $role = '';        // libellé du rôle (obtenu par jointure avec la table role)
    private string $statut = 'actif'; // actif | bloque
    private string $dateCreation = '';

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getNomComplet(): string { return $this->prenom . ' ' . $this->nom; }
    public function getEmail(): string { return $this->email; }
    public function getMotDePasse(): string { return $this->motDePasse; }
    public function getTelephone(): string { return $this->telephone; }
    public function getIdRole(): int { return $this->idRole; }
    public function getRole(): string { return $this->role; }
    public function getStatut(): string { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function isBloque(): bool { return $this->statut === 'bloque'; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setPrenom(string $prenom): void { $this->prenom = $prenom; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setMotDePasse(string $motDePasse): void { $this->motDePasse = $motDePasse; }
    public function setTelephone(string $telephone): void { $this->telephone = $telephone; }
    public function setIdRole(int $idRole): void { $this->idRole = $idRole; }
    public function setRole(string $role): void { $this->role = $role; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setDateCreation(string $dateCreation): void { $this->dateCreation = $dateCreation; }

    /** Transforme une ligne SQL (tableau associatif) en objet UserModel. */
    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->setId((int) ($row['id_utilisateur'] ?? 0));
        $user->setNom((string) ($row['nom'] ?? ''));
        $user->setPrenom((string) ($row['prenom'] ?? ''));
        $user->setEmail((string) ($row['email'] ?? ''));
        $user->setMotDePasse((string) ($row['mot_de_passe'] ?? ''));
        $user->setTelephone((string) ($row['telephone'] ?? ''));
        $user->setIdRole((int) ($row['id_role'] ?? 0));
        $user->setRole((string) ($row['role'] ?? ''));
        $user->setStatut((string) ($row['statut'] ?? 'actif'));
        $user->setDateCreation((string) ($row['date_creation'] ?? ''));
        return $user;
    }
}
