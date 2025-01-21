<?php
session_start();
include '../core/db_connection.php';

// Ensure admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT admin_activity_log.*, admin_users.username 
                        FROM admin_activity_log 
                        JOIN admin_users ON admin_activity_log.admin_id = admin_users.id 
                        ORDER BY timestamp DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Activity Logs</title>
</head>
<body>
    <h1>Admin Activity Logs</h1>
    <table border="1">
        <tr>
            <th>Admin</th>
            <th>Action</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td><?= htmlspecialchars($row['timestamp']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
