<?php

namespace App\Repositories\Contracts;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface
{
    /**
     * Obtiene todos los clientes, opcionalmente filtrados por búsqueda
     */
    public function getAll(?string $search = null, bool $withTrashed = false): Collection;

    /**
     * Encuentra un cliente por su ID
     */
    public function findById(int $id): Client;

    /**
     * Busca un cliente por su RUT normalizado
     */
    public function findByRut(string $normalizedRut, bool $withTrashed = false): ?Client;

    /**
     * Crea un nuevo cliente
     */
    public function create(array $data): Client;

    /**
     * Actualiza un cliente existente
     */
    public function update(int $id, array $data): Client;

    /**
     * Elimina un cliente (soft delete)
     */
    public function delete(int $id): void;

    /**
     * Restaura un cliente eliminado
     */
    public function restore(int $id): void;

    /**
     * Elimina permanentemente un cliente
     */
    public function forceDelete(int $id): void;
}
