<?php
// Save as control/quick-actions.php
session_start();
require_once '../core/db_connection.php';

// Function to log admin activity (reusing from your admin.php)
function logAdminActivity($adminId, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $adminId, $action);
    $stmt->execute();
    $stmt->close();
}

// Verify token validity
function verifyToken($tributeId, $token, $action) {
    global $conn;
    
    $tokenColumn = $action . '_token';
    $sql = "SELECT * FROM tribute_tokens 
            WHERE tribute_id = ? 
            AND {$tokenColumn} = ? 
            AND expires_at > NOW()";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tributeId, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Valid token found - create a temporary admin session
        $_SESSION['temp_admin'] = true;
        $_SESSION['temp_admin_expires'] = time() + 300; // 5 minutes
        return true;
    }
    return false;
}

// Check for temporary admin session expiration
if (isset($_SESSION['temp_admin']) && $_SESSION['temp_admin_expires'] < time()) {
    unset($_SESSION['temp_admin']);
    unset($_SESSION['temp_admin_expires']);
}

if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['token'])) {
    $action = $_GET['action'];
    $tributeId = $_GET['id'];
    $token = $_GET['token'];
    
    // Verify token or check if user is already logged in
    if (verifyToken($tributeId, $token, $action) || 
        (isset($_SESSION['logged_in']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_user'))) {
        
        switch ($action) {
            case 'approve':
                try {
                    $stmt = $conn->prepare("UPDATE tributes SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $tributeId);
                    
                    if ($stmt->execute()) {
                        // Log the activity
                        $adminId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 0; // 0 for email actions
                        logAdminActivity($adminId, "Approved tribute ID $tributeId via email link");
                        
                        // Clean up used token
                        $stmt = $conn->prepare("DELETE FROM tribute_tokens WHERE tribute_id = ? AND approve_token = ?");
                        $stmt->bind_param("is", $tributeId, $token);
                        $stmt->execute();
                        
                        $_SESSION['success'] = "Tribute approved successfully!";
                    } else {
                        $_SESSION['error'] = "Error approving tribute.";
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error processing request: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                if (isset($_SESSION['logged_in']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_user')) {
                    // User is already logged in, redirect to admin edit modal
                    header("Location: admin.php?edit=$tributeId");
                    exit;
                } else {
                    // Redirect to login page with return URL
                    $_SESSION['return_url'] = "admin.php?edit=$tributeId";
                    header("Location: login.php");
                    exit;
                }
                break;
        }
        
        // Redirect based on session status
        if (isset($_SESSION['logged_in'])) {
            header("Location: admin.php");
        } else {
            header("Location: login.php");
        }
        exit;
    } else {
        $_SESSION['error'] = "Invalid or expired token. Please log in to the admin portal.";
        header("Location: login.php");
        exit;
    }
}

// Default redirect
header("Location: admin.php");
exit;
?>