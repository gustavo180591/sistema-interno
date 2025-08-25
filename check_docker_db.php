<?php

// Database configuration for Docker container
$config = [
    'host' => '127.0.0.1',
    'port' => '3313',
    'dbname' => 'sistema-interno',
    'user' => 'root',  // Try with root user first
    'password' => '12345678',
    'charset' => 'utf8mb4'
];

try {
    echo "Connecting to MySQL in Docker container...\n";
    
    // First try with the database
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Successfully connected to the database!\n";
    
    // List tables
    $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database. You need to run migrations.\n";
        echo "Run: php bin/console doctrine:migrations:migrate --no-interaction\n";
    } else {
        echo "\nTables in the database:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Try without database to check server connection
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        echo "✅ Successfully connected to MySQL server!\n";
        
        // Try to create database
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            echo "✅ Database '{$config['dbname']}' created successfully!\n";
            
            echo "\nNext steps:\n";
            echo "1. Run migrations: php bin/console doctrine:migrations:migrate --no-interaction\n";
            echo "2. Create admin user: php bin/console app:create-admin admin@example.com admin123 admin Admin User\n";
            
        } catch (PDOException $e2) {
            echo "❌ Failed to create database: " . $e2->getMessage() . "\n";
            
            // Check user privileges
            $users = $pdo->query("SELECT user, host FROM mysql.user;")->fetchAll(PDO::FETCH_ASSOC);
            echo "\nMySQL users:\n";
            foreach ($users as $user) {
                echo "- '{$user['user']}'@'{$user['host']}'\n";
            }
        }
        
    } catch (PDOException $e2) {
        echo "❌ Could not connect to MySQL server: " . $e2->getMessage() . "\n";
        
        // Check if port is open
        $connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
        if (is_resource($connection)) {
            echo "✅ Port {$config['port']} is open on {$config['host']}\n";
            fclose($connection);
        } else {
            echo "❌ Port {$config['port']} is not open on {$config['host']}: $errstr ($errno)\n";
        }
    }
}
