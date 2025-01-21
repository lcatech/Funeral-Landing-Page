<?php
session_start();

// Check if user is logged in and has appropriate access
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_user')) {
    header("Location: login.php");
    exit();
}

$userRole = $_SESSION['role'];
$canDelete = ($userRole === 'admin'); // Only admins can delete

include '../core/db_connection.php';

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Function to log admin activity
function logAdminActivity($adminId, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $adminId, $action);
    $stmt->execute();
    $stmt->close();
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    $adminId = $_SESSION['admin_id'];

    if ($id) {
        try {
            switch($action) {
                case 'approve':
                    $stmt = $conn->prepare("UPDATE tributes SET status='approved' WHERE id=?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    logAdminActivity($adminId, "Changed tribute ID $id status to approved");
                    break;

                case 'pending':
                    $stmt = $conn->prepare("UPDATE tributes SET status='pending' WHERE id=?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    logAdminActivity($adminId, "Changed tribute ID $id status to pending");
                    break;

                case 'delete':
                    if ($canDelete) {
                        $stmt = $conn->prepare("DELETE FROM tributes WHERE id=?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        logAdminActivity($adminId, "Deleted tribute ID $id");
                    }
                    break;

                case 'update':
                    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
                    $relationship = filter_var($_POST['relationship'], FILTER_SANITIZE_STRING);
                    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
                    
                    $stmt = $conn->prepare("UPDATE tributes SET name=?, relationship=?, message=? WHERE id=?");
                    $stmt->bind_param("sssi", $name, $relationship, $message, $id);
                    $stmt->execute();
                    logAdminActivity($adminId, "Updated tribute ID $id details");
                    break;
            }
            
            header("Location: admin.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error processing request: " . $e->getMessage();
            header("Location: admin.php");
            exit();
        }
    }
}

// Fetch all tributes with optional filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause = $status_filter !== 'all' ? "WHERE status = '" . $conn->real_escape_string($status_filter) . "'" : "";
$result = $conn->query("SELECT * FROM tributes $where_clause ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    
    <link rel="stylesheet" href="admin-styles.css">
</head>
<body>
    <nav>
        <?php if ($userRole === 'admin'): ?>
            <a href="approve_admin.php">Manage Admin Accounts</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </nav>

    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! 
        <span class="role-badge <?= $userRole === 'admin' ? 'admin-badge' : 'super-user-badge' ?>">
            <?= ucfirst(str_replace('_', ' ', $userRole)) ?>
        </span>
    </h1>

    <?php if (isset($_SESSION['error'])): ?>
        <p class="error"><?= htmlspecialchars($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <p class="error"><?= htmlspecialchars($_SESSION['error']) ?></p>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <p class="success"><?= htmlspecialchars($_SESSION['success']) ?></p>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

    <div class="filter-section">
        <h3>Filter by Status:</h3>
        <a href="?status=all" class="<?= $status_filter === 'all' ? 'active' : '' ?>">All</a> |
        <a href="?status=pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a> |
        <a href="?status=approved" class="<?= $status_filter === 'approved' ? 'active' : '' ?>">Approved</a>
    </div>

    <h2>Tributes</h2>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Relationship</th>
                <th>Message</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['relationship']) ?></td>
                    <td><?= htmlspecialchars($row['message']) ?></td>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="../<?= htmlspecialchars($row['image']) ?>" alt="Tribute Image" style="max-width: 100px; height: auto;">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= $row['status'] ?>-status">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <?php if ($row['status'] === 'pending'): ?>
                                <button type="submit" name="action" value="approve" class="approve-button">Approve</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="pending" class="reject-button">Set Pending</button>
                            <?php endif; ?>
                        </form>
                        
                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" class="edit-button">Edit</button>
                        
                        <?php if ($canDelete): ?>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" name="action" value="delete" class="reject-button" 
                                    onclick="return confirm('Are you sure you want to delete this tribute?')">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No tributes found.</p>
    <?php endif; ?>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Tribute</h2>
            <form method="post" id="editForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Name:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_relationship">Relationship:</label>
                    <input type="text" id="edit_relationship" name="relationship" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_message">Message:</label>
                    <textarea id="edit_message" name="message" required></textarea>
                </div>
                
                <button type="submit" class="approve-button">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(row) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_id').value = row.id;
            document.getElementById('edit_name').value = row.name;
            document.getElementById('edit_relationship').value = row.relationship;
            document.getElementById('edit_message').value = row.message;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        // Add this to your existing script section
document.addEventListener('DOMContentLoaded', function() {
    // Check for parameters in URL
    const urlParams = new URLSearchParams(window.location.search);
    const tributeId = urlParams.get('tribute');
    const action = urlParams.get('action');
    
    if (tributeId && action === 'edit') {
        // Find the tribute data in the table
        const rows = document.querySelectorAll('table tr');
        for (let row of rows) {
            const firstCell = row.cells[0];
            if (firstCell && firstCell.textContent === tributeId) {
                // Create the tribute data object
                const tributeData = {
                    id: firstCell.textContent,
                    name: row.cells[1].textContent,
                    relationship: row.cells[2].textContent,
                    message: row.cells[3].textContent
                };
                // Open the edit modal
                openEditModal(tributeData);
                break;
            }
        }
    }
});
    </script>
</body>
</html>