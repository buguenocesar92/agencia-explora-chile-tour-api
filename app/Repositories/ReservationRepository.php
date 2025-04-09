<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function create(array $data)
    {
        return Reservation::create($data);
    }

    public function getAll(?string $search = null)
    {
        return Reservation::with(['client', 'trip.tourTemplate', 'payment'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('client', function ($q) use ($search) {
                    // Limpiar el formato del RUT para comparación (quitar puntos y guiones)
                    $cleanSearch = preg_replace('/[.-]/', '', $search);

                    $q->where('name', 'ilike', "%$search%")
                      // Buscar coincidencia exacta de RUT limpio
                      ->orWhereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') ILIKE ?", ["%$cleanSearch%"]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function updateStatus(int $id, string $status)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->status = $status;
        $reservation->save();

        return $reservation;
    }

    public function getById(int $id)
    {
        return Reservation::with(['client', 'trip', 'payment'])->findOrFail($id);
    }

    public function delete(int $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();
        return $reservation;
    }
}
