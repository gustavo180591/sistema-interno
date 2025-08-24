<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250824021500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove priority column from ticket table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket DROP COLUMN IF EXISTS priority');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket ADD priority VARCHAR(20) DEFAULT NULL');
    }
}
