<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTime $datepub = null;

    #[ORM\ManyToOne(inversedBy: 'comment')]
    #[ORM\JoinColumn(nullable: false)]
<<<<<<< HEAD
    private ?article $article = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;
=======
    private ?Article $Article = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
>>>>>>> master

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

    public function getDatepub(): ?\DateTime
    {
        return $this->datepub;
    }

    public function setDatepub(\DateTime $datepub): static
    {
        $this->datepub = $datepub;

        return $this;
    }

<<<<<<< HEAD
    public function getArticle(): ?article
    {
        return $this->article;
    }

    public function setArticle(?article $article): static
    {
        $this->article = $article;
=======
    public function getArticle(): ?Article
    {
        return $this->Article;
    }

    public function setArticle(?Article $article): static
    {
        $this->Article = $article;
>>>>>>> master

        return $this;
    }

<<<<<<< HEAD
    public function getUser(): ?user
=======
    public function getUser(): ?User
>>>>>>> master
    {
        return $this->user;
    }

<<<<<<< HEAD
    public function setUser(?user $user): static
=======
    public function setUser(?User $user): static
>>>>>>> master
    {
        $this->user = $user;

        return $this;
    }
}
