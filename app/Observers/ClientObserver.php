<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info('ClientObserver: deleted event triggered', ['client_id' => $client->id]);

        // Obtener los IDs de las reservas asociadas a este cliente
        $reservationIds = $client->reservations()->pluck('id')->toArray();

        // Soft delete de las reservas asociadas al cliente
        $reservationsCount = $client->reservations()->update(['deleted_at' => now()]);
        Log::info('ClientObserver: reservations soft deleted', ['count' => $reservationsCount]);

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
                Log::info('ClientObserver: payments soft deleted', ['count' => $paymentsCount]);
            }
        }
    }

    /**
     * Handle the Client "restored" event.
     *
     * @param  \App\Models\Client  $client
     * @return void
     */
    public function restored(Client $client)
    {
        Log::info('ClientObserver: restored event triggered', ['client_id' => $client->id]);

        // Obtener los IDs de las reservas asociadas (incluso eliminadas)
        $reservationIds = $client->reservations()->withTrashed()->pluck('id')->toArray();

        // Restaurar las reservas asociadas que fueron eliminadas
        $reservationsCount = $client->reservations()->onlyTrashed()->update(['deleted_at' => null]);
        Log::info('ClientObserver: reservations restored', ['count' => $reservationsCount]);

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
                Log::info('ClientObserver: payments restored', ['count' => $paymentsCount]);
            }
        }
    }
}
