<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $datereservation = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    /**
     * @var Collection<int, Evenement>
     */
    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'reservation')]
    private Collection $events;

    /**
     * @var Collection<int, User>
     */
    // user reservations relation removed

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatereservation(): ?\DateTime
    {
        return $this->datereservation;
    }

    public function setDatereservation(\DateTime $datereservation): static
    {
        $this->datereservation = $datereservation;

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

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Evenement $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setReservation($this);
        }

        return $this;
    }

    public function removeEvent(Evenement $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getReservation() === $this) {
                $event->setReservation(null);
            }
        }

        return $this;
    }

    // user reservations collection and accessors removed

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }
}
