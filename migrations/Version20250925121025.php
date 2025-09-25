<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925121025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE note_read_status (id INT AUTO_INCREMENT NOT NULL, note_id INT NOT NULL, user_id INT NOT NULL, is_read TINYINT(1) NOT NULL, INDEX IDX_DC58642826ED0855 (note_id), INDEX IDX_DC586428A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note_read_status ADD CONSTRAINT FK_DC58642826ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note_read_status ADD CONSTRAINT FK_DC586428A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maintenance_task ADD origin_ticket_id INT DEFAULT NULL, ADD within_sla TINYINT(1) DEFAULT NULL, ADD reopened TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9CD8E03A4 FOREIGN KEY (origin_ticket_id) REFERENCES ticket (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9D6DBBE9CD8E03A4 ON maintenance_task (origin_ticket_id)');
        $this->addSql('ALTER TABLE ticket ADD assigned_to_id INT DEFAULT NULL, CHANGE area_origen area_origen VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3B4ADCBD9 ON ticket (id_sistema_interno)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3F4BD7827 ON ticket (assigned_to_id)');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note_read_status DROP FOREIGN KEY FK_DC58642826ED0855');
        $this->addSql('ALTER TABLE note_read_status DROP FOREIGN KEY FK_DC586428A76ED395');
        $this->addSql('DROP TABLE note_read_status');
        $this->addSql('ALTER TABLE `user` CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3F4BD7827');
        $this->addSql('DROP INDEX UNIQ_97A0ADA3B4ADCBD9 ON ticket');
        $this->addSql('DROP INDEX IDX_97A0ADA3F4BD7827 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP assigned_to_id, CHANGE area_origen area_origen VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9CD8E03A4');
        $this->addSql('DROP INDEX IDX_9D6DBBE9CD8E03A4 ON maintenance_task');
        $this->addSql('ALTER TABLE maintenance_task DROP origin_ticket_id, DROP within_sla, DROP reopened');
    }
}
