<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924220503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maintenance_task ADD origin_ticket_id INT DEFAULT NULL, CHANGE reopened reopened TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9CD8E03A4 FOREIGN KEY (origin_ticket_id) REFERENCES ticket (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9D6DBBE9CD8E03A4 ON maintenance_task (origin_ticket_id)');
        $this->addSql('ALTER TABLE ticket CHANGE area_origen area_origen VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9CD8E03A4');
        $this->addSql('DROP INDEX IDX_9D6DBBE9CD8E03A4 ON maintenance_task');
        $this->addSql('ALTER TABLE maintenance_task DROP origin_ticket_id, CHANGE reopened reopened TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ticket CHANGE area_origen area_origen VARCHAR(100) DEFAULT NULL');
    }
}
