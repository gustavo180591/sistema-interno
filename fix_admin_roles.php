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
    
    // Set admin roles
    $adminRoles = json_encode(['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER']);
    
    // Update admin user
    $stmt = $pdo->prepare("UPDATE `user` SET roles = :roles WHERE email = :email");
    $stmt->execute([
        'roles' => $adminRoles,
        'email' => 'admin@example.com'
    ]);
    
    // Verify the update
    $admin = $pdo->query("SELECT email, roles FROM `user` WHERE email = 'admin@example.com'")->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user updated successfully!\n";
        echo "- Email: {$admin['email']}\n";
        echo "- Roles: {$admin['roles']}\n";
    } else {
        echo "❌ Failed to update admin user.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
