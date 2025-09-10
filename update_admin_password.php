<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// Database configuration
$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$dbUser = 'gustavo';
$dbPass = '12345678';

// New admin credentials
$adminEmail = 'admin@example.com';
$adminUsername = 'admin';
$adminPassword = 'admin123';

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email, username FROM user WHERE email = ? OR username = ?");
    $stmt->execute([$adminEmail, $adminUsername]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Error: No user found with email '$adminEmail' or username '$adminUsername'\n");
    }

    // Update password
    $factory = new PasswordHasherFactory([
        'Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface' => ['algorithm' => 'auto'],
    ]);
    $hasher = $factory->getPasswordHasher('Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface');
    $hashedPassword = $hasher->hash($adminPassword);

    $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $user['id']]);

    // Ensure user has admin role
    $stmt = $pdo->prepare("UPDATE user SET roles = ? WHERE id = ?");
    $stmt->execute([json_encode(['ROLE_ADMIN']), $user['id']]);

    echo "Admin user updated successfully!\n";
    echo "User ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Username: {$user['username']}\n";
    echo "New Password: $adminPassword\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
