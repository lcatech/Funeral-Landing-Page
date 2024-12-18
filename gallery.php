<?php
include 'core/db_connection.php';

// Fetch images for the gallery
$galleryQuery = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
   
    <title>Gallery</title>
  
</head>
<?php include 'nav/header.php'; ?>
<body>
    <div class="gallery-container">
        <h1>Gallery</h1>
        <div class="gallery-grid">
            <?php while ($row = $galleryQuery->fetch_assoc()): ?>
                <div class="gallery-item">
                    <a href="#modal-<?= $row['id'] ?>">
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                    </a>
                    <p class="gallery-title"><?= htmlspecialchars($row['title']) ?></p>
                </div>
                <!-- Modal for full image -->
                <div id="modal-<?= $row['id'] ?>" class="modal">
                    <a href="#" class="modal-close">&times;</a>
                    <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                    <div class="modal-caption">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php include 'nav/footer.php'; ?>
</body>
</html>
