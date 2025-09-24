<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250924033408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add withinSla and reopened fields to maintenance_task table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maintenance_task ADD within_sla TINYINT(1) DEFAULT NULL, ADD reopened TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE maintenance_task DROP within_sla, DROP reopened');
    }
}
