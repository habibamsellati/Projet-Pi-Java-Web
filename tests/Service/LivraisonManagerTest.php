<?php

namespace App\Tests\Service;

use App\Entity\Livraison;
use App\Service\LivraisonManager;
use PHPUnit\Framework\TestCase;

class LivraisonManagerTest extends TestCase
{
    private LivraisonManager $manager;

    protected function setUp(): void
    {
        $this->manager = new LivraisonManager();
    }

    public function testValidLivraison(): void
    {
        $livraison = new Livraison();
        $livraison->setAddresslivraison('123 Avenue Habib Bourguiba, Tunis');
        $livraison->setStatutlivraison('en_attente');
        $livraison->setDatelivraison(new \DateTime('+1 day'));

        $this->assertTrue($this->manager->validate($livraison));
    }

    public function testAdresseMustHaveMinimum10Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'adresse de livraison doit contenir au moins 10 caractères');

        $livraison = new Livraison();
        $livraison->setAddresslivraison('Court');
        $livraison->setStatutlivraison('en_attente');

        $this->manager->validate($livraison);
    }

    public function testStatutMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être: en_attente, en_cours, livre ou annulee');

        $livraison = new Livraison();
        $livraison->setAddresslivraison('123 Avenue Habib Bourguiba, Tunis');
        $livraison->setStatutlivraison('statut_invalide');

        $this->manager->validate($livraison);
    }

    public function testDateCannotBeInPast(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de livraison ne peut pas être dans le passé');

        $livraison = new Livraison();
        $livraison->setAddresslivraison('123 Avenue Habib Bourguiba, Tunis');
        $livraison->setStatutlivraison('en_attente');
        $livraison->setDatelivraison(new \DateTime('-1 day'));

        $this->manager->validate($livraison);
    }

    public function testCanBeModifiedWhenEnAttente(): void
    {
        $livraison = new Livraison();
        $livraison->setStatutlivraison('en_attente');

        $this->assertTrue($this->manager->canBeModified($livraison));
    }

    public function testCannotBeModifiedWhenLivree(): void
    {
        $livraison = new Livraison();
        $livraison->setStatutlivraison('livre');

        $this->assertFalse($this->manager->canBeModified($livraison));
    }

    public function testIsDelivered(): void
    {
        $livraison = new Livraison();
        $livraison->setStatutlivraison('livre');

        $this->assertTrue($this->manager->isDelivered($livraison));
    }

    public function testIsNotDelivered(): void
    {
        $livraison = new Livraison();
        $livraison->setStatutlivraison('en_cours');

        $this->assertFalse($this->manager->isDelivered($livraison));
    }
}
