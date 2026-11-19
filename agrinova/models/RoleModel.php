<?php
/**
 * models/RoleModel.php
 * Représente UN rôle (admin, agriculteur, vendeur, parrain).
 */
class RoleModel
{
    private int $id = 0;
    private string $libelle = '';
    private string $description = '';

    public function __construct()
    {
    }

    public function getId(): int { return $this->id; }
    public function getLibelle(): string { return $this->libelle; }
    public function getDescription(): string { return $this->description; }

    public function setId(int $id): void { $this->id = $id; }
    public function setLibelle(string $libelle): void { $this->libelle = $libelle; }
    public function setDescription(string $description): void { $this->description = $description; }

    public static function fromArray(array $row): self
    {
        $role = new self();
        $role->setId((int) ($row['id_role'] ?? 0));
        $role->setLibelle((string) ($row['libelle'] ?? ''));
        $role->setDescription((string) ($row['description'] ?? ''));
        return $role;
    }
}
