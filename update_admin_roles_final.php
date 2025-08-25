<?php

// Database configuration
$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'root';
$password = '12345678';

// Connect to MySQL
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

echo "✅ Connected to MySQL successfully!\n";

// Check if user table exists
$result = $conn->query("SHOW TABLES LIKE 'user'");
if ($result->num_rows === 0) {
    die("❌ User table does not exist!\n");
}

echo "✅ User table exists.\n";

// Get admin user
$email = 'admin@example.com';
$stmt = $conn->prepare("SELECT id, email, roles FROM `user` WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Admin user not found with email: $email\n");
}

$admin = $result->fetch_assoc();
echo "\n🔍 Found admin user:\n";
echo "- ID: " . $admin['id'] . "\n";
echo "- Email: " . $admin['email'] . "\n";
echo "- Current Roles: " . $admin['roles'] . "\n";

// Update roles
$newRoles = json_encode(['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER']);
$updateStmt = $conn->prepare("UPDATE `user` SET roles = ? WHERE id = ?");
$updateStmt->bind_param("si", $newRoles, $admin['id']);

if ($updateStmt->execute()) {
    echo "\n✅ Successfully updated admin roles to: $newRoles\n";
} else {
    echo "\n❌ Failed to update admin roles: " . $conn->error . "\n";
}

// Verify the update
$verifyStmt = $conn->prepare("SELECT roles FROM `user` WHERE id = ?");
$verifyStmt->bind_param("i", $admin['id']);
$verifyStmt->execute();
$result = $verifyStmt->get_result();
$updatedAdmin = $result->fetch_assoc();

echo "\n🔍 Verification - Current roles: " . $updatedAdmin['roles'] . "\n";

$conn->close();

echo "\n✅ Script completed.\n";
