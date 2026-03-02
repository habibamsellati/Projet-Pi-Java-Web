<?php

namespace App\Tests\Service;

use App\Entity\Commentaire;
use App\Service\CommentaireManager;
use PHPUnit\Framework\TestCase;

class CommentaireManagerTest extends TestCase
{
    private CommentaireManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CommentaireManager();
    }

    public function testValidCommentaire(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Ceci est un commentaire valide avec suffisamment de contenu.');

        $this->assertTrue($this->manager->validate($commentaire));
    }

    public function testContenuMustHaveMinimum10Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu doit contenir au moins 10 caractères');

        $commentaire = new Commentaire();
        $commentaire->setContenu('Court');

        $this->manager->validate($commentaire);
    }

    public function testContenuIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu doit contenir au moins 10 caractères');

        $commentaire = new Commentaire();
        $commentaire->setContenu('');

        $this->manager->validate($commentaire);
    }

    public function testGetWordCount(): void
    {
        $commentaire = new Commentaire();
        $commentaire->setContenu('Un deux trois quatre cinq six sept');

        $count = $this->manager->getWordCount($commentaire);

        $this->assertEquals(7, $count);
    }
}
