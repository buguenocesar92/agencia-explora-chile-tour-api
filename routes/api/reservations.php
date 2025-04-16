<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

Route::group(['prefix' => 'reservations'], function () {
    Route::post('/', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::put('/{id}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::put('/status/{id}', [ReservationController::class, 'updateStatus'])->name('reservations.updateStatus');
    Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::put('/{id}/mark-as-paid', [ReservationController::class, 'markAsPaid'])->name('reservations.markAsPaid');

    // Nuevas rutas para soft delete
    Route::put('/{id}/restore', [ReservationController::class, 'restore'])->name('reservations.restore');
    Route::delete('/{id}/force', [ReservationController::class, 'forceDelete'])->name('reservations.forceDelete');

    // Ruta para exportar reservas a Excel
    Route::get('/export/excel', [ReservationController::class, 'exportToExcel'])->name('reservations.exportToExcel');

    // Ruta de prueba para el servicio de WhatsApp
    Route::get('/test/whatsapp/{phone}', function ($phone) {
        try {
            Log::info('Test WhatsApp - Inicio', ['phone' => $phone]);

            $whatsAppService = app(WhatsAppService::class);
            $result = $whatsAppService->sendPaymentConfirmation($phone, [
                'nombre' => 'Cliente de Prueba',
                'destino' => 'Isla de Pascua',
                'fecha' => date('Y-m-d')
            ]);

            Log::info('Test WhatsApp - Resultado', ['success' => $result]);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'WhatsApp enviado correctamente' : 'Error al enviar WhatsApp'
            ]);
        } catch (\Exception $e) {
            Log::error('Test WhatsApp - Error', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('reservations.testWhatsApp');
});
