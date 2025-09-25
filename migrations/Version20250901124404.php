<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250901124404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maintenance_task ADD machine_id INT DEFAULT NULL, ADD office_id INT DEFAULT NULL, ADD priority VARCHAR(20) DEFAULT \'normal\' NOT NULL, ADD estimated_duration INT DEFAULT NULL, ADD attachments JSON DEFAULT NULL, ADD checklist JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9FFA0C224 FOREIGN KEY (office_id) REFERENCES office (id)');
        $this->addSql('CREATE INDEX IDX_9D6DBBE9F6B75B26 ON maintenance_task (machine_id)');
        $this->addSql('CREATE INDEX IDX_9D6DBBE9FFA0C224 ON maintenance_task (office_id)');
//        $this->addSql('ALTER TABLE office DROP area');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE office ADD area VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9F6B75B26');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9FFA0C224');
        $this->addSql('DROP INDEX IDX_9D6DBBE9F6B75B26 ON maintenance_task');
        $this->addSql('DROP INDEX IDX_9D6DBBE9FFA0C224 ON maintenance_task');
        $this->addSql('ALTER TABLE maintenance_task DROP machine_id, DROP office_id, DROP priority, DROP estimated_duration, DROP attachments, DROP checklist');
    }
}
