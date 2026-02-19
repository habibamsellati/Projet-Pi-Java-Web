<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sync schema with current entities: add only missing columns (safe for old DB).
 */
final class Version20260214120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing article/reclamation columns (idempotent)';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $article = $sm->introspectTable('article');
        $reclamation = $sm->introspectTable('reclamation');

        // article: add only missing columns
        if (!$article->hasColumn('image')) {
            $this->addSql('ALTER TABLE article ADD image VARCHAR(255) DEFAULT NULL');
        }
        if (!$article->hasColumn('prix')) {
            $this->addSql('ALTER TABLE article ADD prix NUMERIC(10, 2) DEFAULT NULL');
        }
        if (!$article->hasColumn('categorie')) {
            $this->addSql('ALTER TABLE article ADD categorie VARCHAR(100) DEFAULT NULL');
        }
        if (!$article->hasColumn('artisan_id')) {
            $this->addSql('ALTER TABLE article ADD artisan_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66B5E3F35A FOREIGN KEY (artisan_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_23A0E66B5E3F35A ON article (artisan_id)');
        }
        if (!$article->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE article ADD user_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_23A0E66A76ED395 ON article (user_id)');
        }
        $this->addSql('ALTER TABLE article MODIFY contenu LONGTEXT NOT NULL');

        // reclamation: descripition nullable
        $this->addSql('ALTER TABLE reclamation MODIFY descripition VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $article = $sm->introspectTable('article');

        if ($article->hasColumn('artisan_id')) {
            $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66B5E3F35A');
            $this->addSql('DROP INDEX IDX_23A0E66B5E3F35A ON article');
            $this->addSql('ALTER TABLE article DROP artisan_id');
        }
        if ($article->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66A76ED395');
            $this->addSql('DROP INDEX IDX_23A0E66A76ED395 ON article');
            $this->addSql('ALTER TABLE article DROP user_id');
        }
        if ($article->hasColumn('image')) {
            $this->addSql('ALTER TABLE article DROP image');
        }
        if ($article->hasColumn('prix')) {
            $this->addSql('ALTER TABLE article DROP prix');
        }
        if ($article->hasColumn('categorie')) {
            $this->addSql('ALTER TABLE article DROP categorie');
        }
        $this->addSql('ALTER TABLE article MODIFY contenu VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE reclamation MODIFY descripition VARCHAR(255) NOT NULL');
    }
}
