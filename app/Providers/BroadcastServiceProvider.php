<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(); // Habilita las rutas de broadcasting

        // Carga las autorizaciones desde el nuevo archivo
        require base_path('routes/channels.php');
    }
}
