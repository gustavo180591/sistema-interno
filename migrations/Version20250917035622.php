<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917035622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if the column already exists
        $table = $schema->getTable('ticket');
        if (!$table->hasColumn('assigned_to_id')) {
            $this->addSql('ALTER TABLE ticket ADD assigned_to_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
            $this->addSql('CREATE INDEX IDX_97A0ADA3F4BD7827 ON ticket (assigned_to_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3F4BD7827');
        $this->addSql('DROP INDEX IDX_97A0ADA3F4BD7827 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP assigned_to_id');
    }
}
