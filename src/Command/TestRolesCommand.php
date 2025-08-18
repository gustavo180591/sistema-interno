<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-roles',
    description: 'Test user roles functionality'
)]
class TestRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setPassword('testpass');
        $user->setNombre('Test');
        $user->setApellido('User');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $output->writeln('Test user created with roles: ' . json_encode($user->getRoles()));
        
        return Command::SUCCESS;
    }
}
