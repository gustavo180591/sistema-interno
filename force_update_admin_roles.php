<?php

// Database configuration
$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'root';
$password = '12345678';

// Connect to MySQL
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✅ Connected to database successfully!\n";
    
    // Define admin email and new roles
    $adminEmail = 'admin@example.com';
    $newRoles = json_encode(['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER']);
    
    // Update the admin user
    $stmt = $pdo->prepare("UPDATE `user` SET roles = :roles WHERE email = :email");
    $stmt->execute([
        ':roles' => $newRoles,
        ':email' => $adminEmail
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Successfully updated admin user with roles: $newRoles\n";
        
        // Verify the update
        $user = $pdo->query("SELECT email, roles FROM `user` WHERE email = '" . $adminEmail . "'")->fetch();
        echo "\n🔍 Verification:\n";
        echo "- Email: " . $user['email'] . "\n";
        echo "- Roles: " . $user['roles'] . "\n";
    } else {
        echo "ℹ️ No changes were made. The admin user may not exist or already has these roles.\n";
    }
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}

echo "\n✅ Script completed.\n";
