<?php

namespace App\Service;

use App\Entity\Reservation;

class ReservationManager
{
    public function validate(Reservation $reservation): bool
    {
        if ($reservation->getNbPlaces() !== null && $reservation->getNbPlaces() <= 0) {
            throw new \InvalidArgumentException('Le nombre de places doit être supérieur à zéro');
        }

        $statutsValides = ['confirmee', 'annulee', 'en_attente', 'confirme', 'annule'];
        if ($reservation->getStatut() && !in_array($reservation->getStatut(), $statutsValides, true)) {
            throw new \InvalidArgumentException('Le statut doit être: confirmee, annulee ou en_attente');
        }

        return true;
    }

    public function canBeCancelled(Reservation $reservation): bool
    {
        return in_array($reservation->getStatut(), ['confirmee', 'confirme', 'en_attente'], true);
    }

    public function getTotalPrice(Reservation $reservation): float
    {
        $evenement = $reservation->getEvenement();
        if (!$evenement || !$evenement->getPrix()) {
            return 0.0;
        }

        $prixUnitaire = (float) $evenement->getPrix();
        $nombrePlaces = $reservation->getNbPlaces() ?? 1;

        return $prixUnitaire * $nombrePlaces;
    }
}
