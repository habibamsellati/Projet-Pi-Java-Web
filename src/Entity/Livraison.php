<?php

namespace App\Entity;

use App\Repository\LivraisonRepository;
<<<<<<< HEAD
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM;
=======
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
>>>>>>> master

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column]
    #[Assert\NotBlank(message: "La date de livraison est obligatoire")]
    #[Assert\GreaterThanOrEqual(
    "today",
    message: "La date de livraison ne peut pas être dans le passé"
)]
    private ?\DateTime $datelivraison = null;

    #[ORM\Column(length: 255,nullable: false)]
    #[Assert\NotBlank(message: "L'adresse de livraison est obligatoire")]
    #[Assert\Length(
    min: 1,
    minMessage: "L'adresse doit contenir au moins {{ limit }} caractères",
    max: 255,
    maxMessage: "L'adresse ne doit pas dépasser {{ limit }} caractères"
)]
#[Assert\Regex(
    pattern: "/^[a-zA-Z0-9\s,'\-]+$/",
    message: "L'adresse contient des caractères non autorisés"
)]
    private ?string $addresslivraison = null;

   #[ORM\Column(length: 255, nullable: false)]
    #[Assert\NotBlank(message: "Le statut de livraison est obligatoire")]
    private string $statutlivraison = 'en_attente';
   #[ORM\OneToOne(mappedBy: 'livraison', cascade: ['persist', 'remove'])]
    private ?Commande $commande = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatelivraison(): ?\DateTime
    {
        return $this->datelivraison;
    }

   public function setDatelivraison(?\DateTimeInterface $datelivraison): static
{
    $this->datelivraison = $datelivraison;
    return $this;
}

    public function getAddresslivraison(): ?string
    {
        return $this->addresslivraison;
    }

    public function setAddresslivraison(string $addresslivraison): static
    {
        $this->addresslivraison = $addresslivraison;

        return $this;
    }

    public function getStatutlivraison(): ?string
    {
        return $this->statutlivraison;
    }

public function setStatutlivraison(?string $statutlivraison): static
{
    $this->statutlivraison = $statutlivraison ?? 'en_attente';
    return $this;
}

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        // unset the owning side of the relation if necessary
        if ($commande === null && $this->commande !== null) {
            $this->commande->setLivraison(null);
        }

        // set the owning side of the relation if necessary
        if ($commande !== null && $commande->getLivraison() !== $this) {
            $commande->setLivraison($this);
        }

        $this->commande = $commande;

        return $this;
    }
}
=======
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date de livraison est obligatoire")]
    #[Assert\GreaterThanOrEqual("today", message: "La date ne peut pas être dans le passé")]
    private ?\DateTimeInterface $datelivraison = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire")]
    private ?string $addresslivraison = null;

    #[ORM\Column(length: 20)]
    private string $statutlivraison = 'en_attente';

    #[ORM\OneToOne(inversedBy: 'livraison', targetEntity: Commande::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    private ?User $livreur = null;

    // Cette relation permet à $livraison->getSuivis() de fonctionner
    #[ORM\OneToMany(mappedBy: 'livraison', targetEntity: SuiviLivraison::class, orphanRemoval: true)]
    private Collection $suivis;

    #[ORM\Column(nullable: true)]
    private ?int $noteLivreur = null;

    public function __construct()
    {
        $this->datelivraison = new \DateTime();
        $this->suivis = new ArrayCollection();
        $this->statutlivraison = 'en_attente';
    }

    public function getId(): ?int { return $this->id; }

    public function getDatelivraison(): ?\DateTimeInterface { return $this->datelivraison; }
    public function setDatelivraison(?\DateTimeInterface $datelivraison): self { 
        $this->datelivraison = $datelivraison; 
        return $this; 
    }

    public function getAddresslivraison(): ?string { return $this->addresslivraison; }
    public function setAddresslivraison(string $addresslivraison): self { 
        $this->addresslivraison = $addresslivraison; 
        return $this; 
    }

    public function getStatutlivraison(): string { return $this->statutlivraison; }
    public function setStatutlivraison(string $statutlivraison): self { 
        $this->statutlivraison = $statutlivraison; 
        return $this; 
    }

    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $commande): self { 
        $this->commande = $commande; 
        return $this; 
    }

    public function getLivreur(): ?User { return $this->livreur; }
    public function setLivreur(?User $livreur): self { 
        $this->livreur = $livreur; 
        return $this; 
    }

    /**
     * @return Collection<int, SuiviLivraison>
     */
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(SuiviLivraison $suivi): self
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis->add($suivi);
            $suivi->setLivraison($this);
        }
        return $this;
    }

    public function removeSuivi(SuiviLivraison $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            if ($suivi->getLivraison() === $this) {
                $suivi->setLivraison(null);
            }
        }
        return $this;
    }

    public function getNoteLivreur(): ?int
    {
        return $this->noteLivreur;
    }

    public function setNoteLivreur(?int $noteLivreur): static
    {
        $this->noteLivreur = $noteLivreur;

        return $this;
    }
}
>>>>>>> master
