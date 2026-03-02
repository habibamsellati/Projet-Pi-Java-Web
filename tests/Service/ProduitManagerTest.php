<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\ProduitManager;
use PHPUnit\Framework\TestCase;

class ProduitManagerTest extends TestCase
{
    private ProduitManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ProduitManager();
    }

    public function testValidProduit(): void
    {
        $produit = new Produit();
        $produit->setNomproduit('Chaise en bois');
        $produit->setImpactecologique(50.0);
        $produit->setQuantite(10);

        $this->assertTrue($this->manager->validate($produit));
    }

    public function testNomMustHaveMinimum3Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit doit contenir au moins 3 caractères');

        $produit = new Produit();
        $produit->setNomproduit('AB');

        $this->manager->validate($produit);
    }

    public function testImpactEcologiqueCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'impact écologique ne peut pas être négatif');

        $produit = new Produit();
        $produit->setNomproduit('Chaise');
        $produit->setImpactecologique(-10.0);

        $this->manager->validate($produit);
    }

    public function testQuantiteCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La quantité ne peut pas être négative');

        $produit = new Produit();
        $produit->setNomproduit('Chaise');
        $produit->setQuantite(-5);

        $this->manager->validate($produit);
    }

    public function testIsInStockWhenQuantityPositive(): void
    {
        $produit = new Produit();
        $produit->setQuantite(10);

        $this->assertTrue($this->manager->isInStock($produit));
    }

    public function testIsNotInStockWhenQuantityZero(): void
    {
        $produit = new Produit();
        $produit->setQuantite(0);

        $this->assertFalse($this->manager->isInStock($produit));
    }

    public function testDecrementStock(): void
    {
        $produit = new Produit();
        $produit->setQuantite(10);

        $this->manager->decrementStock($produit, 3);

        $this->assertEquals(7, $produit->getQuantite());
    }

    public function testDecrementStockThrowsExceptionWhenInsufficient(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $produit = new Produit();
        $produit->setQuantite(5);

        $this->manager->decrementStock($produit, 10);
    }
}
