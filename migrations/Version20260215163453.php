<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215163453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_historique CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE action_historique RENAME INDEX idx_action_historique_admin TO IDX_8E1DBF26642B8210');
        $this->addSql('ALTER TABLE article CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE artisan_id artisan_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66f5c5e5ce TO IDX_23A0E665ED3C7B7');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66fa76ed395 TO IDX_23A0E66A76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `FK_commande_livraison`');
        $this->addSql('ALTER TABLE commande CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE livraison_id livraison_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D8E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D19EB6921 FOREIGN KEY (client_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6EEAA67D19EB6921 ON commande (client_id)');
        $this->addSql('ALTER TABLE commande RENAME INDEX uniq_6eeaa67d8e54f25 TO UNIQ_6EEAA67D8E54FB25');
        $this->addSql('ALTER TABLE commande_article DROP FOREIGN KEY `FK_commande_article_article`');
        $this->addSql('ALTER TABLE commande_article DROP FOREIGN KEY `FK_commande_article_commande`');
        $this->addSql('ALTER TABLE commande_article CHANGE commande_id commande_id INT NOT NULL, CHANGE article_id article_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_article RENAME INDEX idx_commande_article_article TO IDX_F4817CC67294869C');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `FK_commentaire_article`');
        $this->addSql('ALTER TABLE commentaire CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE article_id article_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE commentaire RENAME INDEX idx_commentaire_article TO IDX_67F068BC7294869C');
        $this->addSql('ALTER TABLE commentaire RENAME INDEX idx_commentaire_user TO IDX_67F068BCA76ED395');
        $this->addSql('ALTER TABLE evenement ADD reservation_id INT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_B26681EB83297E7 ON evenement (reservation_id)');
        $this->addSql('ALTER TABLE livraison CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE statutlivraison statutlivraison VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT FK_A60C9F1F82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT FK_A60C9F1FF8646701 FOREIGN KEY (livreur_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A60C9F1F82EA2E54 ON livraison (commande_id)');
        $this->addSql('CREATE INDEX IDX_A60C9F1FF8646701 ON livraison (livreur_id)');
        $this->addSql('ALTER TABLE produit CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nomproduit nomproduit VARCHAR(150) NOT NULL, CHANGE typemateriau typemateriau VARCHAR(100) DEFAULT NULL, CHANGE etat etat VARCHAR(80) DEFAULT NULL, CHANGE origine origine VARCHAR(120) DEFAULT NULL, CHANGE impactecologique impactecologique DOUBLE PRECISION DEFAULT NULL, CHANGE dateajout dateajout DATETIME DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY `FK_proposition_produit`');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY `FK_proposition_user`');
        $this->addSql('ALTER TABLE proposition CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE produit_id produit_id INT DEFAULT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE datesoumision date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT FK_C7CDC353A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT FK_C7CDC353F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE proposition RENAME INDEX idx_proposition_user TO IDX_C7CDC353A76ED395');
        $this->addSql('ALTER TABLE proposition RENAME INDEX idx_proposition_produit TO IDX_C7CDC353F347EFB');
        $this->addSql('ALTER TABLE reclamation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX idx_reclamation_user TO IDX_CE606404A76ED395');
        $this->addSql('ALTER TABLE reponse_reclamation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE reclamation_id reclamation_id INT NOT NULL, CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_reponse_reclamation_reclamation TO IDX_C7CB51012D6BA2D9');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_reponse_reclamation_admin TO IDX_C7CB5101642B8210');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_reservation_user`');
        $this->addSql('DROP INDEX IDX_reservation_user ON reservation');
        $this->addSql('ALTER TABLE reservation DROP user_id, DROP created_at, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE evenement_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_reservation_evenement TO IDX_42C84955FD02F13');
        $this->addSql('ALTER TABLE suivi_livraison DROP FOREIGN KEY `FK_suivi_livraison_livraison`');
        $this->addSql('ALTER TABLE suivi_livraison CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE livraison_id livraison_id INT NOT NULL, CHANGE commentaire commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE suivi_livraison ADD CONSTRAINT FK_CFAC64718E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id)');
        $this->addSql('ALTER TABLE suivi_livraison RENAME INDEX idx_suivi_livraison_livraison TO IDX_CFAC64718E54FB25');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action_historique CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE admin_id admin_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE action_historique RENAME INDEX idx_8e1dbf26642b8210 TO IDX_action_historique_admin');
        $this->addSql('ALTER TABLE article CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE artisan_id artisan_id INT UNSIGNED NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e665ed3c7b7 TO IDX_23A0E66F5C5E5CE');
        $this->addSql('ALTER TABLE article RENAME INDEX idx_23a0e66a76ed395 TO IDX_23A0E66FA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D8E54FB25');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D19EB6921');
        $this->addSql('DROP INDEX IDX_6EEAA67D19EB6921 ON commande');
        $this->addSql('ALTER TABLE commande CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE livraison_id livraison_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_commande_livraison` FOREIGN KEY (livraison_id) REFERENCES livraison (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande RENAME INDEX uniq_6eeaa67d8e54fb25 TO UNIQ_6EEAA67D8E54F25');
        $this->addSql('ALTER TABLE commande_article CHANGE commande_id commande_id INT UNSIGNED NOT NULL, CHANGE article_id article_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE commande_article RENAME INDEX idx_f4817cc67294869c TO IDX_commande_article_article');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC7294869C');
        $this->addSql('ALTER TABLE commentaire CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE article_id article_id INT UNSIGNED NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `FK_commentaire_article` FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire RENAME INDEX idx_67f068bc7294869c TO IDX_commentaire_article');
        $this->addSql('ALTER TABLE commentaire RENAME INDEX idx_67f068bca76ed395 TO IDX_commentaire_user');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EB83297E7');
        $this->addSql('DROP INDEX IDX_B26681EB83297E7 ON evenement');
        $this->addSql('ALTER TABLE evenement DROP reservation_id, CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY FK_A60C9F1F82EA2E54');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY FK_A60C9F1FF8646701');
        $this->addSql('DROP INDEX UNIQ_A60C9F1F82EA2E54 ON livraison');
        $this->addSql('DROP INDEX IDX_A60C9F1FF8646701 ON livraison');
        $this->addSql('ALTER TABLE livraison CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE statutlivraison statutlivraison VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE nomproduit nomproduit VARCHAR(255) NOT NULL, CHANGE typemateriau typemateriau VARCHAR(255) NOT NULL, CHANGE etat etat VARCHAR(255) NOT NULL, CHANGE origine origine VARCHAR(255) NOT NULL, CHANGE impactecologique impactecologique DOUBLE PRECISION NOT NULL, CHANGE dateajout dateajout DATETIME NOT NULL, CHANGE description description VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_C7CDC353A76ED395');
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_C7CDC353F347EFB');
        $this->addSql('ALTER TABLE proposition CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL, CHANGE produit_id produit_id INT UNSIGNED NOT NULL, CHANGE date datesoumision DATETIME NOT NULL');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT `FK_proposition_produit` FOREIGN KEY (produit_id) REFERENCES produit (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT `FK_proposition_user` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE proposition RENAME INDEX idx_c7cdc353a76ed395 TO IDX_proposition_user');
        $this->addSql('ALTER TABLE proposition RENAME INDEX idx_c7cdc353f347efb TO IDX_proposition_produit');
        $this->addSql('ALTER TABLE reclamation CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX idx_ce606404a76ed395 TO IDX_reclamation_user');
        $this->addSql('ALTER TABLE reponse_reclamation CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE reclamation_id reclamation_id INT UNSIGNED NOT NULL, CHANGE admin_id admin_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_c7cb5101642b8210 TO IDX_reponse_reclamation_admin');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_c7cb51012d6ba2d9 TO IDX_reponse_reclamation_reclamation');
        $this->addSql('ALTER TABLE reservation ADD user_id INT UNSIGNED DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE evenement_id evenement_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_reservation_user` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_reservation_user ON reservation (user_id)');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c84955fd02f13 TO IDX_reservation_evenement');
        $this->addSql('ALTER TABLE suivi_livraison DROP FOREIGN KEY FK_CFAC64718E54FB25');
        $this->addSql('ALTER TABLE suivi_livraison CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE commentaire commentaire LONGTEXT NOT NULL, CHANGE livraison_id livraison_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE suivi_livraison ADD CONSTRAINT `FK_suivi_livraison_livraison` FOREIGN KEY (livraison_id) REFERENCES livraison (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE suivi_livraison RENAME INDEX idx_cfac64718e54fb25 TO IDX_suivi_livraison_livraison');
        $this->addSql('ALTER TABLE user CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }
}
