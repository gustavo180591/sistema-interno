<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Creates a test user with admin, auditor, and regular user roles',
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
        
        // Create admin user
        $this->createUser('admin@example.com', 'admin', 'Admin', 'User', ['ROLE_ADMIN'], 'admin123', $io);
        
        // Create auditor user
        $this->createUser('auditor@example.com', 'auditor', 'Auditor', 'User', ['ROLE_AUDITOR'], 'auditor123', $io);
        
        // Create regular user
        $this->createUser('user@example.com', 'user', 'Regular', 'User', ['ROLE_USER'], 'user123', $io);
        
        $io->success('Test users created successfully!');
        $io->writeln('Admin: admin@example.com / admin123');
        $io->writeln('Auditor: auditor@example.com / auditor123');
        $io->writeln('User: user@example.com / user123');
        
        return Command::SUCCESS;
    }
    
    private function createUser(
        string $email, 
        string $username, 
        string $firstName, 
        string $lastName, 
        array $roles, 
        string $plainPassword,
        SymfonyStyle $io
    ): void {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setNombre($firstName);
        $user->setApellido($lastName);
        $user->setRoles($roles);
        
        // Hash the password before setting it
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->note(sprintf('Created %s user: %s', $roles[0], $email));
    }
}
