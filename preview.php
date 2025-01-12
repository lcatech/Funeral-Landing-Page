<?php
session_start();
require_once 'core/db_connection.php';

if (!isset($_SESSION['form_data'])) {
    header('Location: /sharememory');
    exit;
}

$formData = $_SESSION['form_data'];
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>Preview Your Tribute</title>
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
            <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($formData['message'])); ?></p>
            
            <?php if (isset($formData['temp_image'])): ?>
                <p><strong>Image:</strong><br>
                <img src="data:image/jpeg;base64,<?php echo $formData['temp_image']; ?>" style="max-width: 300px;">
                <p class="image-name"><?php echo htmlspecialchars($formData['image_name']); ?></p>
                </p>
            <?php endif; ?>
        </div>
        
        <div id="preview-buttons">
            <form action="/sharememory" method="POST">
                <input type="hidden" name="action" value="submit">
                <?php foreach ($formData as $key => $value): ?>
                    <?php if ($key !== 'action'): ?>
                        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Submit Tribute</button>
            </form>
            
            <button onclick="window.history.back()">Edit</button>
            
            <form action="/sharememory" method="POST">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="delete-button">Delete</button>
            </form>
        </div>
    </div>

    <?php include 'nav/footer.php'; ?>
</body>
</html>