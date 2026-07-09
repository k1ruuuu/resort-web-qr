<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which origins, methods, and headers are allowed for CORS requests.
    | This prevents unauthorized websites from accessing your API.
    |
    */

    'paths' => ['api/*', 'v/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost')),

    'allowed_origins_patterns' => [],

    // SECURITY FIX: Restricted headers to specific needed headers only
    'allowed_headers' => [
        'Accept',
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
