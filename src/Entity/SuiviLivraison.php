<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\SuiviLivraisonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // <-- Ajouter ceci

#[ORM\Entity(repositoryClass: SuiviLivraisonRepository::class)]
class SuiviLivraison
=======
namespace App\Entity; // 1. Vérifiez que c'est bien App\Entity

use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviLivraisonRepository::class)]
class SuiviLivraison // 2. Le nom doit être EXACTEMENT SuiviLivraison
>>>>>>> master
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de suivi est obligatoire.")]
#[Assert\Type(\DateTimeInterface::class, message: "La date doit être valide.")]
#[Assert\GreaterThanOrEqual("today", message: "La date de suivi ne peut pas être dans le passé.")]
private ?\DateTimeInterface $datesuivi = null;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'état est obligatoire.")]
    #[Assert\Choice(
        choices: ['en cours', 'livré', 'annulé'],
        message: "L'état doit être : en cours, livré ou annulé."
    )]
    private ?string $etat = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La localisation est obligatoire.")]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        max: 10,
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Vous devez sélectionner une livraison.")]
    private ?Livraison $livraison = null;

    // === Getters et Setters === //

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatesuivi(): ?\DateTimeInterface
    {
        return $this->datesuivi;
    }

    public function setDatesuivi(\DateTimeInterface $datesuivi): static
    {
        $this->datesuivi = $datesuivi;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
{
    $this->localisation = $localisation;
    return $this;
}

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
{
    $this->commentaire = $commentaire;
    return $this;
}


    public function getLivraison(): ?Livraison
    {
        return $this->livraison;
    }

    public function setLivraison(?Livraison $livraison): static
    {
        $this->livraison = $livraison;
        return $this;
    }
}
=======
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $datesuivi = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Livraison::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livraison $livraison = null;

    public function __construct()
    {
        $this->datesuivi = new \DateTime();
    }

    // Getters et Setters...
    public function getId(): ?int { return $this->id; }
    
    public function getDatesuivi(): ?\DateTimeInterface { return $this->datesuivi; }
    public function setDatesuivi(\DateTimeInterface $datesuivi): self { $this->datesuivi = $datesuivi; return $this; }

    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(string $etat): self { $this->etat = $etat; return $this; }

    public function getLocalisation(): ?string { return $this->localisation; }
    public function setLocalisation(string $localisation): self { $this->localisation = $localisation; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): self { $this->commentaire = $commentaire; return $this; }

    public function getLivraison(): ?Livraison { return $this->livraison; }
    public function setLivraison(?Livraison $livraison): self { $this->livraison = $livraison; return $this; }
}
>>>>>>> master
