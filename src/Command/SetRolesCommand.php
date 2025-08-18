<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:set-roles',
    description: 'Set roles for a user',
)]
class SetRolesCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'User email')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of roles')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getOption('email');
        $rolesStr = $input->getOption('roles');
        
        if (!$email || !$rolesStr) {
            $io->error('Both --email and --roles are required');
            return Command::FAILURE;
        }
        
        // Convert roles string to array and format as JSON
        $roles = array_map('trim', explode(',', $rolesStr));
        $jsonRoles = json_encode($roles);
        
        // Update the user
        $result = $this->connection->executeStatement(
            'UPDATE user SET roles = :roles WHERE email = :email',
            ['roles' => $jsonRoles, 'email' => $email],
            ['roles' => 'string', 'email' => 'string']
        );
        
        if ($result === 0) {
            $io->error(sprintf('User with email %s not found', $email));
            return Command::FAILURE;
        }
        
        $io->success(sprintf('Updated roles for %s to %s', $email, $jsonRoles));
        return Command::SUCCESS;
    }
}
