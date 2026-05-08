<?php

namespace App\Entity;

use App\Repository\EntretienRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    #[Assert\Type('\DateTimeInterface')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le type d\'entretien est obligatoire')]
    #[Assert\Choice([
        'Révision',
        'Réparation', 
        'Entretien',
        'Contrôle technique',
        'Vidange',
        'Autre'
    ], message: 'Veuillez sélectionner un type valide')]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le coût est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le coût doit être positif ou zéro')]
    private ?float $cout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'entretiens')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le véhicule est obligatoire')]
    private ?Vehicle $vehicle = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCout(): ?float
    {
        return $this->cout;
    }

    public function setCout(?float $cout): static
    {
        $this->cout = $cout;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function __toString(): string
    {
        return sprintf(
            'Entretien %s du %s - %.2f€',
            $this->type ?? 'N/A',
            $this->date ? $this->date->format('d/m/Y') : 'N/A',
            $this->cout ?? 0
        );
    }
}
