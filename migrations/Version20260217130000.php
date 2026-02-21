<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add oauth provider fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD oauth_provider VARCHAR(30) DEFAULT NULL, ADD oauth_provider_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6494D4D66BC ON user (oauth_provider, oauth_provider_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_8D93D6494D4D66BC ON user');
        $this->addSql('ALTER TABLE user DROP oauth_provider, DROP oauth_provider_id');
    }
}
