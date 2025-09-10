<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\UserPasswordHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:update-passwords',
    description: 'Update user passwords with proper hashing'
)]
class UpdateUserPasswordsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasher $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findAll();
        $updated = 0;

        foreach ($users as $user) {
            // Skip if no password is set
            if (empty($user->getPassword())) {
                continue;
            }

            // Rehash the password
            $plainPassword = $user->getPassword(); // This assumes the current password is stored in plain text
            $user->setPlainPassword($plainPassword, $this->passwordHasher);
            $this->entityManager->persist($user);
            $updated++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Updated passwords for %d users', $updated));

        return Command::SUCCESS;
    }
}
