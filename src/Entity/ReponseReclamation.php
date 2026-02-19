<?php

namespace App\Entity;

use App\Repository\ReponseReclamationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseReclamationRepository::class)]
class ReponseReclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ doit être rempli.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'Ce champ doit contenir au moins {{ limit }} caractères.', maxMessage: 'Ce champ ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTime $datereponse = null;

    #[ORM\ManyToOne(inversedBy: 'reponseReclamations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reclamation $reclamation = null;

    #[ORM\ManyToOne(inversedBy: 'reponseReclamations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $admin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDatereponse(): ?\DateTime
    {
        return $this->datereponse;
    }

    public function setDatereponse(\DateTime $datereponse): static
    {
        $this->datereponse = $datereponse;

        return $this;
    }

    public function getReclamation(): ?Reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?Reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;

        return $this;
    }
}
