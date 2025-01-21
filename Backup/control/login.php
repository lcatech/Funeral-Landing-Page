<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include '../core/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Replace the current success login block with this:
if ($user && password_verify($password, $user['password'])) {
    if ($user['status'] === 'active') {
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check for pending actions
        if (isset($_SESSION['pending_action'])) {
            $pendingAction = $_SESSION['pending_action'];
            unset($_SESSION['pending_action']); // Clear it immediately
            
            if ($pendingAction['action'] === 'edit') {
                header("Location: admin.php?action=edit&tribute=" . $pendingAction['tribute_id']);
                exit();
            } elseif ($pendingAction['action'] === 'approve') {
                header("Location: quick-actions.php?action=approve&id=" . $pendingAction['tribute_id'] . 
                       "&token=" . $pendingAction['token']);
                exit();
            }
        }
        
        // Default redirect if no pending action
        header("Location: /control/admin.php");
        exit();
    } else {
        $error = "Account is not active";
    }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        .error { color: red; margin-bottom: 15px; }
        form { max-width: 400px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST">
        <h1>Admin Login</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
</body>
</html>