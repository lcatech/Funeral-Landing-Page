<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tributes_db";

// Create and check database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
