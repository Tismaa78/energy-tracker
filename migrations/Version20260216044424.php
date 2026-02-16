<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216044424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE type_energie ADD user_id INT DEFAULT NULL');
        $this->addSql('UPDATE type_energie SET user_id = (SELECT id FROM user ORDER BY id ASC LIMIT 1) WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE type_energie MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE type_energie ADD CONSTRAINT FK_AA6B555A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AA6B555A76ED395 ON type_energie (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE type_energie DROP FOREIGN KEY FK_AA6B555A76ED395');
        $this->addSql('DROP INDEX IDX_AA6B555A76ED395 ON type_energie');
        $this->addSql('ALTER TABLE type_energie DROP user_id');
    }
}
