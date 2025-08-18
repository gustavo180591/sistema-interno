<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-roles-sql',
    description: 'Fix roles format using direct SQL',
)]
class FixRolesSqlCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // First, let's see what the current data looks like
        $users = $this->connection->executeQuery(
            "SELECT id, email, username, roles FROM user"
        )->fetchAllAssociative();
        
        if (empty($users)) {
            $io->warning('No users found in the database.');
            return Command::SUCCESS;
        }
        
        $io->title('Current Users');
        $io->table(
            ['ID', 'Email', 'Username', 'Roles'],
            array_map(fn($user) => [
                $user['id'],
                $user['email'],
                $user['username'],
                $user['roles']
            ], $users)
        );
        
        $io->section('Updating roles to JSON format...');
        
        $updated = 0;
        foreach ($users as $user) {
            $roles = $user['roles'];
            
            // Skip if already in JSON format
            if (str_starts_with($roles, '[') && str_ends_with($roles, ']')) {
                continue;
            }
            
            // Convert string like "[ROLE_USER, ROLE_ADMIN]" to proper JSON
            $roles = trim($roles, '[] ');
            $rolesArray = array_map('trim', explode(',', $roles));
            $jsonRoles = json_encode($rolesArray);
            
            // Update the user with proper JSON format
            $this->connection->executeStatement(
                'UPDATE user SET roles = :roles WHERE id = :id',
                ['roles' => $jsonRoles, 'id' => $user['id']],
                ['roles' => 'string', 'id' => 'integer']
            );
            
            $io->writeln(sprintf('Updated user %s (%s)', $user['email'], $jsonRoles));
            $updated++;
        }
        
        if ($updated > 0) {
            $io->success(sprintf('Updated roles for %d users.', $updated));
        } else {
            $io->success('No users needed role updates.');
        }
        
        return Command::SUCCESS;
    }
}
