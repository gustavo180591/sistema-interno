<?php

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'port' => 3313,
    'dbname' => 'sistema-interno',
    'username' => 'root',
    'password' => '12345678'
];

// Test connection and get database information
function testConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        echo "✅ Successfully connected to MySQL server!\n";
        
        // Get MySQL version
        $version = $pdo->query('SELECT VERSION() as version')->fetch()['version'];
        echo "- MySQL Version: $version\n";
        
        // List databases
        echo "\n📊 Databases on the server:\n";
        $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($databases as $db) {
            echo "- $db\n";
        }
        
        // Check if our database exists
        if (in_array($config['dbname'], $databases)) {
            echo "\n🔍 Database '{$config['dbname']}' exists.\n";
            
            // Select the database
            $pdo->exec("USE `{$config['dbname']}`");
            
            // List tables
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            echo "\n📋 Tables in '{$config['dbname']}':\n";
            foreach ($tables as $table) {
                echo "- $table\n";
            }
            
            // Check if user table exists
            if (in_array('user', $tables)) {
                echo "\n👤 User table structure:\n";
                $columns = $pdo->query('DESCRIBE `user`')->fetchAll(PDO::FETCH_ASSOC);
                
                echo str_pad("Field", 20) . str_pad("Type", 25) . str_pad("Null", 8) . "Key\n";
                echo str_repeat("-", 60) . "\n";
                foreach ($columns as $col) {
                    echo str_pad($col['Field'], 20) . 
                         str_pad($col['Type'], 25) . 
                         str_pad($col['Null'], 8) . 
                         $col['Key'] . "\n";
                }
                
                // Get all users
                $users = $pdo->query('SELECT * FROM `user`')->fetchAll(PDO::FETCH_ASSOC);
                echo "\n👥 Users in the database:\n";
                if (empty($users)) {
                    echo "No users found.\n";
                } else {
                    foreach ($users as $user) {
                        echo "\n- ID: {$user['id']}";
                        echo "\n  Email: {$user['email']}";
                        echo "\n  Username: {$user['username']}";
                        echo "\n  Roles: {$user['roles']}\n";
                    }
                }
                
                // Update admin user
                $adminEmail = 'admin@example.com';
                $adminStmt = $pdo->prepare("SELECT * FROM `user` WHERE email = :email");
                $adminStmt->execute(['email' => $adminEmail]);
                $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    echo "\n🔧 Updating admin user...\n";
                    $newRoles = json_encode(['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER']);
                    $updateStmt = $pdo->prepare("UPDATE `user` SET roles = :roles WHERE id = :id");
                    $updateStmt->execute([
                        'roles' => $newRoles,
                        'id' => $admin['id']
                    ]);
                    
                    echo "✅ Admin user updated with roles: $newRoles\n";
                } else {
                    echo "\n❌ Admin user not found in the database.\n";
                }
            } else {
                echo "\n❌ User table does not exist in the database.\n";
            }
        } else {
            echo "\n❌ Database '{$config['dbname']}' does not exist.\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Run the test
echo "🔍 Starting database connection test...\n\n";
testConnection($config);

echo "\n✅ Script completed.\n";
