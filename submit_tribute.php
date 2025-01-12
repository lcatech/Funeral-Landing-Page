<?php
include '/core/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $name = htmlspecialchars(trim($_POST['name']));
    $location = htmlspecialchars(trim($_POST['location']));
    $church_name = htmlspecialchars(trim($_POST['church_name']));
    $relationship = htmlspecialchars(trim($_POST['relationship']));
    $message = htmlspecialchars(trim($_POST['message']));
    $status = 'pending'; // Default status is 'pending'
    $reference = uniqid(); // Generate a unique reference ID

    // Initialize variables
    $imagePath = null;
    $uploadError = '';

    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $imageName = basename($_FILES['image']['name']);
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];
        $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type
        if (in_array($fileExtension, $allowedExtensions)) {
            // Set the upload path
            $uploadDir = 'uploads/';
            $newFileName = uniqid() . '.' . $fileExtension; // Ensure unique file name
            $imagePath = $uploadDir . $newFileName;

            // Move uploaded file to the target directory
            if (!move_uploaded_file($fileTmpPath, $imagePath)) {
                $uploadError = "Failed to upload the image. Please try again.";
            }
        } else {
            $uploadError = "Invalid file type. Allowed types are: " . implode(', ', $allowedExtensions);
        }
    }

    // Handle database insertion
    if (empty($uploadError)) {
        try {
            // Prepare the SQL query
            $stmt = $conn->prepare("INSERT INTO tributes (name, location, church_name, relationship, message, image, status, reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssss', $name, $location, $church_name, $relationship, $message, $imagePath, $status, $reference);

            // Execute the query
            if ($stmt->execute()) {
                // Redirect to preview page with reference
                header("Location: preview.php?reference=$reference");
                exit();
            } else {
                throw new Exception("Database insertion failed. Please try again.");
            }
        } catch (Exception $e) {
            echo "<p>Error: " . $e->getMessage() . "</p>";
        } finally {
            $stmt->close();
            $conn->close();
        }
    } else {
        // Display upload error if any
        echo "<p>Error: $uploadError</p>";
    }
}
?>
