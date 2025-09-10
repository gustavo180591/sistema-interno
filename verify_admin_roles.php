<?php

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'port' => 3313,
    'dbname' => 'sistema-interno',
    'username' => 'root',
    'password' => '12345678'
];

// Create connection
$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "✅ Connected to database successfully!\n\n";

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Get admin user
$email = 'admin@example.com';
$sql = "SELECT id, email, username, roles FROM `user` WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("❌ Error in prepare: " . $conn->error . "\n");
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ No admin user found with email: $email\n");
}

$admin = $result->fetch_assoc();

echo "🔍 Admin User Details:\n";
echo "- ID: " . $admin['id'] . "\n";
echo "- Email: " . $admin['email'] . "\n";
echo "- Username: " . $admin['username'] . "\n";
echo "- Roles: " . $admin['roles'] . "\n\n";

// Update admin roles if needed
$currentRoles = json_decode($admin['roles'], true);
$requiredRoles = ['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER'];
$needsUpdate = false;

foreach ($requiredRoles as $role) {
    if (!in_array($role, $currentRoles)) {
        $currentRoles[] = $role;
        $needsUpdate = true;
    }
}

if ($needsUpdate) {
    $newRoles = json_encode($currentRoles);
    $updateSql = "UPDATE `user` SET roles = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if ($updateStmt === false) {
        die("❌ Error in prepare: " . $conn->error . "\n");
    }
    
    $updateStmt->bind_param("si", $newRoles, $admin['id']);
    
    if ($updateStmt->execute()) {
        echo "✅ Successfully updated admin roles to: " . $newRoles . "\n";
        
        // Verify the update
        $verifyStmt = $conn->prepare("SELECT roles FROM `user` WHERE id = ?");
        $verifyStmt->bind_param("i", $admin['id']);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $updatedAdmin = $verifyResult->fetch_assoc();
        
        echo "🔍 Verification - Current roles: " . $updatedAdmin['roles'] . "\n";
    } else {
        echo "❌ Failed to update admin roles: " . $updateStmt->error . "\n";
    }
    
    $updateStmt->close();
} else {
    echo "ℹ️ Admin already has all required roles.\n";
}

$stmt->close();
$conn->close();

echo "\n✅ Script completed.\n";
