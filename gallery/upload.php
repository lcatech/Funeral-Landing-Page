<?php
$config = require 'config.php';

// Ensure pending directory exists
if (!file_exists($config['pending_dir'])) {
    mkdir($config['pending_dir'], 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_FILES['images']) || !isset($_POST['category'])) {
            throw new Exception('Missing required fields');
        }

        $category = $_POST['category'];
        if (!isset($config['categories'][$category])) {
            throw new Exception('Invalid category');
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

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload error for {$file['name']}";
                continue;
            }

            if ($file['size'] > $config['max_size']) {
                $errors[] = "{$file['name']} is too large (max 5MB)";
                continue;
            }

            if (!in_array($file['type'], $config['allowed_types'])) {
                $errors[] = "{$file['name']} is not an allowed image type";
                continue;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $category . '_' . date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . $extension;
            $destination = $config['pending_dir'] . $filename;

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Images</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .preview-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
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
        .button:hover { background: #d1a204; }
        #file-input { display: none; }
        .category-select {
            padding: 8px;
            margin: 10px 0;
            width: 200px;
        }
        .notice {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Gallery Images</h2>
        <div class="notice">
            Images will be reviewed by an administrator before being published to the gallery.
        </div>
        
        <div class="upload-form">
            <form id="upload-form" enctype="multipart/form-data">
                <select name="category" class="category-select" required>
                    <option value="">Select Category</option>
                    <?php foreach (array_keys($config['categories']) as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>">
                            <?= ucfirst(htmlspecialchars($category)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br>
                <input type="file" id="file-input" name="images[]" multiple accept="image/*">
                <button type="button" class="button" onclick="document.getElementById('file-input').click()">
                    Select Images
                </button>
                <button type="submit" class="button">Upload Images</button>
            </form>
            <div class="preview-container" id="preview-container"></div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const previewContainer = document.getElementById('preview-container');
        const uploadForm = document.getElementById('upload-form');

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

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!uploadForm.category.value) {
                alert('Please select a category');
                return;
            }

            if (fileInput.files.length === 0) {
                alert('Please select at least one image to upload');
                return;
            }

            const formData = new FormData(uploadForm);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Images uploaded successfully and are pending approval!');
                    uploadForm.reset();
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