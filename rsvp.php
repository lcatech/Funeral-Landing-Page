<?php
require_once 'core/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $email = !empty($_POST['email']) ? $_POST['email'] : NULL;
    $service_of_songs_guest_count = (int)$_POST['service_of_songs_guest_count'];
    $main_funeral_guest_count = (int)$_POST['main_funeral_guest_count'];

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
                $success = "Thank you for your RSVP!";
            } else {
                $error = "Error: Could not save your RSVP. " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Error: Failed to prepare the statement. " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>


    <title>RSVP | Funeral Events</title>


    <style>
    
        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label, input, button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
    <?php include 'nav/header.php'; ?>

</head>
<body>
    <h1>RSVP for Memorial Events</h1>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Your Name</label>
        <input type="text" id="name" name="name" required>

        <label for="mobile">Your Mobile Number</label>
        <input type="tel" id="mobile" name="mobile" required>

        <label for="email">Your Email (Optional)</label>
        <input type="email" id="email" name="email">

        <label for="service_of_songs_guest_count">Number of Guests for Service of Songs (Including Yourself)</label>
        <input type="number" id="service_of_songs_guest_count" name="service_of_songs_guest_count" min="0" value="0" required>

        <label for="main_funeral_guest_count">Number of Guests for Main Funeral (Including Yourself)</label>
        <input type="number" id="main_funeral_guest_count" name="main_funeral_guest_count" min="0" value="0" required>

        <button type="submit">Submit RSVP</button>
    </form>
</body>
</html>
