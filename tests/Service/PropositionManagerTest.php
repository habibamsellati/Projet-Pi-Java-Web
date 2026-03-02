<?php

namespace App\Tests\Service;

use App\Entity\Proposition;
use App\Service\PropositionManager;
use PHPUnit\Framework\TestCase;

class PropositionManagerTest extends TestCase
{
    private PropositionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new PropositionManager();
    }

    /**
     * Test: Une proposition valide doit passer la validation
     */
    public function testValidProposition(): void
    {
        $proposition = new Proposition();
        $proposition->setPrixPropose(150.50);
        $proposition->setClientPhone('+21698765432');
        $proposition->setStatut('en_attente');

        $this->assertTrue($this->manager->validate($proposition));
    }

    /**
     * Test: Le prix proposé doit être supérieur à zéro
     */
    public function testPrixProposeMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix proposé doit être supérieur à zéro');

        $proposition = new Proposition();
        $proposition->setPrixPropose(0); // Prix invalide
        $proposition->setClientPhone('+21698765432');
        $proposition->setStatut('en_attente');

        $this->manager->validate($proposition);
    }

    /**
     * Test: Le prix négatif doit être rejeté
     */
    public function testNegativePrixIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix proposé doit être supérieur à zéro');

        $proposition = new Proposition();
        $proposition->setPrixPropose(-50); // Prix négatif
        $proposition->setClientPhone('+21698765432');
        $proposition->setStatut('en_attente');

        $this->manager->validate($proposition);
    }

    /**
     * Test: Le téléphone doit être au format tunisien valide
     */
    public function testPhoneMustBeValidTunisianFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit être au format tunisien valide');

        $proposition = new Proposition();
        $proposition->setPrixPropose(100);
        $proposition->setClientPhone('123456'); // Format invalide
        $proposition->setStatut('en_attente');

        $this->manager->validate($proposition);
    }

    /**
     * Test: Différents formats de téléphone tunisien valides
     */
    public function testValidTunisianPhoneFormats(): void
    {
        $validPhones = [
            '+21698765432',
            '0021698765432',
            '98765432',
            '22345678',
        ];

        foreach ($validPhones as $phone) {
            $proposition = new Proposition();
            $proposition->setPrixPropose(100);
            $proposition->setClientPhone($phone);
            $proposition->setStatut('en_attente');

            $this->assertTrue($this->manager->validate($proposition), "Le téléphone {$phone} devrait être valide");
        }
    }

    /**
     * Test: Le statut doit être valide
     */
    public function testStatutMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être: en_attente, acceptee, refusee ou terminee');

        $proposition = new Proposition();
        $proposition->setPrixPropose(100);
        $proposition->setClientPhone('+21698765432');
        $proposition->setStatut('statut_invalide'); // Statut invalide

        $this->manager->validate($proposition);
    }

    /**
     * Test: Tous les statuts valides sont acceptés
     */
    public function testAllValidStatutsAreAccepted(): void
    {
        $statutsValides = ['en_attente', 'acceptee', 'refusee', 'terminee'];

        foreach ($statutsValides as $statut) {
            $proposition = new Proposition();
            $proposition->setPrixPropose(100);
            $proposition->setClientPhone('+21698765432');
            $proposition->setStatut($statut);

            $this->assertTrue($this->manager->validate($proposition));
        }
    }

    /**
     * Test: Une proposition en attente peut être acceptée
     */
    public function testPropositionEnAttenteCanBeAccepted(): void
    {
        $proposition = new Proposition();
        $proposition->setStatut('en_attente');

        $this->assertTrue($this->manager->canBeAccepted($proposition));
    }

    /**
     * Test: Une proposition acceptée ne peut plus être acceptée
     */
    public function testAcceptedPropositionCannotBeAcceptedAgain(): void
    {
        $proposition = new Proposition();
        $proposition->setStatut('acceptee');

        $this->assertFalse($this->manager->canBeAccepted($proposition));
    }

    /**
     * Test: Calcul du pourcentage de réduction
     */
    public function testCalculateDiscount(): void
    {
        $proposition = new Proposition();
        $proposition->setPrixPropose(80);

        $prixInitial = 100;
        $discount = $this->manager->calculateDiscount($proposition, $prixInitial);

        $this->assertEquals(20, $discount); // 20% de réduction
    }

    /**
     * Test: Calcul de réduction avec prix initial zéro
     */
    public function testCalculateDiscountWithZeroInitialPrice(): void
    {
        $proposition = new Proposition();
        $proposition->setPrixPropose(50);

        $discount = $this->manager->calculateDiscount($proposition, 0);

        $this->assertEquals(0, $discount);
    }

    /**
     * Test: Une proposition sans téléphone est valide
     */
    public function testPropositionWithoutPhoneIsValid(): void
    {
        $proposition = new Proposition();
        $proposition->setPrixPropose(100);
        $proposition->setClientPhone(null); // Pas de téléphone
        $proposition->setStatut('en_attente');

        $this->assertTrue($this->manager->validate($proposition));
    }
}
