<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823170954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Skip notification table operations as it doesn't exist
        // $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA700047D2');
        // $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        // $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE ticket ADD proposed_by_id INT DEFAULT NULL, ADD proposed_status VARCHAR(20) DEFAULT NULL, ADD proposal_note LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3DAB5A938 FOREIGN KEY (proposed_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3DAB5A938 ON ticket (proposed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, recipient_id INT NOT NULL, ticket_id INT NOT NULL, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BF5476CA700047D2 (ticket_id), INDEX IDX_BF5476CAE92F8F78 (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3DAB5A938');
        $this->addSql('DROP INDEX IDX_97A0ADA3DAB5A938 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP proposed_by_id, DROP proposed_status, DROP proposal_note');
    }
}
