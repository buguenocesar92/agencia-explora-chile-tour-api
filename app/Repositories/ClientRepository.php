<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Obtiene todos los clientes, opcionalmente filtrados por búsqueda
     *
     * @param string|null $search Término de búsqueda
     * @param bool $withTrashed Incluir clientes eliminados
     * @return Collection
     */
    public function getAll(?string $search = null, bool $withTrashed = false): Collection
    {
        $query = Client::query();

        // Incluir clientes eliminados si se solicita
        if ($withTrashed) {
            $query->withTrashed();
        }

        // Aplicar búsqueda si se proporciona
        if ($search) {
            // Limpiar el formato del RUT para comparación (quitar puntos y guiones)
            $cleanSearch = preg_replace('/[.-]/', '', $search);

            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  // Buscar en RUT limpio con LOWER() para hacerlo case-insensitive en MySQL
                  ->orWhereRaw("LOWER(REPLACE(REPLACE(rut, '.', ''), '-', '')) LIKE LOWER(?)", ["%$cleanSearch%"]);
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Encuentra un cliente por su ID
     *
     * @param int $id
     * @return Client
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Client
    {
        return Client::findOrFail($id);
    }

    /**
     * Crea un nuevo cliente
     *
     * @param array $data
     * @return Client
     */
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    /**
     * Actualiza un cliente existente
     *
     * @param int $id
     * @param array $data
     * @return Client
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Client
    {
        $client = Client::findOrFail($id);
        $client->update($data);
        return $client->fresh();
    }

    /**
     * Elimina un cliente (soft delete)
     *
     * @param int $id
     * @throws ModelNotFoundException
     */
    public function delete(int $id): void
    {
        $client = Client::findOrFail($id);
        $client->delete();
    }

    /**
     * Restaura un cliente eliminado
     *
     * @param int $id
     * @throws ModelNotFoundException
     */
    public function restore(int $id): void
    {
        $client = Client::withTrashed()->findOrFail($id);
        $client->restore();
    }

    /**
     * Elimina permanentemente un cliente
     *
     * @param int $id
     * @throws ModelNotFoundException
     */
    public function forceDelete(int $id): void
    {
        $client = Client::withTrashed()->findOrFail($id);
        $client->forceDelete();
    }

    /**
     * Busca un cliente por su RUT normalizado
     *
     * @param string $normalizedRut RUT sin puntos ni guiones
     * @param bool $withTrashed Incluir clientes eliminados
     * @return Client|null
     */
    public function findByRut(string $normalizedRut, bool $withTrashed = false): ?Client
    {
        $query = Client::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') = ?", [$normalizedRut])
            ->first();
    }
}
