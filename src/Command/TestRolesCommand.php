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
        // Generate a unique username
        $timestamp = time();
        $username = 'testuser_' . $timestamp;
        $email = 'test_' . $timestamp . '@example.com';
        
        // Create a test user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword(password_hash('testpass', PASSWORD_DEFAULT));
        $user->setNombre('Test');
        $user->setApellido('User');
        $user->setIsVerified(true);
        
        // Set roles
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        
        // Save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Output the results
        $output->writeln('Test user created:');
        $output->writeln(sprintf('  Username: %s', $user->getUsername()));
        $output->writeln(sprintf('  Email: %s', $user->getEmail()));
        $output->writeln(sprintf('  Roles: %s', json_encode($user->getRoles())));
        
        // Test role checking
        $output->writeln('\nTesting role checks:');
        $output->writeln(sprintf('  Has ROLE_ADMIN: %s', $user->hasRole('ROLE_ADMIN') ? 'YES' : 'NO'));
        $output->writeln(sprintf('  Has ROLE_SUPER_ADMIN: %s', $user->hasRole('ROLE_SUPER_ADMIN') ? 'YES' : 'NO'));
        
        // Test User-Role relationship
        $roleRepository = $this->entityManager->getRepository(\App\Entity\Role::class);
        $adminRole = $roleRepository->findOneBy(['roleName' => 'ROLE_ADMIN']);
        
        if ($adminRole) {
            $output->writeln('\nFound ROLE_ADMIN in the database');
            $adminRole->addUser($user);
            $this->entityManager->flush();
            
            // Refresh user to get updated roles
            $this->entityManager->refresh($user);
            $output->writeln('Added user to ROLE_ADMIN group');
            $output->writeln('Updated user roles: ' . json_encode($user->getRoles()));
        } else {
            $output->writeln('\nWarning: ROLE_ADMIN not found in the database');
            $output->writeln('Run app:add-default-roles command to create default roles');
        }
        
        return Command::SUCCESS;
    }
}
