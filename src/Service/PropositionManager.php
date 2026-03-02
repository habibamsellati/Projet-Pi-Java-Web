<?php

namespace App\Service;

use App\Entity\Proposition;

/**
 * Service de gestion des règles métier pour l'entité Proposition
 */
class PropositionManager
{
    /**
     * Règle 1: Le prix proposé doit être supérieur à zéro
     * Règle 2: Le téléphone client doit être valide (format tunisien)
     * Règle 3: Le statut doit être valide
     * 
     * @param Proposition $proposition
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Proposition $proposition): bool
    {
        // Règle 1: Le prix proposé doit être supérieur à zéro
        if ($proposition->getPrixPropose() !== null && $proposition->getPrixPropose() <= 0) {
            throw new \InvalidArgumentException('Le prix proposé doit être supérieur à zéro');
        }

        // Règle 2: Le téléphone client doit être valide (format tunisien)
        if ($proposition->getClientPhone() !== null) {
            $phone = $proposition->getClientPhone();
            // Format tunisien: +216 ou 00216 suivi de 8 chiffres
            if (!preg_match('/^(\+216|00216)?[2-9]\d{7}$/', $phone)) {
                throw new \InvalidArgumentException('Le numéro de téléphone doit être au format tunisien valide');
            }
        }

        // Règle 3: Le statut doit être valide
        $statutsValides = ['en_attente', 'acceptee', 'refusee', 'terminee'];
        if ($proposition->getStatut() && !in_array($proposition->getStatut(), $statutsValides, true)) {
            throw new \InvalidArgumentException('Le statut doit être: en_attente, acceptee, refusee ou terminee');
        }

        return true;
    }

    /**
     * Vérifie si une proposition peut être acceptée
     * 
     * @param Proposition $proposition
     * @return bool
     */
    public function canBeAccepted(Proposition $proposition): bool
    {
        return $proposition->getStatut() === 'en_attente';
    }

    /**
     * Calcule le pourcentage de réduction par rapport au prix initial
     * 
     * @param Proposition $proposition
     * @param float $prixInitial
     * @return float
     */
    public function calculateDiscount(Proposition $proposition, float $prixInitial): float
    {
        if ($prixInitial <= 0) {
            return 0;
        }

        $prixPropose = $proposition->getPrixPropose() ?? 0;
        return (($prixInitial - $prixPropose) / $prixInitial) * 100;
    }
}
