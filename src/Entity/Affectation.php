<?php

namespace App\Entity;

use App\Repository\AffectationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
//use App\Entity\Vehicle;
//use App\Entity\User;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
class Affectation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "La date de début est obligatoire")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\GreaterThan(
        propertyPath: "dateDebut",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Un véhicule doit être sélectionné")]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Un conducteur doit être sélectionné")]
    private ?User $conducteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }
    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getConducteur(): ?User
    {
        return $this->conducteur;
    }

    public function setConducteur(?User $conducteur): static
    {
        $this->conducteur = $conducteur;
        return $this;
    }

    public function isActive(): bool
    {
        $now = new \DateTime();
        if ($this->dateDebut > $now) {
            return false;
        }
        if ($this->dateFin === null) {
            return true;
        }
        return $this->dateFin > $now;
    }

    public function getStatut(): string
    {
        $now = new \DateTime();
        if ($this->dateDebut > $now) {
            return 'À venir';
        }
        if ($this->dateFin === null) {
            return 'En cours';
        }
        if ($this->dateFin > $now) {
            return 'En cours';
        }
        return 'Terminé';
    }

    public function __toString(): string
    {
        return sprintf(
            'Affectation #%d - %s (%s)',
            $this->id ?? 0,
            $this->conducteur ? $this->conducteur->getEmail() : 'N/A',
            $this->vehicle ? $this->vehicle->getImmatriculation() : 'N/A'
        );
    }
}