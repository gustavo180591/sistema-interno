<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix user roles data format
 */
final class Version20250818010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix user roles data format';
    }

    public function up(Schema $schema): void
    {
        // This migration will be handled in the postUp method
    }

    public function postUp(Schema $schema): void
    {
        // Get all users with their current roles
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM user');
        
        foreach ($users as $user) {
            $roles = $user['roles'];
            
            // If roles is already a JSON string, skip
            if (str_starts_with($roles, '[') && str_ends_with($roles, ']')) {
                continue;
            }
            
            // Clean up the roles string and convert it to a proper JSON array
            $roles = str_replace(['[', ']', "'", ' '], '', $roles);
            $rolesArray = array_filter(explode(',', $roles));
            
            // Ensure ROLE_USER is present
            if (!in_array('ROLE_USER', $rolesArray, true)) {
                $rolesArray[] = 'ROLE_USER';
            }
            
            // Convert to JSON
            $rolesJson = json_encode(array_values(array_unique($rolesArray)));
            
            // Update the user with the new roles format
            $this->connection->update('user', 
                ['roles' => $rolesJson],
                ['id' => $user['id']]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // This migration is not easily reversible
        $this->throwIrreversibleMigrationException('This migration cannot be reverted automatically.');
    }
}
