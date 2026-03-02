<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Service\ArticleManager;
use PHPUnit\Framework\TestCase;

class ArticleManagerTest extends TestCase
{
    private ArticleManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ArticleManager();
    }

    /**
     * Test: Un article valide doit passer la validation
     */
    public function testValidArticle(): void
    {
        $article = new Article();
        $article->setTitre('Article sur le recyclage');
        $article->setContenu('Ceci est un contenu suffisamment long pour être valide et informatif.');
        $article->setLikes(5);

        $this->assertTrue($this->manager->validate($article));
    }

    /**
     * Test: Le titre est obligatoire
     */
    public function testTitreIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 5 caractères');

        $article = new Article();
        $article->setTitre(''); // Titre vide
        $article->setContenu('Contenu valide avec plus de vingt caractères.');

        $this->manager->validate($article);
    }

    /**
     * Test: Le titre doit contenir au moins 5 caractères
     */
    public function testTitreMustHaveMinimum5Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire et doit contenir au moins 5 caractères');

        $article = new Article();
        $article->setTitre('Test'); // Titre trop court (4 caractères)
        $article->setContenu('Contenu valide avec plus de vingt caractères.');

        $this->manager->validate($article);
    }

    /**
     * Test: Le contenu doit contenir au moins 20 caractères
     */
    public function testContenuMustHaveMinimum20Characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu doit contenir au moins 20 caractères');

        $article = new Article();
        $article->setTitre('Titre valide');
        $article->setContenu('Court'); // Contenu trop court

        $this->manager->validate($article);
    }

    /**
     * Test: Le contenu est obligatoire
     */
    public function testContenuIsRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le contenu doit contenir au moins 20 caractères');

        $article = new Article();
        $article->setTitre('Titre valide');
        $article->setContenu(''); // Contenu vide

        $this->manager->validate($article);
    }

    /**
     * Test: Le nombre de likes ne peut pas être négatif
     */
    public function testLikesCannotBeNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de likes ne peut pas être négatif');

        $article = new Article();
        $article->setTitre('Titre valide');
        $article->setContenu('Contenu valide avec plus de vingt caractères.');
        $article->setLikes(-5); // Likes négatifs

        $this->manager->validate($article);
    }

    /**
     * Test: Un article avec zéro like est valide
     */
    public function testArticleWithZeroLikesIsValid(): void
    {
        $article = new Article();
        $article->setTitre('Titre valide');
        $article->setContenu('Contenu valide avec plus de vingt caractères.');
        $article->setLikes(0);

        $this->assertTrue($this->manager->validate($article));
    }

    /**
     * Test: Incrémenter les likes
     */
    public function testIncrementLikes(): void
    {
        $article = new Article();
        $article->setLikes(5);

        $newLikes = $this->manager->incrementLikes($article);

        $this->assertEquals(6, $newLikes);
        $this->assertEquals(6, $article->getLikes());
    }

    /**
     * Test: Incrémenter les likes depuis zéro
     */
    public function testIncrementLikesFromZero(): void
    {
        $article = new Article();
        $article->setLikes(0);

        $newLikes = $this->manager->incrementLikes($article);

        $this->assertEquals(1, $newLikes);
    }

    /**
     * Test: Incrémenter les likes quand null
     */
    public function testIncrementLikesWhenNull(): void
    {
        $article = new Article();
        // Ne pas définir les likes (laisser à 0 par défaut)

        $newLikes = $this->manager->incrementLikes($article);

        $this->assertEquals(1, $newLikes);
    }

    /**
     * Test: Un article est populaire avec plus de 10 likes
     */
    public function testArticleIsPopularWithMoreThan10Likes(): void
    {
        $article = new Article();
        $article->setLikes(15);

        $this->assertTrue($this->manager->isPopular($article));
    }

    /**
     * Test: Un article n'est pas populaire avec 10 likes ou moins
     */
    public function testArticleIsNotPopularWith10OrLessLikes(): void
    {
        $article = new Article();
        $article->setLikes(10);

        $this->assertFalse($this->manager->isPopular($article));
    }

    /**
     * Test: Un article sans likes n'est pas populaire
     */
    public function testArticleWithoutLikesIsNotPopular(): void
    {
        $article = new Article();
        $article->setLikes(0);

        $this->assertFalse($this->manager->isPopular($article));
    }

    /**
     * Test: Compter les mots dans le contenu
     */
    public function testGetWordCount(): void
    {
        $article = new Article();
        $article->setContenu('Un deux trois quatre cinq six sept huit neuf.');

        $wordCount = $this->manager->getWordCount($article);

        $this->assertEquals(9, $wordCount);
    }

    /**
     * Test: Compter les mots avec HTML
     */
    public function testGetWordCountWithHtml(): void
    {
        $article = new Article();
        $article->setContenu('<p>Ceci est un <strong>test</strong> avec HTML.</p>');

        $wordCount = $this->manager->getWordCount($article);

        $this->assertEquals(6, $wordCount); // Les balises HTML sont ignorées
    }
}
