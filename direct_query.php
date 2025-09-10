<?php

// Database configuration
$host = '127.0.0.1';
$port = 3313;
$dbname = 'sistema-interno';
$username = 'root';
$password = '12345678';

// Connect using mysqli
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "✅ Connected to database successfully!\n\n";

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Get all users
$result = $conn->query("SELECT * FROM `user`");

if ($result->num_rows > 0) {
    echo "👥 Users in the database:\n";
    echo str_repeat("-", 100) . "\n";
    
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"] . "\n";
        echo "Email: " . $row["email"] . "\n";
        echo "Username: " . $row["username"] . "\n";
        echo "Roles: " . $row["roles"] . "\n";
        echo str_repeat("-", 100) . "\n";
        
        // If this is the admin user, update their roles
        if ($row["email"] === 'admin@example.com') {
            $newRoles = json_encode(['ROLE_ADMIN', 'ROLE_AUDITOR', 'ROLE_USER']);
            $updateSql = "UPDATE `user` SET roles = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $newRoles, $row["id"]);
            
            if ($stmt->execute()) {
                echo "✅ Updated admin user roles to: " . $newRoles . "\n";
            } else {
                echo "❌ Failed to update admin roles: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
    }
} else {
    echo "No users found in the database.\n";
}

$conn->close();

echo "\n✅ Script completed.\n";
