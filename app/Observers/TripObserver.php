<?php

namespace App\Observers;

use App\Models\Trip;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info('TripObserver: deleted event triggered', ['trip_id' => $trip->id]);

        // Obtener los IDs de las reservas asociadas a este viaje
        $reservationIds = $trip->reservations()->pluck('id')->toArray();

        // Soft delete de las reservas asociadas
        $reservationsCount = $trip->reservations()->update(['deleted_at' => now()]);
        Log::info('TripObserver: reservations soft deleted', ['count' => $reservationsCount]);

        // Soft delete de los pagos asociados a las reservas
        if (!empty($reservationIds)) {
            // Obtenemos los IDs de los pagos
            $paymentIds = DB::table('payments')
                ->join('reservations', 'payments.id', '=', 'reservations.payment_id')
                ->whereIn('reservations.id', $reservationIds)
                ->whereNull('payments.deleted_at')
                ->pluck('payments.id')
                ->toArray();

            if (!empty($paymentIds)) {
                $paymentsCount = Payment::whereIn('id', $paymentIds)->update(['deleted_at' => now()]);
                Log::info('TripObserver: payments soft deleted', ['count' => $paymentsCount]);
            }
        }
    }

    /**
     * Handle the Trip "restored" event.
     *
     * @param  \App\Models\Trip  $trip
     * @return void
     */
    public function restored(Trip $trip)
    {
        Log::info('TripObserver: restored event triggered', ['trip_id' => $trip->id]);

        // Obtener los IDs de las reservas asociadas (incluso eliminadas)
        $reservationIds = $trip->reservations()->withTrashed()->pluck('id')->toArray();

        // Restaurar las reservas asociadas que fueron eliminadas
        $reservationsCount = $trip->reservations()->onlyTrashed()->update(['deleted_at' => null]);
        Log::info('TripObserver: reservations restored', ['count' => $reservationsCount]);

        // Restaurar los pagos asociados a las reservas
        if (!empty($reservationIds)) {
            // Obtenemos los IDs de los pagos eliminados
            $paymentIds = DB::table('payments')
                ->join('reservations', 'payments.id', '=', 'reservations.payment_id')
                ->whereIn('reservations.id', $reservationIds)
                ->whereNotNull('payments.deleted_at')
                ->pluck('payments.id')
                ->toArray();

            if (!empty($paymentIds)) {
                $paymentsCount = Payment::whereIn('id', $paymentIds)->update(['deleted_at' => null]);
                Log::info('TripObserver: payments restored', ['count' => $paymentsCount]);
            }
        }
    }
}
