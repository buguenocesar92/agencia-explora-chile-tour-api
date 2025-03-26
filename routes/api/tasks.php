<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::group([
    'prefix' => 'tasks',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
