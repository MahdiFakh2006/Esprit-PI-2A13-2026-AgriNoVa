<?php
/**
 * models/ReclamationModel.php
 * Représente UNE réclamation. Uniquement des attributs privés + getters/setters.
 * Aucune requête SQL ici (règle MVC du projet).
 */
class ReclamationModel
{
    private int $id = 0;
    private int $idUtilisateur = 0;
    private string $sujet = '';
    private string $categorie = '';
    private string $description = '';
    private string $dateCreation = '';
    private string $statut = 'ouverte'; // ouverte | en_cours | resolue | cloturee

    // Données issues des jointures (lecture seule)
    private string $auteurNom = '';
    private string $auteurEmail = '';
    private int $nbReponses = 0;

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getSujet(): string { return $this->sujet; }
    public function getCategorie(): string { return $this->categorie; }
    public function getDescription(): string { return $this->description; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getStatut(): string { return $this->statut; }
    public function getAuteurNom(): string { return $this->auteurNom; }
    public function getAuteurEmail(): string { return $this->auteurEmail; }
    public function getNbReponses(): int { return $this->nbReponses; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setIdUtilisateur(int $idUtilisateur): void { $this->idUtilisateur = $idUtilisateur; }
    public function setSujet(string $sujet): void { $this->sujet = $sujet; }
    public function setCategorie(string $categorie): void { $this->categorie = $categorie; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setDateCreation(string $dateCreation): void { $this->dateCreation = $dateCreation; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setAuteurNom(string $auteurNom): void { $this->auteurNom = $auteurNom; }
    public function setAuteurEmail(string $auteurEmail): void { $this->auteurEmail = $auteurEmail; }
    public function setNbReponses(int $nbReponses): void { $this->nbReponses = $nbReponses; }

    /** Construit un objet à partir d'une ligne SQL (tableau associatif). */
    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->setId((int) ($row['id_reclamation'] ?? 0));
        $r->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $r->setSujet((string) ($row['sujet'] ?? ''));
        $r->setCategorie((string) ($row['categorie'] ?? ''));
        $r->setDescription((string) ($row['description'] ?? ''));
        $r->setDateCreation((string) ($row['date_creation'] ?? ''));
        $r->setStatut((string) ($row['statut'] ?? 'ouverte'));
        $r->setAuteurNom((string) ($row['auteur_nom'] ?? ''));
        $r->setAuteurEmail((string) ($row['auteur_email'] ?? ''));
        $r->setNbReponses((int) ($row['nb_rep'] ?? 0));
        return $r;
    }
}
