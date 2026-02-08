<?php

namespace App\Entity;

use App\Entity\Article;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $datecommande = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column(length: 255)]
    private ?string $adresselivraison = null;

    #[ORM\Column(length: 255)]
    private ?string $modepaiement = null;

    /**
     * @var Collection<int, article>
     */
    #[ORM\ManyToMany(targetEntity: article::class, inversedBy: 'commandes')]
    private Collection $articles;

    #[ORM\OneToOne(inversedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Livraison $livraison = null; // majuscule corrigée


    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatecommande(): ?\DateTime
    {
        return $this->datecommande;
    }

    public function setDatecommande(\DateTime $datecommande): static
    {
        $this->datecommande = $datecommande;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getAdresselivraison(): ?string
    {
        return $this->adresselivraison;
    }

    public function setAdresselivraison(string $adresselivraison): static
    {
        $this->adresselivraison = $adresselivraison;

        return $this;
    }

    public function getModepaiement(): ?string
    {
        return $this->modepaiement;
    }

    public function setModepaiement(string $modepaiement): static
    {
        $this->modepaiement = $modepaiement;

        return $this;
    }

    /**
     * @return Collection<int, article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }

        return $this;
    }

    public function removeArticle(article $article): static
    {
        $this->articles->removeElement($article);

        return $this;
    }

    public function getLivraison(): ?livraison
    {
        return $this->livraison;
    }

    public function setLivraison(?livraison $livraison): static
    {
        $this->livraison = $livraison;

        return $this;
    }
}
