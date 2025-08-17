<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817083256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ticket_collaborator (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ticket_id INTEGER NOT NULL, user_id INTEGER NOT NULL, joined_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_A0BBB4D7700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A0BBB4D7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A0BBB4D7700047D2 ON ticket_collaborator (ticket_id)');
        $this->addSql('CREATE INDEX IDX_A0BBB4D7A76ED395 ON ticket_collaborator (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ticket AS SELECT id, created_at, ticket_id, descripcion, departamento, estado FROM ticket');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('CREATE TABLE ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , ticket_id VARCHAR(50) NOT NULL, descripcion CLOB NOT NULL, departamento SMALLINT NOT NULL, estado VARCHAR(20) NOT NULL, CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ticket (id, created_at, ticket_id, descripcion, departamento, estado) SELECT id, created_at, ticket_id, descripcion, departamento, estado FROM __temp__ticket');
        $this->addSql('DROP TABLE __temp__ticket');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3700047D2 ON ticket (ticket_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3B03A8386 ON ticket (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, is_verified, nombre, apellido FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, is_verified, nombre, apellido) SELECT id, email, roles, password, is_verified, nombre, apellido FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ticket_collaborator');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ticket AS SELECT id, created_at, ticket_id, descripcion, departamento, estado FROM ticket');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('CREATE TABLE ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, ticket_id VARCHAR(50) NOT NULL, descripcion CLOB NOT NULL, departamento SMALLINT NOT NULL, estado VARCHAR(20) NOT NULL)');
        $this->addSql('INSERT INTO ticket (id, created_at, ticket_id, descripcion, departamento, estado) SELECT id, created_at, ticket_id, descripcion, departamento, estado FROM __temp__ticket');
        $this->addSql('DROP TABLE __temp__ticket');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, is_verified, nombre, apellido FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, is_verified, nombre, apellido) SELECT id, email, roles, password, is_verified, nombre, apellido FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
