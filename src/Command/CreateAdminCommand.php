<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates an admin user or updates existing user to admin',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email', 'admin@sistema.com')
            ->addArgument('username', InputArgument::OPTIONAL, 'Admin username', 'admin')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password', 'admin123')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force update if user exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $force = $input->getOption('force');

        // Check if user exists
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $email]);

        if ($existingUser && !$force) {
            $io->warning("User with email '$email' already exists. Use --force to update.");
            return Command::FAILURE;
        }

        if ($existingUser) {
            $user = $existingUser;
            $io->note("Updating existing user: $email");
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($username);
            $io->note("Creating new admin user: $email");
        }

        // Set admin and auditor roles
        $user->setRoles(['ROLE_ADMIN', 'ROLE_AUDITOR']);
        
        // Set password - properly hashed
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Set optional fields
        $user->setNombre('Administrador');
        $user->setApellido('Sistema');
        $user->setIsActive(true);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $io->success('Admin user created/updated successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Email', $email],
                    ['Username', $username],
                    ['Password', $password],
                    ['Roles', implode(', ', $user->getRoles())],
                ]
            );
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating/updating admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}