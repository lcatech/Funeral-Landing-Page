<?php
session_start();
require_once 'core/db_connection.php';
require_once 'core/email_notification.php';
require_once 'TributeProcessor.php';
$processor = new TributeProcessor($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'preview':
                $_SESSION['form_data'] = $_POST;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $tmpName = $_FILES['image']['tmp_name'];
                    $_SESSION['form_data']['temp_image'] = base64_encode(file_get_contents($tmpName));
                    $_SESSION['form_data']['image_name'] = $_FILES['image']['name'];
                }
                header('Location: /preview');
                exit;
                
                case 'submit':
                    // Generate shorter reference ID using current timestamp and random numbers
                    $reference = 'T' . strtoupper(substr(base_convert(time(), 10, 36) . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3), 0, 8));
                    $imagePath = null;
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $uploadDir = 'uploads/tributes/';
                    
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $uniqueFilename = $reference . '_' . time() . '.' . $imageExtension;
                    $imagePath = $uploadDir . $uniqueFilename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                        $_SESSION['error'] = "Error uploading image. Please try again.";
                        header('Location: /sharememory');
                        exit;
                    }
                } elseif (isset($_SESSION['form_data']['temp_image']) && isset($_SESSION['form_data']['image_name'])) {
                    $uploadDir = 'uploads/tributes/';
                    
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $imageExtension = pathinfo($_SESSION['form_data']['image_name'], PATHINFO_EXTENSION);
                    $uniqueFilename = $reference . '_' . time() . '.' . $imageExtension;
                    $imagePath = $uploadDir . $uniqueFilename;
                    
                    $imageData = base64_decode($_SESSION['form_data']['temp_image']);
                    if (!file_put_contents($imagePath, $imageData)) {
                        $_SESSION['error'] = "Error saving image. Please try again.";
                        header('Location: /sharememory');
                        exit;
                    }
                }
                
        
                
                // Process the tribute using TributeProcessor
    if ($processor->processNewTribute([
        'name' => $_POST['name'],
        'location' => $_POST['location'],
        'church_name' => $_POST['church_name'],
        'relationship' => $_POST['relationship'],
        'message' => $_POST['message'],
        'reference' => $reference,
        'image' => $imagePath
    ])) {
        // Prepare tribute data for email notification
        $tributeData = [
            'reference' => $reference,
            'name' => $_POST['name'],
            'location' => $_POST['location'],
            'church_name' => $_POST['church_name'],
            'relationship' => $_POST['relationship'],
            'message' => $_POST['message'],
            'image' => $imagePath,
            'status' => 'pending'
        ];
                    
                    // Send email notification
        $notification = new TributeNotification();
        $notification->sendNewTributeNotification($tributeData);
                    
                    // Set success message and redirect
        $_SESSION['success'] = "Thank you for your tribute. It has been submitted successfully and will be available on the page after review.";
        unset($_SESSION['form_data']);
        header('Location: tributes.php');
        exit();
    } else {
        $_SESSION['error'] = "Error submitting tribute. Please try again.";
        header('Location: /sharememory');
        exit();
    }
    break;
                
            case 'delete':
                if (isset($_POST['id'])) {
                    // Delete associated image if exists
                    $stmt = $conn->prepare("SELECT image FROM tributes WHERE id = ?");
                    $stmt->bind_param("i", $_POST['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        if (!empty($row['image']) && file_exists($row['image'])) {
                            unlink($row['image']);
                        }
                    }
                    
                    // Delete the tribute record
                    $stmt = $conn->prepare("DELETE FROM tributes WHERE id = ?");
                    $stmt->bind_param("i", $_POST['id']);
                    $stmt->execute();
                }
                header('Location: /sharememory');
                exit;
        }
    }
}
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>Submit Your Tribute</title>
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        input[type="text"],
        select,
        textarea {
            font-size: 16px;
            color: #333;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        fieldset {
            border: none;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <section class="form-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <h3>Submit Your Tribute</h3>
<div style="text-align: center; margin-bottom: 2rem;">
            <p style="color: #333; font-size: 1.4rem;">All tributes will be reviewed before being published online.</p>
        </div>
        <form action="/sharememory" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="preview">
            <fieldset>
                <input type="text" name="name" placeholder="Your full name" required>
            </fieldset>
            <fieldset>
                <input type="text" name="location" placeholder="Your location" required>
            </fieldset>
            <fieldset>
                <input type="text" name="church_name" placeholder="Church name (optional)">
            </fieldset>
            <fieldset>
                <select name="relationship" required>
                    <option value="">Select Relationship</option>
                    <option value="Family">Family</option>
                    <option value="Friend">Friend</option>
                    <option value="Church">Church</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                </select>
            </fieldset>
            <fieldset>
                <textarea name="message" placeholder="Type your message here..." required style="width: 100%; min-height: 150px; line-height: 1.6; padding: 12px;"></textarea>
            </fieldset>
            <fieldset>
                <p style="font-size: 16px; color: #333; margin-bottom: 0.5rem;">Upload an image (optional)</p>
                <input type="file" name="image" accept="image/*">
                <p style="font-size: 14px; color: #666; margin-top: 0.5rem;">Supported formats: JPG, PNG, GIF (Max size: 5MB)</p>
            </fieldset>
            <fieldset>
                <button type="submit" style="font-size: 16px; padding: 12px 24px;">Preview Tribute</button>
            </fieldset>
        </form>
    </section>
    <?php include 'nav/footer.php'; ?>
</body>
</html>