<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test user with admin role',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setUsername('admin');
        $user->setNombre('Admin');
        $user->setApellido('User');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'admin123'
        );
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success('Test admin user created successfully!');
        $io->writeln('Email: admin@example.com');
        $io->writeln('Password: admin123');
        
        return Command::SUCCESS;
    }
}
