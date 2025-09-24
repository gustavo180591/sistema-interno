<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923143954 extends AbstractMigration
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE note_read_status DROP FOREIGN KEY FK_DC58642826ED0855');
        $this->addSql('ALTER TABLE note_read_status DROP FOREIGN KEY FK_DC586428A76ED395');
        $this->addSql('DROP TABLE note_read_status');
    }
}
