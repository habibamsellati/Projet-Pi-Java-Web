<?php

namespace App\Service;

use App\Entity\Commande;

/**
 * Service de gestion des règles métier pour l'entité Commande
 */
class CommandeManager
{
    /**
     * Règle 1: Le total doit être supérieur à zéro
     * Règle 2: L'adresse de livraison doit contenir au moins 10 caractères
     * Règle 3: Le téléphone doit être au format tunisien valide
     * 
     * @param Commande $commande
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Commande $commande): bool
    {
        // Règle 1: Le total doit être supérieur à zéro
        if ($commande->getTotal() !== null && $commande->getTotal() <= 0) {
            throw new \InvalidArgumentException('Le total de la commande doit être supérieur à zéro');
        }

        // Règle 2: L'adresse de livraison doit contenir au moins 10 caractères
        if (empty($commande->getAdresselivraison()) || strlen($commande->getAdresselivraison()) < 10) {
            throw new \InvalidArgumentException('L\'adresse de livraison doit contenir au moins 10 caractères');
        }

        // Règle 3: Le téléphone doit être au format tunisien valide si fourni
        if ($commande->getTelephone() !== null) {
            if (!preg_match('/^(\+216|00216)?[2-9]\d{7}$/', $commande->getTelephone())) {
                throw new \InvalidArgumentException('Le numéro de téléphone doit être au format tunisien valide');
            }
        }

        return true;
    }

    /**
     * Calcule le total de la commande basé sur les articles
     * 
     * @param Commande $commande
     * @return float
     */
    public function calculateTotal(Commande $commande): float
    {
        $total = 0.0;
        foreach ($commande->getArticles() as $article) {
            $total += (float) ($article->getPrix() ?? 0);
        }
        return $total;
    }

    /**
     * Vérifie si une commande peut être annulée
     * 
     * @param Commande $commande
     * @return bool
     */
    public function canBeCancelled(Commande $commande): bool
    {
        $statutsAnnulables = ['en_attente', 'confirmee'];
        return in_array($commande->getStatut(), $statutsAnnulables, true);
    }

    /**
     * Génère un numéro de commande unique
     * 
     * @return string
     */
    public function generateNumero(): string
    {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
