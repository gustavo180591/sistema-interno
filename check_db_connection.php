<?php

$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'gustavo';
$password = '12345678';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Connected to the database successfully!\n";
    
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
    die("Connection failed: " . $e->getMessage() . "\n");
}
