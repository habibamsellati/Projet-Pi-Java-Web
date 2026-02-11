<?php

namespace App\Entity;

use App\Repository\PropositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropositionRepository::class)]
class Proposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTime $datesoumision = null;

    #[ORM\ManyToOne(inversedBy: 'propositions')]
    #[ORM\JoinColumn(nullable: false)]
<<<<<<< HEAD
    private ?produit $produit = null;
=======
    private ?Produit $Produit = null;
>>>>>>> master

    #[ORM\ManyToOne(inversedBy: 'prop')]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDatesoumision(): ?\DateTime
    {
        return $this->datesoumision;
    }

    public function setDatesoumision(\DateTime $datesoumision): static
    {
        $this->datesoumision = $datesoumision;

        return $this;
    }

<<<<<<< HEAD
    public function getProduit(): ?produit
    {
        return $this->produit;
    }

    public function setProduit(?produit $produit): static
    {
        $this->produit = $produit;
=======
    public function getProduit(): ?Produit
    {
        return $this->Produit;
    }

    public function setProduit(?Produit $Produit): static
    {
        $this->Produit = $Produit;
>>>>>>> master

        return $this;
    }

<<<<<<< HEAD
    public function getUser(): ?user
=======
    public function getUser(): ?User
>>>>>>> master
    {
        return $this->user;
    }

<<<<<<< HEAD
    public function setUser(?user $user): static
=======
    public function setUser(?User $user): static
>>>>>>> master
    {
        $this->user = $user;

        return $this;
    }
}
