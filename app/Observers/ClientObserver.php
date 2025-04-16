<?php

namespace App\Observers;

use App\Models\Client;

class ClientObserver
{
    /**
     * Handle the Client "deleted" event.
     *
     * @param  \App\Models\Client  $client
     * @return void
     */
    public function deleted(Client $client)
    {
        // Soft delete de las reservas asociadas al cliente
        $client->reservations()->update(['deleted_at' => now()]);
    }

    /**
     * Handle the Client "restored" event.
     *
     * @param  \App\Models\Client  $client
     * @return void
     */
    public function restored(Client $client)
    {
        // Restaurar las reservas asociadas que fueron eliminadas
        $client->reservations()->onlyTrashed()->update(['deleted_at' => null]);
    }
}
