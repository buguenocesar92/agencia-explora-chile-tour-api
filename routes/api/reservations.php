<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;

Route::group(['prefix' => 'reservations'], function () {
    Route::post('/', [ReservationController::class, 'store'])->name('reservations.store');
});
