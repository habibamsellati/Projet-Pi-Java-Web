<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202105846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu VARCHAR(255) NOT NULL, date DATE NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu VARCHAR(255) NOT NULL, datepub DATETIME NOT NULL, user VARCHAR(255) NOT NULL, article_id INT NOT NULL, INDEX IDX_67F068BC7294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livraison (id INT AUTO_INCREMENT NOT NULL, datelivraison DATETIME NOT NULL, addresslivraison VARCHAR(255) NOT NULL, statutlivraison VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, descripition VARCHAR(255) NOT NULL, datecreation DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_CE606404A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse_reclamation (id INT AUTO_INCREMENT NOT NULL, contenu VARCHAR(255) NOT NULL, datereponse DATETIME NOT NULL, reclamation_id INT NOT NULL, admin_id INT NOT NULL, INDEX IDX_C7CB51012D6BA2D9 (reclamation_id), INDEX IDX_C7CB5101642B8210 (admin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE suivi_livraison (id INT AUTO_INCREMENT NOT NULL, datesuivi DATETIME NOT NULL, etat VARCHAR(255) NOT NULL, localisation VARCHAR(255) NOT NULL, commentaire LONGTEXT NOT NULL, livraison_id INT DEFAULT NULL, INDEX IDX_CFAC64718E54FB25 (livraison_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, motdepasse VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, datecreation DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB51012D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id)');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB5101642B8210 FOREIGN KEY (admin_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE suivi_livraison ADD CONSTRAINT FK_CFAC64718E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC7294869C');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404A76ED395');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB51012D6BA2D9');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB5101642B8210');
        $this->addSql('ALTER TABLE suivi_livraison DROP FOREIGN KEY FK_CFAC64718E54FB25');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE livraison');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('DROP TABLE reponse_reclamation');
        $this->addSql('DROP TABLE suivi_livraison');
        $this->addSql('DROP TABLE user');
    }
}
