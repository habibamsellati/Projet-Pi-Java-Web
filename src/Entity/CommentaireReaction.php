<?php

namespace App\Entity;

use App\Repository\CommentaireReactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireReactionRepository::class)]
#[ORM\Table(name: 'commentaire_reaction')]
#[ORM\UniqueConstraint(name: 'uniq_commentaire_user_reaction', columns: ['commentaire_id', 'user_id'])]
class CommentaireReaction
{
    public const TYPE_LIKE = 1;
    public const TYPE_DISLIKE = -1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private int $type = self::TYPE_LIKE;

    #[ORM\ManyToOne(inversedBy: 'reactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Commentaire $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'commentaireReactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        if (!in_array($type, [self::TYPE_LIKE, self::TYPE_DISLIKE], true)) {
            throw new \InvalidArgumentException('Type de reaction invalide.');
        }

        $this->type = $type;

        return $this;
    }

    public function getCommentaire(): ?Commentaire
    {
        return $this->commentaire;
    }

    public function setCommentaire(?Commentaire $commentaire): static
    {
        $this->commentaire = $commentaire;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

