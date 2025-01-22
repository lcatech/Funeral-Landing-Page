<?php
session_start();
include 'core/db_connection.php';

// Error handling setup
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Cache control
header("Cache-Control: public, max-age=300");

// Get tribute statistics
$stats = $conn->query("
    SELECT 
        status,
        COUNT(*) as count 
    FROM tributes 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

$tribute_stats = [
    'pending' => 0,
    'approved' => 0
];

foreach ($stats as $stat) {
    $tribute_stats[$stat['status']] = $stat['count'];
}

// Pagination setup
$tributes_per_page = 10;
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
$pageTitle = "Tributes";

// Message formatting function
function formatTributeMessage($message) {
    // Decode HTML entities
    $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Normalize line endings
    $message = str_replace(["\r\n", "\r"], "\n", $message);
    
    // Split into lines
    $lines = explode("\n", $message);
    $formatted = '';
    $current_sentence = '';
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        // Skip empty lines if we're not after a sentence ending
        if (empty($line) && !preg_match('/[.!?]$/', trim($current_sentence))) {
            continue;
        }
        
        // If we're continuing a sentence, add the line with a space
        if (!empty($current_sentence) && !preg_match('/[.!?]$/', trim($current_sentence))) {
            $current_sentence .= ' ' . $line;
        } else {
            // If we have a complete sentence, add it and check for break
            if (!empty($current_sentence)) {
                $formatted .= trim($current_sentence);
                
                // Check if this was the end of a sentence
                $prev_line_ended_sentence = preg_match('/[.!?]$/', trim($lines[$i-1]));
                
                if ($prev_line_ended_sentence) {
                    // Add single line break if there was any kind of break in original
                    $next_non_empty = $i;
                    while ($next_non_empty < count($lines) && empty(trim($lines[$next_non_empty]))) {
                        $next_non_empty++;
                    }
                    
                    if ($next_non_empty > $i) {
                        $formatted .= "\n";
                    } else {
                        $formatted .= " ";
                    }
                }
            }
            $current_sentence = $line;
        }
    }
    
    // Add any remaining sentence
    if (!empty($current_sentence)) {
        $formatted .= trim($current_sentence);
    }
    
    // Handle markdown-style bold text
    $formatted = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formatted);
    
    // Final cleanup - ensure no double line breaks
    $formatted = preg_replace('/\n{2,}/', "\n", $formatted);
    
    return trim($formatted);
}
?>

<?php include 'nav/header.php'; ?>

<style>
/* Base Styles */
.tribute-wrapper {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin: 4rem 0;
}

.tribute-wrapper .h1-container h1 {
    color: #efbf04;
    font-size: 4rem !important;
    font-family: "Sofia", cursive;
    margin: 0;
    padding: 0;
    line-height: 1.2;
    font-weight: 600;
    text-align: center;
}

/* Container Styles */
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

/* Card Styles */
.modern-tribute-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 1.6rem;
    padding: 2.5rem;
    position: relative;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
}

.modern-tribute-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.08);
}

/* Header Styles */
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
    margin: 0.5rem 0;
    line-height: 1.4;
}

.modern-tribute-date {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 0.5rem;
}

/* Content Styles */
.modern-tribute-content-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.modern-tribute-text {
    position: relative;
    min-height: 100px; /* Add minimum height */
    max-height: 200px;
    overflow: hidden;
    transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 1.5rem;
    padding-bottom: 2rem; /* Increase bottom padding */
    background: rgba(0, 0, 0, 0.2);
    border-radius: 1rem;
}

.modern-tribute-text.expanded {
    max-height: 2000px;
}

.modern-tribute-content {
    color: rgba(255, 255, 255, 0.95);
    font-size: 1.5rem;
    line-height: 1.8;
    white-space: pre-line;
    padding: 0.5rem 0;
    word-break: break-word;
    hyphens: auto;
}

/* Only show gradient fade when text is truncated */
.modern-tribute-text:not(.expanded)::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 80px;
    background: linear-gradient(
        transparent 0%,
        rgba(0, 0, 0, 0.8) 40%,
        rgba(0, 0, 0, 0.95) 100%
    );
    pointer-events: none;
    /* Only display gradient if content is taller than container */
    display: none;
}

/* Show gradient only when content is taller than container */
.modern-tribute-text.needs-gradient:not(.expanded)::after {
    display: block;
}

/* Read More Button */
.modern-read-more-btn {
    background: rgba(250, 209, 75, 0.1);
    border: none;
    color: #fad14b;
    font-size: 1.4rem;
    padding: 0.8rem 1.6rem;
    border-radius: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    margin-top: 1.5rem;
}

.modern-read-more-btn:hover {
    background: rgba(250, 209, 75, 0.2);
    color: #fff;
}

.modern-read-more-btn::after {
    content: '→';
    transition: transform 0.3s ease;
}

.modern-read-more-btn:hover::after {
    transform: translateX(5px);
}

/* Image and Modal Styles */
.modern-tribute-image-container {
    margin-top: 2rem;
    border-radius: 1.2rem;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}

.modern-tribute-image-container::before {
    content: ' Click to enlarge';
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

/* Responsive Styles */
@media (max-width: 1100px) {
    .modern-tributes-list {
        grid-template-columns: 1fr;
        padding: 0 2rem;
    }

    .modern-tribute-card {
        padding: 2rem;
    }
    
    .modern-tribute-content {
        font-size: 1.4rem;
        line-height: 1.7;
    }
    
    .modern-tribute-text {
        padding: 1rem;
    }
}

/* Success Message Styles */
.success-message {
    background-color: rgba(223, 240, 216, 0.9);
    color: #3c763d;
    padding: 20px;
    margin: 20px auto;
    border-radius: 8px;
    max-width: 800px;
    text-align: center;
    font-size: 18px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(60, 118, 61, 0.3);
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

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

<!-- Success Message Section -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="success-message">
        <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']); 
        ?>
    </div>
<?php endif; ?>

<!-- Tributes Section -->
<div class="modern-tributes-container">
    <?php if ($result->num_rows > 0): ?>
        <div class="modern-tributes-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="modern-tribute-card">
                    <div class="modern-tribute-header">
                        <div class="modern-tribute-author">
                            <h2><?= htmlspecialchars(trim($row['name'])) ?></h2>
                            <div class="modern-tribute-relationship">
                                <?= htmlspecialchars(trim($row['relationship'])) ?>
                            </div>
                            <div class="modern-tribute-date">
                                <?php 
                                    $date = strtotime($row['created_at']);
                                    echo $date ? date('F j, Y', $date) : 'Date not available';
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modern-tribute-content-wrapper">
                        <div class="modern-tribute-text">
                            <div class="modern-tribute-content">
    <?= nl2br(htmlspecialchars(formatTributeMessage($row['message']))) ?>
</div>
                        </div>
                        <button class="modern-read-more-btn">Read More</button>
                    </div>
        
        <?php if ($row['image'] && file_exists($row['image'])): ?>
            <div class="modern-tribute-image-container" onclick="openModal('modal-<?= $row['id'] ?>')">
                <img src="<?= htmlspecialchars($row['image']) ?>" 
                     alt="Tribute from <?= htmlspecialchars(trim($row['name'])) ?>" 
                     class="modern-tribute-thumbnail"
                     loading="lazy"
                     onerror="this.parentElement.style.display='none'">
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
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="page-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    
    <?php else: ?>
        <!-- No tributes message -->
        <div class="modern-no-tributes">
            <div class="tribute-status">No tributes have been posted yet.</div>
            <div class="tribute-cta">Be the first to share your memories.</div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Enhanced Read More functionality
// Enhanced Read More functionality
document.querySelectorAll('.modern-tribute-text').forEach(container => {
    const content = container.querySelector('.modern-tribute-content');
    const button = container.parentElement.querySelector('.modern-read-more-btn');
    
    // Check if content is taller than container
    if (content.clientHeight > container.clientHeight) {
        container.classList.add('needs-gradient');
        if (button) {
            button.style.display = 'inline-flex';
        }
    } else {
        container.classList.remove('needs-gradient');
        if (button) {
            button.style.display = 'none';
        }
    }
});

document.querySelectorAll('.modern-read-more-btn').forEach(button => {
    button.addEventListener('click', function() {
        const textContainer = this.parentElement.querySelector('.modern-tribute-text');
        textContainer.classList.toggle('expanded');
        this.textContent = textContainer.classList.contains('expanded') ? 'Read Less' : 'Read More';
        
        if (!textContainer.classList.contains('expanded')) {
            textContainer.parentElement.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
    // Modal functionality
    function openModal(modalId) {
        try {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            document.body.style.overflow = 'hidden';
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        } catch (error) {
            console.error('Error opening modal:', error);
        }
    }

    function closeModal(modalId) {
        try {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        } catch (error) {
            console.error('Error closing modal:', error);
        }
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