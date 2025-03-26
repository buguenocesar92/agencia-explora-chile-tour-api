<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;

Route::group([
    'prefix' => 'permissions',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/', [PermissionController::class, 'index'])->name('permission.index');
});
