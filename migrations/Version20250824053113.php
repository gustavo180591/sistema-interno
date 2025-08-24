<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824053113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD completed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA385ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA385ECDE76 ON ticket (completed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA385ECDE76');
        $this->addSql('DROP INDEX IDX_97A0ADA385ECDE76 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP completed_by_id');
    }
}
