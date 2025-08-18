<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-users-simple',
    description: 'List all users in a simple format',
)]
class ListUsersSimpleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        if (empty($users)) {
            $io->warning('No users found in the database.');
            return Command::SUCCESS;
        }
        
        foreach ($users as $user) {
            $io->writeln(sprintf(
                'ID: %d, Email: %s, Username: %s',
                $user->getId(),
                $user->getEmail(),
                $user->getUsername()
            ));
        }
        
        return Command::SUCCESS;
    }
}
