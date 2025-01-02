<?php
include 'core/db_connection.php';

// Fetch approved tributes in descending order by submission date
$result = $conn->query("SELECT * FROM tributes WHERE status='approved' ORDER BY created_at DESC");

// Fetch a random ID from the banners table
$randomIdQuery = $conn->query("SELECT id FROM banners WHERE banner_type='tribute' ORDER BY RAND() LIMIT 1");

if ($randomIdQuery->num_rows > 0) {
    $randomId = $randomIdQuery->fetch_assoc()['id'];

    // Use the random ID to fetch the corresponding image_path
    $bannerImageQuery = $conn->query("SELECT image_path FROM banners WHERE id=$randomId LIMIT 1");
    $bannerImage = $bannerImageQuery->num_rows > 0 ? $bannerImageQuery->fetch_assoc()['image_path'] : 'default-banner.jpg';
} else {
    // Fallback to a default image if no records are found
    $bannerImage = 'images/hero-image.png';
}


?>
<!DOCTYPE html>
<html lang="en">

<head>


    <title>Tributes | Reverend Elijah O. Akinyemi</title>

</head>
<?php include 'nav/header.php'; ?>

<body>

    <!-- Banner Section -->
    <div class="banner">
        <div class="banner-text">
            <h1>Rev. Elijah Oluranti Akinyemi</h1>
            <p>1956 - 2024</p>
        </div>
        <div class="banner-image">
            <img src="<?= htmlspecialchars($bannerImage) ?>" alt="Rev. Elijah Oluranti Akinyemi">
        </div>
    </div>

    <!-- Leave a Tribute Button -->

    <div class="tribute-wrapper">
        <div class="h1-container">
            <h1 style="  color: #efbf04;
font-size: 4rem;
  font-family: Sofia;">Tributes</h1>
        </div>

        <div class="banner-button">
            <a href="sharememory.php" class="cta-button"> <i class="fa-solid fa-pen"></i> Leave a Tribute</a>
        </div>
    </div>
    <!-- Tributes Section -->
    <div class="tributes-container">

        <?php if ($result->num_rows > 0): ?>
            <div class="tributes-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="tribute">
                        <h2>
                            <?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['relationship']) ?>)
                        </h2>
                        <p class="tribute-date"><?= date('F j, Y', strtotime($row['created_at'])) ?></p>
                        <p><?= htmlspecialchars($row['message']) ?></p>
                        <?php if ($row['image']): ?>
                            <a href="#modal-<?= $row['id'] ?>">
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Tribute Image" class="tribute-thumbnail">
                            </a>
                            <!-- Modal -->
                            <div id="modal-<?= $row['id'] ?>" class="modal">
                                <a href="#" class="modal-close">&times;</a>
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Full Tribute Image">
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No submitted tributes yet.</p>
        <?php endif; ?>
    </div>
    <div style="height: 20px;"></div> <!-- Adds vertical space -->


</body>
<?php include 'nav/footer.php'; ?>


</html>