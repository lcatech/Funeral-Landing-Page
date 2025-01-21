<?php
session_start(); // Start the session at the beginning

$galleryDir = 'images/gallery/'; // Path to the gallery folder
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Allowed image types

// Fetch all subfolders in the gallery directory
$subfolders = array_filter(glob($galleryDir . '*'), 'is_dir');

// Get the selected folder from the filter (default to 'all')
$selectedFolder = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get sort order (default to newest first)
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Function to get image with its modification time
function getImageWithTime($path) {
    return [
        'path' => $path,
        'mtime' => filemtime($path)
    ];
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
            $fullPath = $subfolder . '/' . $image;
            $images[] = getImageWithTime($fullPath);
        }
    }
} else {
    $folderPath = $galleryDir . $selectedFolder;
    if (is_dir($folderPath)) {
        $folderImages = array_filter(scandir($folderPath), function ($file) use ($folderPath, $allowedExtensions) {
            $filePath = $folderPath . '/' . $file;
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            return is_file($filePath) && in_array($fileExtension, $allowedExtensions);
        });
        
        foreach ($folderImages as $image) {
            $fullPath = $folderPath . '/' . $image;
            $images[] = getImageWithTime($fullPath);
        }
    }
}

// Sort images based on modification time
usort($images, function($a, $b) use ($sortOrder) {
    if ($sortOrder === 'oldest') {
        return $a['mtime'] - $b['mtime'];
    }
    return $b['mtime'] - $a['mtime']; // newest first (default)
});

// Extract just the paths for use in the template
$images = array_map(function($img) {
    return $img['path'];
}, $images);

// Pagination setup
$itemsPerPage = 20;
$totalImages = count($images);
$totalPages = ceil($totalImages / $itemsPerPage);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages)); // Clamp page value between 1 and totalPages
$startIndex = ($page - 1) * $itemsPerPage;
$paginatedImages = array_slice($images, $startIndex, $itemsPerPage);

// Function to format date for image tooltips
function formatImageDate($path) {
    return date("F j, Y, g:i a", filemtime($path));
}
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
    grid-template-columns: repeat(4, 1fr);
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

/* Filter Controls */
.filter-container {
    text-align: center;
    margin-bottom: 3rem;
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.control-group {
    display: flex;
    gap: 1rem;
    align-items: center;
    background: #f5f5f5;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.control-label {
    font-family: 'Sofia', sans-serif;
    color: #666;
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.styled-select {
    position: relative;
    min-width: 180px;
    font-family: 'Sofia', sans-serif;
}

.styled-select select {
    width: 100%;
    padding: 0.8rem 2.5rem 0.8rem 1rem;
    font-size: 1.5rem;
    border: 2px solid #efbf04;
    border-radius: 8px;
    background: white;
    color: #333;
    cursor: pointer;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    transition: all 0.3s ease;
}

.styled-select select:hover {
    border-color: #d1a204;
}

.styled-select select:focus {
    border-color: #d1a204;
    box-shadow: 0 0 0 3px rgba(209, 162, 4, 0.2);
}

.styled-select::after {
    content: '';
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #efbf04;
    pointer-events: none;
    transition: transform 0.3s ease;
}

.styled-select:hover::after {
    border-top-color: #d1a204;
}

/* Modal Styles */
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
    transition: background-color 0.3s ease;
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
    transition: all 0.3s ease;
}

.modal-prev {
    left: 20px;
}

.modal-next {
    right: 20px;
}

.modal-nav:hover {
    color: #d1a204;
    background: rgba(0, 0, 0, 0.7);
}

/* Pagination */
.pagination {
    margin-top: 3rem;
    padding: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pagination a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 0.8rem;
    background: white;
    color: #666;
    text-decoration: none;
    border: 2px solid #efbf04;
    border-radius: 8px;
    font-family: 'Sofia', sans-serif;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #efbf04;
    color: white;
    transform: translateY(-1px);
}

.pagination .active {
    background: #d1a204;
    border-color: #d1a204;
    color: white;
    font-weight: bold;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(209, 162, 4, 0.3);
}

.pagination a[href*="page=1"],
.pagination a[href*="Last"] {
    font-weight: 600;
    padding: 0 1.2rem;
}

.image-info {
    position: absolute;
    bottom: 20px;
    left: 20px;
    color: white;
    background: rgba(0,0,0,0.7);
    padding: 10px;
    border-radius: 5px;
    font-size: 0.9rem;
    z-index: 1001;
}

/* Responsive Design */
@media (max-width: 75em) {
    .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
}

@media (max-width: 63em) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

@media (max-width: 40em) {
    .gallery-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .control-group {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
    }
    
    .styled-select {
        width: 100%;
    }
    
    .pagination a {
        min-width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    .modal-nav {
        font-size: 1.5rem;
        padding: 8px;
    }
    
    .modal-prev {
        left: 10px;
    }
    
    .modal-next {
        right: 10px;
    }
}
    </style>

    
</head>

<body>
    <div class="gallery-container">
        <h1 style="color: #d1a204; font-size: 6rem; font-family: Sofia; margin-bottom: 3rem;">Gallery</h1>

        <!-- Upload Button -->
        <div class="upload-button-container" style="text-align: center; margin-bottom: 2rem;">
            <a href="media/upload.php" class="upload-button" style="
                display: inline-block;
                background: #efbf04;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 8px;
                font-family: 'Sofia', sans-serif;
                font-size: 2rem;
                transition: background 0.3s ease;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            ">
                Share Your Pictures
            </a>
        </div>

        
         <!-- Filter and Sort Controls -->
         <div class="filter-container">
    <form method="GET" action="gallery.php" class="control-group">
        <div class="styled-select">
            <span class="control-label">Category</span>
            <select name="filter" onchange="this.form.submit()">
                <option value="all" <?= $selectedFolder === 'all' ? 'selected' : '' ?>>All Folders</option>
                <?php foreach ($subfolders as $subfolder): ?>
                    <?php $folderName = basename($subfolder); ?>
                    <option value="<?= $folderName ?>" <?= $selectedFolder === $folderName ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('-', ' ', $folderName)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="styled-select">
            <span class="control-label">Sort By</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            </select>
        </div>
    </form>
</div>

        <!-- Gallery Grid -->
        <div class="gallery-grid">
            <?php foreach ($paginatedImages as $index => $image): ?>
                <div class="gallery-item">
                    <a href="#modal-<?= $startIndex + $index ?>" title="Uploaded: <?= formatImageDate($image) ?>">
                        <img src="<?= $image ?>" alt="Gallery Image" loading="lazy">
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
                    <img src="<?= $image ?>" alt="Full Image" loading="lazy">
                    <div class="image-info" style="position: absolute; bottom: 20px; left: 20px; color: white; background: rgba(0,0,0,0.7); padding: 10px; border-radius: 5px;">
                        Uploaded: <?= formatImageDate($image) ?>
                    </div>
                    <a href="#modal-<?= $startIndex + (($index + 1) % count($paginatedImages)) ?>"
                        class="modal-nav modal-next">&#8250;</a>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($totalPages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="?filter=<?= $selectedFolder ?>&sort=<?= $sortOrder ?>&page=1">First</a>
                    <a href="?filter=<?= $selectedFolder ?>&sort=<?= $sortOrder ?>&page=<?= $page - 1 ?>">Previous</a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?filter=<?= $selectedFolder ?>&sort=<?= $sortOrder ?>&page=<?= $i ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?filter=<?= $selectedFolder ?>&sort=<?= $sortOrder ?>&page=<?= $page + 1 ?>">Next</a>
                    <a href="?filter=<?= $selectedFolder ?>&sort=<?= $sortOrder ?>&page=<?= $totalPages ?>">Last</a>
                <?php endif; ?>
            <?php endif; ?>
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
