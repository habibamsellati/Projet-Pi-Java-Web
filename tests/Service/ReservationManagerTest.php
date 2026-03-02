<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Entity\Evenement;
use App\Service\ReservationManager;
use PHPUnit\Framework\TestCase;

class ReservationManagerTest extends TestCase
{
    private ReservationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ReservationManager();
    }

    public function testValidReservation(): void
    {
        $reservation = new Reservation();
        $reservation->setNbPlaces(2);
        $reservation->setStatut('confirmee');

        $this->assertTrue($this->manager->validate($reservation));
    }

    public function testNombreplacesMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de places doit être supérieur à zéro');

        $reservation = new Reservation();
        $reservation->setNbPlaces(0);

        $this->manager->validate($reservation);
    }

    public function testNegativePlacesIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de places doit être supérieur à zéro');

        $reservation = new Reservation();
        $reservation->setNbPlaces(-5);

        $this->manager->validate($reservation);
    }

    public function testStatutMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être: confirmee, annulee ou en_attente');

        $reservation = new Reservation();
        $reservation->setNbPlaces(2);
        $reservation->setStatut('statut_invalide');

        $this->manager->validate($reservation);
    }

    public function testCanBeCancelledWhenConfirmee(): void
    {
        $reservation = new Reservation();
        $reservation->setStatut('confirmee');

        $this->assertTrue($this->manager->canBeCancelled($reservation));
    }

    public function testCannotBeCancelledWhenAnnulee(): void
    {
        $reservation = new Reservation();
        $reservation->setStatut('annulee');

        $this->assertFalse($this->manager->canBeCancelled($reservation));
    }

    public function testGetTotalPrice(): void
    {
        $evenement = new Evenement();
        $evenement->setPrix('50.00');

        $reservation = new Reservation();
        $reservation->setEvenement($evenement);
        $reservation->setNbPlaces(3);

        $total = $this->manager->getTotalPrice($reservation);

        $this->assertEquals(150.0, $total);
    }

    public function testGetTotalPriceWithoutEvenement(): void
    {
        $reservation = new Reservation();
        $reservation->setNbPlaces(3);

        $total = $this->manager->getTotalPrice($reservation);

        $this->assertEquals(0.0, $total);
    }
}
