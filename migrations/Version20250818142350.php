<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818142350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE role ADD COLUMN created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__role AS SELECT id, name, role_name, description FROM role');
        $this->addSql('DROP TABLE role');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, role_name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO role (id, name, role_name, description) SELECT id, name, role_name, description FROM __temp__role');
        $this->addSql('DROP TABLE __temp__role');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6A5E237E06 ON role (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6AE09C0C92 ON role (role_name)');
    }
}
