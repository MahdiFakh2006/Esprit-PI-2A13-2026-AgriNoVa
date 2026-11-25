
<?php

class EquipementModel
{
    //Attri

    private int $id = 0;
    private string $nom = '';
    private string $type = '';
    private float $tarifJour = 0.0;
    private float $caution = 0.0;
    private string $etat = 'disponible';
    private int $idVendeur = 0;

    public function __construct()
    {
    }

    //getgetget

    public function getId(): int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarifJour(): float
    {
        return $this->tarifJour;
    }

    public function getCaution(): float
    {
        return $this->caution;
    }

    public function getEtat(): string
    {
        return $this->etat;
    }

    public function getIdVendeur(): int
    {
        return $this->idVendeur;
    }

    public function isDisponible(): bool
    {
        return $this->etat === 'disponible';
    }

    //Setsetset

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setTarifJour(float $tarifJour): void
    {
        $this->tarifJour = $tarifJour;
    }

    public function setCaution(float $caution): void
    {
        $this->caution = $caution;
    }

    public function setEtat(string $etat): void
    {
        $this->etat = $etat;
    }

    public function setIdVendeur(int $idVendeur): void
    {
        $this->idVendeur = $idVendeur;
    }

    
    public static function fromArray(array $row): self
    {
        $equipement = new self();

        $equipement->setId(
            (int) ($row['id_equipement'] ?? 0)
        );

        $equipement->setNom(
            (string) ($row['nom'] ?? '')
        );

        $equipement->setType(
            (string) ($row['type'] ?? '')
        );

        $equipement->setTarifJour(
            (float) ($row['tarif_jour'] ?? 0)
        );

        $equipement->setCaution(
            (float) ($row['caution'] ?? 0)
        );

        $equipement->setEtat(
            (string) ($row['etat'] ?? 'disponible')
        );

        $equipement->setIdVendeur(
            (int) ($row['id_vendeur'] ?? 0)
        );

        return $equipement;
    }
}
