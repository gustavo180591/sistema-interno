<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822151557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_update ADD ticket_id INT NOT NULL, ADD user_id INT NOT NULL, ADD type VARCHAR(50) NOT NULL, ADD changes JSON DEFAULT NULL, ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ticket_update ADD CONSTRAINT FK_E2675FB3700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE ticket_update ADD CONSTRAINT FK_E2675FB3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_E2675FB3700047D2 ON ticket_update (ticket_id)');
        $this->addSql('CREATE INDEX IDX_E2675FB3A76ED395 ON ticket_update (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_update DROP FOREIGN KEY FK_E2675FB3700047D2');
        $this->addSql('ALTER TABLE ticket_update DROP FOREIGN KEY FK_E2675FB3A76ED395');
        $this->addSql('DROP INDEX IDX_E2675FB3700047D2 ON ticket_update');
        $this->addSql('DROP INDEX IDX_E2675FB3A76ED395 ON ticket_update');
        $this->addSql('ALTER TABLE ticket_update DROP ticket_id, DROP user_id, DROP type, DROP changes, DROP created_at');
    }
}
