<?php

namespace App\Service;

use App\Entity\Produit;

class ProduitManager
{
    public function validate(Produit $produit): bool
    {
        if (empty($produit->getNomproduit()) || strlen($produit->getNomproduit()) < 3) {
            throw new \InvalidArgumentException('Le nom du produit doit contenir au moins 3 caractères');
        }

        if ($produit->getImpactecologique() !== null && $produit->getImpactecologique() < 0) {
            throw new \InvalidArgumentException('L\'impact écologique ne peut pas être négatif');
        }

        if ($produit->getQuantite() !== null && $produit->getQuantite() < 0) {
            throw new \InvalidArgumentException('La quantité ne peut pas être négative');
        }

        return true;
    }

    public function isInStock(Produit $produit): bool
    {
        return ($produit->getQuantite() ?? 0) > 0;
    }

    public function decrementStock(Produit $produit, int $quantity): void
    {
        $currentStock = $produit->getQuantite() ?? 0;
        if ($currentStock < $quantity) {
            throw new \InvalidArgumentException('Stock insuffisant');
        }
        $produit->setQuantite($currentStock - $quantity);
    }
}
