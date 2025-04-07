<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function getAll(?string $search = null)
    {
        return Client::when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%$search%")
                      ->orWhere('rut', 'ilike', "%$search%");
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
}
