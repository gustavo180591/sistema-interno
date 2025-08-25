<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825040519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Remove priority column if it exists
        $this->addSql('ALTER TABLE maintenance_task DROP COLUMN IF EXISTS priority');
    }

    public function down(Schema $schema): void
    {
        // Add back the priority column if needed
        $this->addSql('ALTER TABLE maintenance_task ADD priority VARCHAR(20) DEFAULT NULL');
    }
}
