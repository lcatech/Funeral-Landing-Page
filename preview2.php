<?php
include 'core/db_connection.php';

$reference = $_GET['reference'] ?? null;

if (!$reference) {
    echo "Reference not provided.";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM tributes WHERE reference = ?");
$stmt->bind_param('s', $reference);
$stmt->execute();
$result = $stmt->get_result();
$tribute = $result->fetch_assoc();

if (!$tribute) {
    echo "Invalid reference.";
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tributes WHERE reference = ?");
    $stmt->bind_param('s', $reference);
    $stmt->execute();
    echo "<p>Your tribute has been successfully deleted.</p>";
    echo '<a href="index.php">Return to Home</a>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Your Submitted Tribute</title>
</head>
<?php include 'nav/header.php'; ?>

<body>

    <section class="preview-container">
        <h3>Your Submitted Tribute</h3>
        <div class="preview-content">
            <p><strong>Name:</strong> <?= htmlspecialchars($tribute['name']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($tribute['location']) ?></p>
            <p><strong>Church Name:</strong> <?= htmlspecialchars($tribute['church_name']) ?></p>
            <p><strong>Relationship:</strong> <?= htmlspecialchars($tribute['relationship']) ?></p>
            <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($tribute['message'])) ?></p>
            <?php if ($tribute['image']): ?>
                <img src="<?= htmlspecialchars($tribute['image']) ?>" alt="Uploaded Image" style="max-width: 200px; border-radius: 8px;">
            <?php endif; ?>
        </div>
        <form method="post">
            <button name="delete" style="background-color: red; color: white;">Delete Tribute</button>
        </form>
        <a href="index.php" class="button" style="margin-top: 20px;">Return to Home</a> |         <a href="tributes.php" class="button" style="margin-top: 20px;">View Tributes</a>

    </section>

</body>

<?php include 'nav/footer.php'; ?>

</html>
