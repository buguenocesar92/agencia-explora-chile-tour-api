<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para la gestión de reservas
 *
 * @package App\Repositories
 */
class ReservationRepository implements ReservationRepositoryInterface
{
    /**
     * Crea una nueva reserva
     *
     * @param array $data
     * @return Reservation
     */
    public function create(array $data)
    {
        return Reservation::create($data);
    }

    /**
     * Obtiene todas las reservas con filtros opcionales
     *
     * @param string|null $search
     * @param array $filters
     * @param bool $withTrashed
     * @return Collection
     */
    public function getAll(?string $search = null, array $filters = [], bool $withTrashed = false)
    {
        $query = Reservation::with(['client', 'trip.tourTemplate', 'payment']);

        // Incluir reservas eliminadas si se solicita
        if ($withTrashed) {
            $query->withTrashed();
        }

        $this->applyFilters($query, $search, $filters);

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtiene todas las reservas para exportar a Excel
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllForExport(array $filters = [])
    {
        $query = Reservation::with(['client', 'trip.tourTemplate', 'payment']);

        $this->applyFilters($query, null, $filters);

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Aplica filtros a la consulta de reservas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $search
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, ?string $search = null, array $filters = []): void
    {
        $query->when($search, function ($query) use ($search) {
            $query->whereHas('client', function ($q) use ($search) {
                // Limpiar el formato del RUT para comparación (quitar puntos y guiones)
                $cleanSearch = preg_replace('/[.-]/', '', $search);

                $q->where('name', 'like', "%$search%")
                  // Buscar coincidencia exacta de RUT limpio con LOWER() para hacerlo case-insensitive en MySQL
                  ->orWhereRaw("LOWER(REPLACE(REPLACE(rut, '.', ''), '-', '')) LIKE LOWER(?)", ["%$cleanSearch%"]);
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
        })
        ->when(isset($filters['from_date']), function ($query) use ($filters) {
            $query->whereDate('date', '>=', $filters['from_date']);
        })
        ->when(isset($filters['to_date']), function ($query) use ($filters) {
            $query->whereDate('date', '<=', $filters['to_date']);
        })
        ->when(isset($filters['client_id']), function ($query) use ($filters) {
            $query->where('client_id', $filters['client_id']);
        });
    }

    /**
     * Actualiza el estado de una reserva
     *
     * @param int $id
     * @param string $status
     * @return Reservation
     */
    public function updateStatus(int $id, string $status)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->status = $status;
        $reservation->save();

        return $reservation;
    }

    /**
     * Obtiene una reserva por su ID
     *
     * @param int $id
     * @param bool $withTrashed
     * @return Reservation
     */
    public function getById(int $id, bool $withTrashed = false)
    {
        $query = Reservation::with(['client', 'trip.tourTemplate', 'payment']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    /**
     * Elimina una reserva (soft delete)
     *
     * @param int $id
     * @return Reservation
     */
    public function delete(int $id)
    {
        $reservation = Reservation::with(['payment'])->findOrFail($id);

        // Primero eliminamos el pago asociado si existe
        if ($reservation->payment) {
            // Si tiene un comprobante, lo eliminamos del storage
            if ($reservation->payment->receipt) {
                Storage::disk('public')->delete($reservation->payment->receipt);
            }

            // Eliminamos el registro de pago
            $reservation->payment->delete();
        }

        // Ahora eliminamos la reserva
        $reservation->delete();
        return $reservation;
    }

    /**
     * Restaura una reserva eliminada
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool
    {
        return Reservation::withTrashed()->findOrFail($id)->restore();
    }

    /**
     * Elimina permanentemente una reserva
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool
    {
        $reservation = Reservation::withTrashed()->with(['payment'])->findOrFail($id);

        // Eliminar primero el archivo del pago si existe
        if ($reservation->payment && $reservation->payment->receipt) {
            Storage::disk('public')->delete($reservation->payment->receipt);
        }

        // Eliminar permanentemente el pago asociado
        if ($reservation->payment) {
            $reservation->payment->forceDelete();
        }

        // Eliminar permanentemente la reserva
        return $reservation->forceDelete();
    }
}
