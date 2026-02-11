<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomproduit = null;

    #[ORM\Column(length: 255)]
    private ?string $typemateriau = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 255)]
    private ?string $origine = null;

    #[ORM\Column]
    private ?float $impactecologique = null;

    #[ORM\Column]
    private ?\DateTime $dateajout = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Proposition>
     */
    #[ORM\OneToMany(targetEntity: Proposition::class, mappedBy: 'produit')]
    private Collection $propositions;

    public function __construct()
    {
        $this->propositions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomproduit(): ?string
    {
        return $this->nomproduit;
    }

    public function setNomproduit(string $nomproduit): static
    {
        $this->nomproduit = $nomproduit;

        return $this;
    }

    public function getTypemateriau(): ?string
    {
        return $this->typemateriau;
    }

    public function setTypemateriau(string $typemateriau): static
    {
        $this->typemateriau = $typemateriau;

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

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->origine;
    }

    public function setOrigine(string $origine): static
    {
        $this->origine = $origine;

        return $this;
    }

    public function getImpactecologique(): ?float
    {
        return $this->impactecologique;
    }

    public function setImpactecologique(float $impactecologique): static
    {
        $this->impactecologique = $impactecologique;

        return $this;
    }

    public function getDateajout(): ?\DateTime
    {
        return $this->dateajout;
    }

    public function setDateajout(\DateTime $dateajout): static
    {
        $this->dateajout = $dateajout;

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

    /**
     * @return Collection<int, Proposition>
     */
    public function getPropositions(): Collection
    {
        return $this->propositions;
    }

    public function addProposition(Proposition $proposition): static
    {
        if (!$this->propositions->contains($proposition)) {
            $this->propositions->add($proposition);
            $proposition->setProduit($this);
        }

        return $this;
    }

    public function removeProposition(Proposition $proposition): static
    {
        if ($this->propositions->removeElement($proposition)) {
            // set the owning side to null (unless already changed)
            if ($proposition->getProduit() === $this) {
                $proposition->setProduit(null);
            }
        }

        return $this;
    }
}
