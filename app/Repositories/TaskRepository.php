<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;

/*
Repository: Se dedica a interactuar con la base de datos, es decir, realiza operaciones CRUD (crear, leer, actualizar, eliminar).
Esto facilita cambiar la forma de acceder a los datos (por ejemplo, de Eloquent a SQL puro) sin modificar la lógica de negocio.
*/
class TaskRepository implements TaskRepositoryInterface
{
    public function getAll()
    {
        return Task::all();
    }

    public function findById(int $id)
    {
        return Task::findOrFail($id);
    }

    public function create(array $data)
    {
        return Task::create($data);
    }

    public function update(int $id, array $data)
    {
        $task = Task::findOrFail($id);
        $task->update($data);
        return $task;
    }

    public function delete(int $id): void
    {
        $task = Task::findOrFail($id);
        $task->delete();
    }
}
