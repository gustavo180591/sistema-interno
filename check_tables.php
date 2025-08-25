<?php

require __DIR__.'/vendor/autoload.php';

$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'gustavo';
$password = '12345678';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Connected to the database successfully!\n";
    
    // List all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "Tables in the database:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
            
            // Show table structure
            echo "  Columns:\n";
            $columns = $pdo->query("DESCRIBE `$table`")->fetchAll();
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']})\n";
            }
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}
