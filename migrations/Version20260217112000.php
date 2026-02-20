<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset attributes directly on user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD reset_token_hash VARCHAR(128) DEFAULT NULL, ADD reset_token_created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reset_token_request_ip VARCHAR(45) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649C12FE7EC ON user (reset_token_hash)');
        $this->addSql('CREATE INDEX IDX_8D93D649E95B5B4D ON user (reset_token_expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_8D93D649C12FE7EC ON user');
        $this->addSql('DROP INDEX IDX_8D93D649E95B5B4D ON user');
        $this->addSql('ALTER TABLE user DROP reset_token_hash, DROP reset_token_created_at, DROP reset_token_expires_at, DROP reset_token_request_ip');
    }
}
