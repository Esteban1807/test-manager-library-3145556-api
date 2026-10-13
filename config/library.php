<?php

// Manager Library settings from the environment (constitution Article IX: no secret in the repo)
return [
    'jwt_secret' => env('JWT_SECRET'),
    'jwt_ttl_seconds' => 28800, // 8 hours (spec.md CA-01.1)

    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),

    'cors_allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'),
];
