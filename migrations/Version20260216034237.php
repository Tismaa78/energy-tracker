<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216034237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alerte (id INT AUTO_INCREMENT NOT NULL, type_alerte VARCHAR(30) NOT NULL, message LONGTEXT NOT NULL, date_alerte DATETIME NOT NULL, seuil_declenche DOUBLE PRECISION NOT NULL, est_lue TINYINT(1) NOT NULL, consommation_id INT NOT NULL, type_energie_id INT NOT NULL, INDEX IDX_3AE753AC1076F84 (consommation_id), INDEX IDX_3AE753AAA317541 (type_energie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753AC1076F84 FOREIGN KEY (consommation_id) REFERENCES consommation (id)');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753AAA317541 FOREIGN KEY (type_energie_id) REFERENCES type_energie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753AC1076F84');
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753AAA317541');
        $this->addSql('DROP TABLE alerte');
    }
}
