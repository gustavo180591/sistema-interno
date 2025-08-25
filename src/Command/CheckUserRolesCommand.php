<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\UserRepository;

#[AsCommand(
    name: 'app:check-user-roles',
    description: 'Check the current user\'s roles',
)]
class CheckUserRolesCommand extends Command
{
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        parent::__construct();
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'The username to check roles for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        
        if ($username) {
            $user = $this->userRepository->findOneBy(['username' => $username]);
            if (!$user) {
                $output->writeln(sprintf('<error>User "%s" not found.</error>', $username));
                return Command::FAILURE;
            }
            $output->writeln(sprintf('Checking roles for user: %s', $user->getUserIdentifier()));
        } else {
            $user = $this->security->getUser();
            if (!$user) {
                $output->writeln('<error>No user is currently logged in and no username provided.</error>');
                return Command::FAILURE;
            }
            $output->writeln(sprintf('Current user: %s', $user->getUserIdentifier()));
        }

        $output->writeln('Roles:');
        
        foreach ($user->getRoles() as $role) {
            $output->writeln(sprintf('  - %s', $role));
        }

        return Command::SUCCESS;
    }
}
