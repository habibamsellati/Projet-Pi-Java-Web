<?php

namespace App\Tests\Service;

use App\Entity\Reclamation;
use App\Service\ReclamationManager;
use PHPUnit\Framework\TestCase;

class ReclamationManagerTest extends TestCase
{
    private ReclamationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ReclamationManager();
    }

    /**
     * Test: Une réclamation valide doit passer la validation
     */
    public function testValidReclamation(): void
    {
        $reclamation = new Reclamation();
        $reclamation->setTitre('Problème de livraison');
        $reclamation->setDescripition('Ma commande n\'est pas arrivée à temps');
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime());

        $this->assertTrue($this->manager->validate($reclamation));
    }

    /**
     * Test: Le titre est obligatoire
     */
    public function testTitreIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 3 caractères');

        $reclamation = new Reclamation();
        $reclamation->setTitre(''); // Titre vide
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime());

        $this->manager->validate($reclamation);
    }

    /**
     * Test: Le titre doit contenir au moins 3 caractères
     */
    public function testTitreMustHaveMinimum3Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 3 caractères');

        $reclamation = new Reclamation();
        $reclamation->setTitre('AB'); // Titre trop court
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime());

        $this->manager->validate($reclamation);
    }

    /**
     * Test: Le statut doit être valide
     */
    public function testStatutMustBeValid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être: en_attente, en_cours, resolu ou rejete');

        $reclamation = new Reclamation();
        $reclamation->setTitre('Problème');
        $reclamation->setStatut('statut_invalide'); // Statut invalide
        $reclamation->setDatecreation(new \DateTime());

        $this->manager->validate($reclamation);
    }

    /**
     * Test: La description doit contenir au moins 10 caractères si fournie
     */
    public function testDescriptionMustHaveMinimum10Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères');

        $reclamation = new Reclamation();
        $reclamation->setTitre('Problème');
        $reclamation->setDescripition('Court'); // Description trop courte
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime());

        $this->manager->validate($reclamation);
    }

    /**
     * Test: Une réclamation sans description est valide
     */
    public function testReclamationWithoutDescriptionIsValid(): void
    {
        $reclamation = new Reclamation();
        $reclamation->setTitre('Problème de livraison');
        $reclamation->setDescripition(null); // Pas de description
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime());

        $this->assertTrue($this->manager->validate($reclamation));
    }

    /**
     * Test: Tous les statuts valides sont acceptés
     */
    public function testAllValidStatutsAreAccepted(): void
    {
        $statutsValides = ['en_attente', 'en_cours', 'resolu', 'rejete'];

        foreach ($statutsValides as $statut) {
            $reclamation = new Reclamation();
            $reclamation->setTitre('Problème');
            $reclamation->setStatut($statut);
            $reclamation->setDatecreation(new \DateTime());

            $this->assertTrue($this->manager->validate($reclamation));
        }
    }

    /**
     * Test: Vérifier si une réclamation est en attente depuis plus de 7 jours
     */
    public function testIsPendingForMoreThan7Days(): void
    {
        $reclamation = new Reclamation();
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime('-8 days')); // 8 jours dans le passé

        $this->assertTrue($this->manager->isPending($reclamation));
    }

    /**
     * Test: Une réclamation récente n'est pas considérée comme en attente trop longtemps
     */
    public function testRecentReclamationIsNotPending(): void
    {
        $reclamation = new Reclamation();
        $reclamation->setStatut('en_attente');
        $reclamation->setDatecreation(new \DateTime('-3 days')); // 3 jours dans le passé

        $this->assertFalse($this->manager->isPending($reclamation));
    }

    /**
     * Test: Une réclamation résolue ne peut pas être en attente
     */
    public function testResolvedReclamationIsNotPending(): void
    {
        $reclamation = new Reclamation();
        $reclamation->setStatut('resolu');
        $reclamation->setDatecreation(new \DateTime('-10 days'));

        $this->assertFalse($this->manager->isPending($reclamation));
    }
}
