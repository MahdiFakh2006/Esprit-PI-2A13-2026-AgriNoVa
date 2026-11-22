<?php
/**
 * models/CommandeModel.php
 * Représente UNE commande passée par un agriculteur. Attributs privés + getters/setters uniquement.
 * Les lignes de la commande sont fournies par le contrôleur sous forme de tableau.
 */
class CommandeModel
{
    private int $id = 0;
    private int $idAgriculteur = 0;
    private string $dateCommande = '';
    private float $total = 0.0;
    private string $statut = 'en_attente';
    private string $agriculteurNom = ''; // jointure avec la table utilisateur
    private array $lignes = [];          // [['nom_produit', 'prix_unitaire', 'quantite'], ...]

    public function __construct()
    {
    }

    public function getId(): int { return $this->id; }
    public function getIdAgriculteur(): int { return $this->idAgriculteur; }
    public function getDateCommande(): string { return $this->dateCommande; }
    public function getTotal(): float { return $this->total; }
    public function getStatut(): string { return $this->statut; }
    public function getAgriculteurNom(): string { return $this->agriculteurNom; }
    public function getLignes(): array { return $this->lignes; }

    public function setId(int $id): void { $this->id = $id; }
    public function setIdAgriculteur(int $idAgriculteur): void { $this->idAgriculteur = $idAgriculteur; }
    public function setDateCommande(string $dateCommande): void { $this->dateCommande = $dateCommande; }
    public function setTotal(float $total): void { $this->total = $total; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setAgriculteurNom(string $agriculteurNom): void { $this->agriculteurNom = $agriculteurNom; }
    public function setLignes(array $lignes): void { $this->lignes = $lignes; }

    public static function fromArray(array $row): self
    {
        $c = new self();
        $c->setId((int) ($row['id_commande'] ?? 0));
        $c->setIdAgriculteur((int) ($row['id_agriculteur'] ?? 0));
        $c->setDateCommande((string) ($row['date_commande'] ?? ''));
        $c->setTotal((float) ($row['total'] ?? 0));
        $c->setStatut((string) ($row['statut'] ?? 'en_attente'));
        $c->setAgriculteurNom((string) ($row['agriculteur_nom'] ?? ''));
        return $c;
    }
}
