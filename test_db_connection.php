<?php

// Database configuration
$configs = [
    [
        'host' => '127.0.0.1',
        'port' => 3313,
        'dbname' => 'sistema-interno',
        'username' => 'root',
        'password' => '12345678'
    ],
    [
        'host' => '127.0.0.1',
        'port' => 3313,
        'dbname' => 'sistema-interno',
        'username' => 'gustavo',
        'password' => '12345678'
    ]
];

foreach ($configs as $config) {
    echo "\n".str_repeat("=", 80)."\n";
    echo "Testing connection with:\n";
    echo "- Host: {$config['host']}:{$config['port']}\n";
    echo "- Database: {$config['dbname']}\n";
    echo "- Username: {$config['username']}\n";
    
    try {
        // First try with database
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "✅ Successfully connected to the database!\n";
        
        // Get MySQL version
        $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
        echo "- MySQL Version: $version\n";
        
        // List all tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "ℹ️ No tables found in the database.\n";
        } else {
            echo "\nTables in the database:\n";
            foreach ($tables as $table) {
                echo "- $table\n";
            }
            
            // Check if user table exists
            if (in_array('user', $tables)) {
                $userCount = $pdo->query('SELECT COUNT(*) as count FROM `user`')->fetch()['count'];
                echo "\nFound $userCount users in the user table.\n";
                
                // List admin users
                $admins = $pdo->query("SELECT id, email, username, roles FROM `user` WHERE roles LIKE '%ROLE_ADMIN%'")->fetchAll();
                if (!empty($admins)) {
                    echo "\nAdmin users:\n";
                    foreach ($admins as $admin) {
                        echo "- ID: {$admin['id']}, Email: {$admin['email']}, Username: {$admin['username']}, Roles: {$admin['roles']}\n";
                    }
                } else {
                    echo "\nNo admin users found in the database.\n";
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        
        // Try to connect without database to check server
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            echo "ℹ️ Successfully connected to MySQL server but couldn't access database '{$config['dbname']}'\n";
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['dbname']}'");
            if ($stmt->rowCount() > 0) {
                echo "ℹ️ Database '{$config['dbname']}' exists but user '{$config['username']}' doesn't have access to it.\n";
            } else {
                echo "ℹ️ Database '{$config['dbname']}' does not exist.\n";
            }
            
        } catch (PDOException $e2) {
            echo "❌ Could not connect to MySQL server: " . $e2->getMessage() . "\n";
        }
    }
    
    echo str_repeat("=", 80)."\n";
}
