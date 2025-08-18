<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix user roles column to store JSON data properly
 */
final class Version20250818003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix user roles column to store JSON data properly';
    }

    public function up(Schema $schema): void
    {
        // First, create a backup of the current roles data
        $this->addSql('ALTER TABLE user ADD roles_backup LONGTEXT DEFAULT NULL');
        
        // Get all users with their current roles
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM user');
        
        // Update each user's roles to proper JSON format
        foreach ($users as $user) {
            $roles = $user['roles'];
            // Clean up the roles string and convert it to a proper JSON array
            $roles = str_replace(['[', ']', "'", ' '], '', $roles);
            $rolesArray = array_filter(explode(',', $roles));
            // Ensure ROLE_USER is always present
            if (!in_array('ROLE_USER', $rolesArray, true)) {
                $rolesArray[] = 'ROLE_USER';
            }
            $rolesJson = json_encode(array_values(array_unique($rolesArray)));
            
            // Update the backup column with the properly formatted JSON
            $this->addSql('UPDATE user SET roles_backup = :roles WHERE id = :id', [
                'roles' => $rolesJson,
                'id' => $user['id']
            ]);
        }
        
        // Drop the old roles column and rename the backup
        $this->addSql('ALTER TABLE user DROP roles');
        $this->addSql('ALTER TABLE user CHANGE roles_backup roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // This migration is not easily reversible
        $this->throwIrreversibleMigrationException('This migration cannot be reverted automatically.');
    }
}
