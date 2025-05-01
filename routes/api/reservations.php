<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
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
});
