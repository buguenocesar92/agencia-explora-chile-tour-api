<?php

namespace App\Repositories\Contracts;

interface ClientRepositoryInterface
{
    public function getAll(?string $search = null, bool $withTrashed = false);
    public function findById(int $id);
    public function findByRut(string $normalizedRut, bool $withTrashed = false);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): void;
    public function restore(int $id): void;
    public function forceDelete(int $id): void;
}
