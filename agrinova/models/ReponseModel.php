<?php
/**
 * models/ReponseModel.php
 * Représente UNE réponse à une réclamation. Uniquement des attributs privés + getters/setters.
 * Aucune requête SQL ici (règle MVC du projet).
 */
class ReponseModel
{
    private int $id = 0;
    private int $idReclamation = 0;
    private int $idAuteur = 0;
    private string $message = '';
    private string $dateReponse = '';

    // Données issues des jointures (lecture seule)
    private string $auteurNom = '';
    private string $auteurRole = '';

    public function __construct()
    {
    }

    // ---------- Getters ----------
    public function getId(): int { return $this->id; }
    public function getIdReclamation(): int { return $this->idReclamation; }
    public function getIdAuteur(): int { return $this->idAuteur; }
    public function getMessage(): string { return $this->message; }
    public function getDateReponse(): string { return $this->dateReponse; }
    public function getAuteurNom(): string { return $this->auteurNom; }
    public function getAuteurRole(): string { return $this->auteurRole; }

    // ---------- Setters ----------
    public function setId(int $id): void { $this->id = $id; }
    public function setIdReclamation(int $idReclamation): void { $this->idReclamation = $idReclamation; }
    public function setIdAuteur(int $idAuteur): void { $this->idAuteur = $idAuteur; }
    public function setMessage(string $message): void { $this->message = $message; }
    public function setDateReponse(string $dateReponse): void { $this->dateReponse = $dateReponse; }
    public function setAuteurNom(string $auteurNom): void { $this->auteurNom = $auteurNom; }
    public function setAuteurRole(string $auteurRole): void { $this->auteurRole = $auteurRole; }

    /** Construit un objet à partir d'une ligne SQL (tableau associatif). */
    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->setId((int) ($row['id_reponse'] ?? 0));
        $r->setIdReclamation((int) ($row['id_reclamation'] ?? 0));
        $r->setIdAuteur((int) ($row['id_auteur'] ?? 0));
        $r->setMessage((string) ($row['message'] ?? ''));
        $r->setDateReponse((string) ($row['date_reponse'] ?? ''));
        $r->setAuteurNom((string) ($row['auteur_nom'] ?? ''));
        $r->setAuteurRole((string) ($row['auteur_role'] ?? ''));
        return $r;
    }
}
