<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    public function testValidUser(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setTelephone('+21698765432');
        $user->setRole('CLIENT');

        $this->assertTrue($this->manager->validate($user));
    }

    public function testNomMustHaveMinimum2Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 2 caractères');

        $user = new User();
        $user->setNom('A');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');

        $this->manager->validate($user);
    }

    public function testPrenomMustHaveMinimum2Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom doit contenir au moins 2 caractères');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('J');
        $user->setEmail('jean@example.com');

        $this->manager->validate($user);
    }

    public function testEmailMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email doit être valide');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('email_invalide');

        $this->manager->validate($user);
    }

    public function testPhoneMustBeValidTunisianFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit être au format tunisien valide');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setTelephone('123456');

        $this->manager->validate($user);
    }

    public function testRoleMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle doit être: CLIENT, ADMIN, ARTISANT ou LIVREUR');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('ROLE_INVALIDE');

        $this->manager->validate($user);
    }

    public function testIsActiveWhenStatutActif(): void
    {
        $user = new User();
        $user->setStatut('actif');

        $this->assertTrue($this->manager->isActive($user));
    }

    public function testIsNotActiveWhenStatutInactif(): void
    {
        $user = new User();
        $user->setStatut('inactif');

        $this->assertFalse($this->manager->isActive($user));
    }

    public function testIsNotActiveWhenDeleted(): void
    {
        $user = new User();
        $user->setStatut('actif');
        $user->setDeletedAt(new \DateTime());

        $this->assertFalse($this->manager->isActive($user));
    }

    public function testHasRole(): void
    {
        $user = new User();
        $user->setRole('CLIENT');

        $this->assertTrue($this->manager->hasRole($user, 'CLIENT'));
        $this->assertFalse($this->manager->hasRole($user, 'ADMIN'));
    }
}
