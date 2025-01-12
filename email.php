<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require 'vendor/autoload.php';

// SMTP Configuration
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'reveoakinyemi.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'app@reveoakinyemi.com'; 
    $mail->Password   = 'Jan2025Test!'; // Replace with your actual password
    $mail->SMTPSecure = 'ssl';  // SSL for port 465
    $mail->Port       = 465;

    // Sender and Recipient
    $mail->setFrom('app@reveoakinyemi.com', 'Your Web App');
    $mail->addAddress('hello@reveoakinyemi.com'); // Replace with your email for testing

    // Email Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Test Email from Namecheap Server';
    $mail->Body    = 'This is a test email sent from your Namecheap hosted web app using PHP.';
    
    $mail->send();
    echo 'Message sent successfully!';
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}
?>
