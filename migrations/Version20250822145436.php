<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822145436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD taken_by_id INT DEFAULT NULL, ADD taken_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA317F014F6 FOREIGN KEY (taken_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA317F014F6 ON ticket (taken_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA317F014F6');
        $this->addSql('DROP INDEX IDX_97A0ADA317F014F6 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP taken_by_id, DROP taken_at');
    }
}
