<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-roles-directly',
    description: 'Fix roles format directly in the database',
)]
class FixRolesDirectlyCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get all users
        $users = $this->connection->executeQuery('SELECT id, roles FROM user')->fetchAllAssociative();
        $updated = 0;
        
        foreach ($users as $user) {
            $roles = $user['roles'];
            
            // If roles is not a valid JSON array, fix it
            if (!empty($roles) && !$this->isJson($roles)) {
                // Convert string like "[ROLE_USER, ROLE_ADMIN]" to proper JSON
                $rolesArray = array_map('trim', explode(',', trim($roles, '[] ')));
                $jsonRoles = json_encode($rolesArray);
                
                // Update the user with proper JSON format
                $this->connection->executeStatement(
                    'UPDATE user SET roles = :roles WHERE id = :id',
                    ['roles' => $jsonRoles, 'id' => $user['id']],
                    ['roles' => 'string', 'id' => 'integer']
                );
                $updated++;
            }
        }
        
        if ($updated > 0) {
            $io->success(sprintf('Updated roles for %d users.', $updated));
        } else {
            $io->success('No users needed role updates.');
        }
        
        return Command::SUCCESS;
    }
    
    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
