<?php
session_start(); // Start the session at the beginning

$galleryDir = 'images/gallery/'; // Path to the gallery folder
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image types

// Fetch all subfolders in the gallery directory
$subfolders = array_filter(glob($galleryDir . '*'), 'is_dir');

// Get the selected folder from the filter (default to 'all')
$selectedFolder = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Generate a new random seed if it doesn't exist in the session
if (!isset($_SESSION['gallery_seed'])) {
    $_SESSION['gallery_seed'] = rand(1, 1000000);
}

// Prepare the list of images based on the selected filter
$images = [];
if ($selectedFolder === 'all') {
    foreach ($subfolders as $subfolder) {
        $folderImages = array_filter(scandir($subfolder), function ($file) use ($subfolder, $allowedExtensions) {
            $filePath = $subfolder . '/' . $file;
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            return is_file($filePath) && in_array($fileExtension, $allowedExtensions);
        });

        // Append images from this subfolder to the main images array
        foreach ($folderImages as $image) {
            $images[] = $subfolder . '/' . $image; // Store full path
        }
    }
} else {
    $folderPath = $galleryDir . $selectedFolder;
    if (is_dir($folderPath)) {
        $images = array_filter(scandir($folderPath), function ($file) use ($folderPath, $allowedExtensions) {
            $filePath = $folderPath . '/' . $file;
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            return is_file($filePath) && in_array($fileExtension, $allowedExtensions);
        });
        $images = array_map(function ($image) use ($folderPath) {
            return $folderPath . '/' . $image;
        }, $images);
    }
}

// Use the session seed to randomize images consistently within the session
srand($_SESSION['gallery_seed']);
shuffle($images);
srand(); // Reset the random seed to not affect other random operations

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
            object-position: center;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.6s;
        }

        .gallery-item img:hover {
            transform: scale(1.05);
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
            touch-action: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal img.loaded {
            opacity: 1;
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
            z-index: 1001;
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
            z-index: 1001;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 50%;
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


        /* Styled Dropdown Menu */
        .styled-select {
            position: relative;
            display: inline-block;
            width: 200px;
            font-family: 'Sofia', sans-serif;
        }

        .styled-select select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background: #efbf04;
            color: #fff;
            padding: 10px 15px;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            outline: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .styled-select select:hover {
            background: #d1a204;
        }

        .styled-select:after {
            content: '\25BC'; /* Downward arrow symbol */
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: #fff;
            pointer-events: none;
        }

        .styled-select select:focus {
            box-shadow: 0 0 4px #d1a204;
        }

        /* Dropdown container spacing */
        .filter-container {
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="gallery-container">
        <h1 style="color: #d1a204; font-size: 6rem; font-family: Sofia; margin-bottom: 3rem;">Gallery</h1>

        <!-- Filter Dropdown -->
        <div class="filter-container">
            <form method="GET" action="gallery.php" class="styled-select">
                <select name="filter" onchange="this.form.submit()">
                    <option value="all" <?= $selectedFolder === 'all' ? 'selected' : '' ?>>All</option>
                    <?php foreach ($subfolders as $subfolder): ?>
                        <?php $folderName = basename($subfolder); ?>
                        <option value="<?= $folderName ?>" <?= $selectedFolder === $folderName ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('-', ' ', $folderName)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="gallery-grid">
            <?php foreach ($paginatedImages as $index => $image): ?>
                <div class="gallery-item">
                    <a href="#modal-<?= $startIndex + $index ?>">
                        <img src="<?= $image ?>" alt="Gallery Image">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal Viewer -->
        <?php foreach ($paginatedImages as $index => $image): ?>
            <div id="modal-<?= $startIndex + $index ?>" class="modal">
                <div class="modal-content">
                    <a href="#" class="modal-close">&times;</a>
                    <a href="#modal-<?= $startIndex + (($index - 1 + count($paginatedImages)) % count($paginatedImages)) ?>"
                        class="modal-nav modal-prev">&#8249;</a>
                    <img src="<?= $image ?>" alt="Full Image">
                    <a href="#modal-<?= $startIndex + (($index + 1) % count($paginatedImages)) ?>"
                        class="modal-nav modal-next">&#8250;</a>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?filter=<?= $selectedFolder ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let touchStartX = 0;
            let touchEndX = 0;
            
            const modalViewer = {
                init() {
                    this.bindEvents();
                    this.handleInitialHash();
                },

                handleInitialHash() {
                    if (location.hash) {
                        const modalId = location.hash.slice(1);
                        const modal = document.getElementById(modalId);
                        if (modal) {
                            modal.classList.add('active');
                            const img = modal.querySelector('img');
                            if (img) {
                                img.classList.remove('loaded');
                                // Force browser to load image
                                img.src = img.src;
                            }
                        }
                    }
                },

                bindEvents() {
                    // Keyboard navigation
                    document.addEventListener('keydown', (e) => {
                        const activeModal = document.querySelector('.modal.active');
                        if (!activeModal) return;

                        switch(e.key) {
                            case 'ArrowLeft':
                                e.preventDefault();
                                activeModal.querySelector('.modal-prev').click();
                                break;
                            case 'ArrowRight':
                                e.preventDefault();
                                activeModal.querySelector('.modal-next').click();
                                break;
                            case 'Escape':
                                e.preventDefault();
                                activeModal.querySelector('.modal-close').click();
                                break;
                        }
                    });

                    // Touch events for swipe
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.addEventListener('touchstart', (e) => {
                            touchStartX = e.changedTouches[0].screenX;
                        }, false);

                        modal.addEventListener('touchend', (e) => {
                            touchEndX = e.changedTouches[0].screenX;
                            this.handleSwipe(modal);
                        }, false);

                        // Prevent default touch behaviors
                        modal.addEventListener('touchmove', (e) => {
                            e.preventDefault();
                        }, { passive: false });

                        // Image loading handler
                        const modalImg = modal.querySelector('img');
                        modalImg.addEventListener('load', () => {
                            modalImg.classList.add('loaded');
                        });
                    });
                },

                handleSwipe(modal) {
                    const swipeThreshold = 50;
                    const swipeDistance = touchEndX - touchStartX;

                    if (Math.abs(swipeDistance) >= swipeThreshold) {
                        if (swipeDistance > 0) {
                            modal.querySelector('.modal-prev').click();
                        } else {
                            modal.querySelector('.modal-next').click();
                        }
                    }
                }
            };

            // Initialize the modal viewer
            modalViewer.init();

            // Handle modal transitions
            window.addEventListener('hashchange', function() {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.id === location.hash.slice(1)) {
                        modal.classList.add('active');
                        const img = modal.querySelector('img');
                        img.classList.remove('loaded');
                        // Force browser to load image
                        img.src = img.src;
                    } else {
                        modal.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
<?php include 'nav/footer.php'; ?>

</html>
