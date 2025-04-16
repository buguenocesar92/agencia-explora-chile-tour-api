<?php

namespace App\Services;

use App\Repositories\Contracts\ClientRepositoryInterface;

class ClientService
{
    private ClientRepositoryInterface $clientRepo;

    public function __construct(ClientRepositoryInterface $clientRepo)
    {
        $this->clientRepo = $clientRepo;
    }

    public function getAll(?string $search = null, bool $withTrashed = false)
    {
        return $this->clientRepo->getAll($search, $withTrashed);
    }

    public function findById(int $id)
    {
        return $this->clientRepo->findById($id);
    }

    public function create(array $data)
    {
        return $this->clientRepo->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->clientRepo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->clientRepo->delete($id);
    }

    /**
     * Restaura un cliente eliminado
     */
    public function restore(int $id): void
    {
        $this->clientRepo->restore($id);
    }

    /**
     * Elimina permanentemente un cliente
     */
    public function forceDelete(int $id): void
    {
        $this->clientRepo->forceDelete($id);
    }

    /**
     * Busca un cliente por su RUT normalizado
     *
     * @param string $normalizedRut RUT sin puntos ni guiones
     * @param bool $withTrashed Si es true, incluye clientes con soft delete
     * @return \App\Models\Client|null Cliente encontrado o null
     */
    public function findByRut(string $normalizedRut, bool $withTrashed = false)
    {
        return $this->clientRepo->findByRut($normalizedRut, $withTrashed);
    }
}
