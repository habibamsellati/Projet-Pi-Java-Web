<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206184658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit CHANGE nomproduit nomproduit VARCHAR(150) NOT NULL, CHANGE typemateriau typemateriau VARCHAR(100) DEFAULT NULL, CHANGE etat etat VARCHAR(80) DEFAULT NULL, CHANGE origine origine VARCHAR(120) DEFAULT NULL, CHANGE impactecologique impactecologique DOUBLE PRECISION DEFAULT NULL, CHANGE dateajout dateajout DATETIME DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY `FK_C7CDC353F347EFB`');
        $this->addSql('DROP INDEX IDX_C7CDC353F347EFB ON proposition');
        $this->addSql('ALTER TABLE proposition CHANGE description description VARCHAR(500) NOT NULL, CHANGE produit_id id_produit INT NOT NULL');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT FK_C7CDC353F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_C7CDC353F7384557 ON proposition (id_produit)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit CHANGE nomproduit nomproduit VARCHAR(255) NOT NULL, CHANGE typemateriau typemateriau VARCHAR(255) NOT NULL, CHANGE etat etat VARCHAR(255) NOT NULL, CHANGE origine origine VARCHAR(255) NOT NULL, CHANGE impactecologique impactecologique DOUBLE PRECISION NOT NULL, CHANGE dateajout dateajout DATETIME NOT NULL, CHANGE description description VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_C7CDC353F7384557');
        $this->addSql('DROP INDEX IDX_C7CDC353F7384557 ON proposition');
        $this->addSql('ALTER TABLE proposition CHANGE description description VARCHAR(255) NOT NULL, CHANGE id_produit produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT `FK_C7CDC353F347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_C7CDC353F347EFB ON proposition (produit_id)');
    }
}
