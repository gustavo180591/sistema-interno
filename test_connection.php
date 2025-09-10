<?php

require __DIR__.'/vendor/autoload.php';

$dsn = 'mysql:host=127.0.0.1;port=3313;dbname=sistema-interno;charset=utf8mb4';
$user = 'gustavo';
$password = '12345678';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Connected to the database successfully!\n";
    
    // Check if user table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "User table exists.\n";
        // List all users
        $users = $pdo->query("SELECT * FROM user")->fetchAll();
        if (count($users) > 0) {
            echo "Users in the database:\n";
            foreach ($users as $user) {
                echo "- ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Roles: {$user['roles']}\n";
            }
        } else {
            echo "No users found in the database.\n";
        }
    } else {
        echo "User table does not exist.\n";
        // List all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            echo "Available tables: " . implode(', ', $tables) . "\n";
        } else {
            echo "No tables found in the database.\n";
        }
    }
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}
