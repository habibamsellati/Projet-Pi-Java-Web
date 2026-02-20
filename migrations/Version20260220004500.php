<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220004500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set user FK constraints to ON DELETE SET NULL for article/reclamation/reponse_reclamation';
    }

    public function up(Schema $schema): void
    {
        $this->dropFkOnUser('article', 'artisan_id');
        $this->addSql('ALTER TABLE article CHANGE artisan_id artisan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_ARTISAN_SET_NULL FOREIGN KEY (artisan_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->dropFkOnUser('article', 'user_id');
        $this->addSql('ALTER TABLE article CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_USER_SET_NULL FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->dropFkOnUser('reclamation', 'user_id');
        $this->addSql('ALTER TABLE reclamation CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_RECLAMATION_USER_SET_NULL FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->dropFkOnUser('reponse_reclamation', 'admin_id');
        $this->addSql('ALTER TABLE reponse_reclamation CHANGE admin_id admin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_REPONSE_RECLAMATION_ADMIN_SET_NULL FOREIGN KEY (admin_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_ARTICLE_ARTISAN_SET_NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_ARTISAN_SET_NULL FOREIGN KEY (artisan_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_ARTICLE_USER_SET_NULL');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_USER_SET_NULL FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_RECLAMATION_USER_SET_NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_RECLAMATION_USER_SET_NULL FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_REPONSE_RECLAMATION_ADMIN_SET_NULL');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_REPONSE_RECLAMATION_ADMIN_SET_NULL FOREIGN KEY (admin_id) REFERENCES user (id)');
    }

    private function dropFkOnUser(string $table, string $column): void
    {
        $foreignKeys = $this->connection->fetchAllAssociative(
            "SELECT kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             WHERE kcu.TABLE_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = :tableName
               AND kcu.COLUMN_NAME = :columnName
               AND kcu.REFERENCED_TABLE_NAME = 'user'",
            [
                'tableName' => $table,
                'columnName' => $column,
            ]
        );

        foreach ($foreignKeys as $fk) {
            $name = (string) ($fk['CONSTRAINT_NAME'] ?? '');
            if ($name !== '') {
                $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY `%s`', $table, $name));
            }
        }
    }
}
