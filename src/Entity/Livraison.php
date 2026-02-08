<?php

namespace App\Entity;

use App\Repository\LivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $datelivraison = null;

    #[ORM\Column(length: 255)]
    private ?string $addresslivraison = null;

    #[ORM\Column(length: 255)]
    private ?string $statutlivraison = null;

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

    public function setDatelivraison(\DateTime $datelivraison): static
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

    public function setStatutlivraison(string $statutlivraison): static
    {
        $this->statutlivraison = $statutlivraison;

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
