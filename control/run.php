<?php
include '../core/db_connection.php';

// Set admin credentials
$admin_username = "it-man";
$admin_password = "Th"; // Change this immediately after first login
$admin_role = "admin";

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

try {
    // First, check if any admin exists
    $check = $conn->query("SELECT COUNT(*) as count FROM admin_users");
    $result = $check->fetch_assoc();
    
    if ($result['count'] > 0) {
        die("Admin accounts already exist. For security reasons, this script can only be used for initial setup.");
    }
    
    // Create the admin account with the correct table structure
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $admin_username, $hashed_password, $admin_role);
    
    if ($stmt->execute()) {
        echo "Admin account created successfully!\n";
        echo "Username: " . $admin_username . "\n";
        echo "Password: " . $admin_password . "\n";
        echo "\nPlease change your password immediately after logging in.";
    } else {
        echo "Error creating admin account: " . $conn->error;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Delete this file after running it for security
unlink(__FILE__);
?>