<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220001500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Article artisan_id: make nullable and use ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        // Drop any existing FK on article.artisan_id to user.id (name may vary by DB history).
        $foreignKeys = $this->connection->fetchAllAssociative(
            "SELECT kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             WHERE kcu.TABLE_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = 'article'
               AND kcu.COLUMN_NAME = 'artisan_id'
               AND kcu.REFERENCED_TABLE_NAME = 'user'"
        );

        foreach ($foreignKeys as $fk) {
            $name = (string) ($fk['CONSTRAINT_NAME'] ?? '');
            if ($name !== '') {
                $this->addSql(sprintf('ALTER TABLE article DROP FOREIGN KEY `%s`', $name));
            }
        }

        $this->addSql('ALTER TABLE article CHANGE artisan_id artisan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_ARTISAN_SET_NULL FOREIGN KEY (artisan_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_ARTICLE_ARTISAN_SET_NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_ARTISAN_SET_NULL FOREIGN KEY (artisan_id) REFERENCES user (id)');
    }
}

