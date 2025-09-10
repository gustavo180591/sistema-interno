<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Database connection
$dbHost = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/sistema_interno?serverVersion=8.0.32';
$dbParams = parse_url($dbHost);

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s',
    $dbParams['host'],
    $dbParams['port'] ?? 3306,
    ltrim($dbParams['path'], '/')
);

$username = $dbParams['user'] ?? 'root';
$password = $dbParams['pass'] ?? '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get admin user
    $stmt = $pdo->query("SELECT * FROM user WHERE email = 'admin@example.com'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found: " . $user['email'] . "\n";
        echo "Roles: " . $user['roles'] . "\n";
        
        // Check if ROLE_ADMIN is in the roles
        $roles = json_decode($user['roles'], true);
        if (in_array('ROLE_ADMIN', $roles)) {
            echo "User has ROLE_ADMIN\n";
        } else {
            echo "User does NOT have ROLE_ADMIN\n";
        }
    } else {
        echo "Admin user not found\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
