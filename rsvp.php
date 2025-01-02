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


<!DOCTYPE html>
<html lang="en">

<head>
    <title>RSVP | Funeral Events</title>
    <?php include 'nav/header.php'; ?>

</head>
<style>


</style>
<body>
<div style="height: 10px;"></div> <!-- Adds vertical space -->

    <div class="form-section2">


        <div class="rsvp-form">
            <h4>RSVP for Memorial Events</h4>

            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="POST" id="rsvp-form">
                <fieldset>
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required>

                    <label for="mobile">Your Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile" required>

                    <label for="email">Your Email (Optional)</label>
                    <input type="email" id="email" name="email">

                    <label for="service_of_songs_guest_count">Number of Guests for Service of Songs (Including
                        Yourself)</label>
                    <input type="number" id="service_of_songs_guest_count" name="service_of_songs_guest_count" min="0"
                        value="0" required>

                    <label for="main_funeral_guest_count">Number of Guests for Main Funeral (Including Yourself)</label>
                    <input type="number" id="main_funeral_guest_count" name="main_funeral_guest_count" min="0" value="0"
                        required>
                </fieldset>

                <button type="submit" class="button-rsvp">Submit RSVP</button>
            </form>
        </div>
    </div>

    <div style="height: 20px;"></div> <!-- Adds vertical space -->

</body>
<?php include 'nav/footer.php'; ?>

</html>