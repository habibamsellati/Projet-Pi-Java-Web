<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EvenementManager;
use PHPUnit\Framework\TestCase;

class EvenementManagerTest extends TestCase
{
    private EvenementManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EvenementManager();
    }

    /**
     * Test: Un événement valide doit passer la validation
     */
    public function testValidEvenement(): void
    {
        $evenement = new Evenement();
        $evenement->setNom('Exposition d\'Art');
        $evenement->setDescription('Une belle exposition');
        $evenement->setDateDebut(new \DateTime('2026-04-01'));
        $evenement->setDateFin(new \DateTime('2026-04-10'));
        $evenement->setLieu('Tunis');
        $evenement->setCapacite(100);
        $evenement->setPrix('50.00');

        $this->assertTrue($this->manager->validate($evenement));
    }

    /**
     * Test: La date de fin doit être postérieure à la date de début
     */
    public function testDateFinMustBeAfterDateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');

        $evenement = new Evenement();
        $evenement->setDateDebut(new \DateTime('2026-04-10'));
        $evenement->setDateFin(new \DateTime('2026-04-01')); // Date de fin avant date de début
        $evenement->setCapacite(100);
        $evenement->setPrix('50.00');

        $this->manager->validate($evenement);
    }

    /**
     * Test: La capacité doit être positive
     */
    public function testCapaciteMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être un nombre positif');

        $evenement = new Evenement();
        $evenement->setDateDebut(new \DateTime('2026-04-01'));
        $evenement->setDateFin(new \DateTime('2026-04-10'));
        $evenement->setCapacite(0); // Capacité invalide
        $evenement->setPrix('50.00');

        $this->manager->validate($evenement);
    }

    /**
     * Test: La capacité négative doit être rejetée
     */
    public function testNegativeCapacityIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être un nombre positif');

        $evenement = new Evenement();
        $evenement->setDateDebut(new \DateTime('2026-04-01'));
        $evenement->setDateFin(new \DateTime('2026-04-10'));
        $evenement->setCapacite(-10); // Capacité négative
        $evenement->setPrix('50.00');

        $this->manager->validate($evenement);
    }

    /**
     * Test: Le prix ne peut pas être négatif
     */
    public function testPrixCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix ne peut pas être négatif');

        $evenement = new Evenement();
        $evenement->setDateDebut(new \DateTime('2026-04-01'));
        $evenement->setDateFin(new \DateTime('2026-04-10'));
        $evenement->setCapacite(100);
        $evenement->setPrix('-10.00'); // Prix négatif

        $this->manager->validate($evenement);
    }

    /**
     * Test: Un événement avec prix zéro est valide (gratuit)
     */
    public function testPrixZeroIsValid(): void
    {
        $evenement = new Evenement();
        $evenement->setDateDebut(new \DateTime('2026-04-01'));
        $evenement->setDateFin(new \DateTime('2026-04-10'));
        $evenement->setCapacite(100);
        $evenement->setPrix('0.00'); // Événement gratuit

        $this->assertTrue($this->manager->validate($evenement));
    }

    /**
     * Test: Vérifier s'il reste des places disponibles
     */
    public function testHasAvailableSeats(): void
    {
        $evenement = new Evenement();
        $evenement->setCapacite(100);

        // Pas de réservations, donc places disponibles
        $this->assertTrue($this->manager->hasAvailableSeats($evenement));
    }

    /**
     * Test: Calculer le nombre de places restantes
     */
    public function testGetRemainingSeats(): void
    {
        $evenement = new Evenement();
        $evenement->setCapacite(100);

        // Pas de réservations
        $this->assertEquals(100, $this->manager->getRemainingSeats($evenement));
    }
}
