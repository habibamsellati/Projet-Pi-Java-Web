<?php

namespace App\Entity;

use App\Repository\LivraisonRepository;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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
