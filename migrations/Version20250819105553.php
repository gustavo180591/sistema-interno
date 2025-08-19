<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250819105553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create area table and add area_id to ticket table';
    }

    public function up(Schema $schema): void
    {
        // Create area table
        $this->addSql('CREATE TABLE area (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            nombre VARCHAR(100) NOT NULL, 
            descripcion CLOB DEFAULT NULL, 
            activo BOOLEAN NOT NULL, 
            fecha_creacion DATETIME NOT NULL
        )');

        // SQLite doesn't support adding foreign key constraints after table creation
        // We'll need to create a new table with the foreign key and copy data
        
        // 1. Create a temporary table with the new schema
        $this->addSql('CREATE TABLE __temp__ticket AS SELECT * FROM ticket');
        
        // 2. Drop the original table
        $this->addSql('DROP TABLE ticket');
        
        // 3. Create the new table with area_id column
        $this->addSql('CREATE TABLE ticket (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            created_by_id INTEGER NOT NULL, 
            area_id INTEGER DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            ticket_id VARCHAR(50) NOT NULL, 
            descripcion CLOB NOT NULL, 
            departamento INTEGER NOT NULL, 
            estado VARCHAR(20) NOT NULL, 
            pedido VARCHAR(100) DEFAULT NULL, 
            completed_at DATETIME DEFAULT NULL, 
            CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
            CONSTRAINT FK_97A0ADA3BD0F409C FOREIGN KEY (area_id) REFERENCES area (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        
        // 4. Recreate indexes
        $this->addSql('CREATE INDEX IDX_97A0ADA3B03A8386 ON ticket (created_by_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3BD0F409C ON ticket (area_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3CFFD0057 ON ticket (ticket_id)');
        
        // 5. Copy data back
        $this->addSql('INSERT INTO ticket (id, created_by_id, created_at, ticket_id, descripcion, departamento, estado, pedido, completed_at) 
                       SELECT id, created_by_id, created_at, ticket_id, descripcion, departamento, estado, pedido, completed_at FROM __temp__ticket');
        
        // 6. Drop the temporary table
        $this->addSql('DROP TABLE __temp__ticket');
    }

    public function down(Schema $schema): void
    {
        // Create a temporary table without area_id
        $this->addSql('CREATE TABLE __temp__ticket AS SELECT id, created_by_id, created_at, ticket_id, descripcion, departamento, estado, pedido, completed_at FROM ticket');
        
        // Drop the current table
        $this->addSql('DROP TABLE ticket');
        
        // Recreate the table without area_id
        $this->addSql('CREATE TABLE ticket (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            created_by_id INTEGER NOT NULL, 
            created_at DATETIME NOT NULL, 
            ticket_id VARCHAR(50) NOT NULL, 
            descripcion CLOB NOT NULL, 
            departamento INTEGER NOT NULL, 
            estado VARCHAR(20) NOT NULL, 
            pedido VARCHAR(100) DEFAULT NULL, 
            completed_at DATETIME DEFAULT NULL, 
            CONSTRAINT FK_97A0ADA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        
        // Recreate indexes
        $this->addSql('CREATE INDEX IDX_97A0ADA3B03A8386 ON ticket (created_by_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3CFFD0057 ON ticket (ticket_id)');
        
        // Copy data back
        $this->addSql('INSERT INTO ticket (id, created_by_id, created_at, ticket_id, descripcion, departamento, estado, pedido, completed_at) 
                       SELECT id, created_by_id, created_at, ticket_id, descripcion, departamento, estado, pedido, completed_at FROM __temp__ticket');
        
        // Drop the temporary table
        $this->addSql('DROP TABLE __temp__ticket');
        
        // Drop the area table
        $this->addSql('DROP TABLE area');
    }
}
