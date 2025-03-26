<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Trip;
use App\Models\Payment;
use Carbon\Carbon;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReservationService
{
    private ReservationRepositoryInterface $reservationRepo;

    public function __construct(ReservationRepositoryInterface $reservationRepo)
    {
        $this->reservationRepo = $reservationRepo;
    }

    public function createReservation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Crear el cliente
            $client = Client::create($data['client']);

            // Crear el viaje
            $trip = Trip::create($data['trip']);

            // Procesar el pago:
            // Si no se provee 'payment_date', asignamos la fecha actual.
            if (empty($data['payment']['payment_date'])) {
                $data['payment']['payment_date'] = Carbon::now()->toDateString();
            }

            // Procesar el comprobante si existe y es un archivo
            if (
                isset($data['payment']['receipt']) &&
                $data['payment']['receipt'] instanceof UploadedFile
            ) {
                // Almacenar el archivo en el directorio 'payments' en el disco 'public'
                $path = $data['payment']['receipt']->store('payments', 'public');
                $data['payment']['receipt'] = $path;
            }

            // Crear el pago
            $payment = Payment::create($data['payment']);

            // Preparar los datos para la reserva
            $reservationData = [
                'client_id' => $client->id,
                'trip_id'   => $trip->id,
                'payment_id'=> $payment->id,
                // Asignamos la fecha de reserva con la fecha actual
                'date' => Carbon::now()->toDateString(),
                // Puedes agregar otros campos como 'descripcion' o 'status' aquí
            ];

            // Usamos el repositorio para crear la reserva
            return $this->reservationRepo->create($reservationData);
        });
    }
}
