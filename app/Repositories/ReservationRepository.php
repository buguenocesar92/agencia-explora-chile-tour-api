<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function create(array $data)
    {
        return Reservation::create($data);
    }

    public function getAll(?string $search = null, array $filters = [], bool $withTrashed = false)
    {
        $query = Reservation::with(['client', 'trip.tourTemplate', 'payment']);

        // Incluir reservas eliminadas si se solicita
        if ($withTrashed) {
            $query->withTrashed();
        }

        $query->when($search, function ($query) use ($search) {
            $query->whereHas('client', function ($q) use ($search) {
                // Limpiar el formato del RUT para comparación (quitar puntos y guiones)
                $cleanSearch = preg_replace('/[.-]/', '', $search);

                $q->where('name', 'ilike', "%$search%")
                  // Buscar coincidencia exacta de RUT limpio
                  ->orWhereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') ILIKE ?", ["%$cleanSearch%"]);
            });
        })
        ->when(isset($filters['tour_id']), function ($query) use ($filters) {
            $query->whereHas('trip', function ($q) use ($filters) {
                $q->where('tour_template_id', $filters['tour_id']);
            });
        })
        ->when(isset($filters['status']), function ($query) use ($filters) {
            $query->where('status', $filters['status']);
        })
        ->when(isset($filters['date']), function ($query) use ($filters) {
            $query->whereDate('date', $filters['date']);
        });

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function updateStatus(int $id, string $status)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->status = $status;
        $reservation->save();

        return $reservation;
    }

    public function getById(int $id, bool $withTrashed = false)
    {
        $query = Reservation::with(['client', 'trip', 'payment']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    public function delete(int $id)
    {
        $reservation = Reservation::with(['payment'])->findOrFail($id);

        // Primero eliminamos el pago asociado si existe
        if ($reservation->payment) {
            // Si tiene un comprobante, lo eliminamos del storage
            if ($reservation->payment->receipt) {
                Storage::disk('s3')->delete($reservation->payment->receipt);
            }

            // Eliminamos el registro de pago
            $reservation->payment->delete();
        }

        // Ahora eliminamos la reserva
        $reservation->delete();
        return $reservation;
    }

    public function restore(int $id): void
    {
        Reservation::withTrashed()->findOrFail($id)->restore();
    }

    public function forceDelete(int $id): void
    {
        $reservation = Reservation::withTrashed()->with(['payment'])->findOrFail($id);

        // Eliminar primero el archivo del pago si existe
        if ($reservation->payment && $reservation->payment->receipt) {
            Storage::disk('s3')->delete($reservation->payment->receipt);
        }

        // Eliminar permanentemente el pago asociado
        if ($reservation->payment) {
            $reservation->payment->delete();
        }

        // Eliminar permanentemente la reserva
        $reservation->forceDelete();
    }
}
