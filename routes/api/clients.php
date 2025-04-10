<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

// Ruta pública para buscar cliente por RUT - no requiere autenticación
Route::get('/clients/search-by-rut', [ClientController::class, 'findByRut'])->name('clients.find-by-rut');

Route::group([
    'prefix' => 'clients',
    'middleware' => ['auth:api', 'check_route_permission'],
], function () {
    Route::get('/', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});
