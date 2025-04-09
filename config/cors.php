<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Especifica el origen exacto de tu frontend
    'allowed_origins' => ['https://explora-chile-tour.netlify.app'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Activa el soporte de credenciales para enviar cookies
    'supports_credentials' => true,
];
