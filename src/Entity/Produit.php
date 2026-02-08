<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    #[Assert\Choice(
        choices: ['Plastique', 'Papier', 'Carton', 'Verre', 'Métal', 'Bois', 'Tissu'],
        message: "Le nom du produit doit être l'un des suivants : Plastique, Papier, Carton, Verre, Métal, Bois, Tissu."
    )]
    private ?string $nomproduit = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Le type de matériau est obligatoire.")]
    #[Assert\Choice(
        choices: ['Naturel', 'Durable', 'Écologique', 'Zéro déchet', 'Réutilisable'],
        message: "Le type de matériau doit être valide."
    )]
    private ?string $typemateriau = null;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Choice(
        choices: ['Bon', 'Moyen', 'Mauvais'],
        message: "L'état doit être Bon, Moyen ou Mauvais."
    )]
    private ?string $etat = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "La quantité est obligatoire.")]
    #[Assert\PositiveOrZero(message: "La quantité ne peut pas être négative.")]
    #[Assert\Range(
        min: 0,
        max: 999999,
        notInRangeMessage: "La quantité doit être comprise entre 0 et 999 999."
    )]
    private ?int $quantite = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\NotBlank(message: "L'origine est obligatoire.")]
    #[Assert\Length(max: 120, maxMessage: "L'origine ne peut pas dépasser 120 caractères.")]
    private ?string $origine = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: "L'impact écologique est obligatoire.")]
    #[Assert\PositiveOrZero(message: "L'impact écologique ne peut pas être négatif.")]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: "L'impact écologique doit être compris entre 0 et 100."
    )]
    private ?float $impactecologique = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\NotNull(message: "La date d'ajout est obligatoire.")]
    #[Assert\LessThanOrEqual(
        value: "now",
        message: "La date d'ajout ne peut pas être dans le futur."
    )]
    private ?\DateTimeInterface $dateajout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 3000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    /**
     * @var Collection<int, Proposition>
     */
    #[ORM\OneToMany(targetEntity: Proposition::class, mappedBy: 'produit', orphanRemoval: true)]
    private Collection $propositions;

    public function __construct()
    {
        $this->propositions = new ArrayCollection();
        // Optionnel : date d'ajout automatique à la création
        $this->dateajout = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomproduit(): ?string
    {
        return $this->nomproduit;
    }

    public function setNomproduit(?string $nomproduit): static
    {
        $this->nomproduit = $nomproduit;
        return $this;
    }

    public function getTypemateriau(): ?string
    {
        return $this->typemateriau;
    }

    public function setTypemateriau(?string $typemateriau): static
    {
        $this->typemateriau = $typemateriau;
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

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->origine;
    }

    public function setOrigine(?string $origine): static
    {
        $this->origine = $origine;
        return $this;
    }

    public function getImpactecologique(): ?float
    {
        return $this->impactecologique;
    }

    public function setImpactecologique(?float $impactecologique): static
    {
        $this->impactecologique = $impactecologique;
        return $this;
    }

    public function getDateajout(): ?\DateTimeInterface
    {
        return $this->dateajout;
    }

    public function setDateajout(?\DateTimeInterface $dateajout): static
    {
        $this->dateajout = $dateajout;
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