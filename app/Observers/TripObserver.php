<?php

namespace App\Observers;

use App\Models\Trip;

class TripObserver
{
    /**
     * Handle the Trip "deleted" event.
     *
     * @param  \App\Models\Trip  $trip
     * @return void
     */
    public function deleted(Trip $trip)
    {
        // Soft delete de las reservas asociadas
        $trip->reservations()->update(['deleted_at' => now()]);
    }

    /**
     * Handle the Trip "restored" event.
     *
     * @param  \App\Models\Trip  $trip
     * @return void
     */
    public function restored(Trip $trip)
    {
        // Restaurar las reservas asociadas que fueron eliminadas
        $trip->reservations()->onlyTrashed()->update(['deleted_at' => null]);
    }
}
