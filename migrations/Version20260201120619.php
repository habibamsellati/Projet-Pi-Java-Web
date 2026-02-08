<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201120619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration déjà appliquée, table article existante.';
    }

    public function up(Schema $schema): void
    {
        // Migration vide : table article existe déjà, donc rien à faire
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback nécessaire
    }
}
