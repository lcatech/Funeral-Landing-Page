<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../core/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = trim($_POST['password']);
    
    // Debug: Print submitted credentials
    echo "Submitted username: " . $username . "<br>";
    
    // Fetch admin details
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('s', $username);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    // Debug: Check if user was found
    if ($admin) {
        echo "User found in database<br>";
        
        // Debug: Check password verification
        if (password_verify($password, $admin['password'])) {
            echo "Password verified successfully<br>";
            
            // Store session data
            $_SESSION['logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            
            // Debug: Print session data
            echo "Session data set:<br>";
            print_r($_SESSION);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect to admin panel
            header("Location: admin2.php");
            exit();
        } else {
            echo "Password verification failed<br>";
            $error = "Invalid username or password";
        }
    } else {
        echo "No user found with this username<br>";
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
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
        }
        .debug {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Admin Login</h1>
    
    <?php if (isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required 
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>

    <?php
    // Debug: Display connection status
    if (isset($conn)) {
        echo "<div class='debug'>Database connection status: Connected</div>";
    } else {
        echo "<div class='debug'>Database connection status: Not connected</div>";
    }
    ?>
</body>
</html>