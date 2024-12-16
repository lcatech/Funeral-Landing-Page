<?php

// local server
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tributes_db";

// Create and check database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Log admin activities
function logAdminActivity($adminId, $action)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action) VALUES (?, ?)");
    if ($stmt) {
        $action = htmlspecialchars($action); // Sanitize action input
        $stmt->bind_param('is', $adminId, $action);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare logAdminActivity statement: " . $conn->error);
    }
}
?>