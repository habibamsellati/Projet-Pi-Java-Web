<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add added_by_id to produit table (client who added the product).
 */
final class Version20260215180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add added_by_id to produit for client ownership';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit MODIFY added_by_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC2755B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_29A5EC2755B127A4 ON produit (added_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC2755B127A4');
        $this->addSql('DROP INDEX IDX_29A5EC2755B127A4 ON produit');
        $this->addSql('ALTER TABLE produit DROP added_by_id');
    }
}
