<?php

namespace App\Entity;

use App\Repository\LivraisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivraisonRepository::class)]
class Livraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idLivraison = null;

    #[ORM\Column(length: 255)]
    private ?string $idFormulaire = null;

    #[ORM\Column(length: 255)]
    private ?string $adresseClient = null;

    #[ORM\Column(length: 255)]
    private ?string $adresseArtisant = null;

    #[ORM\Column]
    private ?\DateTime $dateLivraison = null;

    #[ORM\Column(length: 255)]
    private ?string $statutLivraison = null;

    /**
     * @var Collection<int, Livreur>
     */
    #[ORM\OneToMany(targetEntity: Livreur::class, mappedBy: 'livreur')]
    private Collection $livreurs;

    #[ORM\ManyToOne(inversedBy: 'livraisons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?livreur $livreur = null;

    public function __construct()
    {
        $this->livreurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdLivraison(): ?string
    {
        return $this->idLivraison;
    }

    public function setIdLivraison(string $idLivraison): static
    {
        $this->idLivraison = $idLivraison;

        return $this;
    }

    public function getIdFormulaire(): ?string
    {
        return $this->idFormulaire;
    }

    public function setIdFormulaire(string $idFormulaire): static
    {
        $this->idFormulaire = $idFormulaire;

        return $this;
    }

    public function getAdresseClient(): ?string
    {
        return $this->adresseClient;
    }

    public function setAdresseClient(string $adresseClient): static
    {
        $this->adresseClient = $adresseClient;

        return $this;
    }

    public function getAdresseArtisant(): ?string
    {
        return $this->adresseArtisant;
    }

    public function setAdresseArtisant(string $adresseArtisant): static
    {
        $this->adresseArtisant = $adresseArtisant;

        return $this;
    }

    public function getDateLivraison(): ?\DateTime
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(\DateTime $dateLivraison): static
    {
        $this->dateLivraison = $dateLivraison;

        return $this;
    }

    public function getStatutLivraison(): ?string
    {
        return $this->statutLivraison;
    }

    public function setStatutLivraison(string $statutLivraison): static
    {
        $this->statutLivraison = $statutLivraison;

        return $this;
    }

    /**
     * @return Collection<int, Livreur>
     */
    public function getLivreurs(): Collection
    {
        return $this->livreurs;
    }

    public function addLivreur(Livreur $livreur): static
    {
        if (!$this->livreurs->contains($livreur)) {
            $this->livreurs->add($livreur);
            $livreur->setLivreur($this);
        }

        return $this;
    }

    public function removeLivreur(Livreur $livreur): static
    {
        if ($this->livreurs->removeElement($livreur)) {
            // set the owning side to null (unless already changed)
            if ($livreur->getLivreur() === $this) {
                $livreur->setLivreur(null);
            }
        }

        return $this;
    }

    public function getLivreur(): ?livreur
    {
        return $this->livreur;
    }

    public function setLivreur(?livreur $livreur): static
    {
        $this->livreur = $livreur;

        return $this;
    }
}
