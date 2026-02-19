<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
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
    private ?\DateTime $datepub = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(onDelete: 'CASCADE', nullable: true)]
    private ?self $parent = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, orphanRemoval: true)]
    private Collection $replies;

    /**
     * @var Collection<int, CommentaireReaction>
     */
    #[ORM\OneToMany(mappedBy: 'commentaire', targetEntity: CommentaireReaction::class, orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->reactions = new ArrayCollection();
    }

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

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setParent($this);
        }

        return $this;
    }

    public function removeReply(self $reply): static
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getParent() === $this) {
                $reply->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommentaireReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(CommentaireReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setCommentaire($this);
        }

        return $this;
    }

    public function removeReaction(CommentaireReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getCommentaire() === $this) {
                $reaction->setCommentaire(null);
            }
        }

        return $this;
    }
}
