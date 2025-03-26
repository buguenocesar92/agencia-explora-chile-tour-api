<?php

namespace App\Services;

use App\Repositories\Contracts\TaskRepositoryInterface;

/*
Service: Alberga la lógica de negocio, es decir, las reglas y procesos que definen el comportamiento de la aplicación.
Permite centralizar la lógica y evitar duplicaciones.
*/
class TaskService
{
    private TaskRepositoryInterface $taskRepo;

    public function __construct(TaskRepositoryInterface $taskRepo)
    {
        $this->taskRepo = $taskRepo;
    }

    public function getAll()
    {
        return $this->taskRepo->getAll();
    }

    public function findById(int $id)
    {
        return $this->taskRepo->findById($id);
    }

    public function create(array $data)
    {
        return $this->taskRepo->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->taskRepo->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->taskRepo->delete($id);
    }
}
