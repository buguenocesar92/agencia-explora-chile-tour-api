<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function getAll(): Collection
    {
        return $this->model->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();
    }


    // Otros métodos según tus necesidades...
}
