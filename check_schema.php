<?php

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'port' => 3313,
    'dbname' => 'sistema-interno',
    'username' => 'root',
    'password' => '12345678'
];

try {
    // Connect to MySQL server
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Connected to MySQL server successfully!\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['dbname']}'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Database '{$config['dbname']}' does not exist.\n";
        exit(1);
    }
    
    echo "📊 Database '{$config['dbname']}' exists.\n";
    
    // Select the database
    $pdo->exec("USE `{$config['dbname']}`;");
    
    // List all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "\n❌ No tables found in the database. You need to run migrations.\n";
        exit(1);
    }
    
    echo "\n📋 Tables in the database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check if user table exists
    if (!in_array('user', $tables)) {
        echo "\n❌ User table does not exist. You need to run migrations.\n";
        exit(1);
    }
    
    // Describe user table
    echo "\n🔍 User table structure:\n";
    $columns = $pdo->query("DESCRIBE `user`")->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad("Field", 20) . str_pad("Type", 25) . str_pad("Null", 8) . str_pad("Key", 8) . "Default\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 25) . 
             str_pad($column['Null'], 8) . 
             str_pad($column['Key'], 8) . 
             $column['Default'] . "\n";
    }
    
    // Check if there are any users
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM `user`")->fetch()['count'];
    echo "\n👥 Number of users in the database: $userCount\n";
    
    // List admin users if any
    if ($userCount > 0) {
        $admins = $pdo->query("SELECT id, email, username, roles FROM `user` WHERE roles LIKE '%ROLE_ADMIN%'")->fetchAll();
        if (!empty($admins)) {
            echo "\n👑 Admin users:\n";
            foreach ($admins as $admin) {
                echo "- ID: {$admin['id']}, Email: {$admin['email']}, Username: {$admin['username']}, Roles: {$admin['roles']}\n";
            }
        } else {
            echo "\nℹ️ No admin users found in the database.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Database check completed.\n";
