<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250829172821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE machine (id INT AUTO_INCREMENT NOT NULL, office_id INT NOT NULL, inventory_number VARCHAR(50) NOT NULL, ram_gb INT NOT NULL, institutional TINYINT(1) NOT NULL, cpu VARCHAR(100) DEFAULT NULL, os VARCHAR(80) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_1505DF84964C83FF (inventory_number), INDEX IDX_1505DF84FFA0C224 (office_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE office (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, location VARCHAR(180) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE machine ADD CONSTRAINT FK_1505DF84FFA0C224 FOREIGN KEY (office_id) REFERENCES office (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE machine DROP FOREIGN KEY FK_1505DF84FFA0C224');
        $this->addSql('DROP TABLE machine');
        $this->addSql('DROP TABLE office');
    }
}
