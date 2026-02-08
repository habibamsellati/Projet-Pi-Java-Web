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
    #[Assert\NotBlank(message: 'Le contenu de la réponse est obligatoire')]
    #[Assert\Length(max: 255, maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères')]
    private ?string $contenu = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date de réponse est obligatoire')]
    private ?\DateTime $datereponse = null;

    #[ORM\ManyToOne(inversedBy: 'reponseReclamations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La réclamation est obligatoire')]
    private ?reclamation $reclamation = null;

    #[ORM\ManyToOne(inversedBy: 'reponseReclamations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'administrateur est obligatoire')]
    private ?user $admin = null;

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

    public function getReclamation(): ?reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function getAdmin(): ?user
    {
        return $this->admin;
    }

    public function setAdmin(?user $admin): static
    {
        $this->admin = $admin;

        return $this;
    }
}
