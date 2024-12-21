<?php
$galleryDir = 'images/Grid Images/'; // Path to the gallery folder
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image types


// Scan the directory for files
$images = array_filter(scandir($galleryDir), function ($file) use ($galleryDir, $allowedExtensions) {
    $filePath = $galleryDir . $file;
    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return is_file($filePath) && in_array($fileExtension, $allowedExtensions);
});

// Re-index array for easier handling
$images = array_values($images);

// Pagination setup
$itemsPerPage = 20;
$totalImages = count($images);
$totalPages = ceil($totalImages / $itemsPerPage);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages)); // Clamp page value between 1 and totalPages
$startIndex = ($page - 1) * $itemsPerPage;
$paginatedImages = array_slice($images, $startIndex, $itemsPerPage);
?>

<?php include 'nav/header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Gallery | Reverend Elijah O. Akinyemi</title>
    <style>
        /* Grid Layout */
        .gallery-container {
            max-width: 1500px;
            margin: 0 auto;
            padding: 2rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 2fr);
            /* Customizable grid: Set max 4 items per row */
            gap: 2.2rem;
        }

        .gallery-item {
            position: relative;
            width: 100%;
            padding-top: 100%;
            /* Aspect ratio 1:1 (square) */
            overflow: hidden;
            border-radius: 1.2rem;
            /* Ensures images don't overflow */
        }

        .gallery-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Ensures consistent sizing */
            object-position: center;
            /* Centers the image within the grid cell */
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.6s;
        }

        .gallery-item img:hover {
            transform: scale(1.05);
            /* Slight zoom effect on hover */
        }


        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal img {
            max-width: 90%;
            max-height: 80%;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .modal:target {
            display: flex;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: black;
            padding: 10px;
            border-radius: 50%;
            font-size: 20px;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-close:hover {
            background: #f4f4f4;
        }

        /* Navigation Arrows */
        .modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            color: white;
            cursor: pointer;
            user-select: none;
        }

        .modal-prev {
            left: 20px;
        }

        .modal-next {
            right: 20px;
        }

        .modal-nav:hover {
            color: #d1a204;
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
                /* Customizable grid: Set max 4 items per row */
                gap: 2rem;
            }
        }

        @media (max-width: 63em) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
                /* Customizable grid: Set max 4 items per row */
                gap: 2rem;
            }

        }
    </style>
</head>

<body>
    <div class="gallery-container">
        <h1 style="color: #d1a204; font-size: 6rem; font-family: Sofia; margin-bottom: 3rem;">Gallery</h1>
        <div class="gallery-grid">
            <?php foreach ($paginatedImages as $index => $image): ?>
                <div class="gallery-item">
                    <a href="#modal-<?= $startIndex + $index ?>">
                        <img src="<?= $galleryDir . $image ?>" alt="Gallery Image">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal Viewer -->
        <?php foreach ($paginatedImages as $index => $image): ?>
            <div id="modal-<?= $startIndex + $index ?>" class="modal">
                <a href="#" class="modal-close">&times;</a>
                <a href="#modal-<?= $startIndex + ($index - 1 + $itemsPerPage) % $itemsPerPage ?>"
                    class="modal-nav modal-prev">&#8249;</a>
                <img src="<?= $galleryDir . $image ?>" alt="Full Image">
                <a href="#modal-<?= $startIndex + ($index + 1) % $itemsPerPage ?>" class="modal-nav modal-next">&#8250;</a>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        // Keyboard Navigation for Modals
        document.addEventListener('keydown', (e) => {
            const openModal = document.querySelector('.modal:target');
            if (!openModal) return;

            const allModals = Array.from(document.querySelectorAll('.modal'));
            const currentIndex = allModals.indexOf(openModal);

            if (e.key === 'ArrowRight') {
                const nextModal = allModals[(currentIndex + 1) % allModals.length];
                window.location.hash = nextModal.id;
            } else if (e.key === 'ArrowLeft') {
                const prevModal = allModals[(currentIndex - 1 + allModals.length) % allModals.length];
                window.location.hash = prevModal.id;
            } else if (e.key === 'Escape') {
                window.location.hash = '';
            }
        });
    </script>
</body>
<?php include 'nav/footer.php'; ?>

</html>