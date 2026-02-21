<?php

namespace App\Entity;

use App\Repository\EvenementPredictionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementPredictionRepository::class)]
class EvenementPrediction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'prediction', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Column]
    private ?float $tauxPredicted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePrediction = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getTauxPredicted(): ?float
    {
        return $this->tauxPredicted;
    }

    public function setTauxPredicted(float $tauxPredicted): static
    {
        $this->tauxPredicted = $tauxPredicted;

        return $this;
    }

    public function getDatePrediction(): ?\DateTimeInterface
    {
        return $this->datePrediction;
    }

    public function setDatePrediction(\DateTimeInterface $datePrediction): static
    {
        $this->datePrediction = $datePrediction;

        return $this;
    }
}
