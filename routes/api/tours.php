<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourController;

Route::group([
    'prefix' => 'tours',
   /*  'middleware' => ['auth:api', 'check_route_permission'], */
], function () {
    Route::get('/', [TourController::class, 'index']);
    Route::get('/{tourId}/dates', [TourController::class, 'getDates']);
});
