<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user',
)]
class CreateAdminUserCommand extends Command
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
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email address for the admin user', 'admin@example.com')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password for the admin user', 'admin123')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Username for the admin user', 'admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $username = $input->getOption('username');

        // Check if user exists by email or username
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        }

        if ($user) {
            $io->note('Updating existing admin user...');
        } else {
            $user = new User();
            $user->setEmail($email);
            $io->note('Creating new admin user...');
        }

        $user->setNombre('Admin');
        $user->setApellido('Sistema');
        $user->setUsername($username);
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user updated successfully!</info>');
        $output->writeln(sprintf('Email: <comment>%s</comment>', $email));
        $output->writeln('Password: <comment>123456</comment>');

        return Command::SUCCESS;
    }
}
