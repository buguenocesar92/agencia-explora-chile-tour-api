<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmacionReserva;

Route::get('/test-email', function() {
    try {
        $datos = [
            'nombre' => 'Usuario de Prueba',
            'destino' => 'Tour de Prueba',
            'fecha' => date('Y-m-d'),
        ];

        // Cambia la dirección por tu correo real para la prueba
        Mail::to('cbm3lla@gmail.com')->send(new ConfirmacionReserva($datos));

        return [
            'success' => true,
            'message' => 'Correo enviado correctamente desde ' . config('mail.from.address') . ' usando el servidor ' . config('mail.host') . ':' . config('mail.port')
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al enviar correo: ' . $e->getMessage(),
            'error_class' => get_class($e),
            'config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'encryption' => config('mail.encryption'),
                'username' => config('mail.username'),
                'from_address' => config('mail.from.address'),
            ],
            'trace' => $e->getTraceAsString()
        ];
    }
});
