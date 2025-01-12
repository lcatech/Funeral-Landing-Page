<?php
require_once 'core/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = !empty($_POST['email']) ? $_POST['email'] : NULL;
    $service_of_songs_guest_count = (int) $_POST['service_of_songs_guest_count'];
    $main_funeral_guest_count = (int) $_POST['main_funeral_guest_count'];

    // Validate inputs
    if (empty($name) || empty($mobile) || $service_of_songs_guest_count < 0 || $main_funeral_guest_count < 0) {
        $error = "Please fill all required fields correctly.";
    } else {
        // Insert the RSVP data into the database
        $stmt = $conn->prepare("INSERT INTO rsvp (name, mobile, email, service_of_songs_guest_count, main_funeral_guest_count) 
                                VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssii", $name, $mobile, $email, $service_of_songs_guest_count, $main_funeral_guest_count);

            if ($stmt->execute()) {
                // Redirect to the same page with a success flag
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit(); // Ensure no further code is executed
            } else {
                $error = "Error: Could not save your RSVP. " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Error: Failed to prepare the statement. " . $conn->error;
        }
    }
}

// Check for success flag in the URL
if (isset($_GET['success'])) {
    $success = "Thank you for your RSVP!";
    // Redirect again to clear the success flag from the URL
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>RSVP | Funeral Events</title>
</head>

<body>
    <section class="form-container">
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <h3>RSVP for Memorial Events</h3>
        <form method="POST" enctype="multipart/form-data">
            <fieldset>
                <input type="text" name="name" placeholder="Your full name" required>
            </fieldset>
            
            <fieldset>
                <input type="tel" name="mobile" placeholder="Your mobile number" required>
            </fieldset>
            
            <fieldset>
                <input type="email" name="email" placeholder="Your email (Optional)">
            </fieldset>
            
            <fieldset>
                <label style="color: #666; font-size: 1.4rem; margin-bottom: 0.8rem; display: block;">
                    Number of Guests for Service of Songs (Including Yourself)
                </label>
                <input type="number" name="service_of_songs_guest_count" min="0" value="0" required>
            </fieldset>
            
            <fieldset>
                <label style="color: #666; font-size: 1.4rem; margin-bottom: 0.8rem; display: block;">
                    Number of Guests for Main Funeral (Including Yourself)
                </label>
                <input type="number" name="main_funeral_guest_count" min="0" value="0" required>
            </fieldset>
            
            <fieldset>
                <button type="submit">Submit RSVP</button>
            </fieldset>
        </form>
    </section>
    
    <?php include 'nav/footer.php'; ?>
</body>
</html>