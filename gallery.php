<?php
$galleryDir = 'images/gallery/'; // Path to the gallery folder
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image types

// Scan the directory for files
$images = array_filter(scandir($galleryDir), function ($file) use ($galleryDir, $allowedExtensions) {
    $filePath = $galleryDir . $file;
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return is_file($filePath) && in_array($fileExtension, $allowedExtensions);
});

// Re-index array for easier handling
$images = array_values($images);

// Define categories and group images by category
$categories = ['earlylife', 'wedding', 'family', 'ministry'];
$imagesByCategory = [];
foreach ($categories as $category) {
    $imagesByCategory[$category] = array_filter($images, function ($file) use ($category) {
        return stripos($file, $category) !== false; // Match category in filename
    });
}

// Sorting and Filtering
$selectedCategory = isset($_GET['category']) ? strtolower($_GET['category']) : 'all';
$filteredImages = $selectedCategory === 'all' ? $images : ($imagesByCategory[$selectedCategory] ?? []);

// Pagination setup
$itemsPerPage = 20;
$totalImages = count($filteredImages);
$totalPages = ceil($totalImages / $itemsPerPage);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages)); // Clamp page value between 1 and totalPages
$startIndex = ($page - 1) * $itemsPerPage;
$paginatedImages = array_slice($filteredImages, $startIndex, $itemsPerPage);

$pageUrl = "?category=$selectedCategory&page=";
?>

<?php include 'nav/header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Gallery | Reverend Elijah O. Akinyemi</title>
    <style>
        /* Grid Layout */
        .gallery-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 2rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 2fr);
            gap: 2.2rem;
        }

        .gallery-item {
            position: relative;
            width: 100%;
            padding-top: 100%;
            overflow: hidden;
            border-radius: 1.2rem;
        }

        .gallery-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.6s;
        }

        .gallery-item img:hover {
            transform: scale(1.05);
        }

        /* Category Label */
        .category-label {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 5px;
        }

        /* Pagination */
        .pagination {
            text-align: center;
            margin-top: 2rem;
        }

        .pagination a {
            display: inline-block;
            margin: 0 5px;
            padding: 10px 15px;
            background: #efbf04;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .pagination a:hover {
            background: #d1a204;
        }

        .pagination .active {
            background: #d1a204;
            pointer-events: none;
        }

        @media (max-width: 75em) {
            .gallery-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 63em) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="gallery-container">
        <h1 style="color: #d1a204; font-size: 6rem; font-family: Sofia; margin-bottom: 3rem;">Gallery</h1>
        <form method="get" style="text-align: center; margin-bottom: 2rem;">
            <select name="category">
                <option value="all" <?= $selectedCategory === 'all' ? 'selected' : '' ?>>All</option>
                <option value="earlylife" <?= $selectedCategory === 'earlylife' ? 'selected' : '' ?>>Early Life</option>
                <option value="wedding" <?= $selectedCategory === 'wedding' ? 'selected' : '' ?>>Wedding</option>
                <option value="family" <?= $selectedCategory === 'family' ? 'selected' : '' ?>>Family</option>
                <option value="ministry" <?= $selectedCategory === 'ministry' ? 'selected' : '' ?>>Ministry</option>
            </select>
            <button type="submit">Apply</button>
        </form>

        <div class="gallery-grid">
            <?php foreach ($paginatedImages as $image): ?>
                <div class="gallery-item">
                    <span class="category-label">
                        <?= ucfirst(explode('_', $image)[0] ?? 'Unknown') ?>
                    </span>
                    <a href="#">
                        <img src="<?= $galleryDir . $image ?>" alt="Gallery Image">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= $pageUrl . $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>

<?php include 'nav/footer.php'; ?>

</html>
