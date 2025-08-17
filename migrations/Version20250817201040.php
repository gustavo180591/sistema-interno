<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817201040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket ADD COLUMN pedido VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__ticket AS SELECT id, created_by_id, created_at, ticket_id, descripcion, departamento, estado FROM ticket');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('CREATE TABLE ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_by_id INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , ticket_id VARCHAR(50) NOT NULL, descripcion CLOB NOT NULL, departamento SMALLINT NOT NULL, estado VARCHAR(20) NOT NULL, CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ticket (id, created_by_id, created_at, ticket_id, descripcion, departamento, estado) SELECT id, created_by_id, created_at, ticket_id, descripcion, departamento, estado FROM __temp__ticket');
        $this->addSql('DROP TABLE __temp__ticket');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3700047D2 ON ticket (ticket_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3B03A8386 ON ticket (created_by_id)');
    }
}
