<?php
// PHP code remains the same
$config = require 'config.php';

if (!file_exists($config['pending_dir'])) {
    mkdir($config['pending_dir'], 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prevent any HTML output for AJAX requests
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    try {
        if (!isset($_FILES['images'])) {
            throw new Exception('No images uploaded');
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
            $filename = date('Y-m-d-H-i-s') . '_' . uniqid() . '.' . $extension;
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

// Only output HTML if not an AJAX request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include "../nav/header.php";
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Images</title>
    <style>
        /* Scoped styles for upload page */
        .image-upload-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .image-upload-title {
            font-family: "Sofia", cursive;
            color: #efbf04;
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }

        .image-upload-notice {
            background: rgba(33, 28, 12, 0.8);
            color: #fad14b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 14px;
            text-align: center;
            border: 1px solid rgba(239, 191, 4, 0.2);
            font-size: 18px;
            font-weight: 500;
        }

        .image-upload-form {
            background: rgba(33, 28, 12, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(239, 191, 4, 0.2);
            text-align: center;
        }

        .image-upload-input {
            display: none;
        }

        .image-upload-area {
            border: 2px dashed #efbf04;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(239, 191, 4, 0.05);
            margin-bottom: 20px;
        }

        .image-upload-area.drag-over {
            background: rgba(239, 191, 4, 0.1);
            border-color: #fad14b;
        }

        .image-upload-area svg {
            width: 48px;
            height: 48px;
            margin-bottom: 15px;
            color: #efbf04;
        }

        .image-upload-area p {
            color: #fad14b;
            margin: 10px 0;
            font-size: 16px;
        }

        .image-upload-button {
            background: #efbf04;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin: 5px;
            display: inline-block;
        }

        .image-upload-button:hover {
            background: #d1a204;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: rgba(33, 28, 12, 0.6);
            border-radius: 8px;
            min-height: 100px;
            max-height: 400px;
            overflow-y: auto;
        }

        /* Styling the scrollbar */
        .image-preview-container::-webkit-scrollbar {
            width: 8px;
        }

        .image-preview-container::-webkit-scrollbar-track {
            background: rgba(33, 28, 12, 0.4);
            border-radius: 4px;
        }

        .image-preview-container::-webkit-scrollbar-thumb {
            background: #efbf04;
            border-radius: 4px;
        }

        .image-preview-container::-webkit-scrollbar-thumb:hover {
            background: #d1a204;
        }

        .image-preview-item {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(239, 191, 4, 0.3);
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-preview-item:hover img {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .image-upload-container {
                padding: 10px;
            }

            .image-preview-item {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="image-upload-container">
        <h2 class="image-upload-title">Upload Gallery Images</h2>
        
        <div class="image-upload-notice">
            Images will be reviewed by an administrator before being published to the gallery.
        </div>
        
        <div class="image-upload-form">
            <form id="upload-form" enctype="multipart/form-data">
                <input type="file" id="file-input" class="image-upload-input" name="images[]" multiple accept="image/*">
                <div class="image-upload-area" id="upload-area">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p>Drag and drop your images here</p>
                    <p>or</p>
                    <button type="button" class="image-upload-button" onclick="document.getElementById('file-input').click()">
                        Browse Files
                    </button>
                </div>
                <div class="image-preview-container" id="preview-container"></div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="image-upload-button" id="upload-button" style="display: none;">
                        Upload Images
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file-input');
        const previewContainer = document.getElementById('preview-container');
        const uploadForm = document.getElementById('upload-form');

        const uploadButton = document.getElementById('upload-button');
        
        const uploadArea = document.getElementById('upload-area');
        
        // Drag and drop handlers
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('drag-over');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('drag-over');
        }

        // Handle dropped files
        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            // Create a DataTransfer object to set files to the input
            const dataTransfer = new DataTransfer();
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    dataTransfer.items.add(file);
                }
            });
            
            // Set the files to the input element
            fileInput.files = dataTransfer.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                uploadButton.style.display = 'inline-block';
            }
            
            previewContainer.innerHTML = '';
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'image-preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        previewItem.appendChild(img);
                        previewContainer.appendChild(previewItem);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        
        fileInput.addEventListener('change', function(e) {
            handleFiles(this.files);
        });

        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!fileInput.files || fileInput.files.length === 0) {
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
                    uploadButton.style.display = 'none';
                } else {
                    throw new Error(result.message || 'Upload failed');
                }
            } catch (error) {
                alert('Error uploading images: ' + error.message);
                console.error(error);
            }
        });
    </script>

    <?php include '../nav/footer.php'; ?>

</body>
</html>