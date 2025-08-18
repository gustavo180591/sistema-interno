<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[AsCommand(
    name: 'app:check-roles',
    description: 'Check current user roles'
)]
class CheckRolesCommand extends Command
{
    public function __construct(
        private Security $security
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            $output->writeln('No user is currently logged in.');
            return Command::SUCCESS;
        }
        
        $output->writeln(sprintf('User: %s', $user->getUserIdentifier()));
        $output->writeln(sprintf('Roles: %s', json_encode($user->getRoles())));
        
        return Command::SUCCESS;
    }
}
