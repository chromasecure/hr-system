<?php
// API configuration for attendance backend
return [
    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'essentia_hr1',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    // JWT secret and TTL
    'jwt_secret' => 'change_me_to_a_long_random_secret_string',
    'jwt_exp_minutes' => 60 * 24, // 24 hours

    // Duplicate prevention window (minutes)
    'recent_window_minutes' => 2,

    // Registration shared secret for creating devices
    'device_registration_secret' => 'ADMIN_DEVICE_SECRET',
];
