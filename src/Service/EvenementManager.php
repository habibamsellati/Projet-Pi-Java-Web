<?php

namespace App\Service;

use App\Entity\Evenement;

/**
 * Service de gestion des règles métier pour l'entité Evenement
 */
class EvenementManager
{
    /**
     * Règle 1: La date de fin doit être postérieure à la date de début
     * Règle 2: La capacité doit être positive
     * Règle 3: Le prix ne peut pas être négatif
     * 
     * @param Evenement $evenement
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Evenement $evenement): bool
    {
        // Règle 1: Vérifier que la date de fin est postérieure à la date de début
        if ($evenement->getDateDebut() && $evenement->getDateFin()) {
            if ($evenement->getDateFin() < $evenement->getDateDebut()) {
                throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
            }
        }

        // Règle 2: La capacité doit être positive
        if ($evenement->getCapacite() !== null && $evenement->getCapacite() <= 0) {
            throw new \InvalidArgumentException('La capacité doit être un nombre positif');
        }

        // Règle 3: Le prix ne peut pas être négatif
        if ($evenement->getPrix() !== null && (float)$evenement->getPrix() < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif');
        }

        return true;
    }

    /**
     * Vérifie si l'événement a encore des places disponibles
     * 
     * @param Evenement $evenement
     * @return bool
     */
    public function hasAvailableSeats(Evenement $evenement): bool
    {
        $capacite = $evenement->getCapacite();
        $reservations = $evenement->getReservations()->count();
        
        return $capacite > $reservations;
    }

    /**
     * Calcule le nombre de places restantes
     * 
     * @param Evenement $evenement
     * @return int
     */
    public function getRemainingSeats(Evenement $evenement): int
    {
        $capacite = $evenement->getCapacite() ?? 0;
        $reservations = $evenement->getReservations()->count();
        
        return max(0, $capacite - $reservations);
    }
}
