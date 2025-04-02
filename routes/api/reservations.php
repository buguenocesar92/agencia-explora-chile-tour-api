<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;

Route::group(['prefix' => 'reservations'], function () {
    Route::post('/', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::put('/{id}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::put('/status/{id}', [ReservationController::class, 'updateStatus'])->name('reservations.updateStatus');
    Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
});
