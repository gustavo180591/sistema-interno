<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'admin@example.com';
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            $output->writeln('Admin user already exists. Updating...');
        } else {
            $user = new User();
            $user->setEmail($email);
            $output->writeln('Creating new admin user...');
        }

        $user->setNombre('Admin');
        $user->setApellido('Sistema');
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_ADMIN']);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            '123456'
        );
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user updated successfully!</info>');
        $output->writeln(sprintf('Email: <comment>%s</comment>', $email));
        $output->writeln('Password: <comment>123456</comment>');

        return Command::SUCCESS;
    }
}
