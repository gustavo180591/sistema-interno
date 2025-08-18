<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add completed_at field to ticket table
 */
final class Version20250818033807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add completed_at field to ticket table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket ADD COLUMN completed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket DROP COLUMN completed_at');
    }
} 