<?php

namespace App\Entity;

use App\Repository\LivreurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivreurRepository::class)]
class Livreur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idLivreur = null;

    #[ORM\Column(length: 255)]
    private ?string $nomLivreur = null;

    #[ORM\Column(length: 255)]
    private ?string $prenomLivreur = null;

    #[ORM\Column(length: 255)]
    private ?string $cantactLivreur = null;

    #[ORM\Column(length: 255)]
    private ?string $vehicule = null;

    #[ORM\ManyToOne(inversedBy: 'livreurs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?livraison $livreur = null;

    /**
     * @var Collection<int, Livraison>
     */
    #[ORM\OneToMany(targetEntity: Livraison::class, mappedBy: 'livreur')]
    private Collection $livraisons;

    public function __construct()
    {
        $this->livraisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdLivreur(): ?string
    {
        return $this->idLivreur;
    }

    public function setIdLivreur(string $idLivreur): static
    {
        $this->idLivreur = $idLivreur;

        return $this;
    }

    public function getNomLivreur(): ?string
    {
        return $this->nomLivreur;
    }

    public function setNomLivreur(string $nomLivreur): static
    {
        $this->nomLivreur = $nomLivreur;

        return $this;
    }

    public function getPrenomLivreur(): ?string
    {
        return $this->prenomLivreur;
    }

    public function setPrenomLivreur(string $prenomLivreur): static
    {
        $this->prenomLivreur = $prenomLivreur;

        return $this;
    }

    public function getCantactLivreur(): ?string
    {
        return $this->cantactLivreur;
    }

    public function setCantactLivreur(string $cantactLivreur): static
    {
        $this->cantactLivreur = $cantactLivreur;

        return $this;
    }

    public function getVehicule(): ?string
    {
        return $this->vehicule;
    }

    public function setVehicule(string $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getLivreur(): ?livraison
    {
        return $this->livreur;
    }

    public function setLivreur(?livraison $livreur): static
    {
        $this->livreur = $livreur;

        return $this;
    }

    /**
     * @return Collection<int, Livraison>
     */
    public function getLivraisons(): Collection
    {
        return $this->livraisons;
    }

    public function addLivraison(Livraison $livraison): static
    {
        if (!$this->livraisons->contains($livraison)) {
            $this->livraisons->add($livraison);
            $livraison->setLivreur($this);
        }

        return $this;
    }

    public function removeLivraison(Livraison $livraison): static
    {
        if ($this->livraisons->removeElement($livraison)) {
            // set the owning side to null (unless already changed)
            if ($livraison->getLivreur() === $this) {
                $livraison->setLivreur(null);
            }
        }

        return $this;
    }
}
