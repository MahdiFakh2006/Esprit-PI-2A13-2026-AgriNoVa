<?php
/**
 * models/AnalyseModel.php
 * Représente UNE analyse de feuille envoyée par un agriculteur (Module 3 – Diagnostic IA).
 * Uniquement des attributs privés + getters/setters. Aucune requête SQL ici (règle MVC du projet).
 */
class AnalyseModel
{
    /** En dessous de ce % de feuille touchée, la feuille est considérée comme saine. */
    public const SEUIL_SAINE = 8;

    private int $id = 0;
    private ?int $idMaladie = null;        // NULL = aucune maladie détectée (feuille saine)
    private int $idAgriculteur = 0;
    private string $culture = '';
    private string $image = '';            // nom du fichier dans uploads/analyses/
    private string $dateAnalyse = '';
    private ?float $confiance = null;      // 0 à 100 %
    private ?float $tauxAtteinte = null;   // % de la feuille touchée (0 à 100)
    private string $statut = 'en_attente'; // en_attente | terminee

    // Champs obtenus par JOINTURE (maladie et utilisateur), pour l'affichage
    private string $nomMaladie = '';
    private string $traitement = '';
    private string $graviteMaladie = '';
    private string $nomAgriculteur = '';

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getIdMaladie(): ?int { return $this->idMaladie; }
    public function getIdAgriculteur(): int { return $this->idAgriculteur; }
    public function getCulture(): string { return $this->culture; }
    public function getImage(): string { return $this->image; }
    public function getDateAnalyse(): string { return $this->dateAnalyse; }
    public function getConfiance(): ?float { return $this->confiance; }
    public function getTauxAtteinte(): ?float { return $this->tauxAtteinte; }
    public function getStatut(): string { return $this->statut; }
    public function getNomMaladie(): string { return $this->nomMaladie; }
    public function getTraitement(): string { return $this->traitement; }
    public function getGraviteMaladie(): string { return $this->graviteMaladie; }
    public function getNomAgriculteur(): string { return $this->nomAgriculteur; }
    public function isSaine(): bool
    {
        return $this->idMaladie === null && $this->tauxAtteinte !== null && $this->tauxAtteinte < self::SEUIL_SAINE;
    }
    /** Des taches, mais aucune maladie connue ne correspond. */
    public function isInconnue(): bool
    {
        return $this->idMaladie === null && !$this->isSaine();
    }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setIdMaladie(?int $id): void { $this->idMaladie = $id; }
    public function setIdAgriculteur(int $id): void { $this->idAgriculteur = $id; }
    public function setCulture(string $culture): void { $this->culture = $culture; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setDateAnalyse(string $date): void { $this->dateAnalyse = $date; }
    public function setConfiance(?float $confiance): void { $this->confiance = $confiance; }
    public function setTauxAtteinte(?float $taux): void { $this->tauxAtteinte = $taux; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setNomMaladie(string $nom): void { $this->nomMaladie = $nom; }
    public function setTraitement(string $traitement): void { $this->traitement = $traitement; }
    public function setGraviteMaladie(string $gravite): void { $this->graviteMaladie = $gravite; }
    public function setNomAgriculteur(string $nom): void { $this->nomAgriculteur = $nom; }

    /** Transforme une ligne SQL (avec ou sans jointure) en objet AnalyseModel. */
    public static function fromArray(array $row): self
    {
        $a = new self();
        $a->setId((int) ($row['id_analyse'] ?? 0));
        $a->setIdMaladie(isset($row['id_maladie']) ? (int) $row['id_maladie'] : null);
        $a->setIdAgriculteur((int) ($row['id_agriculteur'] ?? 0));
        $a->setCulture((string) ($row['culture'] ?? ''));
        $a->setImage((string) ($row['image'] ?? ''));
        $a->setDateAnalyse((string) ($row['date_analyse'] ?? ''));
        $a->setConfiance(isset($row['confiance']) ? (float) $row['confiance'] : null);
        $a->setTauxAtteinte(isset($row['taux_atteinte']) ? (float) $row['taux_atteinte'] : null);
        $a->setStatut((string) ($row['statut'] ?? 'en_attente'));
        $a->setNomMaladie((string) ($row['nom_maladie'] ?? ''));
        $a->setTraitement((string) ($row['traitement'] ?? ''));
        $a->setGraviteMaladie((string) ($row['gravite'] ?? ''));
        $a->setNomAgriculteur((string) ($row['nom_agriculteur'] ?? ''));
        return $a;
    }
}
