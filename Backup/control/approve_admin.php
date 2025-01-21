<?php
session_start();
include '../core/db_connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Add the logAdminActivity function
function logAdminActivity($adminId, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $adminId, $action);
    $stmt->execute();
    $stmt->close();
}

// Fetch pending admin accounts
$result = $conn->query("SELECT a.*, c.username as created_by_username 
                       FROM admin_users a 
                       LEFT JOIN admin_users c ON a.created_by = c.id 
                       WHERE a.status = 'pending'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $admin_id = filter_var($_POST['admin_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    
    if ($admin_id) {
        try {
            if ($action === 'approve') {
                $role = $_POST['role'];
                if (!in_array($role, ['super_user', 'admin'])) {
                    throw new Exception("Invalid role selected");
                }
                
                $stmt = $conn->prepare("UPDATE admin_users SET status='active', role=?, approved_by=?, approved_at=CURRENT_TIMESTAMP WHERE id=?");
                $stmt->bind_param("sii", $role, $_SESSION['admin_id'], $admin_id);
                if ($stmt->execute()) {
                    logAdminActivity($_SESSION['admin_id'], "Approved user ID: $admin_id with role: $role");
                    $success = "Account approved successfully";
                }
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("UPDATE admin_users SET status='disabled', approved_by=?, approved_at=CURRENT_TIMESTAMP WHERE id=?");
                $stmt->bind_param("ii", $_SESSION['admin_id'], $admin_id);
                if ($stmt->execute()) {
                    logAdminActivity($_SESSION['admin_id'], "Rejected user ID: $admin_id");
                    $success = "Account rejected successfully";
                }
            }
            header("Location: approve_admin.php");
            exit();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Admin Accounts</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .approve { background-color: #28a745; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        .reject { background-color: #dc3545; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
        select { padding: 5px; margin-right: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .back-link { display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Pending Admin Approvals</h1>
    
    <?php if (isset($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Username</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['created_by_username']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="admin_id" value="<?= $row['id'] ?>">
                            <select name="role" required>
                                <option value="super_user">Super User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <button type="submit" name="action" value="approve" class="approve">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No pending admin accounts to approve.</p>
    <?php endif; ?>

    <a href="admin.php" class="back-link">Back to Admin Panel</a>
</body>
</html>