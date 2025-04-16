<?php

namespace App\Observers;

use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationObserver
{
    /**
     * Handle the Reservation "deleted" event.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function deleted(Reservation $reservation)
    {
        Log::info('ReservationObserver: deleted event triggered', ['reservation_id' => $reservation->id]);

        // Si la reserva tiene un pago asociado, aplicamos soft delete
        if ($reservation->payment_id) {
            // Usamos update directo en vez de relación para evitar problemas de carga
            $affected = DB::table('payments')
                ->where('id', $reservation->payment_id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);

            Log::info('ReservationObserver: payment soft deleted', [
                'payment_id' => $reservation->payment_id,
                'affected' => $affected
            ]);
        }
    }

    /**
     * Handle the Reservation "restored" event.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function restored(Reservation $reservation)
    {
        Log::info('ReservationObserver: restored event triggered', ['reservation_id' => $reservation->id]);

        // Si la reserva tiene un pago asociado, lo restauramos
        if ($reservation->payment_id) {
            $affected = DB::table('payments')
                ->where('id', $reservation->payment_id)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null]);

            Log::info('ReservationObserver: payment restored', [
                'payment_id' => $reservation->payment_id,
                'affected' => $affected
            ]);
        }
    }

    /**
     * Handle the Reservation "force deleted" event.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function forceDeleted(Reservation $reservation)
    {
        Log::info('ReservationObserver: forceDeleted event triggered', ['reservation_id' => $reservation->id]);

        // Aquí no necesitamos hacer nada especial ya que la eliminación física
        // normalmente se maneja con las restricciones de clave foránea de la base de datos
    }
}
