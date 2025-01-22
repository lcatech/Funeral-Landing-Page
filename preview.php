<?php
session_start();
require_once 'core/db_connection.php';

if (!isset($_SESSION['form_data'])) {
    header('Location: sharememory.php');
    exit;
}

$formData = $_SESSION['form_data'];

// Format message for preview while preserving formatting
function formatPreviewMessage($message) {
    // Decode HTML entities
    $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Normalize line endings
    $message = str_replace(["\r\n", "\r"], "\n", $message);
    
    // Split into lines
    $lines = explode("\n", $message);
    
    // Process each line
    $lines = array_map('trim', $lines);
    
    // Join lines back together
    $message = implode("\n", $lines);
    
    // Remove more than one consecutive line break
    $message = preg_replace('/\n{2,}/', "\n", $message);
    
    // Clean up any remaining whitespace issues
    $message = trim($message);
    
    return $message;
}

// Format the message before displaying
$formData['message'] = formatPreviewMessage($formData['message']);
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>Preview Your Tribute</title>
    <style>
        #preview-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .message-preview {
            white-space: pre-wrap;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        #preview-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        #preview-buttons button {
            padding: 0.75rem 1.5rem;
            font-size: 16px;
        }
        
        .delete-button {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div id="preview-container">
        <h3>Preview Your Tribute</h3>
        
        <div id="preview-content">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($formData['name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($formData['location']); ?></p>
            <?php if (!empty($formData['church_name'])): ?>
                <p><strong>Church:</strong> <?php echo htmlspecialchars($formData['church_name']); ?></p>
            <?php endif; ?>
            <p><strong>Relationship:</strong> <?php echo htmlspecialchars($formData['relationship']); ?></p>
            
            <div class="message-preview">
                <?php echo nl2br(htmlspecialchars($formData['message'])); ?>
            </div>
            
            <?php if (isset($formData['temp_image'])): ?>
                <p><strong>Image:</strong></p>
                <div class="image-preview">
                    <img src="data:image/jpeg;base64,<?php echo $formData['temp_image']; ?>" style="max-width: 300px;">
                    <p class="image-name"><?php echo htmlspecialchars($formData['image_name']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="preview-buttons">
            <form action="sharememory.php" method="POST" id="submitForm">
                <input type="hidden" name="action" value="submit">
                <?php foreach ($formData as $key => $value): ?>
                    <?php if ($key !== 'action'): ?>
                        <textarea style="display: none;" name="<?php echo $key; ?>"><?php echo htmlspecialchars($value); ?></textarea>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Submit Tribute</button>
            </form>
            
            <button onclick="window.history.back()">Edit</button>
            
            <form action="sharememory.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="delete-button">Delete</button>
            </form>
        </div>
    </div>

    <?php include 'nav/footer.php'; ?>
</body>
</html>