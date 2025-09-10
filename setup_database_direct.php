<?php

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'port' => '3313',
    'dbname' => 'sistema-interno',
    'user' => 'gustavo',
    'password' => '12345678',
    'charset' => 'utf8mb4'
];

// Connect to MySQL server (without database)
try {
    echo "Connecting to MySQL server...\n";
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Connected to MySQL server successfully!\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['dbname']}';");
    
    if ($stmt->rowCount() > 0) {
        echo "ℹ️ Database '{$config['dbname']}' already exists.\n";
        
        // Connect to the database
        $pdo->exec("USE `{$config['dbname']}`;");
        
        // Check if we have any tables
        $tables = $pdo->query("SHOW TABLES;")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "ℹ️ No tables found in the database. You need to run migrations.\n";
            echo "Run: php bin/console doctrine:migrations:migrate --no-interaction\n";
        } else {
            echo "\nTables in the database:\n";
            foreach ($tables as $table) {
                echo "- $table\n";
            }
        }
    } else {
        // Create the database
        echo "Creating database '{$config['dbname']}'...\n";
        $pdo->exec("CREATE DATABASE `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        echo "✅ Database '{$config['dbname']}' created successfully!\n";
        
        // Select the database
        $pdo->exec("USE `{$config['dbname']}`;");
        
        echo "\nNext steps:\n";
        echo "1. Run migrations: php bin/console doctrine:migrations:migrate --no-interaction\n";
        echo "2. Create admin user: php bin/console app:create-admin admin@example.com admin123 admin Admin User\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (str_contains($e->getMessage(), 'Access denied')) {
        echo "\nPlease check your MySQL credentials in the script.\n";
        echo "Current configuration:\n";
        echo "- Host: {$config['host']}\n";
        echo "- Port: {$config['port']}\n";
        echo "- User: {$config['user']}\n";
        echo "- Password: " . str_repeat('*', strlen($config['password'])) . "\n";
        echo "\nMake sure the MySQL server is running and the user has proper permissions.\n";
    }
}
