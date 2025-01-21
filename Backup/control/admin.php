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

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';

// Validate sort field to prevent SQL injection
$allowed_sort_fields = ['id', 'name', 'status', 'relationship'];
if (!in_array($sort_field, $allowed_sort_fields)) {
    $sort_field = 'id';
}

// Validate sort order
if ($sort_order !== 'ASC' && $sort_order !== 'DESC') {
    $sort_order = 'DESC';
}

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
                    $_SESSION['success'] = "Tribute approved successfully.";
                    break;

                case 'pending':
                    $stmt = $conn->prepare("UPDATE tributes SET status='pending' WHERE id=?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    logAdminActivity($adminId, "Changed tribute ID $id status to pending");
                    $_SESSION['success'] = "Tribute set to pending.";
                    break;

                case 'delete':
                    if ($canDelete) {
                        // First get the image path
                        $stmt = $conn->prepare("SELECT image FROM tributes WHERE id=?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $tribute = $result->fetch_assoc();
                        
                        // Delete the image file if it exists
                        if ($tribute['image'] && file_exists("../" . $tribute['image'])) {
                            unlink("../" . $tribute['image']);
                        }
                        
                        $stmt = $conn->prepare("DELETE FROM tributes WHERE id=?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        logAdminActivity($adminId, "Deleted tribute ID $id");
                        $_SESSION['success'] = "Tribute deleted successfully.";
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
                    $_SESSION['success'] = "Tribute updated successfully.";
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

// Build the WHERE clause for filtering
$where_clauses = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search_query) {
    $where_clauses[] = "(name LIKE ? OR relationship LIKE ? OR message LIKE ?)";
    $search_term = "%{$search_query}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as count FROM tributes $where_sql";
$stmt = $conn->prepare($count_sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated results
$sql = "SELECT * FROM tributes $where_sql ORDER BY $sort_field $sort_order LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
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
        <div class="nav-left">
            <?php if ($userRole === 'admin'): ?>
                <a href="approve_admin.php">Manage Admin Accounts</a>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <span class="welcome-text">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <span class="role-badge <?= $userRole === 'admin' ? 'admin-badge' : 'super-user-badge' ?>">
                <?= ucfirst(str_replace('_', ' ', $userRole)) ?>
            </span>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </nav>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="filter-section">
        <form method="get" class="filters-form">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" 
                       value="<?= htmlspecialchars($search_query) ?>" 
                       placeholder="Search tributes...">
            </div>

            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <div class="table-container">
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>
                            <a href="?sort=id&order=<?= $sort_field === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>">
                                ID <?= $sort_field === 'id' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=name&order=<?= $sort_field === 'name' && $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>">
                                Name <?= $sort_field === 'name' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>Relationship</th>
                        <th>Message</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['relationship']) ?></td>
                            <?php /* Replace the message and image cells in your table with this updated version */ ?>
<td class="message-cell">
    <div class="message-preview" 
         onclick="toggleMessage(this)" 
         data-full-message="<?= htmlspecialchars($row['message']) ?>">
        <?= htmlspecialchars($row['message']) ?>
    </div>
    <div class="message-tooltip"></div>
</td>
<td class="image-cell">
    <?php if ($row['image']): ?>
        <img src="../<?= htmlspecialchars($row['image']) ?>" 
             alt="Tribute Image" 
             class="tribute-image"
             onclick="openImageModal(this.src)">
    <?php else: ?>
        <span class="no-image">No Image</span>
    <?php endif; ?>
</td>
                            <td>
                                <span class="status-badge <?= $row['status'] ?>-status">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="approve" class="approve-button">
                                            Approve
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="pending" class="pending-button">
                                            Set Pending
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                            class="edit-button">
                                        Edit
                                    </button>
                                    
                                    <?php if ($canDelete): ?>
                                        <button type="submit" 
                                                name="action" 
                                                value="delete" 
                                                class="delete-button"
                                                onclick="return confirm('Are you sure you want to delete this tribute?')">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>" 
                           class="pagination-link">First</a>
                        <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>" 
                           class="pagination-link">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>" 
                           class="pagination-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>" 
                           class="pagination-link">Next</a>
                        <a href="?page=<?= $total_pages ?>&status=<?= $status_filter ?>&search=<?= urlencode($search_query) ?>&sort=<?= $sort_field ?>&order=<?= $sort_order ?>" 
                           class="pagination-link">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="no-results">No tributes found.</p>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
    <div class="modal-content edit-modal-content">
        <span class="close" onclick="closeEditModal()" title="Close">&times;</span>
        <h2>Edit Tribute</h2>
        <form method="post" id="editForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_name">Name</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_relationship">Relationship</label>
                <input type="text" id="edit_relationship" name="relationship" required>
            </div>
            
            <div class="form-group">
                <label for="edit_message">Message</label>
                <textarea id="edit_message" name="message" required></textarea>
            </div>
            
            <button type="submit" class="save-button">Save Changes</button>
        </form>
    </div>
</div>

   <!-- Image Modal -->
<div id="imageModal" class="modal">
    <div class="modal-content image-modal-content">
        <span class="close" onclick="closeImageModal()" title="Close">&times;</span>
        <img id="modalImage" src="" alt="Full size tribute image">
    </div>
</div>

    <script>
        // Modal handling functions
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

        function openImageModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = src;
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('imageModal')) {
                closeImageModal();
            }
        }

        // Auto-submit form when status changes
        document.getElementById('status').addEventListener('change', function() {
            this.form.submit();
        });

        // Debounce search input
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        // Check URL parameters on page load for edit modal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tributeId = urlParams.get('tribute');
            const action = urlParams.get('action');
            
            if (tributeId && action === 'edit') {
                const rows = document.querySelectorAll('table tr');
                for (let row of rows) {
                    const firstCell = row.cells[0];
                    if (firstCell && firstCell.textContent === tributeId) {
                        const tributeData = {
                            id: firstCell.textContent,
                            name: row.cells[1].textContent,
                            relationship: row.cells[2].textContent,
                            message: row.cells[3].textContent
                        };
                        openEditModal(tributeData);
                        break;
                    }
                }
            }
        });

        // Add these functions to your existing script section

// Message preview functionality
function toggleMessage(element) {
    // First close any other open messages
    const allPreviews = document.querySelectorAll('.message-preview');
    allPreviews.forEach(preview => {
        if (preview !== element) {
            preview.classList.remove('expanded');
        }
    });

    // Hide all tooltips first
    const allTooltips = document.querySelectorAll('.message-tooltip');
    allTooltips.forEach(tooltip => {
        tooltip.style.display = 'none';
    });

    // Toggle the clicked message
    element.classList.toggle('expanded');
    
    // Handle tooltip
    const tooltip = element.nextElementSibling;
    if (!element.classList.contains('expanded')) {
        const fullMessage = element.getAttribute('data-full-message');
        if (element.scrollWidth > element.clientWidth) {
            tooltip.style.display = 'block';
            tooltip.textContent = fullMessage;
            
            // Position tooltip
            const rect = element.getBoundingClientRect();
            tooltip.style.top = `${rect.bottom + window.scrollY + 5}px`;
        }
    } else {
        tooltip.style.display = 'none';
    }
}

// Enhanced click outside handling
document.addEventListener('click', function(event) {
    if (!event.target.closest('.message-cell')) {
        const tooltips = document.querySelectorAll('.message-tooltip');
        tooltips.forEach(tooltip => {
            tooltip.style.display = 'none';
        });
        
        const previews = document.querySelectorAll('.message-preview');
        previews.forEach(preview => {
            preview.classList.remove('expanded');
        });
    }
});

// Add scroll event listener to hide tooltips when scrolling
let scrollTimeout;
document.addEventListener('scroll', function() {
    clearTimeout(scrollTimeout);
    
    // Hide all tooltips immediately when scrolling starts
    const tooltips = document.querySelectorAll('.message-tooltip');
    tooltips.forEach(tooltip => {
        tooltip.style.display = 'none';
    });
    
    const previews = document.querySelectorAll('.message-preview');
    previews.forEach(preview => {
        preview.classList.remove('expanded');
    });
    
    // Set a timeout to prevent rapid re-showing of tooltips during scroll
    scrollTimeout = setTimeout(() => {
        // Do nothing after scroll ends
    }, 150);
}, { passive: true });

// Add resize event listener to reposition tooltips
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    
    // Hide all tooltips immediately when resizing starts
    const tooltips = document.querySelectorAll('.message-tooltip');
    tooltips.forEach(tooltip => {
        tooltip.style.display = 'none';
    });
    
    const previews = document.querySelectorAll('.message-preview');
    previews.forEach(preview => {
        preview.classList.remove('expanded');
    });
    
    resizeTimeout = setTimeout(() => {
        // Do nothing after resize ends
    }, 150);
}, { passive: true });
    </script>
</body>
</html>