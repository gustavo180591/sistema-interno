<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818004123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert roles from string to JSON array format';
    }

    public function up(Schema $schema): void
    {
        // Get all users
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM user');
        
        foreach ($users as $user) {
            $roles = $user['roles'];
            
            // Skip if already in correct format
            if (empty($roles) || $roles[0] === '[') {
                continue;
            }
            
            // Convert string to array and back to JSON
            $rolesArray = array_map('trim', explode(',', trim($roles, '[] ')));
            $jsonRoles = json_encode($rolesArray);
            
            // Update the user with proper JSON format
            $this->addSql("UPDATE user SET roles = ? WHERE id = ?", [
                $jsonRoles,
                $user['id']
            ], [
                'json',
                'integer'
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // No need to revert this migration
    }
}
