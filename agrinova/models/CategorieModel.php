<?php
/**
 * models/CategorieModel.php
 * Représente UNE catégorie de produits (Capteurs, Drones, Stations météo, Kits IA…).
 * Uniquement des attributs privés + getters/setters. Aucune requête SQL ici (règle MVC du projet).
 */
class CategorieModel
{
    private int $id = 0;
    private string $nom = '';
    private string $description = '';
    private int $nbProduits = 0; // calculé par jointure (LEFT JOIN produit) pour l'affichage

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getNbProduits(): int { return $this->nbProduits; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setNbProduits(int $nbProduits): void { $this->nbProduits = $nbProduits; }

    /** Transforme une ligne SQL (tableau associatif) en objet CategorieModel. */
    public static function fromArray(array $row): self
    {
        $c = new self();
        $c->setId((int) ($row['id_categorie'] ?? 0));
        $c->setNom((string) ($row['nom'] ?? ''));
        $c->setDescription((string) ($row['description'] ?? ''));
        $c->setNbProduits((int) ($row['nb_produits'] ?? 0));
        return $c;
    }
}
