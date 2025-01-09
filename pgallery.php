<?php
session_start();

// Configuration
$config = [
    'temp_dir' => 'images/gallery/pending/',    // Where uploads go before approval
    'final_dir' => 'images/gallery/random/',    // Where approved images go
    'admin_user' => 'admin',                    // Change this!
    'admin_pass' => 'admin123',                 // Change this!
    'max_size' => 5 * 1024 * 1024              // 5MB max file size
];

// Create directories if they don't exist
foreach ([$config['temp_dir'], $config['final_dir']] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Handle admin login
if (isset($_POST['admin_login'])) {
    if ($_POST['username'] === $config['admin_user'] && 
        $_POST['password'] === $config['admin_pass']) {
        $_SESSION['admin'] = true;
    }
}

// Handle admin logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    header('Content-Type: application/json');
    
    try {
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

            // Basic validation
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for {$file['name']}";
                continue;
            }

            if ($file['size'] > $config['max_size']) {
                $errors[] = "{$file['name']} is too large (max 5MB)";
                continue;
            }

            if (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
                $errors[] = "{$file['name']} is not an allowed image type";
                continue;
            }

            // Generate safe filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . $extension;
            $destination = $config['temp_dir'] . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $uploadedFiles[] = $filename;
            } else {
                $errors[] = "Failed to save {$file['name']}";
            }
        }

        echo json_encode([
            'success' => count($uploadedFiles) > 0,
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
            'message' => 'Images uploaded and pending approval'
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

// Handle image approval/rejection
if (isset($_SESSION['admin']) && $_SESSION['admin']) {
    if (isset($_POST['approve'])) {
        $filename = $_POST['filename'];
        $sourcePath = $config['temp_dir'] . $filename;
        $targetPath = $config['final_dir'] . $filename;
        if (file_exists($sourcePath)) {
            rename($sourcePath, $targetPath);
        }
    } elseif (isset($_POST['reject'])) {
        $filename = $_POST['filename'];
        $filepath = $config['temp_dir'] . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-form { margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
        .preview-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin: 20px 0; }
        .preview-image { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; }
        .button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .button:hover { background: #45a049; }
        .button.reject { background: #f44336; }
        .button.reject:hover { background: #da190b; }
        .admin-panel { margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 8px; }
        .pending-image { max-width: 200px; margin: 10px 0; }
        #file-input { display: none; }
        .login-form { margin: 20px 0; }
        .login-form input { margin: 5px 0; padding: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['admin'])): ?>
            <!-- Admin Login Form -->
            <form method="post" class="login-form">
                <h3>Admin Login</h3>
                <div>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="admin_login" class="button">Login</button>
            </form>
        <?php else: ?>
            <!-- Admin Panel -->
            <div class="admin-panel">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Admin Panel - Pending Approvals</h3>
                    <a href="?logout" class="button reject">Logout</a>
                </div>
                <?php
                $pending_images = glob($config['temp_dir'] . '*');
                if (empty($pending_images)) {
                    echo "<p>No images pending approval</p>";
                } else {
                    foreach ($pending_images as $image) {
                        $filename = basename($image);
                        echo "<div style='margin: 20px 0; padding: 10px; background: white; border-radius: 4px;'>";
                        echo "<img src='{$config['temp_dir']}{$filename}' class='pending-image'>";
                        echo "<div>";
                        echo "<form method='post' style='display: inline-block;'>";
                        echo "<input type='hidden' name='filename' value='{$filename}'>";
                        echo "<button type='submit' name='approve' class='button'>Approve</button>";
                        echo "<button type='submit' name='reject' class='button reject'>Reject</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="upload-form">
            <h2>Upload Gallery Images</h2>
            <form id="upload-form" enctype="multipart/form-data">
                <input type="file" id="file-input" multiple accept="image/*">
                <button type="button" class="button" onclick="document.getElementById('file-input').click()">
                    Select Images
                </button>
                <button type="submit" class="button">
                    Upload Images
                </button>
            </form>
            <div class="preview-container" id="preview-container"></div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const previewContainer = document.getElementById('preview-container');
        const uploadForm = document.getElementById('upload-form');

        // Preview selected images
        fileInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = e.target.files;

            for (let file of files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });

        // Handle form submission
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const files = fileInput.files;
            
            if (files.length === 0) {
                alert('Please select at least one image to upload.');
                return;
            }

            const formData = new FormData();
            for (let file of files) {
                formData.append('images[]', file);
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Images uploaded successfully and pending approval!');
                    fileInput.value = '';
                    previewContainer.innerHTML = '';
                } else {
                    throw new Error(result.message || 'Upload failed');
                }
            } catch (error) {
                alert('Error uploading images: ' + error.message);
                console.error(error);
            }
        });
    </script>
</body>
</html>