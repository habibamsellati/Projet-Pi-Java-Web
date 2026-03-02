<?php

namespace App\Tests\Service;

use App\Entity\Commande;
use App\Service\CommandeManager;
use PHPUnit\Framework\TestCase;

class CommandeManagerTest extends TestCase
{
    private CommandeManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CommandeManager();
    }

    public function testValidCommande(): void
    {
        $commande = new Commande();
        $commande->setTotal(150.50);
        $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');
        $commande->setTelephone('+21698765432');
        $commande->setStatut('en_attente');
        $commande->setModepaiement('carte');
        $commande->setDatecommande(new \DateTime());

        $this->assertTrue($this->manager->validate($commande));
    }

    public function testTotalMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le total de la commande doit être supérieur à zéro');

        $commande = new Commande();
        $commande->setTotal(0);
        $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');

        $this->manager->validate($commande);
    }

    public function testNegativeTotalIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le total de la commande doit être supérieur à zéro');

        $commande = new Commande();
        $commande->setTotal(-50);
        $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');

        $this->manager->validate($commande);
    }

    public function testAdresseMustHaveMinimum10Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'adresse de livraison doit contenir au moins 10 caractères');

        $commande = new Commande();
        $commande->setTotal(100);
        $commande->setAdresselivraison('Court');

        $this->manager->validate($commande);
    }

    public function testAdresseIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'adresse de livraison doit contenir au moins 10 caractères');

        $commande = new Commande();
        $commande->setTotal(100);
        $commande->setAdresselivraison('');

        $this->manager->validate($commande);
    }

    public function testPhoneMustBeValidTunisianFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit être au format tunisien valide');

        $commande = new Commande();
        $commande->setTotal(100);
        $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');
        $commande->setTelephone('123456');

        $this->manager->validate($commande);
    }

    public function testValidTunisianPhoneFormats(): void
    {
        $validPhones = ['+21698765432', '0021698765432', '98765432', '22345678'];

        foreach ($validPhones as $phone) {
            $commande = new Commande();
            $commande->setTotal(100);
            $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');
            $commande->setTelephone($phone);

            $this->assertTrue($this->manager->validate($commande));
        }
    }

    public function testCommandeWithoutPhoneIsValid(): void
    {
        $commande = new Commande();
        $commande->setTotal(100);
        $commande->setAdresselivraison('123 Avenue Habib Bourguiba, Tunis');
        $commande->setTelephone(null);

        $this->assertTrue($this->manager->validate($commande));
    }

    public function testCanBeCancelledWhenEnAttente(): void
    {
        $commande = new Commande();
        $commande->setStatut('en_attente');

        $this->assertTrue($this->manager->canBeCancelled($commande));
    }

    public function testCanBeCancelledWhenConfirmee(): void
    {
        $commande = new Commande();
        $commande->setStatut('confirmee');

        $this->assertTrue($this->manager->canBeCancelled($commande));
    }

    public function testCannotBeCancelledWhenLivree(): void
    {
        $commande = new Commande();
        $commande->setStatut('livree');

        $this->assertFalse($this->manager->canBeCancelled($commande));
    }

    public function testGenerateNumeroFormat(): void
    {
        $numero = $this->manager->generateNumero();

        $this->assertStringStartsWith('CMD-', $numero);
        $this->assertMatchesRegularExpression('/^CMD-\d{8}-[A-Z0-9]{6}$/', $numero);
    }
}
