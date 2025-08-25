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
    
    echo "✅ Connected to database successfully!\n";
    
    // Get current admin user
    $stmt = $pdo->prepare("SELECT id, email, username, roles FROM `user` WHERE email = :email");
    $stmt->execute(['email' => 'admin@example.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit(1);
    }
    
    echo "🔍 Found admin user:\n";
    echo "- ID: {$admin['id']}\n";
    echo "- Email: {$admin['email']}\n";
    echo "- Username: {$admin['username']}\n";
    echo "- Current Roles: {$admin['roles']}\n\n";
    
    // Decode JSON roles
    $roles = json_decode($admin['roles'], true);
    
    // Add ROLE_AUDITOR if not present
    if (!in_array('ROLE_AUDITOR', $roles)) {
        $roles[] = 'ROLE_AUDITOR';
        $newRoles = json_encode($roles);
        
        // Update roles
        $updateStmt = $pdo->prepare("UPDATE `user` SET roles = :roles WHERE id = :id");
        $updateStmt->execute([
            'roles' => $newRoles,
            'id' => $admin['id']
        ]);
        
        echo "✅ Added ROLE_AUDITOR to admin user.\n";
        echo "- New Roles: " . $newRoles . "\n";
    } else {
        echo "ℹ️ ROLE_AUDITOR is already assigned to this user.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Script completed.\n";
