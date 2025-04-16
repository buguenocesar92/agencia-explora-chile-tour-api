<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function getAll(?string $search = null, bool $withTrashed = false)
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

            $query->where('name', 'ilike', "%$search%")
                  // Buscar coincidencia exacta de RUT limpio
                  ->orWhereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') ILIKE ?", ["%$cleanSearch%"]);
        }

        return $query->orderBy('name')->get();
    }

    public function findById(int $id)
    {
        return Client::findOrFail($id);
    }

    public function create(array $data)
    {
        return Client::create($data);
    }

    public function update(int $id, array $data)
    {
        $client = Client::findOrFail($id);
        $client->update($data);
        return $client;
    }

    public function delete(int $id): void
    {
        $client = Client::findOrFail($id);
        $client->delete();
    }

    /**
     * Restaura un cliente eliminado
     */
    public function restore(int $id): void
    {
        Client::withTrashed()->findOrFail($id)->restore();
    }

    /**
     * Elimina permanentemente un cliente
     */
    public function forceDelete(int $id): void
    {
        Client::withTrashed()->findOrFail($id)->forceDelete();
    }

    /**
     * Busca un cliente por su RUT normalizado (sin puntos ni guiones)
     *
     * @param string $normalizedRut
     * @param bool $withTrashed Si es true, incluye clientes con soft delete
     * @return \App\Models\Client|null
     */
    public function findByRut(string $normalizedRut, bool $withTrashed = false)
    {
        $query = Client::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') = ?", [$normalizedRut])
            ->first();
    }
}
