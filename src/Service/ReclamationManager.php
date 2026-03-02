<?php

namespace App\Service;

use App\Entity\Reclamation;

/**
 * Service de gestion des règles métier pour l'entité Reclamation
 */
class ReclamationManager
{
    /**
     * Règle 1: Le titre est obligatoire et doit contenir au moins 3 caractères
     * Règle 2: Le statut doit être valide (en_attente, en_cours, resolu, rejete)
     * Règle 3: La description doit contenir au moins 10 caractères si elle est fournie
     * 
     * @param Reclamation $reclamation
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Reclamation $reclamation): bool
    {
        // Règle 1: Le titre est obligatoire et doit contenir au moins 3 caractères
        if (empty($reclamation->getTitre()) || strlen($reclamation->getTitre()) < 3) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 3 caractères');
        }

        // Règle 2: Le statut doit être valide
        $statutsValides = ['en_attente', 'en_cours', 'resolu', 'rejete'];
        if (!in_array($reclamation->getStatut(), $statutsValides, true)) {
            throw new \InvalidArgumentException('Le statut doit être: en_attente, en_cours, resolu ou rejete');
        }

        // Règle 3: La description doit contenir au moins 10 caractères si elle est fournie
        if ($reclamation->getDescripition() !== null && strlen($reclamation->getDescripition()) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères');
        }

        return true;
    }

    /**
     * Vérifie si une réclamation est en attente depuis plus de 7 jours
     * 
     * @param Reclamation $reclamation
     * @return bool
     */
    public function isPending(Reclamation $reclamation): bool
    {
        if ($reclamation->getStatut() !== 'en_attente') {
            return false;
        }

        $dateCreation = $reclamation->getDatecreation();
        $now = new \DateTime();
        $diff = $now->diff($dateCreation);

        return $diff->days >= 7;
    }

    /**
     * Vérifie si une réclamation peut être résolue
     * 
     * @param Reclamation $reclamation
     * @return bool
     */
    public function canBeResolved(Reclamation $reclamation): bool
    {
        // Une réclamation peut être résolue si elle a au moins une réponse
        return $reclamation->getReponseReclamations()->count() > 0;
    }
}
