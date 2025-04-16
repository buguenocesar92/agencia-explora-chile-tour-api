<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TourTemplateController;

Route::group([
    'prefix' => 'tour-templates',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::post('/', [TourTemplateController::class, 'store']);
    Route::get('/', [TourTemplateController::class, 'index']);
    Route::get('/{id}', [TourTemplateController::class, 'show']);
    Route::put('/{id}', [TourTemplateController::class, 'update']);
    Route::delete('/{id}', [TourTemplateController::class, 'destroy']);
    Route::put('/{id}/restore', [TourTemplateController::class, 'restore']);
    Route::delete('/{id}/force', [TourTemplateController::class, 'forceDelete']);
});
