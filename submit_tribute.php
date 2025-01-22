<?php
session_start();
require_once 'core/db_connection.php';
require_once 'core/email_notification.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate reference ID
        $reference = 'T' . strtoupper(substr(base_convert(time(), 10, 36) . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3), 0, 8));
        
        // Basic sanitization while preserving formatting
        $name = trim($_POST['name']);
        $location = trim($_POST['location']);
        $church_name = trim($_POST['church_name']);
        $relationship = trim($_POST['relationship']);
        $message = trim($_POST['message']);
        $imagePath = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = 'uploads/tributes/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $imageExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($imageExtension, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
            }
            
            $uniqueFilename = $reference . '_' . time() . '.' . $imageExtension;
            $imagePath = $uploadDir . $uniqueFilename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                throw new Exception("Error uploading image.");
            }
        }
        
        // Database insertion
        $stmt = $conn->prepare("INSERT INTO tributes (name, location, church_name, relationship, message, status, reference, image) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
        
        $stmt->bind_param("sssssss", 
            $name,
            $location,
            $church_name,
            $relationship,
            $message,
            $reference,
            $imagePath
        );
        
        if ($stmt->execute()) {
            // Prepare tribute data for email notification
            $tributeData = [
                'reference' => $reference,
                'name' => $name,
                'location' => $location,
                'church_name' => $church_name,
                'relationship' => $relationship,
                'message' => $message,
                'image' => $imagePath,
                'status' => 'pending'
            ];
            
            // Send email notification
            $notification = new TributeNotification();
            $notification->sendNewTributeNotification($tributeData);
            
            $_SESSION['success'] = "Thank you for your tribute. It has been submitted successfully and will be available on the page after review.";
            header('Location: /tributes');
            exit();
        } else {
            throw new Exception("Failed to submit tribute.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: /sharememory');
        exit();
    }
} else {
    // If not POST request, redirect to form
    header('Location: /sharememory');
    exit();
}
?>