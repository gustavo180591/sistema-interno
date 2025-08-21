<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setUsername('admin');
        $admin->setNombre('Admin');
        $admin->setApellido('Principal');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);

        // Auditor user
        $auditor = new User();
        $auditor->setEmail('auditor@example.com');
        $auditor->setUsername('auditor');
        $auditor->setNombre('Auditor');
        $auditor->setApellido('Sistema');
        $auditor->setRoles(['ROLE_AUDITOR']);
        $auditor->setPassword(
            $this->passwordHasher->hashPassword($auditor, 'auditor123')
        );
        $manager->persist($auditor);

        // Regular user
        $user = new User();
        $user->setEmail('usuario@example.com');
        $user->setUsername('usuario');
        $user->setNombre('Usuario');
        $user->setApellido('Regular');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'usuario123')
        );
        $manager->persist($user);

        $manager->flush();
    }
}
