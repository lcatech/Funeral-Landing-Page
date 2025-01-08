<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include '../core/db_connection.php';

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch pending tributes
$result = $conn->query("SELECT * FROM tributes WHERE status='pending'");

/* // Function to log admin activity
function logAdminActivity($adminId, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $adminId, $action);
    $stmt->execute();
    $stmt->close();
} */

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($id && ($action === 'approve' || $action === 'reject')) {
        $adminId = $_SESSION['admin_id']; // Fetch logged-in admin's ID

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE tributes SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            logAdminActivity($adminId, "Approved tribute ID $id");
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("DELETE FROM tributes WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            logAdminActivity($adminId, "Rejected tribute ID $id");
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .logout-btn {
            padding: 8px 15px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .welcome-message {
            color: #28a745;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <h2>Pending Tributes</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Name</th>
                <th>Relationship</th>
                <th>Message</th>
                <th>Image</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['relationship']) ?></td>
                    <td><?= htmlspecialchars($row['message']) ?></td>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Tribute Image">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" name="action" value="approve">Approve</button>
                        </form>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" name="action" value="reject" class="reject-button">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No pending tributes.</p>
    <?php endif; ?>

    <a href="logout.php">Logout</a>
</body>
</html>