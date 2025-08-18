<?php

namespace App\Command;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-default-roles',
    description: 'Adds default roles to the database',
)]
class AddDefaultRolesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $defaultRoles = [
            [
                'name' => 'Administrador',
                'roleName' => 'ROLE_ADMIN'
            ],
            [
                'name' => 'Usuario',
                'roleName' => 'ROLE_USER'
            ]
        ];

        $roleRepository = $this->entityManager->getRepository(Role::class);
        $createdCount = 0;

        foreach ($defaultRoles as $roleData) {
            $role = $roleRepository->findOneBy(['roleName' => $roleData['roleName']]);
            
            if (!$role) {
                $role = new Role();
                $role->setName($roleData['name']);
                $role->setRoleName($roleData['roleName']);
                $role->setCreatedAt(new \DateTime());
                
                $this->entityManager->persist($role);
                $createdCount++;
            }
        }
        
        $this->entityManager->flush();

        if ($createdCount > 0) {
            $io->success(sprintf('Se han creado %d roles por defecto.', $createdCount));
        } else {
            $io->info('Todos los roles por defecto ya existen en la base de datos.');
        }

        return Command::SUCCESS;
    }
}
