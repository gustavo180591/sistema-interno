<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Security\UserPasswordHasher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    private UserPasswordHasher $passwordHasher;

    public function __construct(UserPasswordHasher $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setUsername('admin');
        $admin->setNombre('Admin');
        $admin->setApellido('User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPlainPassword('admin123', $this->passwordHasher);
        $manager->persist($admin);

        // Create auditor user
        $auditor = new User();
        $auditor->setEmail('auditor@example.com');
        $auditor->setUsername('auditor');
        $auditor->setNombre('Auditor');
        $auditor->setApellido('User');
        $auditor->setRoles(['ROLE_AUDITOR']);
        $auditor->setPlainPassword('auditor123', $this->passwordHasher);
        $manager->persist($auditor);

        // Create regular user
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setUsername('user');
        $user->setNombre('Regular');
        $user->setApellido('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPlainPassword('user123', $this->passwordHasher);
        $manager->persist($user);

        $manager->flush();
    }
}
