<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Estados de Reserva
    |--------------------------------------------------------------------------
    |
    | Listado de estados posibles para las reservas en el sistema.
    |
    */
    'status' => [
        'paid' => 'Pagado',
        'not paid' => 'No Pagado',
        'pass' => 'Aprobado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Métodos de Pago
    |--------------------------------------------------------------------------
    |
    | Listado de métodos de pago aceptados para las reservas.
    |
    */
    'payment_methods' => [
        'transfer' => 'Transferencia Bancaria',
        'webpay' => 'WebPay',
        'cash' => 'Efectivo',
        'credit_card' => 'Tarjeta de Crédito',
        'debit_card' => 'Tarjeta de Débito',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para las notificaciones enviadas a los clientes.
    |
    */
    'notifications' => [
        'email' => [
            'enabled' => true,
            'template' => 'emails.reservation-confirmation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Exportación
    |--------------------------------------------------------------------------
    |
    | Configuración para la exportación de reservas a Excel.
    |
    */
    'export' => [
        'filename_prefix' => 'reservas_',
        'disk' => 'public',
    ],
];
