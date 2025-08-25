<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// Database configuration
$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'gustavo';
$password = '12345678';

// Password hasher configuration
$factory = new PasswordHasherFactory([
    'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => ['algorithm' => 'auto'],
]);

$hasher = $factory->getPasswordHasher('Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface');
$hashedPassword = $hasher->hash('admin123');

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Check if user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() === 0) {
        die("Error: User table does not exist. Please run database migrations first.\n");
    }

    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
    $stmt->execute(['admin@example.com', 'admin']);
    if ($stmt->rowCount() > 0) {
        die("Error: An admin user with this email or username already exists.\n");
    }

    // Create admin user
    $stmt = $pdo->prepare("
        INSERT INTO user (email, username, nombre, apellido, roles, password, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $roles = json_encode(['ROLE_ADMIN']);
    $isVerified = 1; // Assuming email verification is not required
    
    $stmt->execute([
        'admin@example.com',
        'admin',
        'Admin',
        'User',
        $roles,
        $hashedPassword,
        $isVerified
    ]);

    echo "Admin user created successfully!\n";
    echo "Email: admin@example.com\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
