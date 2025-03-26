<?php

return [
    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'prefix' => env('REDIS_PREFIX', ''), // Clave para aplicar el prefijo
        ],
    ],
];
