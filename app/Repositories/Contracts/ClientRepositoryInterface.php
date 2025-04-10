<?php

namespace App\Repositories\Contracts;

interface ClientRepositoryInterface
{
    public function getAll(?string $search = null);
    public function findById(int $id);
    public function findByRut(string $normalizedRut);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
