<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_LIVREUR = 'LIVREUR';
    public const ROLE_CLIENT = 'CLIENT';
    public const ROLE_ARTISAN = 'ARTISANT';
    public const ROLE_ADMIN = 'ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(max: 15, maxMessage: "Le nom ne doit pas dépasser {{ limit }} caractères")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/", message: "Le nom ne doit contenir que des lettres")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(max: 30, maxMessage: "Le prénom ne doit pas dépasser {{ limit }} caractères")]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s-]+$/", message: "Le prénom ne doit contenir que des lettres")]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/",
        message: "Le mot de passe doit contenir au moins une majuscule, un chiffre et un symbole"
    )]
    private ?string $motdepasse = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column]
    private ?\DateTime $datecreation = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    /**
     * @var Collection<int, Reclamation>
     */
    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'user')]
    private Collection $reclamations;
    
    /**
     * @var Collection<int, ReponseReclamation>
     */
    #[ORM\OneToMany(targetEntity: ReponseReclamation::class, mappedBy: 'admin')]
    private Collection $reponseReclamations;


    /**
     * @var Collection<int, Proposition>
     */
    #[ORM\OneToMany(targetEntity: Proposition::class, mappedBy: 'user')]
    private Collection $prop;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $commentaires;

    /**
     * @var Collection<int, CommentaireReaction>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CommentaireReaction::class, orphanRemoval: true)]
    private Collection $commentaireReactions;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'artisan')]
    private Collection $articles;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'user')]
    private Collection $articless;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
    private Collection $reservations;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'likedBy')]
    private Collection $likedArticles;

    public function __construct()
    {
        $this->reclamations = new ArrayCollection();
        $this->reponseReclamations = new ArrayCollection();
        $this->prop = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->commentaireReactions = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->articless = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->likedArticles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMotdepasse(): ?string
    {
        return $this->motdepasse;
    }

    public function setMotdepasse(string $motdepasse): static
    {
        $this->motdepasse = $motdepasse;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getDatecreation(): ?\DateTime
    {
        return $this->datecreation;
    }

    public function setDatecreation(\DateTime $datecreation): static
    {
        $this->datecreation = $datecreation;

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

    public function getDeletedAt(): ?\DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setUser($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getUser() === $this) {
                $reclamation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReponseReclamation>
     */
    public function getReponseReclamations(): Collection
    {
        return $this->reponseReclamations;
    }

    public function addReponseReclamation(ReponseReclamation $reponseReclamation): static
    {
        if (!$this->reponseReclamations->contains($reponseReclamation)) {
            $this->reponseReclamations->add($reponseReclamation);
            $reponseReclamation->setAdmin($this);
        }

        return $this;
    }

    public function removeReponseReclamation(ReponseReclamation $reponseReclamation): static
    {
        if ($this->reponseReclamations->removeElement($reponseReclamation)) {
            // set the owning side to null (unless already changed)
            if ($reponseReclamation->getAdmin() === $this) {
                $reponseReclamation->setAdmin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Proposition>
     */
    public function getProp(): Collection
    {
        return $this->prop;
    }

    public function addProp(Proposition $prop): static
    {
        if (!$this->prop->contains($prop)) {
            $this->prop->add($prop);
            $prop->setUser($this);
        }

        return $this;
    }

    public function removeProp(Proposition $prop): static
    {
        if ($this->prop->removeElement($prop)) {
            // set the owning side to null (unless already changed)
            if ($prop->getUser() === $this) {
                $prop->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setUser($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getUser() === $this) {
                $commentaire->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommentaireReaction>
     */
    public function getCommentaireReactions(): Collection
    {
        return $this->commentaireReactions;
    }

    public function addCommentaireReaction(CommentaireReaction $commentaireReaction): static
    {
        if (!$this->commentaireReactions->contains($commentaireReaction)) {
            $this->commentaireReactions->add($commentaireReaction);
            $commentaireReaction->setUser($this);
        }

        return $this;
    }

    public function removeCommentaireReaction(CommentaireReaction $commentaireReaction): static
    {
        if ($this->commentaireReactions->removeElement($commentaireReaction)) {
            if ($commentaireReaction->getUser() === $this) {
                $commentaireReaction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setArtisan($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getArtisan() === $this) {
                $article->setArtisan(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticless(): Collection
    {
        return $this->articless;
    }

    public function addArticless(Article $articless): static
    {
        if (!$this->articless->contains($articless)) {
            $this->articless->add($articless);
            $articless->setUser($this);
        }

        return $this;
    }

    public function removeArticless(Article $articless): static
    {
        if ($this->articless->removeElement($articless)) {
            if ($articless->getUser() === $this) {
                $articless->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getLikedArticles(): Collection
    {
        return $this->likedArticles;
    }

    public function addLikedArticle(Article $article): static
    {
        if (!$this->likedArticles->contains($article)) {
            $this->likedArticles->add($article);
            $article->addLikedBy($this);
        }

        return $this;
    }

    public function removeLikedArticle(Article $article): static
    {
        if ($this->likedArticles->removeElement($article)) {
            $article->removeLikedBy($this);
        }

        return $this;
    }

    // UserInterface methods
    public function getRoles(): array
    {
        $roles = [$this->role ?? 'ROLE_USER'];
        return array_unique($roles);
    }

    public function getPassword(): ?string
    {
        return $this->motdepasse;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // Not needed for password_hash
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
