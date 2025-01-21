#!/usr/local/bin/php
<?php
// At the very top of your script
error_log("\n\n=== NEW EMAIL RECEIVED ===");
error_log("Script path: " . __FILE__);
error_log("Raw POST data: " . print_r($_POST, true));
error_log("Raw GET data: " . print_r($_GET, true));
$raw_input = file_get_contents('php://input');
error_log("Raw input length: " . strlen($raw_input));
error_log("Raw input first 500 chars: " . substr($raw_input, 0, 500));

// tributes_email_handler.php
error_log("Email handler script started at " . date('Y-m-d H:i:s'));

error_log("Attempting to include db_connection.php");
require_once 'db_connection.php';
error_log("db_connection.php included");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    exit();
}
error_log("Database connection successful");

// Get the email content
error_log("Attempting to read email input");
$raw_email = file_get_contents('php://input');
error_log("Raw email content (first 200 chars): " . substr($raw_email, 0, 200));

// Parse email headers and body
$lines = explode("\n", $raw_email);
$headers = [];
$message = '';
$reading_headers = true;

foreach ($lines as $line) {
    $line = rtrim($line);
    if ($reading_headers) {
        if (empty($line)) {
            $reading_headers = false;
            continue;
        }
        if (preg_match('/^([^:]+):\s*(.+)$/', $line, $matches)) {
            $headers[strtolower($matches[1])] = $matches[2];
        }
    } else {
        $message .= $line . "\n";
    }
}

error_log("Headers parsed: " . print_r($headers, true));

// Extract information
$subject = $headers['subject'] ?? 'No Subject';
$from = $headers['from'] ?? '';
error_log("Subject: $subject");
error_log("From: $from");

// Generate reference
$reference = 'EMAIL_' . time() . '_' . substr(md5(uniqid()), 0, 8);
error_log("Generated reference: " . $reference);

// Extract name from From header
$name = '';
if (preg_match('/(.+?)\s*</', $from, $matches)) {
    $name = trim($matches[1]);
} else {
    // If no name in email header, use email address up to @ symbol
    $name = trim(substr($from, 0, strpos($from . '@', '@')));
}
error_log("Extracted name: " . $name);

// Set default values
$relationship = 'Well-wisher';
$location = '';
$church_name = '';

try {
    // Database insert
    error_log("Preparing SQL statement");
    $sql = "INSERT INTO tributes (reference, name, relationship, location, church_name, message, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        exit();
    }
    
    error_log("Binding parameters");
    $stmt->bind_param("ssssss", 
        $reference,
        $name,
        $relationship,
        $location,
        $church_name,
        $message
    );

    error_log("Executing statement");
    if ($stmt->execute()) {
        error_log("SUCCESS: Email tribute saved. Reference: " . $reference);
        
        // Extract reply-to email
        $reply_to = $headers['reply-to'] ?? $from;
        $to_email = preg_match('/<(.+?)>/', $reply_to, $matches) ? $matches[1] : $reply_to;
        
        // Send acknowledgment
        $confirmation_subject = "We received your tribute";
        $confirmation_message = "Thank you for your tribute. It will be reviewed and published soon.";
        if(mail($to_email, $confirmation_subject, $confirmation_message)) {
            error_log("Confirmation email sent successfully to: " . $to_email);
        } else {
            error_log("Failed to send confirmation email");
        }
    } else {
        error_log("ERROR: Failed to save tribute: " . $stmt->error);
    }

    $stmt->close();
    error_log("Statement closed");

} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
}

$conn->close();
error_log("Connection closed");
error_log("Script completed at " . date('Y-m-d H:i:s'));
?>