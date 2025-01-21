<?php
session_start();
require_once 'core/db_connection.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request');
    }

    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = !empty($_POST['email']) ? $_POST['email'] : NULL;
    $service_of_songs_guest_count = (int) $_POST['service_of_songs_guest_count'];
    $main_funeral_guest_count = (int) $_POST['main_funeral_guest_count'];

    // Enhanced validation
    if (empty($name) || empty($mobile) || $service_of_songs_guest_count < 0 || $main_funeral_guest_count < 0) {
        $error = "Please fill all required fields correctly.";
    } elseif (!preg_match("/^[0-9+\-\s()]*$/", $mobile)) {
        $error = "Invalid phone number format";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Insert the RSVP data into the database
        $stmt = $conn->prepare("INSERT INTO rsvp (name, mobile, email, service_of_songs_guest_count, main_funeral_guest_count) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sssii", $name, $mobile, $email, $service_of_songs_guest_count, $main_funeral_guest_count);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Thank you for your RSVP!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Error: Could not save your RSVP. " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Error: Failed to prepare the statement. " . $conn->error;
        }
    }
}

$pageTitle = "RSVP | Funeral Events";


include 'nav/header.php';
?>
    <!-- Custom styles for RSVP form -->
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .error {
            color: #dc3545;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            background-color: #f8d7da;
        }
        .success {
            color: #28a745;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
        background-color: #d4edda;
        font-size: 2rem;  /* Increased font size */
        text-align: center;  /* Center align */
        font-weight: bold;  /* Make it bold */
        }
    </style>

    <section class="form-container">
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <h3>RSVP for Memorial Events</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <fieldset>
                <input type="text" name="name" placeholder="Your full name" required 
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </fieldset>

            <fieldset>
                <input type="tel" name="mobile" placeholder="Your mobile number" required
                       pattern="[0-9+\-\s()]*"
                       value="<?= isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : '' ?>">
            </fieldset>

            <fieldset>
                <input type="email" name="email" placeholder="Your email (Optional)"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </fieldset>

            <fieldset>
                <label style="color: #666; font-size: 1.4rem; margin-bottom: 0.8rem; display: block;">
                    Number of Guests for Service of Songs (Including Yourself)
                </label>
                <input type="number" name="service_of_songs_guest_count" min="0" 
                       value="<?= isset($_POST['service_of_songs_guest_count']) ? htmlspecialchars($_POST['service_of_songs_guest_count']) : '0' ?>" 
                       required>
            </fieldset>

            <fieldset>
                <label style="color: #666; font-size: 1.4rem; margin-bottom: 0.8rem; display: block;">
                    Number of Guests for Main Funeral (Including Yourself)
                </label>
                <input type="number" name="main_funeral_guest_count" min="0" 
                       value="<?= isset($_POST['main_funeral_guest_count']) ? htmlspecialchars($_POST['main_funeral_guest_count']) : '0' ?>" 
                       required>
            </fieldset>

            <fieldset>
                <button type="submit">Submit RSVP</button>
            </fieldset>
        </form>
    </section>

<?php include 'nav/footer.php'; ?>
</body>
</html>