<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208021853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, datecommande DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, total DOUBLE PRECISION NOT NULL, adresselivraison VARCHAR(255) NOT NULL, modepaiement VARCHAR(255) NOT NULL, livraison_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6EEAA67D8E54FB25 (livraison_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commande_article (commande_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_F4817CC682EA2E54 (commande_id), INDEX IDX_F4817CC67294869C (article_id), PRIMARY KEY (commande_id, article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nomproduit VARCHAR(150) NOT NULL, typemateriau VARCHAR(100) DEFAULT NULL, etat VARCHAR(80) DEFAULT NULL, quantite INT NOT NULL, origine VARCHAR(120) DEFAULT NULL, impactecologique DOUBLE PRECISION DEFAULT NULL, dateajout DATETIME DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE proposition (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date DATETIME NOT NULL, iduser INT NOT NULL, produit_id INT DEFAULT NULL, INDEX IDX_C7CDC353F347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE suivi_livraison (id INT AUTO_INCREMENT NOT NULL, datesuivi DATETIME NOT NULL, etat VARCHAR(255) NOT NULL, localisation VARCHAR(255) NOT NULL, commentaire LONGTEXT NOT NULL, livraison_id INT DEFAULT NULL, INDEX IDX_CFAC64718E54FB25 (livraison_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D8E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id)');
        $this->addSql('ALTER TABLE commande_article ADD CONSTRAINT FK_F4817CC682EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_article ADD CONSTRAINT FK_F4817CC67294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT FK_C7CDC353F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE suivi_livraison ADD CONSTRAINT FK_CFAC64718E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id)');
        $this->addSql('ALTER TABLE livreur DROP FOREIGN KEY `FK_EB7A4E6DF8646701`');
        $this->addSql('ALTER TABLE reservetion DROP FOREIGN KEY `FK_B748EF951C0913C2`');
        $this->addSql('DROP TABLE evenemen');
        $this->addSql('DROP TABLE evenement1');
        $this->addSql('DROP TABLE livreur');
        $this->addSql('DROP TABLE reservetion');
        $this->addSql('ALTER TABLE commentaire ADD user_id INT NOT NULL, DROP user');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_67F068BCA76ED395 ON commentaire (user_id)');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY `FK_B26681E5ED3C7B7`');
        $this->addSql('DROP INDEX IDX_B26681E5ED3C7B7 ON evenement');
        $this->addSql('ALTER TABLE evenement ADD datedebut DATETIME NOT NULL, ADD datefin DATETIME NOT NULL, DROP date_debut, DROP date_fin, CHANGE description description VARCHAR(255) NOT NULL, CHANGE nom nomevenement VARCHAR(255) NOT NULL, CHANGE artisan_id reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_B26681EB83297E7 ON evenement (reservation_id)');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY `FK_A60C9F1FF8646701`');
        $this->addSql('DROP INDEX IDX_A60C9F1FF8646701 ON livraison');
        $this->addSql('ALTER TABLE livraison ADD addresslivraison VARCHAR(255) NOT NULL, ADD statutlivraison VARCHAR(255) NOT NULL, DROP id_livraison, DROP id_formulaire, DROP adresse_client, DROP adresse_artisant, DROP statut_livraison, DROP livreur_id, CHANGE date_livraison datelivraison DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_42C84955A76ED395`');
        $this->addSql('DROP INDEX IDX_42C84955A76ED395 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP user_id, CHANGE date_reservation datereservation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649B83297E7 ON user (reservation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evenemen (id INT AUTO_INCREMENT NOT NULL, id_artisan INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, datedebut DATETIME NOT NULL, datefin DATETIME NOT NULL, lieu VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, capacite INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenement1 (id INT AUTO_INCREMENT NOT NULL, id_aartisan INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, datedebut DATETIME NOT NULL, datefin DATETIME NOT NULL, lieu VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, capacite INT NOT NULL, no VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE livreur (id INT AUTO_INCREMENT NOT NULL, id_livreur VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, nom_livreur VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, prenom_livreur VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, cantact_livreur VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, vehicule VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, livreur_id INT NOT NULL, INDEX IDX_EB7A4E6DF8646701 (livreur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservetion (id INT AUTO_INCREMENT NOT NULL, dateres DATETIME NOT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, evenemen_id INT NOT NULL, INDEX IDX_B748EF951C0913C2 (evenemen_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE livreur ADD CONSTRAINT `FK_EB7A4E6DF8646701` FOREIGN KEY (livreur_id) REFERENCES livraison (id)');
        $this->addSql('ALTER TABLE reservetion ADD CONSTRAINT `FK_B748EF951C0913C2` FOREIGN KEY (evenemen_id) REFERENCES evenemen (id)');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D8E54FB25');
        $this->addSql('ALTER TABLE commande_article DROP FOREIGN KEY FK_F4817CC682EA2E54');
        $this->addSql('ALTER TABLE commande_article DROP FOREIGN KEY FK_F4817CC67294869C');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_C7CDC353F347EFB');
        $this->addSql('ALTER TABLE suivi_livraison DROP FOREIGN KEY FK_CFAC64718E54FB25');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_article');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE proposition');
        $this->addSql('DROP TABLE suivi_livraison');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA76ED395');
        $this->addSql('DROP INDEX IDX_67F068BCA76ED395 ON commentaire');
        $this->addSql('ALTER TABLE commentaire ADD user VARCHAR(255) NOT NULL, DROP user_id');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EB83297E7');
        $this->addSql('DROP INDEX IDX_B26681EB83297E7 ON evenement');
        $this->addSql('ALTER TABLE evenement ADD date_debut DATETIME NOT NULL, ADD date_fin DATETIME NOT NULL, DROP datedebut, DROP datefin, CHANGE description description LONGTEXT NOT NULL, CHANGE nomevenement nom VARCHAR(255) NOT NULL, CHANGE reservation_id artisan_id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT `FK_B26681E5ED3C7B7` FOREIGN KEY (artisan_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B26681E5ED3C7B7 ON evenement (artisan_id)');
        $this->addSql('ALTER TABLE livraison ADD id_livraison VARCHAR(255) NOT NULL, ADD id_formulaire VARCHAR(255) NOT NULL, ADD adresse_client VARCHAR(255) NOT NULL, ADD adresse_artisant VARCHAR(255) NOT NULL, ADD statut_livraison VARCHAR(255) NOT NULL, ADD livreur_id INT NOT NULL, DROP addresslivraison, DROP statutlivraison, CHANGE datelivraison date_livraison DATETIME NOT NULL');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT `FK_A60C9F1FF8646701` FOREIGN KEY (livreur_id) REFERENCES livreur (id)');
        $this->addSql('CREATE INDEX IDX_A60C9F1FF8646701 ON livraison (livreur_id)');
        $this->addSql('ALTER TABLE reservation ADD user_id INT NOT NULL, CHANGE datereservation date_reservation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_42C84955A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B83297E7');
        $this->addSql('DROP INDEX IDX_8D93D649B83297E7 ON user');
        $this->addSql('ALTER TABLE user DROP reservation_id');
    }
}
