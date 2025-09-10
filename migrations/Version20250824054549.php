<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824054549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE maintenance_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, frequency VARCHAR(20) NOT NULL, frequency_value INT DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_log (id INT AUTO_INCREMENT NOT NULL, task_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, details JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_26CA3DF38DB60186 (task_id), INDEX IDX_26CA3DF3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_task (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, completed_by_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, scheduled_date DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, estimated_duration INT DEFAULT NULL, actual_duration INT DEFAULT NULL, INDEX IDX_9D6DBBE912469DE2 (category_id), INDEX IDX_9D6DBBE9F4BD7827 (assigned_to_id), INDEX IDX_9D6DBBE985ECDE76 (completed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE maintenance_log ADD CONSTRAINT FK_26CA3DF38DB60186 FOREIGN KEY (task_id) REFERENCES maintenance_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maintenance_log ADD CONSTRAINT FK_26CA3DF3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE912469DE2 FOREIGN KEY (category_id) REFERENCES maintenance_category (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE985ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maintenance_log DROP FOREIGN KEY FK_26CA3DF38DB60186');
        $this->addSql('ALTER TABLE maintenance_log DROP FOREIGN KEY FK_26CA3DF3A76ED395');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE912469DE2');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9F4BD7827');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE985ECDE76');
        $this->addSql('DROP TABLE maintenance_category');
        $this->addSql('DROP TABLE maintenance_log');
        $this->addSql('DROP TABLE maintenance_task');
    }
}
