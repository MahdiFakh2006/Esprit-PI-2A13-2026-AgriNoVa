<?php
/**
 * models/MaladieModel.php
 * Représente UNE maladie des cultures (Module 3 – Diagnostic IA).
 * Uniquement des attributs privés + getters/setters. Aucune requête SQL ici (règle MVC du projet).
 */
class MaladieModel
{
    private int $id = 0;
    private string $nom = '';
    private string $culture = '';
    private string $symptomes = '';
    private string $traitement = '';
    private ?string $couleurSymptome = null;  // jaune | brun | blanc | noir (sert à l'analyse d'image)
    private string $gravite = 'moyenne';      // faible | moyenne | elevee
    private int $nbAnalyses = 0;              // calculé par jointure avec la table analyse

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getCulture(): string { return $this->culture; }
    public function getSymptomes(): string { return $this->symptomes; }
    public function getTraitement(): string { return $this->traitement; }
    public function getCouleurSymptome(): ?string { return $this->couleurSymptome; }
    public function getGravite(): string { return $this->gravite; }
    public function getNbAnalyses(): int { return $this->nbAnalyses; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setCulture(string $culture): void { $this->culture = $culture; }
    public function setSymptomes(string $symptomes): void { $this->symptomes = $symptomes; }
    public function setTraitement(string $traitement): void { $this->traitement = $traitement; }
    public function setCouleurSymptome(?string $couleur): void { $this->couleurSymptome = $couleur; }
    public function setGravite(string $gravite): void { $this->gravite = $gravite; }
    public function setNbAnalyses(int $nb): void { $this->nbAnalyses = $nb; }

    /** Transforme une ligne SQL (tableau associatif) en objet MaladieModel. */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->setId((int) ($row['id_maladie'] ?? 0));
        $m->setNom((string) ($row['nom'] ?? ''));
        $m->setCulture((string) ($row['culture'] ?? ''));
        $m->setSymptomes((string) ($row['symptomes'] ?? ''));
        $m->setTraitement((string) ($row['traitement'] ?? ''));
        $m->setCouleurSymptome(isset($row['couleur_symptome']) ? (string) $row['couleur_symptome'] : null);
        $m->setGravite((string) ($row['gravite'] ?? 'moyenne'));
        $m->setNbAnalyses((int) ($row['nb_analyses'] ?? 0));
        return $m;
    }
}
