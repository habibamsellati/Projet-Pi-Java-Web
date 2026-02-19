<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix evenement and reservation tables to match App\Entity\Evenement and App\Entity\Reservation.
 * - evenement: nom, artisan, date_debut, date_fin (was nomevenement, datedebut, datefin); remove reservation_id.
 * - reservation: created_at, date_reservation, user_id (was datereservation; add created_at, user_id).
 */
final class Version20260215190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align evenement and reservation tables with Entity mapping (nom, artisan, date_debut/date_fin; created_at, date_reservation, user_id)';
    }

    public function up(Schema $schema): void
    {
        $evenementTable = $this->sm->introspectTable('evenement');

        // Evenement: drop FK and reservation_id only if they exist
        if ($evenementTable->hasColumn('reservation_id')) {
            foreach ($evenementTable->getForeignKeyConstraints() as $fk) {
                if (in_array('reservation_id', $fk->getLocalColumns(), true)) {
                    $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY ' . $fk->getName());
                    break;
                }
            }
            foreach ($evenementTable->getIndexes() as $idx) {
                if ($idx->getColumns() === ['reservation_id']) {
                    $this->addSql('DROP INDEX ' . $idx->getName() . ' ON evenement');
                    break;
                }
            }
            $this->addSql('ALTER TABLE evenement DROP reservation_id');
        }

        // Add/rename columns to match Entity
        if ($evenementTable->hasColumn('nomevenement')) {
            $this->addSql('ALTER TABLE evenement ADD nom VARCHAR(255) DEFAULT NULL, ADD artisan VARCHAR(255) DEFAULT NULL');
            $this->addSql('UPDATE evenement SET nom = COALESCE(nomevenement, \'\')');
            $this->addSql('ALTER TABLE evenement CHANGE nom nom VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE evenement DROP nomevenement');
        } elseif (!$evenementTable->hasColumn('nom')) {
            $this->addSql('ALTER TABLE evenement ADD nom VARCHAR(255) NOT NULL, ADD artisan VARCHAR(255) DEFAULT NULL');
        }
        if ($evenementTable->hasColumn('datedebut')) {
            $this->addSql('ALTER TABLE evenement CHANGE datedebut date_debut DATETIME NOT NULL, CHANGE datefin date_fin DATETIME NOT NULL');
        }
        $this->addSql('ALTER TABLE evenement CHANGE description description LONGTEXT NOT NULL');

        // Reservation: add created_at, user_id; rename datereservation -> date_reservation
        $reservationTable = $this->sm->introspectTable('reservation');
        if (!$reservationTable->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE reservation ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
        if (!$reservationTable->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE reservation ADD user_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
            $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        }
        if ($reservationTable->hasColumn('datereservation')) {
            $this->addSql('ALTER TABLE reservation CHANGE datereservation date_reservation DATETIME NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('DROP INDEX IDX_42C84955A76ED395 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP created_at, DROP user_id');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation datereservation DATETIME NOT NULL');

        $this->addSql('ALTER TABLE evenement ADD nomevenement VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('UPDATE evenement SET nomevenement = nom');
        $this->addSql('ALTER TABLE evenement CHANGE nomevenement nomevenement VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE evenement DROP nom, DROP artisan');
        $this->addSql('ALTER TABLE evenement CHANGE date_debut datedebut DATETIME NOT NULL, CHANGE date_fin datefin DATETIME NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_B26681EB83297E7 ON evenement (reservation_id)');
    }
}
