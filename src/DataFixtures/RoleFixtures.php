<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'roleName' => 'ROLE_ADMIN'
            ],
            [
                'name' => 'Usuario',
                'roleName' => 'ROLE_USER'
            ]
        ];

        foreach ($roles as $roleData) {
            $role = new Role();
            $role->setName($roleData['name']);
            $role->setRoleName($roleData['roleName']);
            $role->setDescription($roleData['description']);
            $role->setCreatedAt(new \DateTime());
            
            $manager->persist($role);
            
            // Store reference for other fixtures if needed
            $this->addReference('role_' . strtolower($roleData['roleName']), $role);
        }

        $manager->flush();
    }
}
