<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923133237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add area_origen column to ticket table
        $this->addSql('ALTER TABLE ticket ADD area_origen VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove area_origen column from ticket table
        $this->addSql('ALTER TABLE ticket DROP area_origen');
    }
}
