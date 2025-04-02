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

    public function listReservations()
    {
        return $this->reservationRepo->getAll();
    }

    // Método para actualizar el status de una reserva
    public function updateReservationStatus(int $id, string $status)
    {
        return $this->reservationRepo->updateStatus($id, $status);
    }

    public function getReservation(int $id)
    {
        return $this->reservationRepo->getById($id);
    }

    public function updateReservation(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Obtener la reserva con sus relaciones
            $reservation = $this->reservationRepo->getById($id);

            // Actualizar campos propios de la reserva
            if (isset($data['description'])) {
                $reservation->description = $data['description'];
            }
            if (isset($data['status'])) {
                $reservation->status = $data['status'];
            }
            if (isset($data['date'])) {
                $reservation->date = $data['date'];
            }
            $reservation->save();

            // Actualizar el cliente
            if (isset($data['client'])) {
                $reservation->client->update($data['client']);
            }

            // Actualizar el viaje
            if (isset($data['trip'])) {
                $reservation->trip->update($data['trip']);
            }

            // Actualizar el pago
            if (isset($data['payment'])) {
                // Si se envía un nuevo comprobante (archivo), procesarlo
                if (
                    isset($data['payment']['receipt']) &&
                    $data['payment']['receipt'] instanceof UploadedFile
                ) {
                    $path = $data['payment']['receipt']->store('payments', 'public');
                    $data['payment']['receipt'] = $path;
                }
                $reservation->payment->update($data['payment']);
            }

            return $reservation;
        });
    }

    public function deleteReservation(int $id)
    {
        return DB::transaction(function () use ($id) {
            // Obtener la reserva con sus relaciones
            $reservation = $this->reservationRepo->getById($id);

            // Eliminar el comprobante de pago si existe
            if ($reservation->payment && $reservation->payment->receipt) {
                Storage::disk('public')->delete($reservation->payment->receipt);
            }

            // Eliminar la reserva
            return $this->reservationRepo->delete($id);
        });
    }
}
