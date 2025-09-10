<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Output database configuration
$dbUrl = $_ENV['DATABASE_URL'] ?? 'Not set';
echo "Database URL: " . $dbUrl . "\n";

// Parse database URL
$dbParams = [];
if (preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?]+)/', $dbUrl, $matches)) {
    $dbParams = [
        'user' => $matches[1],
        'pass' => $matches[2],
        'host' => $matches[3],
        'port' => $matches[4],
        'dbname' => $matches[5]
    ];
    
    echo "\nParsed Database Configuration:\n";
    echo "Host: " . $dbParams['host'] . "\n";
    echo "Port: " . $dbParams['port'] . "\n";
    echo "Database: " . $dbParams['dbname'] . "\n";
    echo "User: " . $dbParams['user'] . "\n";
    
    // Test connection
    try {
        $dsn = "mysql:host={$dbParams['host']};port={$dbParams['port']};dbname={$dbParams['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbParams['user'], $dbParams['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "\n✅ Successfully connected to the database!\n";
        
        // List tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            echo "No tables found in the database.\n";
        } else {
            echo "\nTables in the database:\n";
            foreach ($tables as $table) {
                echo "- $table\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "\n❌ Database connection failed: " . $e->getMessage() . "\n";
        
        // Try to connect without database to check server
        try {
            $dsn = "mysql:host={$dbParams['host']};port={$dbParams['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbParams['user'], $dbParams['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            echo "\nℹ️ Successfully connected to MySQL server. Checking if database exists...\n";
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbParams['dbname']}';");
            if ($stmt->rowCount() > 0) {
                echo "✅ Database '{$dbParams['dbname']}' exists but there might be a permission issue.\n";
            } else {
                echo "❌ Database '{$dbParams['dbname']}' does not exist.\n";
                echo "You can create it with: CREATE DATABASE `{$dbParams['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
            }
            
        } catch (PDOException $e2) {
            echo "\n❌ Could not connect to MySQL server: " . $e2->getMessage() . "\n";
        }
    }
} else {
    echo "\n❌ Could not parse DATABASE_URL. Make sure it's in the correct format.\n";
    echo "Example format: mysql://db_user:db_password@127.0.0.1:3306/db_name\n";
}

echo "\nCurrent working directory: " . __DIR__ . "\n";
?>
