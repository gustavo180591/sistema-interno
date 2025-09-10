<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Get database configuration
$dbUrl = $_ENV['DATABASE_URL'] ?? null;

if (!$dbUrl) {
    die("DATABASE_URL not found in .env file\n");
}

// Parse database URL
$dbParams = parse_url($dbUrl);
$dbName = trim($dbParams['path'], '/');
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $dbParams['host'],
    $dbParams['port'] ?? 3306,
    $dbName
);

$username = $dbParams['user'] ?? '';
$password = $dbParams['pass'] ?? '';

try {
    // Connect to the database
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Connected to database successfully!\n";
    
    // List all tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "Tables in the database:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}
