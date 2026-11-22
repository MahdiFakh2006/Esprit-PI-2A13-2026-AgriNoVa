<?php
/**
 * models/ProduitModel.php
 * Représente UN produit du catalogue. Uniquement des attributs privés + getters/setters.
 * Aucune requête SQL ici (règle MVC du projet).
 */
class ProduitModel
{
    private int $id = 0;
    private string $nom = '';
    private string $description = '';
    private float $prix = 0.0;
    private int $stock = 0;
    private string $image = '';
    private int $idCategorie = 0;
    private int $idVendeur = 0;
    private string $categorieNom = ''; // obtenu par jointure avec la table categorie
    private string $vendeurNom = '';   // obtenu par jointure avec la table utilisateur

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getPrix(): float { return $this->prix; }
    public function getStock(): int { return $this->stock; }
    public function getImage(): string { return $this->image; }
    public function getIdCategorie(): int { return $this->idCategorie; }
    public function getIdVendeur(): int { return $this->idVendeur; }
    public function getCategorieNom(): string { return $this->categorieNom; }
    public function getVendeurNom(): string { return $this->vendeurNom; }
    public function isEnRupture(): bool { return $this->stock <= 0; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setPrix(float $prix): void { $this->prix = $prix; }
    public function setStock(int $stock): void { $this->stock = $stock; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setIdCategorie(int $idCategorie): void { $this->idCategorie = $idCategorie; }
    public function setIdVendeur(int $idVendeur): void { $this->idVendeur = $idVendeur; }
    public function setCategorieNom(string $categorieNom): void { $this->categorieNom = $categorieNom; }
    public function setVendeurNom(string $vendeurNom): void { $this->vendeurNom = $vendeurNom; }

    /** Transforme une ligne SQL (tableau associatif) en objet ProduitModel. */
    public static function fromArray(array $row): self
    {
        $p = new self();
        $p->setId((int) ($row['id_produit'] ?? 0));
        $p->setNom((string) ($row['nom'] ?? ''));
        $p->setDescription((string) ($row['description'] ?? ''));
        $p->setPrix((float) ($row['prix'] ?? 0));
        $p->setStock((int) ($row['stock'] ?? 0));
        $p->setImage((string) ($row['image'] ?? ''));
        $p->setIdCategorie((int) ($row['id_categorie'] ?? 0));
        $p->setIdVendeur((int) ($row['id_vendeur'] ?? 0));
        $p->setCategorieNom((string) ($row['categorie_nom'] ?? ''));
        $p->setVendeurNom((string) ($row['vendeur_nom'] ?? ''));
        return $p;
    }
}
