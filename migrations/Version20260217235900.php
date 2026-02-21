<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217235900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les reactions like/dislike sur les commentaires';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commentaire_reaction (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, user_id INT NOT NULL, type SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B00D7A8F67F068BC (commentaire_id), INDEX IDX_B00D7A8FA76ED395 (user_id), UNIQUE INDEX uniq_commentaire_user_reaction (commentaire_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT FK_B00D7A8F67F068BC FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_reaction ADD CONSTRAINT FK_B00D7A8FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY FK_B00D7A8F67F068BC');
        $this->addSql('ALTER TABLE commentaire_reaction DROP FOREIGN KEY FK_B00D7A8FA76ED395');
        $this->addSql('DROP TABLE commentaire_reaction');
    }
}

