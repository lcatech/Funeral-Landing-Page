<?php
// Updated config.php
return [
    'pending_dir' => 'gallery/pending/',
    'categories' => [
        'church' => '../images/gallery/church/',
        'personal' => '../images/gallery/personal/',
        'family' => '../images/gallery/family/',
        'random' => '../images/gallery/random/',
        'ministry' => '../images/gallery/ministry/'
    ],
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'max_size' => 5 * 1024 * 1024,  // 5MB
    'admin_user' => 'it-man',         // Change this!
    'admin_pass' => '7ThUndEr$2024!!!@'       // Change this!
];