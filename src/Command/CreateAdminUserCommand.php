<?php

namespace App\Command;

use App\Entity\User;
use App\Security\UserPasswordHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasher $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the admin user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the admin user')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username (defaults to email)')
            ->addArgument('firstname', InputArgument::OPTIONAL, 'First name', 'Admin')
            ->addArgument('lastname', InputArgument::OPTIONAL, 'Last name', 'User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $username = $input->getArgument('username') ?? $email;
        $firstName = $input->getArgument('firstname');
        $lastName = $input->getArgument('lastname');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            $io->error(sprintf('User with email %s already exists!', $email));
            return Command::FAILURE;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setNombre($firstName);
        $user->setApellido($lastName);
        $user->setRoles(['ROLE_ADMIN']);
        
        // Hash the password before setting it
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Created admin user: %s (username: %s)', $email, $username));
        
        return Command::SUCCESS;
    }
}
