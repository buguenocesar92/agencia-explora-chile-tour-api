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
        'pending' => 'Pendiente',
        'cancelled' => 'Cancelado',
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
        'whatsapp' => [
            'enabled' => true,
            'template_name' => 'reservation_confirmation',
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
