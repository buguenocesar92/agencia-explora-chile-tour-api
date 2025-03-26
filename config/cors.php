<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Especifica el origen exacto de tu frontend
    'allowed_origins' => ['http://localhost:3000','http://localhost:3001', 'http://localhost:5173', 'https://gilded-gelato-8c2ec1.netlify.app', 'https://cbm3lla.me'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Activa el soporte de credenciales para enviar cookies
    'supports_credentials' => true,
];
