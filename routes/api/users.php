<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::group([
    'prefix' => 'users',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/', [UsersController::class, 'index'])->name('users.index');
});
