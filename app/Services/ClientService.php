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

    public function getAll()
    {
        return $this->clientRepo->getAll();
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
}
