<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TripController;

Route::group([
    'prefix' => 'trips',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/', [TripController::class, 'index']);
    Route::post('/', [TripController::class, 'store']);
    Route::get('/{id}', [TripController::class, 'show']);
    Route::put('/{id}', [TripController::class, 'update']);
    Route::delete('/{id}', [TripController::class, 'destroy']);
    Route::put('/{id}/restore', [TripController::class, 'restore']);
    Route::delete('/{id}/force', [TripController::class, 'forceDelete']);
});
