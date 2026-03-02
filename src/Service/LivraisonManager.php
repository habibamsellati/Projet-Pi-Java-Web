<?php

namespace App\Service;

use App\Entity\Livraison;

class LivraisonManager
{
    public function validate(Livraison $livraison): bool
    {
        if (empty($livraison->getAddresslivraison()) || strlen($livraison->getAddresslivraison()) < 10) {
            throw new \InvalidArgumentException('L\'adresse de livraison doit contenir au moins 10 caractères');
        }

        $statutsValides = ['en_attente', 'en_cours', 'livre', 'annulee'];
        if (!in_array($livraison->getStatutlivraison(), $statutsValides, true)) {
            throw new \InvalidArgumentException('Le statut doit être: en_attente, en_cours, livre ou annulee');
        }

        if ($livraison->getDatelivraison() && $livraison->getDatelivraison() < new \DateTime('today')) {
            throw new \InvalidArgumentException('La date de livraison ne peut pas être dans le passé');
        }

        return true;
    }

    public function canBeModified(Livraison $livraison): bool
    {
        return in_array($livraison->getStatutlivraison(), ['en_attente', 'en_cours'], true);
    }

    public function isDelivered(Livraison $livraison): bool
    {
        return $livraison->getStatutlivraison() === 'livre';
    }
}
