<?php

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Config\DoctrineConfig;

// Load environment variables
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__.'/.env');

// Create a minimal container
$container = new ContainerBuilder();

// Load database configuration
$doctrineConfig = new DoctrineConfig();
$doctrineConfig->dbal()
    ->connection('default', [
        'url' => $_ENV['DATABASE_URL']
    ]);

// Set up the entity manager
$config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
    [__DIR__.'/src/Entity'],
    true,
    null,
    null,
    false
);

$connectionParams = [
    'url' => $_ENV['DATABASE_URL']
];

$entityManager = \Doctrine\ORM\EntityManager::create($connectionParams, $config);

// Create admin user
$email = 'admin@example.com';
$password = 'admin123';
$username = 'admin';
$firstName = 'Admin';
$lastName = 'User';

// Check if user already exists
$existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

if ($existingUser) {
    echo "User with email $email already exists!\n";
    exit(1);
}

// Create password hasher
$hasherFactory = new PasswordHasherFactory([
    User::class => ['algorithm' => 'auto'],
]);
$passwordHasher = $hasherFactory->getPasswordHasher(User::class);

// Create new user
$user = new User();
$user->setEmail($email);
$user->setUsername($username);
$user->setNombre($firstName);
$user->setApellido($lastName);
$user->setRoles(['ROLE_ADMIN']);

// Hash and set the password
$hashedPassword = $passwordHasher->hash($password);
$user->setPassword($hashedPassword);

// Save the user
$entityManager->persist($user);
$entityManager->flush();

echo "✅ Admin user created successfully!\n";
echo "Email: $email\n";
echo "Password: $password\n";
