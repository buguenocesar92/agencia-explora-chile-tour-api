<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/api/tasks.php';
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/permissions.php';
require __DIR__ . '/api/roles.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/reservations.php';
require __DIR__ . '/api/tours.php';
require __DIR__ . '/api/tour-templates.php';
require __DIR__ . '/api/trips.php';
require __DIR__ . '/api/clients.php';
require __DIR__ . '/api/test.php';

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/whatsapp-test', function () {
    $whatsAppService = app(\App\Services\WhatsAppService::class);
    $phone = '56944964919'; // Número de prueba
    try {
        $result = $whatsAppService->sendPaymentConfirmation($phone, [
            'nombre' => 'Cliente de Prueba',
            'destino' => 'Isla de Pascua',
            'fecha' => date('Y-m-d')
        ]);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Mensaje enviado correctamente' : 'Error al enviar mensaje'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
