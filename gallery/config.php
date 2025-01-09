<?php
return [
    'gallery_base' => 'images/gallery/',
    'upload_categories' => [
        'church' => 'images/gallery/church/',
        'personal' => 'images/gallery/personal/',
        'family' => 'images/gallery/outfamilyreach/',
        'random' => 'images/gallery/random/',
        'ministry' => 'images/gallery/ministry/'
    ],
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'max_size' => 5 * 1024 * 1024,  // 5MB
    'admin_user' => 'admin',         // Change this!
    'admin_pass' => 'admin123'       // Change this!
];