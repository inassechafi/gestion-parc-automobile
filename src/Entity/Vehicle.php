<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[UniqueEntity( 
    fields: ['immatriculation'],
    message: 'Une voiture avec cette immatriculation existe déjà.'
)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: "L'immatriculation est obligatoire")]
    #[Assert\Length(max: 50)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le modèle est obligatoire")]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "L'état est obligatoire")]
    #[Assert\Choice(
        choices: ['disponible', 'en service', 'en panne', 'en entretien', 'hors service']
    )]
    private ?string $etat = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le kilométrage est obligatoire")]
    #[Assert\PositiveOrZero(message: "Le kilométrage doit être positif")]
    private ?int $kilometrage = null;

    /**
     * @var Collection<int, Affectation>
     */
    #[ORM\OneToMany(targetEntity: Affectation::class, mappedBy: 'vehicle')]
    private Collection $affectations;

    /**
     * @var Collection<int, Entretien>
     */
    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'vehicle')]
    private Collection $entretiens;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation ? trim(strtoupper($immatriculation)) : null;
        return $this;
    }


    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele; 
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat; 
        return $this;
    }

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(?int $kilometrage): static
    {
        $this->kilometrage = $kilometrage; 
        return $this;
    }

    /**
     * @return Collection<int, Affectation>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(Affectation $affectation): static
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations->add($affectation);
            $affectation->setVehicle($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): static
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getVehicle() === $this) {
                $affectation->setVehicle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): static
    {
        if (!$this->entretiens->contains($entretien)) {
            $this->entretiens->add($entretien);
            $entretien->setVehicle($this);
        }

        return $this;
    }

    public function removeEntretien(Entretien $entretien): static
    {
        if ($this->entretiens->removeElement($entretien)) {
            // set the owning side to null (unless already changed)
            if ($entretien->getVehicle() === $this) {
                $entretien->setVehicle(null);
            }
        }

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->etat === 'disponible';
    }

    public function getAffectationActuelle(): ?Affectation
    {
        foreach ($this->affectations as $affectation) {
            if ($affectation->isActive()) {
                return $affectation;
            }
        }
        return null;
    }

    public function getDernierEntretien(): ?Entretien
    {
        if ($this->entretiens->isEmpty()) {
            return null;
        }
    
        $entretiens = $this->entretiens->toArray();
        usort($entretiens, function($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });
    
        return $entretiens[0];
    }

    public function __toString(): string
    {
        return sprintf('%s - %s (%s)', 
            $this->immatriculation, 
            $this->modele, 
            $this->etat ?? 'N/A'
        );
    }

}
