<?php

namespace App\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);
    public function getAll();
    // Método para actualizar el status de una reserva
    public function updateStatus(int $id, string $status);

    // Método para obtener una reserva por ID
    public function getById(int $id);
}
