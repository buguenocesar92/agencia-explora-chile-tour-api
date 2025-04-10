<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function getAll(?string $search = null)
    {
        return Client::when($search, function ($query) use ($search) {
                // Limpiar el formato del RUT para comparación (quitar puntos y guiones)
                $cleanSearch = preg_replace('/[.-]/', '', $search);

                $query->where('name', 'ilike', "%$search%")
                      // Buscar coincidencia exacta de RUT limpio
                      ->orWhereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') ILIKE ?", ["%$cleanSearch%"]);
            })
            ->orderBy('name')
            ->get();
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
     * Busca un cliente por su RUT normalizado (sin puntos ni guiones)
     *
     * @param string $normalizedRut
     * @return \App\Models\Client|null
     */
    public function findByRut(string $normalizedRut)
    {
        return Client::whereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') = ?", [$normalizedRut])
            ->first();
    }
}
