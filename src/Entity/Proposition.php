<?php

namespace App\Entity;

use App\Repository\PropositionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropositionRepository::class)]
class Proposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide")]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9\s\-\'àâäéèêëîïôöùûüçÀÂÄÉÈÊËÎÏÔÖÙÛÜÇ,\.!?]+$/",
        message: "Le titre contient des caractères non autorisés"
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description ne peut pas être vide")]
    #[Assert\Length(min: 10, max: 500)]
    private ?string $description = null;

    #[ORM\Column(name: 'datesoumision', type: 'datetime')]
    #[Assert\NotNull(message: "La date ne peut pas être vide")]
    #[Assert\LessThanOrEqual("now", message: "La date ne peut pas être dans le futur")]
    private ?\DateTime $date = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'prop')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'propositions')]
    #[ORM\JoinColumn(name: 'produit_id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull(message: "Veuillez choisir un produit.")]
    private ?Produit $produit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $prixPropose = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire")]
    #[Assert\Regex(
        pattern: "/^(\\+?216)?\\d{8}$/",
        message: "Le numéro doit être au format tunisien: +216XXXXXXXX ou XXXXXXXX"
    )]
    private ?string $clientPhone = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente';

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getDate(): ?\DateTime { return $this->date; }
    public function setDate(?\DateTime $date): static { $this->date = $date; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): static { $this->produit = $produit; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getPrixPropose(): ?float { return $this->prixPropose; }
    public function setPrixPropose(?float $prixPropose): static { $this->prixPropose = $prixPropose; return $this; }

    public function getClientPhone(): ?string { return $this->clientPhone; }
    public function setClientPhone(?string $clientPhone): static { $this->clientPhone = $clientPhone; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
}
