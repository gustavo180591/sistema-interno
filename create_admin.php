<?php

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use App\Security\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

// Database configuration
$dbParams = [
    'driver'   => 'pdo_mysql',
    'user'     => 'gustavo',
    'password' => '12345678',
    'dbname'   => 'sistema-interno',
    'host'     => '127.0.0.1',
    'port'     => 3313,
    'charset'  => 'utf8mb4',
];

// Create a simple "default" Doctrine ORM configuration
$config = Setup::createAnnotationMetadataConfiguration(
    [__DIR__."/src/Entity"],
    true,
    null,
    null,
    false
);

// Create EntityManager
$entityManager = EntityManager::create($dbParams, $config);

// Password hasher
$factory = new PasswordHasherFactory([
    User::class => ['algorithm' => 'auto'],
]);
$hasher = new UserPasswordHasher($factory);

// Create admin user
$user = new User();
$user->setEmail('admin@example.com');
$user->setUsername('admin');
$user->setNombre('Admin');
$user->setApellido('User');
$user->setRoles(['ROLE_ADMIN']);
$user->setPlainPassword('admin123', $hasher);

// Save to database
$entityManager->persist($user);
$entityManager->flush();

echo "Admin user created successfully!\n";
echo "Email: admin@example.com\n";
echo "Username: admin\n";
echo "Password: admin123\n";
