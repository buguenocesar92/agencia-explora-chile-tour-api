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

});
Route::group([
    'prefix' => 'trips',
], function () {
    Route::get('/{id}/programa', [TripController::class, 'getProgramaFile']);
    Route::get('/{id}/programa/download', [TripController::class, 'downloadProgramaFile']);
});

