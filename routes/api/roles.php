<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;

Route::group([
    'prefix' => 'roles',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/with-permissions', [RolePermissionController::class, 'index'])->name('roles.with-permissions');
    Route::get('/with-permissions/{roleId}', [RolePermissionController::class, 'show'])->name('role.show');
    Route::put('/with-permissions/{roleId}', [RolePermissionController::class, 'updateRolePermissions'])->name('roles.update-permissions');
    Route::put('/{role}/users', [RoleController::class, 'updateUsers'])->name('roles.update-roles-users');
    Route::post('/', [RoleController::class, 'store'])->name('roles.store');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/with-permissions/sync', [RolePermissionController::class, 'sync'])->name('roles.sync');
});
