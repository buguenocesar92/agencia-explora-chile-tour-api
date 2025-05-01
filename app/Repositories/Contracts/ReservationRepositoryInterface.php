<?php

namespace App\Repositories\Contracts;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interfaz para el repositorio de reservas
 *
 * @package App\Repositories\Contracts
 */
interface ReservationRepositoryInterface
{
    /**
     * Crea una nueva reserva
     *
     * @param array $data
     * @return Reservation
     */
    public function create(array $data);

    /**
     * Obtiene todas las reservas con filtros opcionales
     *
     * @param string|null $search
     * @param array $filters
     * @param bool $withTrashed
     * @return Collection
     */
    public function getAll(?string $search = null, array $filters = [], bool $withTrashed = false);

    /**
     * Obtiene todas las reservas para exportar a Excel
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllForExport(array $filters = []);

    /**
     * Actualiza el estado de una reserva
     *
     * @param int $id
     * @param string $status
     * @return Reservation
     */
    public function updateStatus(int $id, string $status);

    /**
     * Obtiene una reserva por su ID
     *
     * @param int $id
     * @param bool $withTrashed
     * @return Reservation
     */
    public function getById(int $id, bool $withTrashed = false);

    /**
     * Elimina una reserva (soft delete)
     *
     * @param int $id
     * @return Reservation
     */
    public function delete(int $id);

    /**
     * Restaura una reserva eliminada
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool;

    /**
     * Elimina permanentemente una reserva
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete(int $id): bool;
}
