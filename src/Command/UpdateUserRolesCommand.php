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

#[AsCommand(
    name: 'app:user:update-roles',
    description: 'Update user roles'
)]
class UpdateUserRolesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('roles', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Roles to add (e.g., ROLE_AUDITOR ROLE_ADMIN)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $roles = $input->getArgument('roles');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email %s not found', $email));
            return Command::FAILURE;
        }

        $currentRoles = $user->getRoles();
        $updated = false;

        foreach ($roles as $role) {
            $role = strtoupper(trim($role));
            if (!in_array($role, $currentRoles, true)) {
                $currentRoles[] = $role;
                $updated = true;
                $io->success(sprintf('Added role: %s', $role));
            }
        }

        if ($updated) {
            $user->setRoles($currentRoles);
            $this->entityManager->flush();
            $io->success(sprintf('Successfully updated roles for user: %s', $email));
            $io->listing($currentRoles);
        } else {
            $io->note('No new roles were added. User already has all specified roles.');
            $io->listing($currentRoles);
        }

        return Command::SUCCESS;
    }
}
