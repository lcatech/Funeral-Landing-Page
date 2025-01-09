<?php
session_start();
$config = [
    'temp_dir' => 'images/gallery/pending/',
    'final_dir' => 'images/gallery/approved/',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'admin_username' => 'admin', // Change this!
    'admin_password' => 'your_secure_password_hash', // Use password_hash() to generate this
    'max_uploads_per_hour' => 10,
    'allowed_domains' => ['yourdomain.com'] // Add your domains here
];

// Create necessary directories
foreach ([$config['temp_dir'], $config['final_dir']] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Admin login handling
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === $config['admin_username'] && 
        password_verify($_POST['password'], $config['admin_password'])) {
        $_SESSION['admin'] = true;
    }
}

// Admin logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle image approval/rejection
if (isset($_SESSION['admin']) && $_SESSION['admin']) {
    if (isset($_POST['approve'])) {
        $filename = $_POST['filename'];
        $sourcePath = $config['temp_dir'] . $filename;
        $targetPath = $config['final_dir'] . $filename;
        if (file_exists($sourcePath) && is_file($sourcePath)) {
            rename($sourcePath, $targetPath);
        }
    } elseif (isset($_POST['reject'])) {
        $filename = $_POST['filename'];
        $filepath = $config['temp_dir'] . $filename;
        if (file_exists($filepath) && is_file($filepath)) {
            unlink($filepath);
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    header('Content-Type: application/json');
    
    try {
        // Security checks
        if (!isset($_SERVER['HTTP_REFERER']) || 
            !in_array(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST), $config['allowed_domains'])) {
            throw new Exception('Invalid request origin');
        }

        // Rate limiting
        $upload_count = isset($_SESSION['upload_count']) ? $_SESSION['upload_count'] : 0;
        $last_upload = isset($_SESSION['last_upload']) ? $_SESSION['last_upload'] : 0;
        
        if (time() - $last_upload > 3600) {
            $_SESSION['upload_count'] = 0;
            $_SESSION['last_upload'] = time();
        } elseif ($upload_count >= $config['max_uploads_per_hour']) {
            throw new Exception('Upload limit reached. Please try again later.');
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file = [
                'name' => $_FILES['images']['name'][$key],
                'type' => $_FILES['images']['type'][$key],
                'tmp_name' => $tmp_name,
                'error' => $_FILES['images']['error'][$key],
                'size' => $_FILES['images']['size'][$key],
            ];

            // Validation
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for file {$file['name']}";
                continue;
            }

            if ($file['size'] > $config['max_file_size']) {
                $errors[] = "{$file['name']} exceeds maximum file size";
                continue;
            }

            if (!in_array($file['type'], $config['allowed_types'])) {
                $errors[] = "{$file['name']} is not an allowed image type";
                continue;
            }

            // Additional security checks
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $errors[] = "{$file['name']} is not a valid image";
                continue;
            }

            // Generate secure filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $destination = $config['temp_dir'] . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $uploadedFiles[] = $filename;
                $_SESSION['upload_count'] = ($upload_count + 1);
            } else {
                $errors[] = "Failed to move uploaded file {$file['name']}";
            }
        }

        echo json_encode([
            'success' => count($uploadedFiles) > 0,
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
            'message' => 'Images uploaded successfully and pending approval'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Gallery Upload</title>
    <style>
        /* Previous styles remain the same */
        .admin-panel {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .pending-image {
            max-width: 200px;
            margin: 10px;
        }
        .approval-controls {
            margin: 10px 0;
        }
        .approve-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .reject-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <?php if (!isset($_SESSION['admin'])): ?>
            <!-- Admin Login Form -->
            <form method="post" class="admin-login">
                <h3>Admin Login</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="admin_login" class="upload-btn">Login</button>
            </form>
        <?php else: ?>
            <a href="?logout" class="upload-btn">Logout</a>
            
            <!-- Admin Panel -->
            <div class="admin-panel">
                <h3>Pending Approvals</h3>
                <?php
                $pending_images = glob($config['temp_dir'] . '*');
                if (empty($pending_images)) {
                    echo "<p>No images pending approval</p>";
                } else {
                    foreach ($pending_images as $image) {
                        $filename = basename($image);
                        echo "<div class='pending-item'>";
                        echo "<img src='{$config['temp_dir']}{$filename}' class='pending-image'>";
                        echo "<div class='approval-controls'>";
                        echo "<form method='post' style='display:inline-block'>";
                        echo "<input type='hidden' name='filename' value='{$filename}'>";
                        echo "<button type='submit' name='approve' class='approve-btn'>Approve</button>";
                        echo "<button type='submit' name='reject' class='reject-btn'>Reject</button>";
                        echo "</form>";
                        echo "</div></div>";
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <h2>Upload Gallery Images</h2>
        <form id="upload-form" enctype="multipart/form-data">
            <input type="file" id="file-input" multiple accept="image/*">
            <button type="button" class="upload-btn" onclick="document.getElementById('file-input').click()">
                Select Images
            </button>
            <button type="submit" class="upload-btn">
                Upload Selected Images
            </button>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </form>
        <div class="preview-container" id="preview-container"></div>
        <div id="debug-info"></div>
    </div>

    <script>
        // Previous JavaScript remains the same, but update the success message
        uploadForm.addEventListener('submit', async function(e) {
            // ... (previous upload handling code) ...
            if (result.success) {
                alert('Images uploaded successfully and are pending approval.');
                fileInput.value = '';
                previewContainer.innerHTML = '';
                debugInfo.style.display = 'none';
            }
            // ... (rest of the code remains the same) ...
        });
    </script>
</body>
</html>