<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;

Route::group(['prefix' => 'reservations'], function () {
    Route::post('/', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/', [ReservationController::class, 'index'])->name('reservations.index');
    Route::put('/{id}', [ReservationController::class, 'update'])->name('reservations.update');
});
