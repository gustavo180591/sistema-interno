<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:update-admin-password',
    description: 'Updates the admin user password with proper hashing',
)]
class UpdateAdminPasswordCommand extends Command
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
            ->addArgument('password', InputArgument::OPTIONAL, 'New password', 'admin123')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Find the user
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error("User with email '$email' not found.");
            return Command::FAILURE;
        }

        // Hash the password properly
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Ensure user is active
        $user->setIsActive(true);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $io->success('Password updated successfully!');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Email', $email],
                    ['Password', $password],
                    ['Hashed', substr($hashedPassword, 0, 20) . '...'],
                    ['Is Active', $user->getIsActive() ? 'Yes' : 'No'],
                ]
            );
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error updating password: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}