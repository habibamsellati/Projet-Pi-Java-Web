<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add titre and description columns to proposition table for App\Entity\Proposition.
 */
final class Version20260215170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add titre and description to proposition table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposition ADD titre VARCHAR(255) NOT NULL DEFAULT \'\', ADD description VARCHAR(255) NOT NULL DEFAULT \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposition DROP titre, DROP description');
    }
}
