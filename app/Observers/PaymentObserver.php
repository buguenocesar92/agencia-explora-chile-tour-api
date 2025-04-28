<?php

namespace App\Observers;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentObserver
{
    /**
     * Handle the Payment "deleted" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function deleted(Payment $payment)
    {
        Log::info('PaymentObserver: deleted event triggered', ['id' => $payment->id]);

        // Aquí no necesitamos hacer cascade delete porque Payment no tiene entidades dependientes
        // Pero podemos manejar la eliminación del archivo de recibo si existe
        if ($payment->receipt) {
            try {
                Storage::disk('public')->delete($payment->receipt);
                Log::info('PaymentObserver: receipt file deleted', ['path' => $payment->receipt]);
            } catch (\Exception $e) {
                Log::error('PaymentObserver: Error deleting receipt file', [
                    'path' => $payment->receipt,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle the Payment "restored" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function restored(Payment $payment)
    {
        Log::info('PaymentObserver: restored event triggered', ['id' => $payment->id]);
        // No es necesario hacer nada aquí ya que el archivo de recibo no se recupera automáticamente
    }

    /**
     * Handle the Payment "force deleted" event.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function forceDeleted(Payment $payment)
    {
        Log::info('PaymentObserver: forceDeleted event triggered', ['id' => $payment->id]);

        // Eliminar el archivo de recibo permanentemente si existe
        if ($payment->receipt) {
            try {
                Storage::disk('public')->delete($payment->receipt);
                Log::info('PaymentObserver: receipt file permanently deleted', ['path' => $payment->receipt]);
            } catch (\Exception $e) {
                Log::error('PaymentObserver: Error permanently deleting receipt file', [
                    'path' => $payment->receipt,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
