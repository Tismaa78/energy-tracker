<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216040816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alerte CHANGE seuil_declenche seuil_declenche DOUBLE PRECISION DEFAULT NULL, CHANGE type_energie_id type_energie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE consommation ADD periode_debut DATE DEFAULT NULL, ADD periode_fin DATE DEFAULT NULL, ADD source_saisie VARCHAR(20) DEFAULT \'manuel\', ADD logement_id INT DEFAULT NULL');
        $this->addSql('UPDATE consommation SET periode_debut = date_consommation, periode_fin = date_consommation, source_saisie = \'manuel\' WHERE date_consommation IS NOT NULL');
        $this->addSql('ALTER TABLE consommation DROP date_consommation');
        $this->addSql('ALTER TABLE consommation ADD CONSTRAINT FK_F993F0A258ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('CREATE INDEX IDX_F993F0A258ABF955 ON consommation (logement_id)');
        $this->addSql('ALTER TABLE objectif ADD type_energie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE objectif ADD CONSTRAINT FK_E2F86851AA317541 FOREIGN KEY (type_energie_id) REFERENCES type_energie (id)');
        $this->addSql('CREATE INDEX IDX_E2F86851AA317541 ON objectif (type_energie_id)');
        $this->addSql('ALTER TABLE type_energie ADD tarif_unitaire DOUBLE PRECISION DEFAULT NULL, ADD icone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(100) DEFAULT NULL, ADD prenom VARCHAR(100) DEFAULT NULL, ADD date_inscription DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alerte CHANGE seuil_declenche seuil_declenche DOUBLE PRECISION NOT NULL, CHANGE type_energie_id type_energie_id INT NOT NULL');
        $this->addSql('ALTER TABLE consommation DROP FOREIGN KEY FK_F993F0A258ABF955');
        $this->addSql('DROP INDEX IDX_F993F0A258ABF955 ON consommation');
        $this->addSql('ALTER TABLE consommation ADD date_consommation DATE NOT NULL, DROP periode_debut, DROP periode_fin, DROP source_saisie, DROP logement_id');
        $this->addSql('ALTER TABLE objectif DROP FOREIGN KEY FK_E2F86851AA317541');
        $this->addSql('DROP INDEX IDX_E2F86851AA317541 ON objectif');
        $this->addSql('ALTER TABLE objectif DROP type_energie_id');
        $this->addSql('ALTER TABLE type_energie DROP tarif_unitaire, DROP icone');
        $this->addSql('ALTER TABLE user DROP nom, DROP prenom, DROP date_inscription');
    }
}
