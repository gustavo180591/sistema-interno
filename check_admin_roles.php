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
    // Connect to database
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Get all users with their roles
    $stmt = $pdo->query("SELECT id, email, username, roles FROM `user`");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No users found in the database.\n";
        exit(1);
    }
    
    echo "👥 Users in the database:\n";
    echo str_repeat("-", 100) . "\n";
    echo str_pad("ID", 5) . str_pad("Email", 30) . str_pad("Username", 20) . "Roles\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($users as $user) {
        echo str_pad($user['id'], 5) . 
             str_pad($user['email'], 30) . 
             str_pad($user['username'], 20) . 
             $user['roles'] . "\n";
    }
    
    // Check admin user
    $admin = $pdo->query("SELECT * FROM `user` WHERE email = 'admin@example.com'")->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "\n❌ Admin user not found!\n";
        exit(1);
    }
    
    echo "\n🔍 Admin user details:\n";
    echo "- ID: {$admin['id']}\n";
    echo "- Email: {$admin['email']}\n";
    echo "- Username: {$admin['username']}\n";
    echo "- Roles: {$admin['roles']}\n";
    
    // Update admin roles if needed
    $roles = json_decode($admin['roles'], true);
    $needsUpdate = false;
    
    if (!in_array('ROLE_AUDITOR', $roles)) {
        $roles[] = 'ROLE_AUDITOR';
        $needsUpdate = true;
    }
    
    if ($needsUpdate) {
        $newRoles = json_encode($roles);
        $updateStmt = $pdo->prepare("UPDATE `user` SET roles = :roles WHERE id = :id");
        $updateStmt->execute([
            'roles' => $newRoles,
            'id' => $admin['id']
        ]);
        
        echo "\n✅ Updated admin roles to: $newRoles\n";
    } else {
        echo "\nℹ️ Admin already has all required roles.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Script completed.\n";
