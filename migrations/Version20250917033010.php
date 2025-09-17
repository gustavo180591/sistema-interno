<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917033010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE machine (id INT AUTO_INCREMENT NOT NULL, office_id INT NOT NULL, inventory_number VARCHAR(50) NOT NULL, ram_gb INT NOT NULL, institutional TINYINT(1) NOT NULL, cpu VARCHAR(100) DEFAULT NULL, os VARCHAR(80) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, disk VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_1505DF84964C83FF (inventory_number), INDEX IDX_1505DF84FFA0C224 (office_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, frequency VARCHAR(20) NOT NULL, frequency_value INT DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_log (id INT AUTO_INCREMENT NOT NULL, task_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, message LONGTEXT NOT NULL, details JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_26CA3DF38DB60186 (task_id), INDEX IDX_26CA3DF3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_task (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, completed_by_id INT DEFAULT NULL, machine_id INT DEFAULT NULL, office_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, scheduled_date DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, notes LONGTEXT DEFAULT NULL, actual_duration INT DEFAULT NULL, priority VARCHAR(20) DEFAULT \'normal\' NOT NULL, estimated_duration INT DEFAULT NULL, attachments JSON DEFAULT NULL, checklist JSON DEFAULT NULL, INDEX IDX_9D6DBBE912469DE2 (category_id), INDEX IDX_9D6DBBE9F4BD7827 (assigned_to_id), INDEX IDX_9D6DBBE985ECDE76 (completed_by_id), INDEX IDX_9D6DBBE9F6B75B26 (machine_id), INDEX IDX_9D6DBBE9FFA0C224 (office_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, created_by_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_CFBDFA14700047D2 (ticket_id), INDEX IDX_CFBDFA14B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE office (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, location VARCHAR(180) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, completed_by_id INT DEFAULT NULL, created_by_id INT NOT NULL, taken_by_id INT DEFAULT NULL, proposed_by_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, observation LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, taken_at DATETIME DEFAULT NULL, area_origen VARCHAR(100) DEFAULT NULL, id_sistema_interno VARCHAR(50) DEFAULT NULL, due_date DATE DEFAULT NULL, proposed_status VARCHAR(20) DEFAULT NULL, proposal_note LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_97A0ADA3B4ADCBD9 (id_sistema_interno), INDEX IDX_97A0ADA385ECDE76 (completed_by_id), INDEX IDX_97A0ADA3B03A8386 (created_by_id), INDEX IDX_97A0ADA317F014F6 (taken_by_id), INDEX IDX_97A0ADA3DAB5A938 (proposed_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_assignment (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, user_id INT NOT NULL, assigned_at DATETIME NOT NULL, INDEX IDX_A656D6EE700047D2 (ticket_id), INDEX IDX_A656D6EEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket_update (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, changes JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_E2675FB3700047D2 (ticket_id), INDEX IDX_E2675FB3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) DEFAULT NULL, apellido VARCHAR(100) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, username VARCHAR(60) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reset_token VARCHAR(100) DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE machine ADD CONSTRAINT FK_1505DF84FFA0C224 FOREIGN KEY (office_id) REFERENCES office (id)');
        $this->addSql('ALTER TABLE maintenance_log ADD CONSTRAINT FK_26CA3DF38DB60186 FOREIGN KEY (task_id) REFERENCES maintenance_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maintenance_log ADD CONSTRAINT FK_26CA3DF3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE912469DE2 FOREIGN KEY (category_id) REFERENCES maintenance_category (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE985ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9F6B75B26 FOREIGN KEY (machine_id) REFERENCES machine (id)');
        $this->addSql('ALTER TABLE maintenance_task ADD CONSTRAINT FK_9D6DBBE9FFA0C224 FOREIGN KEY (office_id) REFERENCES office (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA385ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA317F014F6 FOREIGN KEY (taken_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3DAB5A938 FOREIGN KEY (proposed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket_assignment ADD CONSTRAINT FK_A656D6EE700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE ticket_assignment ADD CONSTRAINT FK_A656D6EEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE ticket_update ADD CONSTRAINT FK_E2675FB3700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE ticket_update ADD CONSTRAINT FK_E2675FB3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE machine DROP FOREIGN KEY FK_1505DF84FFA0C224');
        $this->addSql('ALTER TABLE maintenance_log DROP FOREIGN KEY FK_26CA3DF38DB60186');
        $this->addSql('ALTER TABLE maintenance_log DROP FOREIGN KEY FK_26CA3DF3A76ED395');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE912469DE2');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9F4BD7827');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE985ECDE76');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9F6B75B26');
        $this->addSql('ALTER TABLE maintenance_task DROP FOREIGN KEY FK_9D6DBBE9FFA0C224');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14700047D2');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14B03A8386');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA385ECDE76');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3B03A8386');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA317F014F6');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3DAB5A938');
        $this->addSql('ALTER TABLE ticket_assignment DROP FOREIGN KEY FK_A656D6EE700047D2');
        $this->addSql('ALTER TABLE ticket_assignment DROP FOREIGN KEY FK_A656D6EEA76ED395');
        $this->addSql('ALTER TABLE ticket_update DROP FOREIGN KEY FK_E2675FB3700047D2');
        $this->addSql('ALTER TABLE ticket_update DROP FOREIGN KEY FK_E2675FB3A76ED395');
        $this->addSql('DROP TABLE machine');
        $this->addSql('DROP TABLE maintenance_category');
        $this->addSql('DROP TABLE maintenance_log');
        $this->addSql('DROP TABLE maintenance_task');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE office');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE ticket_assignment');
        $this->addSql('DROP TABLE ticket_update');
        $this->addSql('DROP TABLE `user`');
    }
}
