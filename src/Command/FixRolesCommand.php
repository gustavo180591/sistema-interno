<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-roles',
    description: 'Fix the roles format in the database',
)]
class FixRolesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->entityManager->getConnection();

        // Get all users with non-JSON roles
        $users = $conn->executeQuery(
            "SELECT id, roles FROM user WHERE roles NOT LIKE '\%[%' OR roles IS NULL"
        )->fetchAllAssociative();

        if (empty($users)) {
            $io->success('No users with malformed roles found.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d users with malformed roles. Updating...', count($users)));

        $updated = 0;
        foreach ($users as $user) {
            $roles = $user['roles'] ?? '';
            
            if (empty($roles)) {
                $jsonRoles = '[]'; // Default to empty array if no roles
            } else {
                // Convert string like "[ROLE_USER, ROLE_ADMIN]" to proper JSON
                $rolesArray = array_map('trim', explode(',', trim($roles, '[] ')));
                $jsonRoles = json_encode($rolesArray);
            }

            // Update the user with proper JSON format
            $conn->executeStatement(
                'UPDATE user SET roles = :roles WHERE id = :id',
                ['roles' => $jsonRoles, 'id' => $user['id']],
                ['roles' => 'json', 'id' => 'integer']
            );
            $updated++;
        }

        $io->success(sprintf('Successfully updated roles for %d users.', $updated));
        return Command::SUCCESS;
    }
}
