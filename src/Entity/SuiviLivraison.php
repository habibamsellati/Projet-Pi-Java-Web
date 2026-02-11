<?php

namespace App\Entity; // 1. Vérifiez que c'est bien App\Entity

use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviLivraisonRepository::class)]
class SuiviLivraison // 2. Le nom doit être EXACTEMENT SuiviLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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