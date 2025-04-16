<?php

namespace App\Observers;

use App\Models\TourTemplate;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TourTemplateObserver
{
    /**
     * Handle the TourTemplate "deleted" event.
     *
     * @param  \App\Models\TourTemplate  $tourTemplate
     * @return void
     */
    public function deleted(TourTemplate $tourTemplate)
    {
        Log::info('TourTemplateObserver: deleted event triggered', ['id' => $tourTemplate->id]);

        // Enfoque 1: Usar el ORM directamente para trips
        $tripsCount = DB::table('trips')
            ->where('tour_template_id', $tourTemplate->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        Log::info('TourTemplateObserver: trips soft deleted (DB approach)', ['count' => $tripsCount]);

        // Obtener los IDs de los viajes asociados
        $tripIds = DB::table('trips')
            ->where('tour_template_id', $tourTemplate->id)
            ->pluck('id')
            ->toArray();

        Log::info('TourTemplateObserver: trips affected', ['trip_ids' => $tripIds]);

        // Soft delete de las reservas asociadas a los viajes
        if (!empty($tripIds)) {
            $reservationCount = DB::table('reservations')
                ->whereIn('trip_id', $tripIds)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);

            Log::info('TourTemplateObserver: reservations soft deleted (DB approach)', ['count' => $reservationCount]);
        }
    }

    /**
     * Handle the TourTemplate "restored" event.
     *
     * @param  \App\Models\TourTemplate  $tourTemplate
     * @return void
     */
    public function restored(TourTemplate $tourTemplate)
    {
        Log::info('TourTemplateObserver: restored event triggered', ['id' => $tourTemplate->id]);

        // Obtener los IDs de todos los viajes asociados (incluyendo eliminados)
        $tripIds = DB::table('trips')
            ->where('tour_template_id', $tourTemplate->id)
            ->pluck('id')
            ->toArray();

        Log::info('TourTemplateObserver: trips to restore', ['trip_ids' => $tripIds]);

        // Restaurar los viajes asociados
        $tripsCount = DB::table('trips')
            ->where('tour_template_id', $tourTemplate->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        Log::info('TourTemplateObserver: trips restored (DB approach)', ['count' => $tripsCount]);

        // Restaurar las reservas asociadas
        if (!empty($tripIds)) {
            $reservationCount = DB::table('reservations')
                ->whereIn('trip_id', $tripIds)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null]);

            Log::info('TourTemplateObserver: reservations restored (DB approach)', ['count' => $reservationCount]);
        }
    }
}
