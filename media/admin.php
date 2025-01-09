<?php
require 'auth.php';
requireLogin();
$config = require 'config.php';

// Handle image approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['filename'])) {
        $filename = $_POST['filename'];
        $sourcePath = $config['pending_dir'] . $filename;
        
        if (file_exists($sourcePath)) {
            if ($_POST['action'] === 'approve') {
                // Extract category from filename
                $parts = explode('_', $filename);
                $category = $parts[0];
                
                if (isset($config['categories'][$category])) {
                    $targetPath = $config['categories'][$category] . $filename;
                    rename($sourcePath, $targetPath);
                }
            } else if ($_POST['action'] === 'reject') {
                unlink($sourcePath);
            }
        }
    }
}

// Get pending images
$pendingImages = array_filter(
    glob($config['pending_dir'] . '*'),
    'is_file'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .pending-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .pending-item {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
        }
        .pending-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .button {
            background: #efbf04;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .button.reject {
            background: #dc3545;
        }
        .button:hover { opacity: 0.9; }
        .no-images {
            text-align: center;
            padding: 40px;
            background: #f5f5f5;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Admin Panel - Pending Approvals</h2>
            <a href="login.php?logout=1" class="button reject">Logout</a>
        </div>

        <?php if (empty($pendingImages)): ?>
            <div class="no-images">
                <h3>No images pending approval</h3>
            </div>
        <?php else: ?>
            <div class="pending-grid">
                <?php foreach ($pendingImages as $image): ?>
                    <?php $filename = basename($image); ?>
                    <div class="pending-item">
                        <img src="<?= $config['pending_dir'] . $filename ?>" class="pending-image" alt="Pending image">
                        <div>
                            <p><strong>Filename:</strong> <?= htmlspecialchars($filename) ?></p>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="filename" value="<?= htmlspecialchars($filename) ?>">
                                <button type="submit" name="action" value="approve" class="button">Approve</button>
                                <button type="submit" name="action" value="reject" class="button reject">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>