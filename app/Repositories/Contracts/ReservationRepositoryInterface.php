<?php

namespace App\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);
    public function getAll(?string $search = null, array $filters = [], bool $withTrashed = false);
    // Método para actualizar el status de una reserva
    public function updateStatus(int $id, string $status);

    // Método para obtener una reserva por ID
    public function getById(int $id, bool $withTrashed = false);
    public function delete(int $id);
    public function restore(int $id): void;
    public function forceDelete(int $id): void;
}
