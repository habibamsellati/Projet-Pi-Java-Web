<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219214623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY `FK_DF2DB4E07294869C`');
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY `FK_DF2DB4E0A76ED395`');
        $this->addSql('DROP INDEX idx_df2db4e07294869c ON article_like');
        $this->addSql('CREATE INDEX IDX_1C21C7B27294869C ON article_like (article_id)');
        $this->addSql('DROP INDEX idx_df2db4e0a76ed395 ON article_like');
        $this->addSql('CREATE INDEX IDX_1C21C7B2A76ED395 ON article_like (user_id)');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT `FK_DF2DB4E07294869C` FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT `FK_DF2DB4E0A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY `FK_B00D7A8F67F068BC`');
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY `FK_B00D7A8FA76ED395`');
        $this->addSql('ALTER TABLE commentaire_reaction CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_b00d7a8f67f068bc ON commentaire_reaction');
        $this->addSql('CREATE INDEX IDX_56C6CF2BBA9CD190 ON commentaire_reaction (commentaire_id)');
        $this->addSql('DROP INDEX idx_b00d7a8fa76ed395 ON commentaire_reaction');
        $this->addSql('CREATE INDEX IDX_56C6CF2BA76ED395 ON commentaire_reaction (user_id)');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT `FK_B00D7A8F67F068BC` FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT `FK_B00D7A8FA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation CHANGE descripition descripition VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY FK_1C21C7B27294869C');
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY FK_1C21C7B2A76ED395');
        $this->addSql('DROP INDEX idx_1c21c7b27294869c ON article_like');
        $this->addSql('CREATE INDEX IDX_DF2DB4E07294869C ON article_like (article_id)');
        $this->addSql('DROP INDEX idx_1c21c7b2a76ed395 ON article_like');
        $this->addSql('CREATE INDEX IDX_DF2DB4E0A76ED395 ON article_like (user_id)');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT FK_1C21C7B27294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT FK_1C21C7B2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY FK_56C6CF2BBA9CD190');
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY FK_56C6CF2BA76ED395');
        $this->addSql('ALTER TABLE commentaire_reaction CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_56c6cf2bba9cd190 ON commentaire_reaction');
        $this->addSql('CREATE INDEX IDX_B00D7A8F67F068BC ON commentaire_reaction (commentaire_id)');
        $this->addSql('DROP INDEX idx_56c6cf2ba76ed395 ON commentaire_reaction');
        $this->addSql('CREATE INDEX IDX_B00D7A8FA76ED395 ON commentaire_reaction (user_id)');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT FK_56C6CF2BBA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT FK_56C6CF2BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation CHANGE descripition descripition TEXT DEFAULT NULL');
    }
}
