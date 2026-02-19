<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214143226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_historique (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, target_user_email VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, description VARCHAR(500) DEFAULT NULL, admin_id INT NOT NULL, INDEX IDX_8E1DBF26642B8210 (admin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE action_historique ADD CONSTRAINT FK_8E1DBF26642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `FK_6EEAA67D19EB6921`');
        $this->addSql('DROP INDEX IDX_6EEAA67D19EB6921 ON commande');
        $this->addSql('ALTER TABLE commande DROP client_id, DROP numero');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `FK_8D93D649B83297E7`');
        $this->addSql('DROP INDEX IDX_8D93D649B83297E7 ON user');
        $this->addSql('ALTER TABLE user DROP reservation_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE action_historique DROP FOREIGN KEY FK_8E1DBF26642B8210');
        $this->addSql('DROP TABLE action_historique');
        $this->addSql('ALTER TABLE commande ADD client_id INT DEFAULT NULL, ADD numero VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_6EEAA67D19EB6921` FOREIGN KEY (client_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6EEAA67D19EB6921 ON commande (client_id)');
        $this->addSql('ALTER TABLE user ADD reservation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT `FK_8D93D649B83297E7` FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649B83297E7 ON user (reservation_id)');
    }
}
