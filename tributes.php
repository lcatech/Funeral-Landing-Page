<?php
include 'core/db_connection.php';

// Pagination setup
$tributes_per_page = 10; // Show 4 tributes (2 rows of 2)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $tributes_per_page;

// Fetch tributes with pagination
$result = $conn->query("SELECT * FROM tributes WHERE status='approved' ORDER BY created_at DESC LIMIT $tributes_per_page OFFSET $offset");

// Get total count for pagination
$total_tributes = $conn->query("SELECT COUNT(*) as count FROM tributes WHERE status='approved'")->fetch_assoc()['count'];
$total_pages = ceil($total_tributes / $tributes_per_page);

// Banner image logic
$randomIdQuery = $conn->query("SELECT id FROM banners WHERE banner_type='tribute' ORDER BY RAND() LIMIT 1");

if ($randomIdQuery->num_rows > 0) {
    $randomId = $randomIdQuery->fetch_assoc()['id'];
    $bannerImageQuery = $conn->query("SELECT image_path FROM banners WHERE id=$randomId LIMIT 1");
    $bannerImage = $bannerImageQuery->num_rows > 0 ? $bannerImageQuery->fetch_assoc()['image_path'] : 'default-banner.jpg';
} else {
    $bannerImage = 'images/hero-image.png';
}
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>Tributes | Reverend Elijah O. Akinyemi</title>
    <style>

.tribute-wrapper {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin: 4rem 0;
        }

        /* Increased specificity for tribute header */
        .tribute-wrapper .h1-container h1 {
            color: #efbf04;
            font-size: 4rem !important;
            font-family: "Sofia", cursive;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            font-weight: 600;
            text-align: left;
        }
        .modern-tributes-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .modern-tributes-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .modern-tribute-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 1.6rem;
            padding: 3rem;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .modern-tribute-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }

        .modern-tribute-header {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .modern-tribute-author {
            flex: 1;
        }

        .modern-tribute-card h2 {
            font-size: 2rem;
            color: #fad14b;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            font-weight: 600;
        }

        .modern-tribute-relationship {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: normal;
        }

        .modern-tribute-date {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.5rem;
        }

        .modern-tribute-content-wrapper {
            position: relative;
            margin-bottom: 2rem;
        }

        .modern-tribute-text {
            position: relative;
            max-height: 300px;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-tribute-text.expanded {
            max-height: 2000px;
        }

        .modern-tribute-content {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.5rem;
            line-height: 1.7;
            white-space: pre-line;
        }

        .modern-tribute-text:not(.expanded)::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            pointer-events: none;
        }

        .modern-tribute-image-container {
            margin-top: 2rem;
            border-radius: 1.2rem;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .modern-tribute-image-container::before {
            content: '👆 Click to enlarge';
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }

        .modern-tribute-image-container:hover::before {
            opacity: 1;
        }

        .modern-tribute-thumbnail {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .modern-tribute-image-container:hover .modern-tribute-thumbnail {
            transform: scale(1.03);
        }

        .modern-read-more-btn {
            background: none;
            border: none;
            color: #fad14b;
            font-size: 1.4rem;
            padding: 0.5rem 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .modern-read-more-btn:hover {
            color: #fff;
        }

        .modern-read-more-btn::after {
            content: '→';
            transition: transform 0.3s ease;
        }

        .modern-read-more-btn:hover::after {
            transform: translateX(5px);
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 4rem 0;
        }

        .page-link {
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: #fad14b;
            border-radius: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.4rem;
        }

        .page-link:hover,
        .page-link.active {
            background: #fad14b;
            color: #000;
        }

        /* Modal Styles */
        .modern-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
            cursor: zoom-out;
        }

        .modern-modal.active {
            opacity: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modern-modal img {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 1rem;
            object-fit: contain;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            cursor: default;
        }

        .modern-modal.active img {
            transform: scale(1);
        }

        .modern-modal-close {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            font-size: 2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modern-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modern-no-tributes {
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.6rem;
            padding: 4rem;
        }

        @media (max-width: 1100px) {
            .modern-tributes-list {
                grid-template-columns: 1fr;
                padding: 0 2rem;
            }

            .modern-tribute-card {
                padding: 2rem;
            }
        }
    </style>
</head>

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

    <div class="tribute-wrapper">
        <div class="h1-container">
            <h1>Tributes</h1>
        </div>
        <div class="banner-button">
            <a href="sharememory.php" class="cta-button">
                <i class="fa-solid fa-pen"></i> Leave a Tribute
            </a>
        </div>
    </div>

    <!-- Modern Tributes Section -->
    <div class="modern-tributes-container">
        <?php if ($result->num_rows > 0): ?>
            <div class="modern-tributes-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="modern-tribute-card">
                        <div class="modern-tribute-header">
                            <div class="modern-tribute-author">
                                <h2><?= htmlspecialchars($row['name']) ?></h2>
                                <div class="modern-tribute-relationship">
                                    <?= htmlspecialchars($row['relationship']) ?>
                                </div>
                                <div class="modern-tribute-date">
                                    <?= date('F j, Y', strtotime($row['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modern-tribute-content-wrapper">
                            <div class="modern-tribute-text">
                                <div class="modern-tribute-content">
                                    <?= nl2br(htmlspecialchars($row['message'])) ?>
                                </div>
                            </div>
                            <button class="modern-read-more-btn">Read More</button>
                        </div>
                        
                        <?php if ($row['image'] && file_exists($row['image'])): ?>
                            <div class="modern-tribute-image-container" onclick="openModal('modal-<?= $row['id'] ?>')">
                                <img src="<?= htmlspecialchars($row['image']) ?>" 
                                     alt="Tribute from <?= htmlspecialchars($row['name']) ?>" 
                                     class="modern-tribute-thumbnail"
                                     loading="lazy">
                            </div>
                            
                            <div id="modal-<?= $row['id'] ?>" class="modern-modal">
                                <img src="<?= htmlspecialchars($row['image']) ?>" 
                                     alt="Full tribute image"
                                     loading="lazy">
                                <button class="modern-modal-close" onclick="closeModal('modal-<?= $row['id'] ?>')">&times;</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="modern-no-tributes">No tributes have been submitted yet.</p>
        <?php endif; ?>
    </div>

    <script>
        // Enhanced Read More functionality
        document.querySelectorAll('.modern-read-more-btn').forEach(button => {
            const textContainer = button.parentElement.querySelector('.modern-tribute-text');
            const content = textContainer.querySelector('.modern-tribute-content');
            
            // Update to match new max-height
            if (content.clientHeight <= 300) {
                button.style.display = 'none';
            }
            
            button.addEventListener('click', function() {
                textContainer.classList.toggle('expanded');
                this.textContent = textContainer.classList.contains('expanded') ? 'Read Less' : 'Read More';
                if (!textContainer.classList.contains('expanded')) {
                    textContainer.parentElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Modal functionality
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            document.body.style.overflow = 'hidden';
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        // Close modal on background click
        document.querySelectorAll('.modern-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modern-modal.active');
                if (activeModal) {
                    closeModal(activeModal.id);
                }
            }
        });
    </script>
</body>
</html>